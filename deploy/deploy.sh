#!/usr/bin/env bash
# =============================================================================
# T.O.M.E. Deploy (vom Mac aus)
#
#   deploy/deploy.sh --host training.ssc-landau.de [--dry-run] [--skip-build]
#
#   --host        Server (SSH als Benutzer "tome", Schluessel-Login)
#   --dry-run     baut und stellt das Release zusammen, uebertraegt aber nichts (rsync -n)
#   --skip-build  verwendet das zuletzt gebaute Release in deploy/.build/release erneut
#
# Ablauf: git-Stand pruefen -> Frontend + Backend bauen -> Release zusammenstellen -> per rsync
# nach releases/<zeit>-<sha> -> .env/storage verlinken -> DB-Dump -> migrate -> Caches ->
# current atomar umschalten -> PHP-FPM neu laden -> Health-Check.
# Schlaegt etwas NACH dem Umschalten fehl, wird automatisch auf das vorherige Release zurueck-
# geschaltet. Migrationen werden NICHT zurueckgedreht: dafuer gibt es den Dump vor dem migrate.
#
# Frontend-Konfiguration: deploy/.env.production (siehe .env.production.example), nicht im Git.
# =============================================================================
set -euo pipefail
# shellcheck source=lib.sh
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

DRY_RUN=0
SKIP_BUILD=0
while [[ $# -gt 0 ]]; do
  case "$1" in
    --host) HOST="${2:?--host braucht einen Wert}"; shift ;;
    --dry-run) DRY_RUN=1 ;;
    --skip-build) SKIP_BUILD=1 ;;
    -h|--help) sed -n '2,19p' "$0"; exit 0 ;;
    *) die "Unbekannte Option: $1" ;;
  esac
  shift
done
[[ -n "$HOST" || "$TOME_TRANSPORT" == docker ]] || die "--host fehlt"

BUILD_DIR="$REPO_DIR/deploy/.build"
STAGE="$BUILD_DIR/release"
ENV_FILE="$REPO_DIR/deploy/.env.production"

# ---------------------------------------------------------------- 1. Git-Stand
cd "$REPO_DIR"
if [[ -n "$(git status --porcelain)" ]]; then
  git status --short
  die "Der Git-Arbeitsbaum ist nicht sauber. Erst committen oder verwerfen."
fi
SHA="$(git rev-parse --short HEAD)"
RELEASE_ID="$(date +%Y%m%d-%H%M%S)-${SHA}"
log "Deploy von: $(git log -1 --format='%h %s (%an, %ad)' --date=short)"
log "Branch: $(git rev-parse --abbrev-ref HEAD)   Release: ${RELEASE_ID}   Ziel: ${HOST:-$TOME_CONTAINER}"

# ---------------------------------------------------------------- 2. Build
if [[ "$SKIP_BUILD" == 1 ]]; then
  [[ -d "$STAGE/server/vendor" && -f "$STAGE/app-dist/index.html" ]] || die "--skip-build, aber $STAGE ist leer"
  warn "--skip-build: verwende vorhandenes Release aus $STAGE (nicht neu gebaut)"
else
  [[ -f "$ENV_FILE" ]] || die "$ENV_FILE fehlt (Vorlage: deploy/.env.production.example)"
  [[ -f "$REPO_DIR/app/.env.local" ]] && warn "app/.env.local existiert; Werte aus deploy/.env.production haben Vorrang, unbekannte Schluessel koennten durchschlagen"

  log "Frontend bauen (npm ci && npm run build)"
  (
    cd "$REPO_DIR/app"
    # Prozess-Umgebung hat in Vite Vorrang vor .env-Dateien
    set -a; source "$ENV_FILE"; set +a
    export VITE_API_URL="${VITE_API_URL:-/api/v1}"
    export VITE_IMAGE_FOLDER_URL="${VITE_IMAGE_FOLDER_URL:-/api/storage}"
    rm -rf dist
    npm ci --no-audit --no-fund
    npm run build
  )

  log "Release zusammenstellen"
  rm -rf "$STAGE"
  mkdir -p "$STAGE"
  rsync -a --delete \
    --exclude='/tests' --exclude='/vendor' --exclude='/storage' --exclude='/.env*' \
    --exclude='/phpunit.xml' --exclude='/phpstan*' --exclude='/pint.json' \
    --exclude='/Dockerfile' --exclude='/startup.sh' --exclude='/server.php' --exclude='*.md' \
    --exclude='/bootstrap/cache/*.php' --exclude='/public/storage' --exclude='.DS_Store' \
    "$REPO_DIR/server/" "$STAGE/server/"
  mkdir -p "$STAGE/server/bootstrap/cache"
  cp -R "$REPO_DIR/app/dist" "$STAGE/app-dist"

  log "Composer (no-dev, Plattform PHP 8.4)"
  (
    cd "$STAGE/server"
    # Installiert exakt die Versionen aus composer.lock. composer.json legt config.platform.php = 8.4.1
    # fest (unabhaengig von PHP auf dem Mac); PHP-Erweiterungen des Macs werden ignoriert (der Server
    # hat eigene). Die Mindestversion landet im generierten platform_check.php.
    composer install --no-dev --optimize-autoloader --no-interaction --no-progress \
      --ignore-platform-req='ext-*'
    grep -q "PHP_VERSION_ID >= 804" vendor/composer/platform_check.php || die "platform_check.php verlangt nicht PHP >= 8.4"
  )
fi

if [[ "$DRY_RUN" == 1 ]]; then
  log "DRY-RUN: zeige, was uebertragen wuerde (nichts wird veraendert)"
  remote_rsync "$STAGE" "$TOME_ROOT/releases/${RELEASE_ID}" -n --itemize-changes | tail -n 15 || true
  log "Danach wuerden: DB-Dump, migrate --force, config/route/view:cache, Umschalten, FPM-Reload, Health-Check folgen."
  exit 0
fi

# ---------------------------------------------------------------- 3. Hochladen
PREVIOUS="$(current_release)"
log "Aktuelles Release: ${PREVIOUS:-keins (Erstinstallation)}"

log "Release hochladen"
remote_script "$TOME_ROOT" "$RELEASE_ID" <<'EOF'
set -e
[ -d "$1/shared" ] || { echo "$1/shared fehlt - server-setup.sh ausgefuehrt?" >&2; exit 1; }
mkdir -p "$1/releases/$2"
EOF
remote_rsync "$STAGE" "$TOME_ROOT/releases/${RELEASE_ID}"

# ---------------------------------------------------------------- 4. Vorbereiten, Dump, Migration
DUMP="/var/backups/tome/pre-deploy-${RELEASE_ID}.sql.gz"
SWITCHED=0

# Wird bei jedem Fehler aufgerufen
rollback_and_exit() {
  local reason="$1"
  printf '\033[1;31mFEHLER\033[0m %s\n' "$reason" >&2
  if [[ "$SWITCHED" == 1 && -n "$PREVIOUS" ]]; then
    warn "Schalte zurueck auf ${PREVIOUS}"
    if switch_to "$PREVIOUS"; then ok "Zurueckgeschaltet auf ${PREVIOUS}"; else warn "Zurueckschalten FEHLGESCHLAGEN - manuell: deploy/rollback.sh ${PREVIOUS}"; fi
  elif [[ "$SWITCHED" == 1 ]]; then
    warn "Kein vorheriges Release vorhanden (Erstinstallation) - nichts zum Zurueckschalten"
  else
    warn "Es wurde noch nicht umgeschaltet; das alte Release laeuft unveraendert weiter."
  fi
  if [[ "${MIGRATED:-0}" == 1 ]]; then
    warn "Die Migrationen wurden ausgefuehrt und NICHT zurueckgedreht. Falls das alte Release mit dem neuen Schema nicht laeuft, DB wiederherstellen:"
    echo "  Auf dem Server (als root): gunzip < ${DUMP} | mysql tome" >&2
  fi
  exit 1
}
MIGRATED=0
trap 'rollback_and_exit "Unerwarteter Fehler in Zeile $LINENO"' ERR

log "Release vorbereiten, Datenbank sichern, migrieren"
remote_script "$TOME_ROOT" "$RELEASE_ID" "$DUMP" <<'EOF' || rollback_and_exit "Vorbereitung/Migration fehlgeschlagen"
set -euo pipefail
root="$1"; rel="$root/releases/$2"; dump="$3"
cd "$rel/server"
# Gemeinsame Konfiguration und Uploads/Logs einhaengen
ln -sfn "$root/shared/.env" .env
rm -rf storage
ln -sfn "$root/shared/storage" storage
# Hinweis: storage:link ist nicht noetig - Apache liefert /api/storage direkt aus shared/storage/app/public.

# DB-Zugang aus der .env lesen (ohne die Datei zu "sourcen")
envget() { grep -E "^$1=" "$root/shared/.env" | tail -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'\$//"; }
export MYSQL_PWD; MYSQL_PWD="$(envget DB_PASSWORD)"
db_host="$(envget DB_HOST)"; db_user="$(envget DB_USERNAME)"; db_name="$(envget DB_DATABASE)"

echo "DB-Dump nach $dump"
mysqldump -h "$db_host" -u "$db_user" --single-transaction --no-tablespaces --skip-triggers "$db_name" | gzip > "$dump"
[ -s "$dump" ] || { echo "Dump ist leer" >&2; exit 1; }
chmod 600 "$dump"
echo "MIGRATE_START"
php8.4 artisan migrate --force
EOF
MIGRATED=1   # ab hier kann das Schema veraendert sein

remote_script "$TOME_ROOT" "$RELEASE_ID" <<'EOF' || rollback_and_exit "Cache-Aufbau fehlgeschlagen"
set -euo pipefail
cd "$1/releases/$2/server"
php8.4 artisan config:cache
# route:cache scheitert (erst beim Laden) bei Closure-Routen; dann ohne Route-Cache weiter
if php8.4 artisan route:cache && php8.4 artisan route:list >/dev/null 2>&1; then
  echo "Routen gecacht"
else
  echo "WARNUNG: route:cache nicht moeglich (Closure-Routen in routes/*.php?) - Route-Cache wird uebersprungen" >&2
  rm -f bootstrap/cache/routes-*.php
fi
php8.4 artisan view:cache
EOF

# ---------------------------------------------------------------- 5. Umschalten + Pruefen
log "Umschalten auf ${RELEASE_ID} und PHP-FPM neu laden"
SWITCHED=1
switch_to "$RELEASE_ID" || rollback_and_exit "Umschalten fehlgeschlagen"

log "Health-Check"
health_check || rollback_and_exit "Health-Check nach dem Umschalten fehlgeschlagen"

# ---------------------------------------------------------------- 6. Aufraeumen
trap - ERR
remote_script "$TOME_ROOT" <<'EOF'
cd "$1/releases"
cur="$(basename "$(readlink "$1/current")")"
# Neueste 5 behalten (Namen beginnen mit dem Zeitstempel), das aktive nie loeschen
ls -1 | sort -r | tail -n +6 | while read -r old; do
  [ "$old" = "$cur" ] || rm -rf "$old"
done
EOF
ok "Deploy abgeschlossen: ${RELEASE_ID}  (vorher: ${PREVIOUS:-keins})"
log "DB-Dump vor der Migration: ${DUMP}"

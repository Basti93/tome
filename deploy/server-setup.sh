#!/usr/bin/env bash
# =============================================================================
# T.O.M.E. - Server-Einrichtung (einmalig, idempotent)
#
# Als root auf dem Server ausfuehren (Ubuntu 24.04, Apache 2.4, PHP 8.4-FPM, MySQL 8):
#
#   sudo ./server-setup.sh             # fragt je Abschnitt: "Abschnitt X ausfuehren? [j/N]"
#   sudo ./server-setup.sh -y          # alle Abschnitte ohne Rueckfrage (ausser 12, siehe unten)
#   sudo ./server-setup.sh --only 2,3  # nur bestimmte Abschnitte (ohne Rueckfrage)
#   sudo ./server-setup.sh --list      # Abschnitte auflisten
#
# Das Skript fasst ausschliesslich TOME-spezifische Dinge an. Die WordPress-Seite
# (die Website des Vereins, /var/www/wp-website, Vhosts website.conf / wp-website.conf) und der
# Standard-FPM-Pool "www" bleiben unveraendert.
#
# Abschnitte:
#   1  Voraussetzungen pruefen (Pakete, PHP-Module, Apache-Module)
#   2  Systembenutzer "tome"
#   3  Verzeichnisstruktur /var/www/tome-app
#   4  PHP-FPM-Pool "tome"
#   5  sudoers: tome darf nur "systemctl reload php8.4-fpm"
#   6  MySQL-Benutzer tome_app (nur Rechte auf Datenbank tome)
#   7  .env aus Vorlage (Schluessel werden einmalig erzeugt, nie ueberschrieben)
#   8  Uploads aus der alten App uebernehmen
#   9  Scheduler-Cron (schedule:run jede Minute)
#   10 Taegliche Backups (DB + Uploads), 14 Tage Aufbewahrung
#   11 Neuen Apache-Vhost als training.conf.new ablegen (noch nicht aktiv)
#   12 Vhost AKTIVIEREN (Backup, configtest, reload, automatischer Revert)
#
# Abschnitt 12 wird bei -y NICHT automatisch ausgefuehrt (nur mit --only 12 oder
# interaktiver Bestaetigung), weil er die Live-Seite umschaltet.
#
# Konfiguration ueber Umgebungsvariablen (Standardwerte gelten fuer die Produktion;
# prod-sim ueberschreibt einige davon):
#   TOME_ROOT, TOME_USER, DB_NAME, DB_APP_USER, DB_HOST_GRANT, DB_HOST_APP,
#   MYSQL_ADMIN_CMD, OLD_UPLOADS, OLD_ENV, APACHE_DIR, PHP_VERSION, SYSTEMCTL
# =============================================================================
set -euo pipefail

# ----------------------------------------------------------------- Konfiguration
TOME_ROOT="${TOME_ROOT:-/var/www/tome-app}"
TOME_USER="${TOME_USER:-tome}"
PHP_VERSION="${PHP_VERSION:-8.4}"
FPM_POOL_DIR="${FPM_POOL_DIR:-/etc/php/${PHP_VERSION}/fpm/pool.d}"
FPM_SOCKET="/run/php/php${PHP_VERSION}-fpm-tome.sock"
FPM_SERVICE="php${PHP_VERSION}-fpm"
SYSTEMCTL="${SYSTEMCTL:-systemctl}"
DB_NAME="${DB_NAME:-tome}"
DB_APP_USER="${DB_APP_USER:-tome_app}"
DB_HOST_GRANT="${DB_HOST_GRANT:-localhost}"   # Host-Teil des MySQL-Benutzers
DB_HOST_APP="${DB_HOST_APP:-localhost}"       # DB_HOST fuer die .env
MYSQL_ADMIN_CMD="${MYSQL_ADMIN_CMD:-mysql}"   # root-Zugang (auth_socket als root)
OLD_UPLOADS="${OLD_UPLOADS:-/var/www/tome/api/storage/app/public}"
OLD_ENV="${OLD_ENV:-/var/www/tome/api/.env}"
APACHE_DIR="${APACHE_DIR:-/etc/apache2}"
VHOST_NAME="training.conf"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/tome}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# Standort (Host, Zertifikat, Mail-Absender) aus site.env neben diesem Skript (Vorlage: site.env.example)
# shellcheck source=site-env.sh
source "${SCRIPT_DIR}/site-env.sh"
SHARED="${TOME_ROOT}/shared"
ENV_FILE="${SHARED}/.env"

# ----------------------------------------------------------------- Hilfsfunktionen
ASSUME_YES=0
ONLY=""
ALL_SECTIONS="1 2 3 4 5 6 7 8 9 10 11 12"

info()  { printf '\033[1;34m[info]\033[0m %s\n' "$*"; }
ok()    { printf '\033[1;32m[ ok ]\033[0m %s\n' "$*"; }
warn()  { printf '\033[1;33m[warn]\033[0m %s\n' "$*"; }
die()   { printf '\033[1;31m[fehler]\033[0m %s\n' "$*" >&2; exit 1; }

# Abschnittsabfrage: "Abschnitt X ausfuehren? [j/N]"
ask() {
  local nr="$1" title="$2"
  if [[ -n "$ONLY" && ",${ONLY}," != *",${nr},"* ]]; then return 1; fi
  echo
  echo "================================================================"
  echo " Abschnitt ${nr}: ${title}"
  echo "================================================================"
  if [[ -n "$ONLY" ]]; then return 0; fi
  if [[ "$ASSUME_YES" == 1 ]]; then
    # Der Vhost-Wechsel ist bewusst nie automatisch
    if [[ "$nr" == "12" ]]; then info "Abschnitt 12 wird bei -y nicht automatisch ausgefuehrt"; return 1; fi
    return 0
  fi
  local answer=""
  read -r -p "Abschnitt ${nr} ausführen? [j/N] " answer || true
  [[ "$answer" =~ ^[jJyY]$ ]]
}

# .env-Helfer: env_get KEY | env_default KEY VALUE (nur setzen, wenn fehlt/leer) | env_force KEY VALUE
env_get() { [[ -f "$ENV_FILE" ]] && grep -E "^$1=" "$ENV_FILE" | tail -1 | cut -d= -f2- || true; }
env_force() {
  local key="$1" value="$2" tmp
  # Werte mit Leerzeichen oder # muessen in der .env in Anfuehrungszeichen stehen (sonst Parse-Fehler)
  if [[ "$value" =~ [[:space:]\#] && ! "$value" =~ ^[\"\'] ]]; then value="\"${value//\"/\\\"}\""; fi
  if grep -qE "^${key}=" "$ENV_FILE" 2>/dev/null; then
    tmp="$(mktemp)"
    K="$key" V="$value" awk 'BEGIN{FS=OFS="="} $1==ENVIRON["K"]{print ENVIRON["K"] "=" ENVIRON["V"]; next} {print}' "$ENV_FILE" > "$tmp"
    cat "$tmp" > "$ENV_FILE"; rm -f "$tmp"
  else
    printf '%s=%s\n' "$key" "$value" >> "$ENV_FILE"
  fi
}
env_default() {
  local current; current="$(env_get "$1")"
  if [[ -z "$current" ]]; then env_force "$1" "$2"; fi
}
ensure_env_file() {
  [[ -d "$SHARED" ]] || install -d -o "$TOME_USER" -g "$TOME_USER" -m 755 "$SHARED"
  if [[ ! -f "$ENV_FILE" ]]; then
    install -o "$TOME_USER" -g "$TOME_USER" -m 640 /dev/null "$ENV_FILE"
  fi
  chown "$TOME_USER:$TOME_USER" "$ENV_FILE"; chmod 640 "$ENV_FILE"
}

[[ $EUID -eq 0 ]] || die "Bitte als root ausfuehren (sudo)."

while [[ $# -gt 0 ]]; do
  case "$1" in
    -y|--yes) ASSUME_YES=1 ;;
    --only) ONLY="${2:?--only braucht eine Liste, z.B. 2,3}"; shift ;;
    --list) sed -n '/^# Abschnitte:/,/^# Abschnitt 12 wird/p' "$0"; exit 0 ;;
    -h|--help) sed -n '2,40p' "$0"; exit 0 ;;
    *) die "Unbekannte Option: $1" ;;
  esac
  shift
done

require_site_vars SITE_HOST SITE_ALIAS SITE_CERT_DIR SITE_ADMIN_EMAIL MAIL_FROM_ADDRESS MAIL_FROM_NAME
APP_PUBLIC_URL="https://${SITE_HOST}"

# ============================================================================
# Abschnitt 1: Voraussetzungen
# Prueft nur und aktiviert fehlende Apache-Module. Fehlende Pakete werden gemeldet
# (und auf Rueckfrage per apt installiert). Aendert nichts an WordPress.
# ============================================================================
if ask 1 "Voraussetzungen pruefen (Pakete, PHP-Module, Apache-Module)"; then
  missing_cmds=()
  for c in apache2ctl rsync openssl mysql mysqldump "php${PHP_VERSION}" sudo visudo; do
    command -v "$c" >/dev/null 2>&1 || missing_cmds+=("$c")
  done
  [[ -x "/usr/sbin/php-fpm${PHP_VERSION}" ]] || command -v "php-fpm${PHP_VERSION}" >/dev/null 2>&1 || missing_cmds+=("php${PHP_VERSION}-fpm")
  if ((${#missing_cmds[@]})); then
    warn "Fehlende Programme/Pakete: ${missing_cmds[*]}"
  else
    ok "Alle benoetigten Programme vorhanden"
  fi

  # PHP-Module, die Laravel 13 + Abhaengigkeiten (Excel, Bilder, Firebase) brauchen
  need_mods=(pdo_mysql mbstring xml dom gd zip curl intl bcmath fileinfo openssl tokenizer ctype Zend\ OPcache)
  missing_mods=()
  if command -v "php${PHP_VERSION}" >/dev/null 2>&1; then
    have="$("php${PHP_VERSION}" -m)"
    for m in "${need_mods[@]}"; do
      grep -qix "$m" <<<"$have" || missing_mods+=("$m")
    done
  fi
  if ((${#missing_mods[@]})); then
    warn "Fehlende PHP-Module: ${missing_mods[*]}"
    pkgs=()
    for m in "${missing_mods[@]}"; do
      case "$m" in
        pdo_mysql) pkgs+=("php${PHP_VERSION}-mysql") ;;
        "Zend OPcache") pkgs+=("php${PHP_VERSION}-opcache") ;;
        dom) pkgs+=("php${PHP_VERSION}-xml") ;;
        fileinfo|openssl|tokenizer|ctype) ;;  # im Core / php-common
        *) pkgs+=("php${PHP_VERSION}-${m}") ;;
      esac
    done
    if ((${#pkgs[@]})); then
      read -r -p "Pakete per apt installieren (${pkgs[*]})? [j/N] " a || true
      if [[ "$a" =~ ^[jJyY]$ ]]; then apt-get install -y "${pkgs[@]}"; fi
    fi
  else
    ok "PHP-Module vollstaendig"
  fi

  # Apache-Module (additiv; WordPress nutzt bereits einige davon)
  changed=0
  for mod in ssl proxy proxy_fcgi headers rewrite alias setenvif deflate mime dir; do
    if [[ ! -e "${APACHE_DIR}/mods-enabled/${mod}.load" ]]; then
      a2enmod "$mod" >/dev/null && { info "Apache-Modul aktiviert: $mod"; changed=1; }
    fi
  done
  ((changed)) && warn "Module wurden aktiviert; sie werden mit dem Reload in Abschnitt 12 geladen." || ok "Apache-Module vorhanden"
fi

# ============================================================================
# Abschnitt 2: Systembenutzer "tome"
# Eigener Benutzer ohne Passwort (kein Passwort-Login) mit Shell /bin/bash, damit
# Deployments per SSH (Schluessel) und der Scheduler-Cron laufen koennen.
# Er braucht KEINE Mitgliedschaft in www-data: Apache liest ueber "other"-Rechte
# (Verzeichnisse 755, Dateien 644). Geheimnisse (.env) sind 640 tome:tome.
# ============================================================================
if ask 2 "Systembenutzer ${TOME_USER}"; then
  if id "$TOME_USER" >/dev/null 2>&1; then
    ok "Benutzer ${TOME_USER} existiert bereits"
  else
    useradd --create-home --shell /bin/bash --user-group "$TOME_USER"
    passwd -l "$TOME_USER" >/dev/null   # Passwort-Login sperren
    ok "Benutzer ${TOME_USER} angelegt (ohne Passwort)"
  fi
  install -d -o "$TOME_USER" -g "$TOME_USER" -m 700 "/home/${TOME_USER}/.ssh"
  touch "/home/${TOME_USER}/.ssh/authorized_keys"
  chown "$TOME_USER:$TOME_USER" "/home/${TOME_USER}/.ssh/authorized_keys"
  chmod 600 "/home/${TOME_USER}/.ssh/authorized_keys"
  # Ist die Datei leer, die Schluessel des aufrufenden Admins uebernehmen (idempotent: nur fehlende Zeilen anhaengen).
  AUTH_KEYS="/home/${TOME_USER}/.ssh/authorized_keys"
  ADMIN_USER="${SUDO_USER:-}"
  ADMIN_KEYS="$(getent passwd "$ADMIN_USER" | cut -d: -f6)/.ssh/authorized_keys"
  if [ -n "$ADMIN_USER" ] && [ ! -s "$AUTH_KEYS" ] && [ -r "$ADMIN_KEYS" ]; then
    copied=0
    while IFS= read -r line; do
      case "$line" in ''|'#'*) continue ;; esac
      if ! grep -qxF -- "$line" "$AUTH_KEYS"; then
        printf '%s\n' "$line" >> "$AUTH_KEYS"
        copied=$((copied + 1))
      fi
    done < "$ADMIN_KEYS"
    chown "$TOME_USER:$TOME_USER" "$AUTH_KEYS"
    chmod 600 "$AUTH_KEYS"
    ok "${copied} SSH-Schluessel von ${ADMIN_USER} nach ${AUTH_KEYS} kopiert"
  elif [ -s "$AUTH_KEYS" ]; then
    ok "authorized_keys von ${TOME_USER} enthaelt bereits Schluessel (${AUTH_KEYS}), nichts kopiert"
  else
    info "Keine Schluessel von ${ADMIN_USER} gefunden (${ADMIN_KEYS})"
  fi
  info "Weitere Deploy-Schluessel eintragen: ${AUTH_KEYS}"
  info "Tipp: ssh-copy-id funktioniert nicht ohne Passwort; Schluessel als root anhaengen:"
  info "  echo 'ssh-ed25519 AAAA... deploy' >> /home/${TOME_USER}/.ssh/authorized_keys"
fi

# ============================================================================
# Abschnitt 3: Verzeichnisstruktur
#   /var/www/tome-app/releases        Releases (releases/<zeit>-<sha>)
#   /var/www/tome-app/shared/storage  persistente Uploads, Logs, Sessions
#   /var/www/tome-app/shared/.env     Konfiguration (Abschnitt 7)
#   /var/www/tome-app/current         Symlink auf das aktive Release (macht deploy.sh)
# ============================================================================
if ask 3 "Verzeichnisstruktur ${TOME_ROOT}"; then
  install -d -o "$TOME_USER" -g "$TOME_USER" -m 755 "$TOME_ROOT" "$TOME_ROOT/releases" "$SHARED"
  for d in storage storage/app storage/app/public storage/app/public/profile_images storage/app/public/infos \
           storage/framework storage/framework/cache storage/framework/cache/data \
           storage/framework/sessions storage/framework/views storage/logs; do
    install -d -o "$TOME_USER" -g "$TOME_USER" -m 755 "$SHARED/$d"
  done
  # Logs, Sessions und Caches nicht fuer andere Systembenutzer (WordPress laeuft als www-data) lesbar.
  # Apache braucht nur storage/app/public (755).
  chmod 750 "$SHARED/storage/logs" "$SHARED/storage/framework"
  # Verzeichnis fuer Backups (Abschnitt 10) und pre-deploy-Dumps
  install -d -o root -g "$TOME_USER" -m 770 "$BACKUP_DIR"
  ok "Struktur angelegt"
  ls -la "$TOME_ROOT" "$SHARED"
fi

# ============================================================================
# Abschnitt 4: PHP-FPM-Pool "tome"
# Eigener Pool (Benutzer tome), Socket gehoert www-data (Apache verbindet sich).
# pm=ondemand und max_children=5 wegen 2 GB RAM, die mit WordPress geteilt werden.
# open_basedir begrenzt PHP auf die App und /tmp. opcache.validate_timestamps=0:
# PHP prueft keine Datei-Aenderungen mehr -> nach jedem Deploy muss der FPM-Dienst
# neu geladen werden (macht deploy.sh per sudoers-Regel aus Abschnitt 5).
# Hinweis: Der OPcache-Speicher gehoert dem FPM-Master und wird von allen Pools geteilt;
# validate_timestamps ist pro Pool einstellbar, WordPress behaelt seine Einstellung.
# ============================================================================
if ask 4 "PHP-FPM-Pool tome"; then
  [[ -d "$FPM_POOL_DIR" ]] || die "Pool-Verzeichnis ${FPM_POOL_DIR} nicht gefunden (php${PHP_VERSION}-fpm installiert?)"
  cat > "${FPM_POOL_DIR}/tome.conf" <<EOF
; T.O.M.E. - eigener FPM-Pool (verwaltet von deploy/server-setup.sh)
[tome]
user = ${TOME_USER}
group = ${TOME_USER}

listen = ${FPM_SOCKET}
; Apache (www-data) muss den Socket oeffnen duerfen
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

; 2 GB RAM, geteilt mit WordPress: wenige, kurzlebige Worker
pm = ondemand
pm.max_children = 5
pm.process_idle_timeout = 20s
pm.max_requests = 500

; Fehler/Slowlog pro Pool
catch_workers_output = yes
request_terminate_timeout = 60s
request_slowlog_timeout = 10s
slowlog = /var/log/php${PHP_VERSION}-fpm-tome-slow.log
php_admin_value[error_log] = /var/log/php${PHP_VERSION}-fpm-tome.log
php_admin_flag[log_errors] = on
php_admin_flag[display_errors] = off

; Zugriff nur auf App, Uploads und temporaere Dateien
php_admin_value[open_basedir] = ${TOME_ROOT}/:/tmp/:/etc/ssl/certs/:/dev/urandom
php_admin_value[sys_temp_dir] = /tmp
php_admin_value[upload_tmp_dir] = /tmp
php_admin_value[upload_max_filesize] = 10M
php_admin_value[post_max_size] = 12M
php_admin_value[memory_limit] = 192M
php_admin_value[max_execution_time] = 60
php_admin_flag[expose_php] = off
php_admin_flag[allow_url_include] = off
php_admin_flag[session.cookie_httponly] = on
php_admin_flag[session.cookie_secure] = on

; Keine Shell-/Prozess-Funktionen aus dem Webprozess (artisan laeuft ueber die CLI, nicht ueber FPM)
php_admin_value[disable_functions] = exec,passthru,shell_exec,system,proc_open,popen,pcntl_exec,show_source,dl,posix_kill,posix_mkfifo,posix_setuid,posix_setgid

; OPcache: keine Dateipruefung -> Pool nach jedem Deploy neu laden!
php_admin_flag[opcache.enable] = on
php_admin_value[opcache.validate_timestamps] = 0
php_admin_value[opcache.revalidate_freq] = 0
EOF
  chmod 644 "${FPM_POOL_DIR}/tome.conf"
  # FPM-Konfigurationstest vor dem Reload
  if "php-fpm${PHP_VERSION}" -t >/dev/null 2>&1 || "/usr/sbin/php-fpm${PHP_VERSION}" -t >/dev/null 2>&1; then
    "$SYSTEMCTL" reload "$FPM_SERVICE"
    ok "Pool geschrieben, FPM neu geladen: ${FPM_SOCKET}"
  else
    rm -f "${FPM_POOL_DIR}/tome.conf"
    die "php-fpm -t fehlgeschlagen; tome.conf wurde wieder entfernt. Pruefen: php-fpm${PHP_VERSION} -t"
  fi
fi

# ============================================================================
# Abschnitt 5: sudoers
# tome darf GENAU EINEN Befehl als root ohne Passwort: den FPM-Reload nach dem Deploy.
# ============================================================================
if ask 5 "sudoers-Regel (nur systemctl reload ${FPM_SERVICE})"; then
  sysctl_bin="$(command -v systemctl || echo /usr/bin/systemctl)"
  sysctl_bin="$(readlink -f "$sysctl_bin")"
  tmp="$(mktemp)"
  cat > "$tmp" <<EOF
# T.O.M.E. Deploy: nur den FPM-Reload erlauben (verwaltet von deploy/server-setup.sh)
${TOME_USER} ALL=(root) NOPASSWD: ${sysctl_bin} reload ${FPM_SERVICE}
EOF
  if visudo -cf "$tmp" >/dev/null; then
    install -o root -g root -m 440 "$tmp" /etc/sudoers.d/tome-deploy
    ok "/etc/sudoers.d/tome-deploy installiert"
  else
    rm -f "$tmp"; die "sudoers-Syntaxpruefung fehlgeschlagen, nichts installiert"
  fi
  rm -f "$tmp"
fi

# ============================================================================
# Abschnitt 6: MySQL-Benutzer tome_app
# Nur Rechte auf die Datenbank "tome" (kein GRANT, kein FILE, keine anderen DBs).
# Das Passwort wird erzeugt und in shared/.env geschrieben (nur wenn dort noch keins steht).
# ============================================================================
if ask 6 "MySQL-Benutzer ${DB_APP_USER}@${DB_HOST_GRANT} (nur Datenbank ${DB_NAME})"; then
  ensure_env_file
  db_pass="$(env_get DB_PASSWORD)"
  if [[ -z "$db_pass" ]]; then
    db_pass="$(openssl rand -hex 16)"
    info "Neues Datenbank-Passwort erzeugt"
  else
    info "Vorhandenes DB_PASSWORD aus ${ENV_FILE} wird weiterverwendet"
  fi
  # CREATE USER IF NOT EXISTS + ALTER USER halten Benutzer und .env synchron (idempotent)
  $MYSQL_ADMIN_CMD <<SQL
CREATE USER IF NOT EXISTS '${DB_APP_USER}'@'${DB_HOST_GRANT}' IDENTIFIED BY '${db_pass}';
ALTER USER '${DB_APP_USER}'@'${DB_HOST_GRANT}' IDENTIFIED BY '${db_pass}';
REVOKE ALL PRIVILEGES, GRANT OPTION FROM '${DB_APP_USER}'@'${DB_HOST_GRANT}';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES, CREATE TEMPORARY TABLES, LOCK TABLES
  ON \`${DB_NAME}\`.* TO '${DB_APP_USER}'@'${DB_HOST_GRANT}';
FLUSH PRIVILEGES;
SQL
  env_force DB_CONNECTION mysql
  env_force DB_HOST "$DB_HOST_APP"
  env_default DB_PORT 3306
  env_force DB_DATABASE "$DB_NAME"
  env_force DB_USERNAME "$DB_APP_USER"
  env_force DB_PASSWORD "$db_pass"
  ok "Benutzer ${DB_APP_USER} eingerichtet, Zugangsdaten in ${ENV_FILE}"
  $MYSQL_ADMIN_CMD -N -e "SHOW GRANTS FOR '${DB_APP_USER}'@'${DB_HOST_GRANT}'"
fi

# ============================================================================
# Abschnitt 7: .env
# Fehlende Schluessel werden mit Produktionswerten ergaenzt, vorhandene NIE ueberschrieben.
# APP_KEY und JWT_SECRET werden nur beim ersten Lauf erzeugt. Mail-Einstellungen werden aus
# der alten App uebernommen (OLD_ENV), sonst bleiben Platzhalter AENDERN_*.
# ACHTUNG: Ein neues JWT_SECRET macht alle alten Logins ungueltig (gewollt beim Cutover).
# ============================================================================
if ask 7 ".env aus Vorlage (${ENV_FILE})"; then
  ensure_env_file
  env_default APP_NAME TOME
  env_default APP_ENV production
  env_force   APP_ENV production
  env_default APP_DEBUG false
  env_force   APP_DEBUG false
  env_default APP_URL "${APP_PUBLIC_URL}/api"
  env_default VUE_APP_URL "$APP_PUBLIC_URL"
  env_default FRONTEND_URL "$APP_PUBLIC_URL"
  if [[ -z "$(env_get APP_KEY)" ]]; then
    env_force APP_KEY "base64:$(openssl rand -base64 32)"
    info "APP_KEY erzeugt"
  fi
  if [[ -z "$(env_get JWT_SECRET)" ]]; then
    env_force JWT_SECRET "$(openssl rand -hex 32)"
    info "JWT_SECRET erzeugt"
  fi
  env_default LOG_CHANNEL daily
  env_default LOG_LEVEL info
  env_default LOG_DAILY_DAYS 14
  env_default SESSION_DRIVER file
  env_default CACHE_DRIVER file
  env_default CACHE_STORE file
  env_default QUEUE_CONNECTION sync
  env_default QUEUE_DRIVER sync
  env_default SESSION_SECURE_COOKIE true
  env_default FIREBASE_CREDENTIALS_PATH "${SHARED}/firebase-service-account.json"

  # Mail: aus der alten Installation uebernehmen, falls vorhanden
  old_get() { [[ -f "$OLD_ENV" ]] && grep -E "^$1=" "$OLD_ENV" | tail -1 | cut -d= -f2- || true; }
  for key in MAIL_HOST MAIL_PORT MAIL_USERNAME MAIL_PASSWORD MAIL_ENCRYPTION MAIL_FROM_ADDRESS MAIL_FROM_NAME; do
    val="$(old_get "$key")"
    if [[ -n "$val" ]]; then env_default "$key" "$val"; fi
  done
  env_default MAIL_MAILER smtp
  env_default MAIL_DRIVER smtp
  env_default MAIL_HOST "AENDERN_smtp.example.org"
  env_default MAIL_PORT 587
  env_default MAIL_USERNAME "AENDERN_benutzer"
  env_default MAIL_PASSWORD "AENDERN_passwort"
  env_default MAIL_ENCRYPTION tls
  env_default MAIL_FROM_ADDRESS "$MAIL_FROM_ADDRESS"
  env_default MAIL_FROM_NAME "$MAIL_FROM_NAME"
  chown "$TOME_USER:$TOME_USER" "$ENV_FILE"; chmod 640 "$ENV_FILE"
  ok ".env bereit (640 ${TOME_USER}:${TOME_USER}): ${ENV_FILE}"
  if grep -q "AENDERN_" "$ENV_FILE"; then
    warn "Platzhalter AENDERN_* in der .env muessen noch ersetzt werden (Mail):"
    grep -n "AENDERN_" "$ENV_FILE" | sed 's/PASSWORD=.*/PASSWORD=<...>/'
  fi
  [[ -f "${SHARED}/firebase-service-account.json" ]] || warn "Firebase-Service-Account fehlt noch: ${SHARED}/firebase-service-account.json"
fi

# ============================================================================
# Abschnitt 8: Uploads aus der alten App uebernehmen
# KOPIERT (nicht verschoben) nach shared/storage/app/public. Die alte App bleibt unveraendert,
# damit ein Rollback jederzeit moeglich ist. Kann beliebig oft wiederholt werden (rsync);
# beim Cutover vor dem Umschalten noch einmal ausfuehren, um spaete Uploads zu holen.
# ============================================================================
if ask 8 "Uploads von ${OLD_UPLOADS} uebernehmen"; then
  if [[ ! -d "$OLD_UPLOADS" ]]; then
    warn "Quelle ${OLD_UPLOADS} nicht vorhanden - nichts zu tun"
  else
    install -d -o "$TOME_USER" -g "$TOME_USER" -m 755 "$SHARED/storage/app/public"
    rsync -a --exclude '.gitignore' --chown="${TOME_USER}:${TOME_USER}" --chmod=D755,F644 \
      "${OLD_UPLOADS%/}/" "$SHARED/storage/app/public/"
    ok "Uebernommen: $(find "$SHARED/storage/app/public" -type f | wc -l) Dateien (Quelle: $(find "$OLD_UPLOADS" -type f ! -name .gitignore | wc -l))"
  fi
fi

# ============================================================================
# Abschnitt 9: Scheduler-Cron
# Laravel-Scheduler (Benachrichtigungen, Serien, Retention ...) jede Minute als tome.
# Der Cron-Eintrag ist ein No-op, solange noch kein Release aktiv ist.
# ============================================================================
if ask 9 "Scheduler-Cron fuer ${TOME_USER}"; then
  cat > /etc/cron.d/tome-scheduler <<EOF
# T.O.M.E. Laravel-Scheduler (verwaltet von deploy/server-setup.sh)
SHELL=/bin/bash
PATH=/usr/local/bin:/usr/bin:/bin
* * * * * ${TOME_USER} [ -d ${TOME_ROOT}/current/server ] && cd ${TOME_ROOT}/current/server && php${PHP_VERSION} artisan schedule:run >> /dev/null 2>&1
EOF
  chmod 644 /etc/cron.d/tome-scheduler
  ok "/etc/cron.d/tome-scheduler installiert"
fi

# ============================================================================
# Abschnitt 10: Taegliche Backups
# 03:17 Uhr: mysqldump der Datenbank + tar der Uploads + tar der Konfiguration (.env, Firebase).
# Ziel: /var/backups/tome, 14 Tage Aufbewahrung (auch pre-deploy-Dumps). Nur lokale Backups.
# Der Dump laeuft als root ueber den lokalen Socket (kein Passwort in der Cron-Datei).
# ============================================================================
if ask 10 "Backup-Skript und Cron (14 Tage Aufbewahrung)"; then
  install -d -o root -g "$TOME_USER" -m 770 "$BACKUP_DIR"
  cat > /usr/local/sbin/tome-backup <<EOF
#!/usr/bin/env bash
# T.O.M.E. Backup (verwaltet von deploy/server-setup.sh)
set -euo pipefail
umask 077
BACKUP_DIR="${BACKUP_DIR}"
SHARED="${SHARED}"
DB_NAME="${DB_NAME}"
RETENTION_DAYS=14
MYSQLDUMP_CMD="\${MYSQLDUMP_CMD:-mysqldump}"
TS="\$(date +%Y%m%d-%H%M%S)"

mkdir -p "\$BACKUP_DIR"

# 1. Datenbank (konsistent, ohne Tablespace-Recht)
\$MYSQLDUMP_CMD --single-transaction --no-tablespaces --routines --default-character-set=utf8mb4 "\$DB_NAME" | gzip -9 > "\$BACKUP_DIR/db-\$TS.sql.gz"

# 2. Uploads
tar -C "\$SHARED/storage/app" -czf "\$BACKUP_DIR/uploads-\$TS.tar.gz" public

# 3. Konfiguration/Geheimnisse (nur root/tome lesbar)
tar -C "\$SHARED" -czf "\$BACKUP_DIR/config-\$TS.tar.gz" .env \$(cd "\$SHARED" && ls firebase-service-account.json 2>/dev/null || true)

# 4. Aufbewahrung
find "\$BACKUP_DIR" -maxdepth 1 -type f \\( -name 'db-*.sql.gz' -o -name 'uploads-*.tar.gz' -o -name 'config-*.tar.gz' -o -name 'pre-deploy-*.sql.gz' \\) -mtime +\$RETENTION_DAYS -delete

echo "Backup \$TS fertig: \$(ls -1 "\$BACKUP_DIR" | wc -l) Dateien in \$BACKUP_DIR"
EOF
  chmod 750 /usr/local/sbin/tome-backup
  cat > /etc/cron.d/tome-backup <<EOF
# T.O.M.E. taegliches Backup (verwaltet von deploy/server-setup.sh)
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
17 3 * * * root /usr/local/sbin/tome-backup >> /var/log/tome-backup.log 2>&1
EOF
  chmod 644 /etc/cron.d/tome-backup
  ok "Backup installiert: /usr/local/sbin/tome-backup (Test: sudo /usr/local/sbin/tome-backup)"
fi

# ============================================================================
# Abschnitt 11: Neuen Vhost ablegen (NOCH NICHT aktiv)
# Rendert deploy/apache/training.conf (Platzhalter aus site.env) nach sites-available/training.conf.new. Apache laedt
# nur *.conf aus sites-enabled, die .new-Datei hat also keine Wirkung.
# ============================================================================
if ask 11 "Neuen Vhost als training.conf.new ablegen"; then
  src="${SCRIPT_DIR}/apache/${VHOST_NAME}"
  [[ -f "$src" ]] || die "Vhost-Vorlage nicht gefunden: ${src} (das ganze deploy/-Verzeichnis auf den Server kopieren)"
  # Platzhalter @SITE_HOST@, @SITE_ALIAS@, @SITE_CERT_DIR@, @SITE_ADMIN_EMAIL@ aus site.env einsetzen
  rendered="$(mktemp)"
  sed -e "s|@SITE_HOST@|${SITE_HOST}|g" -e "s|@SITE_ALIAS@|${SITE_ALIAS}|g" -e "s|@SITE_CERT_DIR@|${SITE_CERT_DIR}|g" -e "s|@SITE_ADMIN_EMAIL@|${SITE_ADMIN_EMAIL}|g" "$src" > "$rendered"
  ! grep -q '@SITE_[A-Z_]*@' "$rendered" || die "Unersetzte Platzhalter in der Vhost-Vorlage"
  install -o root -g root -m 644 "$rendered" "${APACHE_DIR}/sites-available/${VHOST_NAME}.new"
  rm -f "$rendered"
  ok "Abgelegt: ${APACHE_DIR}/sites-available/${VHOST_NAME}.new (inaktiv)"
  info "Vergleich mit dem aktuellen Vhost: diff -u ${APACHE_DIR}/sites-enabled/${VHOST_NAME} ${APACHE_DIR}/sites-available/${VHOST_NAME}.new"
fi

# ============================================================================
# Abschnitt 12: Vhost AKTIVIEREN
# Ersetzt den live Vhost durch training.conf.new:
#   1. Backup der alten Datei nach /root/tome-vhost-backup/
#   2. Austausch (den Symlink in sites-enabled auflosen, falls vorhanden)
#   3. apache2ctl configtest -> bei Fehler sofort automatisch zurueck
#   4. Reload -> bei Fehler automatisch zurueck + erneuter Reload
# Erst ausfuehren, wenn Deploy #1 gelaufen ist (current/ existiert).
# Manuelles Zurueck: cp /root/tome-vhost-backup/training.conf.<zeit> <Ziel> && systemctl reload apache2
# ============================================================================
if ask 12 "Vhost aktivieren (Backup, configtest, reload, automatischer Revert)"; then
  new="${APACHE_DIR}/sites-available/${VHOST_NAME}.new"
  [[ -f "$new" ]] || die "${new} fehlt - zuerst Abschnitt 11 ausfuehren"
  live="${APACHE_DIR}/sites-enabled/${VHOST_NAME}"
  target="$(readlink -f "$live" 2>/dev/null || echo "$live")"
  [[ -e "$target" ]] || target="${APACHE_DIR}/sites-available/${VHOST_NAME}"
  [[ -e "${TOME_ROOT}/current" ]] || warn "${TOME_ROOT}/current existiert noch nicht (Deploy #1 fehlt)!"

  bdir=/root/tome-vhost-backup
  install -d -m 700 "$bdir"
  ts="$(date +%Y%m%d-%H%M%S)"
  backup="${bdir}/${VHOST_NAME}.${ts}"
  [[ -e "$target" ]] && cp -a "$target" "$backup" && info "Backup: ${backup}"

  revert() {
    warn "Stelle alten Vhost wieder her"
    if [[ -f "$backup" ]]; then cp -a "$backup" "$target"; fi
    apache2ctl configtest >/dev/null 2>&1 && "$SYSTEMCTL" reload apache2 || warn "Reload nach Revert fehlgeschlagen - bitte manuell pruefen!"
  }

  cp -a "$new" "$target"
  if ! apache2ctl configtest; then
    revert
    die "apache2ctl configtest fehlgeschlagen - alter Vhost wiederhergestellt"
  fi
  if ! "$SYSTEMCTL" reload apache2; then
    revert
    die "Apache-Reload fehlgeschlagen - alter Vhost wiederhergestellt"
  fi
  ok "Neuer Vhost aktiv. Backup des alten: ${backup}"
  info "Zuruecksetzen: cp -a ${backup} ${target} && apache2ctl configtest && systemctl reload apache2"
fi

echo
ok "Fertig."

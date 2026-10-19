#!/usr/bin/env bash
# =============================================================================
# T.O.M.E. Rollback auf ein frueheres Release (nur Code; Datenbank-Migrationen bleiben bestehen!)
#
#   deploy/rollback.sh --host training.ssc-landau.de            # auf das vorherige Release
#   deploy/rollback.sh --host training.ssc-landau.de RELEASE    # auf ein bestimmtes Release
#   deploy/rollback.sh --host training.ssc-landau.de --list     # Releases anzeigen
# =============================================================================
set -euo pipefail
# shellcheck source=lib.sh
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

TARGET=""; LIST=0
while [[ $# -gt 0 ]]; do
  case "$1" in
    --host) HOST="${2:?--host braucht einen Wert}"; shift ;;
    --list) LIST=1 ;;
    -h|--help) sed -n '2,8p' "$0"; exit 0 ;;
    -*) die "Unbekannte Option: $1" ;;
    *) TARGET="$1" ;;
  esac
  shift
done
[[ -n "$HOST" || "$TOME_TRANSPORT" == docker ]] || die "--host fehlt"

CURRENT="$(current_release)"
RELEASES="$(remote_script "$TOME_ROOT" <<'EOF'
ls -1 "$1/releases" | sort
EOF
)"

if [[ "$LIST" == 1 ]]; then
  while read -r r; do [[ "$r" == "$CURRENT" ]] && echo "* $r  (aktiv)" || echo "  $r"; done <<<"$RELEASES"
  exit 0
fi

if [[ -z "$TARGET" ]]; then
  # vorheriges Release = das naechstaeltere vor dem aktiven
  TARGET="$(grep -B1 -Fx "$CURRENT" <<<"$RELEASES" | head -1 || true)"
  [[ -n "$TARGET" && "$TARGET" != "$CURRENT" ]] || die "Kein vorheriges Release gefunden (aktiv: ${CURRENT:-keins})"
fi
grep -Fxq "$TARGET" <<<"$RELEASES" || die "Release '$TARGET' existiert nicht. Vorhanden: $(tr '\n' ' ' <<<"$RELEASES")"
[[ "$TARGET" != "$CURRENT" ]] || die "'$TARGET' ist bereits aktiv"

log "Rollback: ${CURRENT} -> ${TARGET}"
warn "Datenbank-Migrationen werden nicht zurueckgedreht. Bei Schemaproblemen: Dump in /var/backups/tome/pre-deploy-*.sql.gz"
switch_to "$TARGET"
if health_check; then
  ok "Rollback auf ${TARGET} erfolgreich"
else
  die "Health-Check nach dem Rollback fehlgeschlagen! Server pruefen (Apache/PHP-FPM-Logs)."
fi

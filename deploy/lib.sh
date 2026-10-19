#!/usr/bin/env bash
# Gemeinsame Funktionen fuer deploy.sh und rollback.sh (wird nur eingebunden, nicht direkt ausgefuehrt).
#
# Transport: Standard ist SSH als Benutzer "tome". Fuer die lokale Simulation (deploy/prod-sim)
# fuehrt TOME_TRANSPORT=docker dieselben Befehle per "docker exec" im Container TOME_CONTAINER aus,
# damit exakt dieselbe Skriptlogik getestet wird.

TOME_ROOT="${TOME_ROOT:-/var/www/tome-app}"
TOME_PHP="${TOME_PHP:-php8.4}"
TOME_TRANSPORT="${TOME_TRANSPORT:-ssh}"
TOME_CONTAINER="${TOME_CONTAINER:-tome-sim-vm}"
SITE_HOST="${SITE_HOST:-training.ssc-landau.de}"
HEALTH_PORT="${TOME_HEALTH_PORT:-443}"
CURL_OPTS="${TOME_CURL_OPTS:-}"          # Simulation: -k (selbstsigniertes Zertifikat)
HOST="${HOST:-}"
REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

log()  { printf '\033[1;34m==>\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m OK\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33mWARN\033[0m %s\n' "$*" >&2; }
die()  { printf '\033[1;31mFEHLER\033[0m %s\n' "$*" >&2; exit 1; }

# Fuehrt ein Skript von stdin auf dem Server als tome aus: remote_script arg1 arg2 <<'EOF' ... EOF
remote_script() {
  if [[ "$TOME_TRANSPORT" == docker ]]; then
    docker exec -i -u tome "$TOME_CONTAINER" bash -s -- "$@"
  else
    ssh -o BatchMode=yes "tome@${HOST}" bash -s -- "$@"
  fi
}

# rsync QUELLE/ nach Server:ZIEL/  (weitere rsync-Optionen als Argumente davor)
remote_rsync() {
  local src="$1" dest="$2"; shift 2
  if [[ "$TOME_TRANSPORT" == docker ]]; then
    rsync -rlpt --delete "$@" -e "${REPO_DIR}/deploy/prod-sim/docker-rsh.sh" "$src/" "${TOME_CONTAINER}:${dest}/"
  else
    rsync -rlpt --delete -z "$@" -e "ssh -o BatchMode=yes" "$src/" "tome@${HOST}:${dest}/"
  fi
}

resolve_ip() {
  if [[ "$TOME_TRANSPORT" == docker ]]; then echo 127.0.0.1; return; fi
  python3 -c 'import socket,sys; print(socket.gethostbyname(sys.argv[1]))' "$HOST"
}

# curl gegen den Server, aber mit dem echten Hostnamen (SNI/Vhost): http_get PFAD
http_get() {
  local ip; ip="$(resolve_ip)"
  # shellcheck disable=SC2086
  curl -sS -m 15 $CURL_OPTS --resolve "${SITE_HOST}:${HEALTH_PORT}:${ip}" -w '\n%{http_code}' \
    "https://${SITE_HOST}:${HEALTH_PORT}$1"
}

# Prueft /api/v1/health und /; bis zu 6 Versuche. Gibt 0 bei Erfolg zurueck.
health_check() {
  local i out code body
  for i in 1 2 3 4 5 6; do
    if out="$(http_get /api/v1/health 2>&1)"; then
      code="${out##*$'\n'}"; body="${out%$'\n'*}"
      if [[ "$code" == 200 && "$body" == *'"status":"ok"'* ]]; then
        if out="$(http_get / 2>&1)"; then
          code="${out##*$'\n'}"; body="${out%$'\n'*}"
          if [[ "$code" == 200 && "$body" == *'id="app"'* ]]; then
            ok "Health-Check: /api/v1/health = ok, / = App-Shell"
            return 0
          fi
          warn "Versuch $i: / lieferte HTTP $code ohne App-Shell"
        fi
      else
        warn "Versuch $i: /api/v1/health lieferte HTTP ${code:-?}: $(head -c 120 <<<"$body" | tr "\n" " ")"
      fi
    else
      warn "Versuch $i: $(head -c 160 <<<"$out" | tr "\n" " ")"
    fi
    sleep 2
  done
  return 1
}

# Aktives Release (Verzeichnisname) lesen
current_release() {
  remote_script "$TOME_ROOT" <<'EOF'
basename "$(readlink "$1/current" 2>/dev/null)" 2>/dev/null || true
EOF
}

# Atomar umschalten und FPM neu laden: switch_to RELEASE
switch_to() {
  remote_script "$TOME_ROOT" "$1" <<'EOF'
set -e
root="$1"; rel="$2"
[ -d "$root/releases/$rel" ] || { echo "Release $rel existiert nicht" >&2; exit 1; }
ln -sfn "releases/$rel" "$root/current.tmp"
mv -T "$root/current.tmp" "$root/current"
sudo -n systemctl reload php8.4-fpm
EOF
}

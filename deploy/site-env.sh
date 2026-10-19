#!/usr/bin/env bash
# Laedt die standortspezifische Konfiguration (deploy/site.env, nicht im Git; Vorlage: site.env.example).
# Wird von lib.sh, server-setup.sh und matomo-setup.sh eingebunden. Bereits gesetzte Umgebungsvariablen
# haben Vorrang (z. B. fuer die Simulation: SITE_ENV_FILE=deploy/prod-sim/site.env).
#
# Es gibt bewusst KEINE Standardwerte fuer Domains: fehlt ein Wert, bricht das Skript mit einer klaren
# Meldung ab (require_site_vars).

SITE_ENV_FILE="${SITE_ENV_FILE:-$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/site.env}"

if [[ -f "$SITE_ENV_FILE" ]]; then
  while IFS= read -r _line || [[ -n "$_line" ]]; do
    case "$_line" in ''|'#'*) continue ;; esac
    _key="${_line%%=*}"; _val="${_line#*=}"
    [[ "$_key" =~ ^[A-Z][A-Z0-9_]*$ ]] || continue
    _val="${_val%\"}"; _val="${_val#\"}"
    if [[ -z "${!_key+x}" ]]; then export "$_key=$_val"; fi
  done < "$SITE_ENV_FILE"
  unset _line _key _val
fi

# require_site_vars SITE_HOST SITE_CERT_DIR ...  -> bricht ab, wenn eine Variable leer ist
require_site_vars() {
  local missing=() v
  for v in "$@"; do [[ -n "${!v:-}" ]] || missing+=("$v"); done
  if (( ${#missing[@]} )); then
    printf '\033[1;31mFEHLER\033[0m Fehlende Konfiguration: %s\n' "${missing[*]}" >&2
    printf '       Datei deploy/site.env anlegen (Vorlage: deploy/site.env.example) oder die Variablen setzen.\n' >&2
    printf '       Gesucht wurde: %s\n' "$SITE_ENV_FILE" >&2
    exit 1
  fi
}

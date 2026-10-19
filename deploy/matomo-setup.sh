#!/usr/bin/env bash
# =============================================================================
# T.O.M.E. - Matomo einrichten (selbst gehostet unter https://<SITE_HOST>/matomo/)
#
# Als root auf dem Server ausfuehren (Ubuntu 24.04, Apache 2.4, PHP 8.4-FPM, MySQL 8):
#
#   sudo ./matomo-setup.sh             # fragt je Abschnitt: "Abschnitt X ausführen? [j/N]"
#   sudo ./matomo-setup.sh --only 3    # nur bestimmte Abschnitte (ohne Rueckfrage)
#   sudo ./matomo-setup.sh --list      # Abschnitte auflisten
#
# Idempotent. Fasst nur Matomo-eigene Dinge an (Benutzer matomo, /var/www/matomo, FPM-Pool
# matomo, Datenbank matomo, /etc/cron.d/matomo-archive). Der FPM-Pool www und
# andere Seiten auf dem Server bleiben unveraendert. Ablauf: siehe MATOMO_SETUP.md.
#
# Abschnitte:
#   1  Voraussetzungen pruefen (PHP-Module, gpg, unzip)
#   2  Systembenutzer "matomo" (ohne Login)
#   3  Matomo herunterladen, GPG-Signatur pruefen, nach /var/www/matomo installieren
#   4  PHP-FPM-Pool "matomo"
#   5  MySQL-Datenbank + Benutzer "matomo"
#   6  Cron: stuendliche Archivierung (core:archive)
#   7  Apache-Vhost aktivieren (ruft server-setup.sh --only 11,12 auf)
#   8  NACH dem Web-Installer: Konfiguration haerten, Datenschutz-Einstellungen setzen
#
# Konfiguration ueber Umgebungsvariablen (Standardwerte gelten fuer die Produktion;
# prod-sim ueberschreibt einige davon):
#   MATOMO_ROOT, MATOMO_USER, DB_NAME, DB_USER, DB_HOST_GRANT, DB_HOST_APP, MYSQL_ADMIN_CMD,
#   PHP_VERSION, FPM_POOL_DIR, SYSTEMCTL, CRON_DIR, CRED_FILE, SITE_HOST, MATOMO_BASE_URL
# =============================================================================
set -euo pipefail

# ----------------------------------------------------------------- Konfiguration
MATOMO_ROOT="${MATOMO_ROOT:-/var/www/matomo}"
MATOMO_USER="${MATOMO_USER:-matomo}"
PHP_VERSION="${PHP_VERSION:-8.4}"
FPM_POOL_DIR="${FPM_POOL_DIR:-/etc/php/${PHP_VERSION}/fpm/pool.d}"
FPM_SOCKET="/run/php/php${PHP_VERSION}-fpm-matomo.sock"
FPM_SERVICE="php${PHP_VERSION}-fpm"
SYSTEMCTL="${SYSTEMCTL:-systemctl}"
DB_NAME="${DB_NAME:-matomo}"
DB_USER="${DB_USER:-matomo}"
DB_HOST_GRANT="${DB_HOST_GRANT:-localhost}"   # Host-Teil des MySQL-Benutzers
DB_HOST_APP="${DB_HOST_APP:-localhost}"       # Datenbankserver fuer den Web-Installer
MYSQL_ADMIN_CMD="${MYSQL_ADMIN_CMD:-}"   # root-Zugang; leer = aus MYSQL_ADMIN_CNF (site.env) oder "mysql"
CRON_DIR="${CRON_DIR:-/etc/cron.d}"
CRED_FILE="${CRED_FILE:-/root/matomo-db.txt}"
DOWNLOAD_URL="${DOWNLOAD_URL:-https://builds.matomo.org}"
# Offizieller Matomo-Signaturschluessel ("Matomo <hello@matomo.org>"), siehe
# https://matomo.org/faq/how-to-install/faq_20171/ . Der Schluessel wird von builds.matomo.org geladen
# und nur akzeptiert, wenn dieser Fingerabdruck stimmt.
MATOMO_KEY_FPR="F529A27008477483777FC23D63BB30D0E5D2C749"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP_BIN="php${PHP_VERSION}"
# shellcheck source=site-env.sh
source "${SCRIPT_DIR}/site-env.sh"
if [[ -z "$MYSQL_ADMIN_CMD" ]]; then
  if [[ -n "${MYSQL_ADMIN_CNF:-}" ]]; then MYSQL_ADMIN_CMD="mysql --defaults-extra-file=${MYSQL_ADMIN_CNF}"; else MYSQL_ADMIN_CMD=mysql; fi
fi
require_site_vars SITE_HOST
MATOMO_BASE_URL="${MATOMO_BASE_URL:-https://${SITE_HOST}/matomo/}"

# ----------------------------------------------------------------- Hilfsfunktionen
ONLY=""
info()  { printf '\033[1;34m[info]\033[0m %s\n' "$*"; }
ok()    { printf '\033[1;32m[ ok ]\033[0m %s\n' "$*"; }
warn()  { printf '\033[1;33m[warn]\033[0m %s\n' "$*"; }
die()   { printf '\033[1;31m[fehler]\033[0m %s\n' "$*" >&2; exit 1; }

ask() {
  local nr="$1" title="$2"
  if [[ -n "$ONLY" && ",${ONLY}," != *",${nr},"* ]]; then return 1; fi
  echo
  echo "================================================================"
  echo " Abschnitt ${nr}: ${title}"
  echo "================================================================"
  if [[ -n "$ONLY" ]]; then return 0; fi
  local answer=""
  read -r -p "Abschnitt ${nr} ausführen? [j/N] " answer || true
  [[ "$answer" =~ ^[jJyY]$ ]]
}

confirm() { local a=""; read -r -p "$1 [j/N] " a || true; [[ "$a" =~ ^[jJyY]$ ]]; }
# Matomo-Konsole als Benutzer matomo
matomo_console() { sudo -u "$MATOMO_USER" "$PHP_BIN" "${MATOMO_ROOT}/console" "$@"; }
sql() { $MYSQL_ADMIN_CMD "$@"; }

[[ $EUID -eq 0 ]] || die "Bitte als root ausfuehren (sudo)."

while [[ $# -gt 0 ]]; do
  case "$1" in
    --only) ONLY="${2:?--only braucht eine Liste, z.B. 2,3}"; shift ;;
    --list) sed -n '/^# Abschnitte:/,/^#   8 /p' "$0"; exit 0 ;;
    -h|--help) sed -n '2,36p' "$0"; exit 0 ;;
    *) die "Unbekannte Option: $1" ;;
  esac
  shift
done

# ============================================================================
# Abschnitt 1: Voraussetzungen
# ============================================================================
if ask 1 "Voraussetzungen pruefen (PHP-Module, gpg, unzip)"; then
  missing_cmds=()
  for c in "$PHP_BIN" mysql curl unzip gpg openssl apache2ctl rsync sudo; do
    command -v "$c" >/dev/null 2>&1 || missing_cmds+=("$c")
  done
  [[ -x "/usr/sbin/php-fpm${PHP_VERSION}" ]] || missing_cmds+=("php${PHP_VERSION}-fpm")

  # Module laut Matomo-Systemcheck: PDO/MySQL, gd, mbstring, xml, curl, zip (ctype/json/iconv/session sind im Core)
  missing_mods=()
  if command -v "$PHP_BIN" >/dev/null 2>&1; then
    have="$("$PHP_BIN" -m)"
    for m in pdo_mysql mysqli gd mbstring xml dom curl zip json iconv session ctype; do
      grep -qix "$m" <<<"$have" || missing_mods+=("$m")
    done
  fi
  pkgs=()
  for c in "${missing_cmds[@]}"; do
    case "$c" in
      gpg) pkgs+=(gnupg) ;;
      unzip|curl|rsync|sudo|openssl) pkgs+=("$c") ;;
      "php${PHP_VERSION}-fpm") pkgs+=("$c") ;;
      "$PHP_BIN") pkgs+=("php${PHP_VERSION}-cli") ;;
      mysql) warn "mysql-Client fehlt (Paket mysql-client)" ;;
      *) warn "Programm fehlt: $c" ;;
    esac
  done
  for m in "${missing_mods[@]}"; do
    case "$m" in
      pdo_mysql|mysqli) pkgs+=("php${PHP_VERSION}-mysql") ;;
      dom|xml) pkgs+=("php${PHP_VERSION}-xml") ;;
      json|iconv|session|ctype) pkgs+=("php${PHP_VERSION}-common") ;;
      *) pkgs+=("php${PHP_VERSION}-${m}") ;;
    esac
  done
  if ((${#pkgs[@]})); then
    mapfile -t pkgs < <(printf '%s\n' "${pkgs[@]}" | sort -u)
    warn "Fehlende Pakete: ${pkgs[*]}"
    if confirm "Pakete per apt installieren (${pkgs[*]})?"; then
      apt-get install -y "${pkgs[@]}"
    else
      warn "Nicht installiert - spaetere Abschnitte koennen fehlschlagen"
    fi
  else
    ok "Alle Programme und PHP-Module vorhanden"
  fi
  for mod in alias proxy proxy_fcgi headers rewrite ssl; do
    [[ -e "/etc/apache2/mods-enabled/${mod}.load" ]] || { a2enmod "$mod" >/dev/null && info "Apache-Modul aktiviert: $mod (wirkt nach dem Reload in Abschnitt 7)"; }
  done
fi

# ============================================================================
# Abschnitt 2: Systembenutzer
# ============================================================================
if ask 2 "Systembenutzer ${MATOMO_USER}"; then
  if id "$MATOMO_USER" >/dev/null 2>&1; then
    ok "Benutzer ${MATOMO_USER} existiert bereits"
  else
    adduser --system --group --no-create-home --home "$MATOMO_ROOT" --shell /usr/sbin/nologin "$MATOMO_USER"
    ok "Benutzer ${MATOMO_USER} angelegt (kein Login)"
  fi
fi

# ============================================================================
# Abschnitt 3: Matomo herunterladen und installieren
# Quelle: builds.matomo.org (HTTPS). Die Signatur matomo-latest.zip.asc wird gegen den
# Matomo-Schluessel mit dem oben festgehaltenen Fingerabdruck geprueft; ohne gueltige
# Signatur wird nichts installiert.
# Ist Matomo schon installiert (config.ini.php vorhanden), wird aktualisiert: config/config.ini.php,
# tmp/ und misc/user/ bleiben erhalten. Normalerweise ist das Update per Oberflaeche einfacher.
# ============================================================================
if ask 3 "Matomo herunterladen, Signatur pruefen, nach ${MATOMO_ROOT} installieren"; then
  id "$MATOMO_USER" >/dev/null 2>&1 || die "Benutzer ${MATOMO_USER} fehlt - zuerst Abschnitt 2"
  for c in curl unzip gpg rsync; do command -v "$c" >/dev/null || die "$c fehlt - Abschnitt 1"; done
  work="$(mktemp -d)"; trap 'rm -rf "$work"' EXIT
  chmod 700 "$work"; export GNUPGHOME="${work}/gnupg"; install -d -m 700 "$GNUPGHOME"

  info "Lade Matomo und Signatur von ${DOWNLOAD_URL}"
  curl -fsSL --proto '=https' -o "${work}/matomo.zip"     "${DOWNLOAD_URL}/matomo-latest.zip"
  curl -fsSL --proto '=https' -o "${work}/matomo.zip.asc" "${DOWNLOAD_URL}/matomo-latest.zip.asc"
  curl -fsSL --proto '=https' -o "${work}/signature.asc"  "${DOWNLOAD_URL}/signature.asc"

  gpg --batch --quiet --import "${work}/signature.asc" 2>/dev/null
  fpr="$(gpg --batch --with-colons --fingerprint 2>/dev/null | awk -F: '/^fpr:/{print $10; exit}')"
  [[ "$fpr" == "$MATOMO_KEY_FPR" ]] || die "Schluessel-Fingerabdruck ${fpr:-?} entspricht nicht dem erwarteten ${MATOMO_KEY_FPR} - Abbruch"
  status="$(gpg --batch --status-fd 1 --verify "${work}/matomo.zip.asc" "${work}/matomo.zip" 2>/dev/null || true)"
  grep -q "^\[GNUPG:\] VALIDSIG ${MATOMO_KEY_FPR} " <<<"$status" || die "GPG-Signatur des Downloads ungueltig - Abbruch (nichts installiert)"
  ok "Signatur gueltig (Schluessel ${MATOMO_KEY_FPR})"

  unzip -q "${work}/matomo.zip" -d "${work}/unpacked"
  src="${work}/unpacked/matomo"
  [[ -f "${src}/index.php" && -f "${src}/matomo.php" ]] || die "Unerwarteter Inhalt im Archiv"
  version="$(grep -o "VERSION = '[^']*'" "${src}/core/Version.php" | cut -d"'" -f2 || true)"
  info "Matomo ${version:-?}"

  update=0
  if [[ -f "${MATOMO_ROOT}/config/config.ini.php" ]]; then
    update=1
    warn "Matomo ist bereits installiert - Update. Bevorzugt: Matomo-Oberflaeche > Update (Ein-Klick)."
    confirm "Trotzdem per Skript aktualisieren (config, tmp und misc/user bleiben erhalten)?" || { info "Uebersprungen"; update=2; }
  fi
  if [[ "$update" != 2 ]]; then
    install -d -m 755 "$MATOMO_ROOT"
    if ((update)); then
      # Code-Verzeichnisse ohne Nutzerdaten komplett ersetzen, damit keine alten Dateien bleiben
      for d in core vendor libs node_modules lang; do rm -rf "${MATOMO_ROOT:?}/${d}"; done
    fi
    if ((update)); then
      # Update: Nutzerdaten (config, tmp, misc/user) unangetastet lassen
      rsync -a --exclude='/config/config.ini.php' --exclude='/tmp/' --exclude='/misc/user/' "${src}/" "${MATOMO_ROOT}/"
    else
      rsync -a "${src}/" "${MATOMO_ROOT}/"
    fi
    # misc/user/index.html steht im Manifest der Integritaetspruefung; Nutzerdateien nie ueberschreiben.
    # (tmp/ liefert das Archiv nur als leeres Verzeichnis, die Pruefung erwartet dort nichts.)
    rsync -a --ignore-existing "${src}/misc/user/" "${MATOMO_ROOT}/misc/user/"
    install -d "${MATOMO_ROOT}/tmp" "${MATOMO_ROOT}/misc/user"

    # Rechte: alles gehoert matomo (damit das Ein-Klick-Update funktioniert), lesbar fuer Apache (www-data
    # liefert Skripte/Bilder/CSS aus), PHP-Ausfuehrung nur ueber den Matomo-Pool. config/ ist fuer andere
    # Benutzer nicht lesbar (enthaelt das DB-Passwort).
    chown -R "$MATOMO_USER:$MATOMO_USER" "$MATOMO_ROOT"
    find "$MATOMO_ROOT" -type d -exec chmod 755 {} +
    find "$MATOMO_ROOT" -type f -exec chmod 644 {} +
    chmod 750 "${MATOMO_ROOT}/config"
    [[ -f "${MATOMO_ROOT}/config/config.ini.php" ]] && chmod 640 "${MATOMO_ROOT}/config/config.ini.php"
    chmod +x "${MATOMO_ROOT}/console"
    ok "Matomo ${version:-} installiert in ${MATOMO_ROOT} (Besitzer ${MATOMO_USER})"
    if ((update)); then
      matomo_console core:update --yes && ok "Datenbank-Update (core:update) ausgefuehrt" || warn "core:update fehlgeschlagen - in der Oberflaeche pruefen"
      matomo_console cache:clear >/dev/null 2>&1 || true
    fi
  fi
  trap - EXIT; rm -rf "$work"; unset GNUPGHOME
fi

# ============================================================================
# Abschnitt 4: PHP-FPM-Pool "matomo"
# Eigener Pool (Benutzer matomo), Socket gehoert www-data (Apache verbindet sich). Der Pool www
# und der Pool tome bleiben unveraendert. pm=ondemand und max_children=3 wegen kleinem Server.
# ============================================================================
if ask 4 "PHP-FPM-Pool matomo"; then
  [[ -d "$FPM_POOL_DIR" ]] || die "Pool-Verzeichnis ${FPM_POOL_DIR} nicht gefunden (php${PHP_VERSION}-fpm installiert?)"
  id "$MATOMO_USER" >/dev/null 2>&1 || die "Benutzer ${MATOMO_USER} fehlt - zuerst Abschnitt 2"
  cat > "${FPM_POOL_DIR}/matomo.conf" <<EOF
; Matomo - eigener FPM-Pool (verwaltet von deploy/matomo-setup.sh)
[matomo]
user = ${MATOMO_USER}
group = ${MATOMO_USER}

listen = ${FPM_SOCKET}
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = ondemand
pm.max_children = 3
pm.process_idle_timeout = 20s
pm.max_requests = 500

catch_workers_output = yes
request_terminate_timeout = 120s
php_admin_value[error_log] = /var/log/php${PHP_VERSION}-fpm-matomo.log
php_admin_flag[log_errors] = on
php_admin_flag[display_errors] = off

; Zugriff nur auf Matomo und temporaere Dateien
php_admin_value[open_basedir] = ${MATOMO_ROOT}:/tmp
php_admin_value[sys_temp_dir] = /tmp
php_admin_value[upload_tmp_dir] = /tmp
php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 120
php_admin_flag[expose_php] = off
php_admin_flag[allow_url_include] = off
php_admin_flag[session.cookie_httponly] = on
php_admin_flag[session.cookie_secure] = on
EOF
  chmod 644 "${FPM_POOL_DIR}/matomo.conf"
  if "php-fpm${PHP_VERSION}" -t >/dev/null 2>&1 || "/usr/sbin/php-fpm${PHP_VERSION}" -t >/dev/null 2>&1; then
    "$SYSTEMCTL" reload "$FPM_SERVICE"
    ok "Pool geschrieben, FPM neu geladen: ${FPM_SOCKET}"
  else
    rm -f "${FPM_POOL_DIR}/matomo.conf"
    die "php-fpm -t fehlgeschlagen; matomo.conf wurde wieder entfernt. Pruefen: php-fpm${PHP_VERSION} -t"
  fi
fi

# ============================================================================
# Abschnitt 5: MySQL-Datenbank und -Benutzer
# Rechte laut Matomo-Dokumentation. Das Passwort wird erzeugt, einmal angezeigt und in
# /root/matomo-db.txt (600) abgelegt. Bei erneutem Lauf bleibt es unveraendert.
# ============================================================================
if ask 5 "MySQL-Datenbank ${DB_NAME} und Benutzer ${DB_USER}"; then
  sql -e "SELECT 1" >/dev/null || die "MySQL-Zugang (MYSQL_ADMIN_CMD='${MYSQL_ADMIN_CMD}') funktioniert nicht"
  sql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
  user_exists="$(sql -N -e "SELECT COUNT(*) FROM mysql.user WHERE User='${DB_USER}' AND Host='${DB_HOST_GRANT}'")"
  if [[ "$user_exists" != 0 && -s "$CRED_FILE" ]]; then
    ok "Datenbank und Benutzer vorhanden; Zugangsdaten stehen in ${CRED_FILE} (unveraendert)"
  else
    pw="$(openssl rand -base64 36 | tr -dc 'A-Za-z0-9' | head -c 28)"
    sql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'${DB_HOST_GRANT}' IDENTIFIED BY '${pw}'"
    sql -e "ALTER USER '${DB_USER}'@'${DB_HOST_GRANT}' IDENTIFIED BY '${pw}'"
    umask 077
    printf 'Datenbankserver: %s\nDatenbank:       %s\nBenutzer:        %s\nPasswort:        %s\nTabellenpraefix: matomo_\n' \
      "$DB_HOST_APP" "$DB_NAME" "$DB_USER" "$pw" > "$CRED_FILE"
    chmod 600 "$CRED_FILE"
    echo
    echo "---- Zugangsdaten fuer den Web-Installer (werden nur jetzt angezeigt, auch in ${CRED_FILE}) ----"
    cat "$CRED_FILE"
    echo "---------------------------------------------------------------------------------------------"
  fi
  sql -e "GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX, LOCK TABLES, CREATE TEMPORARY TABLES, CREATE VIEW, SHOW VIEW, TRIGGER ON \`${DB_NAME}\`.* TO '${DB_USER}'@'${DB_HOST_GRANT}'"
  ok "Rechte nur auf Datenbank ${DB_NAME}"
fi

# ============================================================================
# Abschnitt 6: Cron fuer die Archivierung
# Matomo berechnet die Berichte per Cron (nicht beim Seitenaufruf). Stuendlich reicht fuer
# einen Verein. Ausgabe nach /dev/null; Fehler zeigt die Matomo-Oberflaeche (Systemcheck).
# ============================================================================
if ask 6 "Cron ${CRON_DIR}/matomo-archive"; then
  php_path="$(command -v "$PHP_BIN" || echo "/usr/bin/${PHP_BIN}")"
  cat > "${CRON_DIR}/matomo-archive" <<EOF
# Matomo: Berichte stuendlich archivieren (verwaltet von deploy/matomo-setup.sh)
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
5 * * * * ${MATOMO_USER} ${php_path} ${MATOMO_ROOT}/console core:archive --url=${MATOMO_BASE_URL} > /dev/null 2>&1
EOF
  chmod 644 "${CRON_DIR}/matomo-archive"
  ok "Cron installiert (jede Stunde, :05)"
fi

# ============================================================================
# Abschnitt 7: Vhost aktivieren
# Der Vhost (deploy/apache/training.conf) enthaelt den /matomo-Block. Es wird dieselbe Logik wie
# bei der TOME-Einrichtung verwendet (server-setup.sh Abschnitte 11+12): Vorlage ablegen, Backup,
# configtest, reload, automatischer Revert bei Fehler.
# ============================================================================
if ask 7 "Apache-Vhost aktivieren (server-setup.sh --only 11,12)"; then
  [[ -x "${SCRIPT_DIR}/server-setup.sh" || -f "${SCRIPT_DIR}/server-setup.sh" ]] || die "server-setup.sh nicht neben diesem Skript gefunden"
  bash "${SCRIPT_DIR}/server-setup.sh" --only 11,12
fi

# ============================================================================
# Abschnitt 8: Nach dem Web-Installer - Konfiguration haerten, Datenschutz
# Voraussetzung: der Installer unter /matomo/ ist durchgelaufen (config/config.ini.php existiert).
# Aenderungen in config.ini.php per "console config:set"; Datenschutz-Einstellungen (IP-
# Anonymisierung, Log-Loeschung, DNT) liegen in Matomos option-Tabelle und werden dort gesetzt.
# ============================================================================
if ask 8 "Matomo haerten und Datenschutz-Einstellungen setzen (nach dem Web-Installer)"; then
  cfg="${MATOMO_ROOT}/config/config.ini.php"
  [[ -f "$cfg" ]] || die "${cfg} fehlt - zuerst den Web-Installer unter ${MATOMO_BASE_URL} durchlaufen"
  chown "$MATOMO_USER:$MATOMO_USER" "$cfg"; chmod 640 "$cfg"; chmod 750 "${MATOMO_ROOT}/config"

  cset() { matomo_console config:set "$1" >/dev/null && ok "config: $1" || warn "config:set fehlgeschlagen: $1"; }
  cset "General.force_ssl=1"
  cset "General.assume_secure_protocol=1"
  cset "General.enable_trusted_host_check=1"
  cset "General.trusted_hosts=[\"${SITE_HOST}\"]"
  # Berichte nur per Cron berechnen, nie beim Seitenaufruf (weniger Last auf dem 2-GB-Server)
  cset "General.enable_browser_archiving_triggering=0"
  cset "General.browser_archiving_disabled_enforce=1"

  # Datenschutz-Einstellungen (Tabellenpraefix aus der Konfiguration)
  prefix="$(matomo_console config:get "database.tables_prefix" 2>/dev/null | tr -d '"[:space:]' || true)"
  [[ -n "$prefix" ]] || prefix="matomo_"
  setopt() { sql "$DB_NAME" -e "INSERT INTO \`${prefix}option\` (option_name, option_value, autoload) VALUES ('$1', '$2', 1) ON DUPLICATE KEY UPDATE option_value='$2'"; }
  setopt "PrivacyManager.ipAnonymizerEnabled" 1
  setopt "PrivacyManager.ipAddressMaskLength" 2
  setopt "PrivacyManager.useAnonymizedIpForVisitEnrichment" 1
  setopt "PrivacyManager.doNotTrackEnabled" 1
  setopt "delete_logs_enable" 1
  setopt "delete_logs_older_than" 90
  setopt "delete_logs_schedule_lowest_interval" 1
  ok "Datenschutz: IP-Anonymisierung (2 Bytes), Rohdaten-Loeschung nach 90 Tagen, Do-Not-Track beachten"

  # Besucherlog und Besucherprofil gehoeren zum Plugin Live
  if matomo_console plugin:deactivate Live >/dev/null 2>&1; then
    ok "Plugin Live (Besucherlog/Besucherprofil) deaktiviert"
  else
    warn "Plugin Live liess sich nicht deaktivieren - in der Oberflaeche pruefen (Einstellungen > Plattform > Plugins)"
  fi
  matomo_console cache:clear >/dev/null 2>&1 || true
  info "Noch in der Oberflaeche zu pruefen: Einstellungen > Datenschutz (siehe MATOMO_SETUP.md)"
fi

echo
ok "Fertig."

#!/usr/bin/env bash
# Smoke-Tests fuer Matomo unter /matomo/ gegen die Simulation (nach matomo-setup.sh Abschnitte 1-7).
#   deploy/prod-sim/smoke-matomo.sh [--installed]   (--installed: nach Web-Installer, prueft Tracking)
set -uo pipefail
PORT="${PORT:-18443}"
HOSTN="${SITE_HOST:-training.example.org}"
BASE="https://${HOSTN}:${PORT}"
C=(curl -sk -m 20 --resolve "${HOSTN}:${PORT}:127.0.0.1")
pass=0; fail=0
check() { if [[ "$2" == 0 ]]; then printf 'PASS  %-58s %s\n' "$1" "${3:-}"; pass=$((pass+1)); else printf 'FAIL  %-58s %s\n' "$1" "${3:-}"; fail=$((fail+1)); fi; }
code() { "${C[@]}" -o /dev/null -w '%{http_code}' "$@"; }
ctype() { "${C[@]}" -o /dev/null -w '%{content_type}' "$@"; }

c=$(code "$BASE/matomo/"); [[ "$c" == 200 || "$c" == 302 ]]; check "/matomo/ Installer/Login erreichbar" $? "HTTP $c"
c=$(code "$BASE/matomo/matomo.js"); t=$(ctype "$BASE/matomo/matomo.js"); [[ "$c" == 200 && "$t" == *javascript* ]]; check "/matomo/matomo.js" $? "HTTP $c $t"
c=$(code "$BASE/matomo/matomo.php"); [[ "$c" == 200 || "$c" == 204 ]]; check "/matomo/matomo.php" $? "HTTP $c"
for p in config/config.ini.php config/ tmp/ tmp/cache/ core/Version.php lang/en.json vendor/autoload.php CHANGELOG.md .htaccess plugins/CoreHome/CoreHome.php js/tracker.php misc/package/; do
  c=$(code "$BASE/matomo/$p"); [[ "$c" == 403 || "$c" == 404 ]]; check "/matomo/$p gesperrt" $? "HTTP $c"
done
c=$(code "$BASE/matomo/plugins/CoreHome/images/favicon.png"); [[ "$c" == 200 || "$c" == 404 ]]; check "Plugin-Bilder ausgeliefert (kein 403)" $? "HTTP $c"
c=$(code "$BASE/api/v1/health"); [[ "$c" == 200 ]]; check "TOME /api/v1/health" $? "HTTP $c"
body="$("${C[@]}" "$BASE/")"; [[ "$body" == *'id="app"'* ]]; check "TOME App-Shell /" $?
h="$("${C[@]}" -D - -o /dev/null "$BASE/")"
[[ "$h" == *"default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self' https://firebaseinstallations.googleapis.com https://fcmregistrations.googleapis.com https://fcm.googleapis.com;"* ]]; check "CSP auf / ohne Matomo-Aufweichung" $?
h="$("${C[@]}" -D - -o /dev/null "$BASE/matomo/")"; grep -qi "^content-security-policy:.*unsafe-eval" <<<"$h"; check "/matomo/ hat eigene CSP" $?
grep -qi "^x-frame-options: SAMEORIGIN" <<<"$h"; check "/matomo/ X-Frame-Options SAMEORIGIN" $?
c=$(code "$BASE/trainings/deep/link"); [[ "$c" == 200 ]]; check "SPA-Deep-Link weiter ok" $? "HTTP $c"
out="$("${C[@]}" "https://example.org:${PORT}/" --resolve "example.org:${PORT}:127.0.0.1")"; [[ "$out" == *OTHERSITE* ]]; check "Andere Seite unveraendert" $?

if [[ "${1:-}" == "--installed" ]]; then
  c=$(code "$BASE/matomo/index.php?module=Login"); [[ "$c" == 200 ]]; check "Login-Seite" $? "HTTP $c"
  c=$(code "$BASE/matomo/matomo.php?idsite=1&rec=1&action_name=smoke&url=https%3A%2F%2Ftraining.example.org%2Fsmoke"); [[ "$c" == 200 || "$c" == 204 ]]; check "Tracking-Request (matomo.php, idsite=1)" $? "HTTP $c"
fi
echo "---- ${pass} bestanden, ${fail} fehlgeschlagen"
[[ "$fail" == 0 ]]

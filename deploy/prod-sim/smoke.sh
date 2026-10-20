#!/usr/bin/env bash
# Smoke-Tests gegen die Simulation (oder gegen Produktion: BASE und RESOLVE anpassen).
#   deploy/prod-sim/smoke.sh
# Standard: https://training.example.org:18443 -> 127.0.0.1 (self-signed, -k)
set -uo pipefail
PORT="${PORT:-18443}"
HTTP_PORT="${HTTP_PORT:-18080}"
HOSTN="${SITE_HOST:-training.example.org}"
BASE="https://${HOSTN}:${PORT}"
C=(curl -sk -m 15 --resolve "${HOSTN}:${PORT}:127.0.0.1" --resolve "example.org:${PORT}:127.0.0.1" --resolve "${HOSTN}:${HTTP_PORT}:127.0.0.1")
pass=0; fail=0
check() { # check NAME BEDINGUNG(0=ok) [DETAIL]
  if [[ "$2" == 0 ]]; then printf 'PASS  %-58s %s\n' "$1" "${3:-}"; pass=$((pass+1)); else printf 'FAIL  %-58s %s\n' "$1" "${3:-}"; fail=$((fail+1)); fi
}
code() { "${C[@]}" -o /dev/null -w '%{http_code}' "$@"; }
hdrs() { "${C[@]}" -D - -o /dev/null "$@"; }

body="$("${C[@]}" "$BASE/")"; c=$(code "$BASE/")
[[ "$c" == 200 && "$body" == *'id="app"'* ]]; check "GET / liefert App-Shell" $? "HTTP $c"

out="$("${C[@]}" "$BASE/api/v1/health")"
[[ "$out" == *'"status":"ok"'* ]]; check "GET /api/v1/health" $? "$out"

out="$("${C[@]}" "$BASE/api/v1/training/upcoming")"; c=$(code "$BASE/api/v1/training/upcoming")
[[ "$c" == 200 && "$out" == '{"data":'* ]]; check "GET /api/v1/training/upcoming (JSON)" $? "HTTP $c, ${#out} Bytes"

h="$(hdrs "$BASE/api/storage/profile_images/old1.png")"
[[ "$h" == *"200"* && "$h" == *"X-Content-Type-Options: nosniff"* ]]; check "Bild unter /api/storage ausgeliefert (+nosniff)" $? "$(head -1 <<<"$h" | tr -d '\r')"

c=$(code "$BASE/api/storage/profile_images/evil.php"); out="$("${C[@]}" "$BASE/api/storage/profile_images/evil.php")"
[[ "$c" == 403 && "$out" != *EXECUTED* ]]; check ".php in storage wird nicht ausgefuehrt" $? "HTTP $c"

c=$(code "$BASE/api/storage/profile_images/"); out="$("${C[@]}" "$BASE/api/storage/profile_images/")"
[[ "$out" != *old1.png* ]]; check "Kein Directory-Listing in /api/storage" $? "HTTP $c"

c=$(code "$BASE/api/api/x"); out="$("${C[@]}" "$BASE/api/api/x")"; h="$(hdrs "$BASE/api/api/x")"
[[ "$c" == 410 && "$out" == *'Diese App-Version ist veraltet. Bitte Seite neu laden.'* && "$h" == *"application/json"* ]]; check "/api/api/x -> 410 JSON" $? "HTTP $c $out"

h="$(hdrs "$BASE/")"
miss=""
for n in Strict-Transport-Security X-Content-Type-Options Referrer-Policy X-Frame-Options Permissions-Policy Content-Security-Policy; do
  grep -qi "^$n:" <<<"$h" || miss="$miss $n"
done
[[ -z "$miss" ]]; check "Sicherheits-Header auf / vorhanden" $? "fehlend:${miss:- keine}"

body="$("${C[@]}" "$BASE/trainings/some/deep/link")"; c=$(code "$BASE/trainings/some/deep/link")
[[ "$c" == 200 && "$body" == *'id="app"'* ]]; check "SPA-Deep-Link -> index.html" $? "HTTP $c"

c=$(code "$BASE/assets/gibt-es-nicht.js")
[[ "$c" == 404 ]]; check "Fehlendes Asset -> 404 (nicht index.html)" $? "HTTP $c"

h="$(hdrs "$BASE/index.html")"; grep -qi "^cache-control: no-cache" <<<"$h"; check "index.html: Cache-Control no-cache" $?
asset="$(grep -o '/assets/index-[^"]*\.js' <<<"$("${C[@]}" "$BASE/")" | head -1)"
h="$(hdrs "$BASE$asset")"; grep -qi "^cache-control: public, max-age=31536000, immutable" <<<"$h"; check "Hash-Asset: immutable ($asset)" $?
h="$(hdrs "$BASE/firebase-messaging-sw.js")"; grep -qi "^cache-control: no-cache" <<<"$h"; check "firebase-messaging-sw.js: no-cache" $?

h="$(hdrs "http://${HOSTN}:${HTTP_PORT}/foo")"
[[ "$h" == *"301"* && "$h" == *"https://${HOSTN}/foo"* ]]; check "HTTP -> HTTPS Redirect" $?

out="$("${C[@]}" "https://example.org:${PORT}/")"
[[ "$out" == *OTHERSITE* ]]; check "Andere Seite unveraendert (example.org)" $?

# Sanctum SPA-Auth: CSRF-Cookie holen, einloggen (Session-Cookie), auth/me
jar="$(mktemp)"
"${C[@]}" -c "$jar" -b "$jar" -H "Referer: ${BASE}/" "$BASE/api/sanctum/csrf-cookie" -o /dev/null
xsrf="$(awk '$6=="XSRF-TOKEN"{print $7}' "$jar" | python3 -c 'import sys,urllib.parse;print(urllib.parse.unquote(sys.stdin.read().strip()))')"
"${C[@]}" -c "$jar" -b "$jar" -X POST -H 'Content-Type: application/json' -H "X-XSRF-TOKEN: $xsrf" -H "Referer: ${BASE}/" -d '{"email":"admin@tome.local","password":"password"}' "$BASE/api/v1/auth/login" >/dev/null
c=$(code -b "$jar" -H "Referer: ${BASE}/" "$BASE/api/v1/auth/me"); rm -f "$jar"; [[ "$c" == 200 ]]; check "CSRF-Cookie + Login + Session-Auth (auth/me)" $? "HTTP $c"

echo "---- ${pass} bestanden, ${fail} fehlgeschlagen"
[[ "$fail" == 0 ]]

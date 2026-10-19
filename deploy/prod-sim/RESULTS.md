# prod-sim Ergebnisse

Simulation des Produktionsservers komplett lokal: Container `tome-sim-vm` (Ubuntu 24.04, Apache 2.4.58
mit mpm_event + proxy_fcgi, PHP 8.4.26-FPM aus dem ondrej-PPA, selbstsigniertes Zertifikat, ein
WordPress-Platzhalter-Vhost, der alte Vhost `training.conf` samt Alt-Upload-Dateien) und
`tome-sim-mysql` (MySQL 8.0, frische Datenbank, per Migration + Seeder befuellt, keine Produktionsdaten).
Es wurde nie eine Verbindung zum echten Server aufgebaut.

Ablauf (dieselben Skripte wie in Produktion; `TOME_TRANSPORT=docker` ersetzt nur ssh/rsync-ssh durch `docker exec`):

```bash
cd deploy/prod-sim && docker-compose up -d --build
docker exec -e MYSQL_ADMIN_CMD="mysql -h mysql -uroot -proot" -e DB_HOST_GRANT=% -e DB_HOST_APP=mysql \
  tome-sim-vm /opt/tome-deploy/server-setup.sh -y          # Abschnitte 1-11
export TOME_TRANSPORT=docker TOME_CONTAINER=tome-sim-vm TOME_HEALTH_PORT=18443 TOME_CURL_OPTS=-k
deploy/deploy.sh                                            # Deploy
docker exec tome-sim-vm /opt/tome-deploy/server-setup.sh --only 12   # Vhost-Wechsel
deploy/prod-sim/smoke.sh
```

## Ergebnisse

| Schritt | Ergebnis |
|---|---|
| server-setup.sh Abschnitte 1-11 | OK. Zweiter Lauf: `.env` bitidentisch (md5 gleich), keine doppelten Eintraege (idempotent) |
| Abschnitt 12 (Vhost-Wechsel) | OK: Backup, configtest, graceful reload. Wurde zweimal ausgefuehrt (zweites Mal nach Vhost-Fix) |
| Deploy #1 (Erstinstallation) | OK nach drei Korrekturen, die die Simulation aufgedeckt hat (siehe unten). Health-Check schlug erwartungsgemaess fehl, solange der alte Vhost aktiv war; ohne Vorgaenger-Release nur Warnung. Nach Abschnitt 12 sofort gruen |
| Smoke-Tests | 16/16 bestanden (Ausgabe unten) |
| Deploy #2 (frischer Voll-Build mit `npm ci`, composer, neue Commit-ID) | OK, Wechsel `...165307-0bb8e3b` -> `...165828-1b1c2f6`, Health-Check gruen, pre-deploy-Dump angelegt |
| Erzwungener Fehl-Deploy (Health-Route im Release kaputt gemacht, `--skip-build`) | Health-Check 6x HTTP 500, **automatischer Rollback** auf `...165828-1b1c2f6`, FPM neu geladen, Exit-Code 1, Restore-Befehl fuer den Dump ausgegeben |
| `rollback.sh` (vorheriges Release, danach benanntes Release) | OK, jeweils mit Health-Check; `--list` zeigt aktives Release |
| Backup-Skript | OK: `db-*.sql.gz`, `uploads-*.tar.gz`, `config-*.tar.gz` (alle 600 root) |
| sudoers | `sudo -n systemctl reload php8.4-fpm` geht; `reload apache2` und `ls` verlangen Passwort |
| Browser-Test CSP (headless Chromium, Login als Admin, Seiten /, login, trainings, info, profile) | 0 CSP-Verletzungen, alle API-Aufrufe 200 |

Korrekturen aus der Simulation: (1) `.env`-Werte mit Leerzeichen brauchen Anfuehrungszeichen
(`MAIL_FROM_NAME`), sonst bricht Laravel ab; (2) `route:cache` erzeugt wegen einer Closure-Route in
`server/routes/web.php` eine Datei, die beim Laden fehlschlaegt (`Closure::__set_state()`), deploy.sh faellt
dann auf nicht gecachte Routen zurueck; (3) ein leerer Apache-`Define` ist "undefiniert", deshalb
Platzhalter `'self'` fuer `TOME_MATOMO_ORIGIN`.

## Smoke-Tests (`smoke.sh`)

```
PASS  GET / liefert App-Shell                                    HTTP 200
PASS  GET /api/v1/health                                         {"status":"ok"}
PASS  GET /api/v1/training/upcoming (JSON)                       HTTP 200, 4855 Bytes
PASS  Bild unter /api/storage ausgeliefert (+nosniff)            HTTP/1.1 200 OK
PASS  .php in storage wird nicht ausgefuehrt                     HTTP 403
PASS  Kein Directory-Listing in /api/storage                     HTTP 403
PASS  /api/api/x -> 410 JSON                                     HTTP 410 {"message":"Diese App-Version ist veraltet. Bitte Seite neu laden."}
PASS  Sicherheits-Header auf / vorhanden                         fehlend: keine
PASS  SPA-Deep-Link -> index.html                                HTTP 200
PASS  Fehlendes Asset -> 404 (nicht index.html)                  HTTP 404
PASS  index.html: Cache-Control no-cache
PASS  Hash-Asset: immutable (/assets/index-B2QtsR6t.js)
PASS  firebase-messaging-sw.js: no-cache
PASS  HTTP -> HTTPS Redirect
PASS  WordPress-Vhost unveraendert (ssc-landau.de)
PASS  Login + Cookie-Auth (auth/me)                              HTTP 200
---- 16 bestanden, 0 fehlgeschlagen
```

Zusaetzlich manuell: Login-Token als `Authorization: Bearer` an `/api/v1/auth/me` -> 200
(Header kommt ueber `mod_proxy_fcgi` bei PHP an).

## Auszug erzwungener Fehl-Deploy

```
WARN Versuch 6: /api/v1/health lieferte HTTP 500: <!DOCTYPE html> ...
FEHLER Health-Check nach dem Umschalten fehlgeschlagen
WARN Schalte zurueck auf 20261019-165828-1b1c2f6
 OK Zurueckgeschaltet auf 20261019-165828-1b1c2f6
WARN Die Migrationen wurden ausgefuehrt und NICHT zurueckgedreht. ... DB wiederherstellen:
  Auf dem Server (als root): gunzip < /var/backups/tome/pre-deploy-20261019-165906-09fca44.sql.gz | mysql tome
```

## Grenzen der Simulation

- Kein systemd: `systemctl reload` ist ein kleines Shim (USR2 an den FPM-Master bzw. `apachectl graceful`).
- MySQL laeuft in einem eigenen Container, daher Benutzer `tome_app`@`%` statt `@localhost`.
- Kein echtes Let's-Encrypt-Zertifikat, kein ufw, kein SSH-Transport (der ssh-Pfad in `lib.sh` ist
  derselbe Code mit `ssh`/`rsync -e ssh`, aber hier nicht ausgefuehrt).
- Der Dump beim Deploy laeuft als `tome_app` ohne `TRIGGER`/`SHOW VIEW`-Recht (`--skip-triggers`); die
  Migrationen legen keine Trigger/Views an.

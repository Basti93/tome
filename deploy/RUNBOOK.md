# T.O.M.E. Runbook (Deployment und Umstieg)

Server: IONOS-VM, Ubuntu 24.04, Apache 2.4, PHP 8.4-FPM, MySQL 8, teilt sich die Maschine mit der
WordPress-Seite (die nie angefasst wird). Alle Skripte wurden vorab lokal gegen eine Simulation getestet
(`deploy/prod-sim/RESULTS.md`).

## Ueberblick

| Was | Wo |
|---|---|
| Neue App | `/var/www/tome-app/current` (Symlink auf `releases/<zeit>-<sha>`) |
| Gemeinsame Daten | `/var/www/tome-app/shared/` (`.env`, `storage/` mit Uploads und Logs, Firebase-JSON) |
| PHP-Pool | `tome` (Benutzer `tome`), Socket `/run/php/php8.4-fpm-tome.sock` |
| Datenbank | `tome`, neuer Benutzer `tome_app` (nur Rechte auf diese DB) |
| Backups | `/var/backups/tome` (taeglich 03:17, 14 Tage) |
| Alte App | `/var/www/tome` und der alte Vhost bleiben unberuehrt |

URLs: `/` = App, `/api/v1/*` = API, `/api/storage/*` = Uploads, `/api/api/*` = 410 (alte App-Version).

## 1. Einrichtung (einmalig, vor dem Cutover)

1. Lokal `deploy/.env.production` anlegen (Vorlage `deploy/.env.production.example`; Firebase-Webwerte, VAPID-Key).
2. Deploy-Verzeichnis auf den Server kopieren:
   `rsync -a deploy/ sbinder@SERVER:/tmp/tome-deploy/`
3. Auf dem Server: `sudo /tmp/tome-deploy/server-setup.sh` und die Abschnitte der Reihe nach bestaetigen:
   1 Voraussetzungen, 2 Benutzer `tome`, 3 Verzeichnisse, 4 PHP-Pool, 5 sudoers, 6 MySQL-Benutzer,
   7 `.env`, 8 Uploads kopieren, 9 Scheduler-Cron, 10 Backups, 11 neuen Vhost ablegen (inaktiv).
   **Abschnitt 12 (Vhost umschalten) noch nicht.** Mit `--only 8` laesst sich ein Abschnitt wiederholen.
4. SSH-Schluessel fuer den Deploy eintragen (am Mac `ssh-keygen -t ed25519` falls noetig), auf dem Server als root:
   `echo 'ssh-ed25519 AAAA... mac' >> /home/tome/.ssh/authorized_keys`. Test: `ssh tome@SERVER true`.
5. `.env` pruefen: `sudo nano /var/www/tome-app/shared/.env`. Die Platzhalter `AENDERN_*` (Mail) ersetzen,
   falls die Werte nicht aus der alten App uebernommen werden konnten.
6. Firebase (siehe Abschnitt 5 unten).
7. Test des Backups: `sudo /usr/local/sbin/tome-backup`.

## 2. CUTOVER

Geplantes Fenster, z. B. abends ohne Training (ca. 30 Minuten).

**Vorher**
- Trainer und Eltern informieren (Datum, kurze Unterbrechung, neue Version, ggf. neu anmelden: alle Logins werden
  ungueltig, weil es ein neues `JWT_SECRET` gibt).
- Einrichtung (Abschnitt 1) ist komplett, ein Probelauf `deploy/deploy.sh --host SERVER --dry-run` funktioniert.

**Im Fenster**
1. **Letztes Backup** (alte App):
   - `sudo mysqldump --single-transaction --routines tome | gzip > /root/tome-final-$(date +%F).sql.gz`
   - `sudo tar -czf /root/tome-old-app-$(date +%F).tar.gz -C /var/www tome`
   - Beide Dateien zusaetzlich auf den Mac kopieren (`scp`).
2. **Uploads erneut kopieren**, um spaete Uploads zu erfassen: `sudo /tmp/tome-deploy/server-setup.sh --only 8`
3. **Deploy** am Mac: `deploy/deploy.sh --host SERVER`.
   Das Skript fuehrt die Migrationen auf der echten DB aus und macht vorher einen Dump
   (`/var/backups/tome/pre-deploy-*.sql.gz`). Beim ersten Deploy meldet der Health-Check eine Warnung, solange der
   alte Vhost aktiv ist; das ist erwartet.
   Ab hier ist die alte App durch die Schemaaenderungen u. U. nicht mehr lauffaehig.
4. **Vhost umschalten**: `sudo /tmp/tome-deploy/server-setup.sh --only 12`.
   Das Skript sichert den alten Vhost nach `/root/tome-vhost-backup/`, prueft die Konfiguration, laedt Apache neu
   und stellt bei einem Fehler selbst den alten Zustand wieder her.
5. **Smoke-Tests** (Browser und Kommandozeile):
   - `https://training.ssc-landau.de/` laedt die neue App, Login funktioniert, ein Training oeffnen, Check-in/out,
     ein Profilbild ist sichtbar, Infos-Seite mit Dokumenten.
   - `curl -s https://training.ssc-landau.de/api/v1/health` liefert `{"status":"ok"}`.
   - `curl -i https://training.ssc-landau.de/api/api/x` liefert 410.
   - WordPress `https://ssc-landau.de` laedt unveraendert.
   - Nur zur Orientierung: `deploy/prod-sim/smoke.sh` prueft dieselben Punkte automatisch gegen die Simulation
     (Muster fuer eigene Curl-Befehle; Test-Login und `127.0.0.1`-Aufloesung gelten nur dort).
6. **Retention nur melden** (nichts loeschen):
   `sudo -u tome php8.4 /var/www/tome-app/current/server/artisan tome:retention --dry-run`
   Die Zahlen notieren. **Das echte Loeschen (ohne `--dry-run`, auch der monatliche Scheduler-Lauf am 1. um 03:00)
   erst nach ausdruecklicher Freigabe**, sonst vorher den Eintrag in `routes/console.php` pruefen.
7. **Push pruefen**: Als Trainer in der App Benachrichtigungen aktivieren (Profil), eine Testbenachrichtigung
   ausloesen (Command `notification:test`/`TestNotification` in `server/app/Console/Commands`, vorher dessen Signatur lesen)
   und das Eintreffen auf dem Handy bestaetigen. Logs: `/var/www/tome-app/shared/storage/logs/`.

**Nach dem Fenster**: Die Anwender informieren, dass die neue Version online ist.

## 3. Alltags-Deploy und Rollback der neuen App

- Deploy: `deploy/deploy.sh --host SERVER` (Arbeitsbaum muss sauber sein; Optionen `--dry-run`, `--skip-build`).
  Die letzten 5 Releases bleiben liegen. Bei einem fehlgeschlagenen Health-Check wird automatisch auf das
  vorherige Release zurueckgeschaltet.
- Manuell zurueck: `deploy/rollback.sh --host SERVER` (vorheriges Release) oder `... RELEASE`; Liste mit `--list`.
- **Migrationen werden nicht zurueckgedreht.** Bei Datenproblemen den Dump vor dem Deploy einspielen:
  `gunzip < /var/backups/tome/pre-deploy-<release>.sql.gz | sudo mysql tome` (verliert alle Aenderungen seit dem Deploy).
- Nach jedem Deploy wird der FPM-Dienst neu geladen (OPcache prueft keine Dateien). Das betrifft kurz auch die WordPress-
  Worker (graceful, keine Unterbrechung laufender Anfragen).

## 4. Rollback auf die ALTE App

Die alte App bleibt **zwei Wochen** unveraendert liegen (`/var/www/tome`, alter Vhost-Backup in
`/root/tome-vhost-backup/`, das Final-Backup aus dem Cutover).

1. Alten Vhost wiederherstellen: `sudo cp -a /root/tome-vhost-backup/training.conf.<zeit> /etc/apache2/sites-available/training.conf && sudo apache2ctl configtest && sudo systemctl reload apache2`
   (der Pfad des Ziels steht in der Ausgabe von Abschnitt 12).
2. Datenbank auf den Stand vor dem Cutover zuruecksetzen, weil die Migrationen das Schema veraendert haben:
   `gunzip < /root/tome-final-<datum>.sql.gz | sudo mysql tome`. Eintraege, die seit dem Cutover in der neuen App
   entstanden sind, gehen dabei verloren (vorher Dump der neuen DB ziehen: `sudo /usr/local/sbin/tome-backup`).
3. Uploads: neue Uploads aus `/var/www/tome-app/shared/storage/app/public` bei Bedarf nach
   `/var/www/tome/api/storage/app/public` kopieren.

## 5. Firebase (Push)

1. Firebase Console > Projekteinstellungen > Dienstkonten > "Neuen privaten Schluessel generieren" (JSON).
2. Auf den Server: `scp key.json sbinder@SERVER:/tmp/` und dann
   `sudo install -o tome -g tome -m 600 /tmp/key.json /var/www/tome-app/shared/firebase-service-account.json && rm /tmp/key.json`.
   Der Pfad steht bereits als `FIREBASE_CREDENTIALS_PATH` in der `.env`.
3. Firebase Console > Cloud Messaging > Web-Push-Zertifikate: oeffentlichen **VAPID-Schluessel** nach
   `deploy/.env.production` (`VITE_FIREBASE_VAPID_KEY`) und die uebrigen `VITE_FIREBASE_*` Werte aus den Web-App-Einstellungen
   (Frontend-Config) eintragen. Das Frontend muss danach neu gebaut und deployt werden (ist in `deploy.sh` enthalten).
4. Der Push-Service-Worker ist `/firebase-messaging-sw.js` (Scope `/`); er wird bei Änderungen nie gecacht.

Hinweis CSP: Erlaubt sind `firebaseinstallations.googleapis.com`, `fcmregistrations.googleapis.com` und
`fcm.googleapis.com`. Wird Matomo aktiviert, im Vhost `Define TOME_MATOMO_ORIGIN "https://matomo.example.org"` setzen
(und `VITE_MATOMO_*` in `.env.production`), sonst blockiert der Browser das Tracking.

## 6. Bekannte Effekte nach dem Umstieg

- Anwender mit der **alten App als PWA** (Home-Bildschirm) sehen u. U. noch die alte Version, bis sich deren Service Worker
  aktualisiert. Ihre API-Aufrufe bekommen die Meldung "Diese App-Version ist veraltet. Bitte Seite neu laden." (HTTP 410).
  Abhilfe, falls noetig: App vom Home-Bildschirm entfernen bzw. die Website-Daten loeschen und neu oeffnen.
- Alle muessen sich einmal neu anmelden (neues JWT-Geheimnis).
- Neue Rollenwahrnehmung:
  - **Trainer:** neues Aussehen (Vuetify 4), gleiche Grundablaeufe. Push-Benachrichtigungen sollten neu aktiviert
    werden (Profil), weil sich der Push-Service-Worker geaendert hat; das beim Push-Test pruefen.
  - **Admins:** neues Design der Verwaltungsseiten; neu sind E-Mail-Verifizierung beim Login, Konto-Sperre nach
    mehreren falschen Passwoertern und die Retention (loescht nach Freigabe inaktive Konten und alte Daten; die Zahlen
    zeigt `tome:retention --dry-run`).
  - **Kinder / Teilnehmer ohne Konto:** Check-in/out ohne Anmeldung funktioniert weiter (Auswahl "Wer bist du?").
    Profilbilder und Info-Dokumente liegen unter `/api/storage`.

## 7. Aufraeumen (nach zwei stabilen Wochen)

- Alte App entfernen: `/var/www/tome` (vorher Tarball behalten), alten Vhost-Backup und das Final-Backup archivieren.
- Alten FPM-/Datenbank-Zugang der alten App (`ssc_u271ss834`) pruefen und, falls nur sie ihn nutzte, entziehen.
- `/root/tome-vhost-backup/` und die Eintraege `*.new` in `sites-available` aufraeumen.
- Pruefen, ob die 410-Regel fuer `/api/api` noch gebraucht wird (Zugriffslog `tome-access.log`).

## Anhang: Fehlersuche

- Logs: Laravel `/var/www/tome-app/shared/storage/logs/laravel-*.log`; PHP-FPM `/var/log/php8.4-fpm-tome.log`;
  Apache `/var/log/apache2/tome-error.log`.
- Health: `curl -s https://training.ssc-landau.de/api/v1/health`.
- Scheduler laeuft? `/var/www/tome-app/shared/storage/logs/com_*.log`, `cat /etc/cron.d/tome-scheduler`.
- Nach direkten Aenderungen an Code oder `.env` auf dem Server: `sudo systemctl reload php8.4-fpm` (OPcache) und
  `php artisan config:cache` im aktuellen Release.

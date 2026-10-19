# Matomo (Web-Analyse)

Selbst gehostetes Matomo unter https://<SITE_HOST>/matomo/ (gleiche Origin wie die App, kein eigener
Host/Zertifikat). Die App sendet nur anonyme Ereignisse ohne Cookies, ohne Namen, E-Mail-Adressen oder IDs
(`app/src/services/analytics.ts`, Do-Not-Track wird beachtet). Matomo laeuft als Benutzer `matomo` mit eigenem
PHP-FPM-Pool und eigener Datenbank; andere Seiten auf dem Server und TOME bleiben unberuehrt.

## Einrichtung (einmalig, auf dem Server als root)

Das ganze `deploy/`-Verzeichnis auf den Server kopieren (wie fuer `server-setup.sh`), **inklusive `deploy/site.env`**
(Host, Zertifikat, Mail-Absender; nicht im Git, Vorlage `deploy/site.env.example`; die Skripte brechen ohne diese Datei ab), dann:

1. `sudo ./matomo-setup.sh` und die Abschnitte 1 bis 7 bestaetigen (Pakete, Benutzer, Download mit
   GPG-Signaturpruefung, FPM-Pool, Datenbank, Cron, Vhost). Abschnitt 5 zeigt die DB-Zugangsdaten **einmal** an
   (auch in `/root/matomo-db.txt`, 600). Abschnitt 7 aktiviert den Vhost mit Backup, configtest und Auto-Revert.
2. Web-Installer: https://<SITE_HOST>/matomo/ . Datenbankserver `localhost`, Benutzer/Passwort/DB aus
   `/root/matomo-db.txt`, Praefix `matomo_`. Super-User mit starkem Passwort anlegen. Website: Name `TOME`,
   URL `https://<SITE_HOST>` (wird Seiten-ID 1). Am Ende "Do Not Track beachten" und "IP anonymisieren" aktiv lassen.
3. `sudo ./matomo-setup.sh --only 8`: setzt `force_ssl`, `assume_secure_protocol`, Trusted Host, sperrt
   `config.ini.php` (640), Archivierung nur per Cron, IP-Anonymisierung (2 Bytes), Rohdaten nach 90 Tagen loeschen,
   Do-Not-Track, und deaktiviert das Plugin Live (Besucherlog/-profil).
4. In `deploy/.env.production` die Matomo-Zeilen setzen (Vorlage: `.env.production.example`): `VITE_MATOMO_ENABLED=true`,
   `VITE_MATOMO_URL=/matomo/`, `VITE_MATOMO_SITE_ID=1`. Danach `deploy/deploy.sh`.

## Datenschutz-Checkliste (Oberflaeche: Administration > Datenschutz)

- IP-Anonymisierung aktiv, 2 Bytes, "anonymisierte IP fuer Besucher-Anreicherung verwenden" an.
- Alte Rohdaten loeschen aktiv, 90 Tage (Berichte bleiben erhalten). Do Not Track wird beachtet.
- Nicht ueber das Skript setzbar, bitte einmal pruefen: unter Websites > Verwalten "Cookies deaktiviert" sehen
  (die App sendet `disableCookies`), unter Administration > Allgemeine Einstellungen "Berichte per Browser
  archivieren" aus, E-Mail-Adresse fuer Update-Benachrichtigungen eintragen, Zwei-Faktor-Login fuer den Super-User einschalten.
- Datenschutzhinweis: im Info-Bereich der App steht "Anonyme Nutzungsstatistik ohne Cookies (Matomo)".

## Update

Matomo zeigt neue Versionen in der Oberflaeche an und schickt eine E-Mail an den Super-User. Update: Banner
"Jetzt aktualisieren" (Ein-Klick-Update; die Dateien gehoeren `matomo`, daher funktioniert es). Alternativ
`sudo ./matomo-setup.sh --only 3` (laedt neu, prueft die Signatur, behaelt config/tmp/misc/user, fuehrt `core:update` aus).

## Betrieb

- Archivierung: `/etc/cron.d/matomo-archive`, stuendlich, Ausgabe nach /dev/null. Pruefen: Administration > Diagnose > Systemcheck.
- Backup: die Datenbank `matomo` steckt im taeglichen "alle Datenbanken"-Dump von root. Die Dateien sind ersetzbar,
  nur `config/config.ini.php` (enthaelt das DB-Passwort) sichern.
- Rechte: `config/` ist fuer andere Benutzer nicht lesbar; Apache fuehrt PHP nur fuer `index.php`, `matomo.php`,
  `piwik.php`, `js/index.php` aus und sperrt `config`, `tmp`, `core`, `lang`, `vendor` (siehe `deploy/apache/training.conf`).
  Die Matomo-Oberflaeche hat eine eigene, lockerere CSP (braucht Inline-Skripte/eval); die strenge CSP der App bleibt unveraendert.
- Probleme: `/var/log/php8.4-fpm-matomo.log`, `/var/log/apache2/tome-error.log`, `/var/www/matomo/tmp/logs/`.
- Lokal (Docker-Entwicklung): `docker-compose` startet einen eigenen Matomo auf http://localhost:8002 (unabhaengig von dieser Anleitung).
- Test ohne Server: `deploy/prod-sim` (`smoke-matomo.sh`, `matomo-sim-install.py`).

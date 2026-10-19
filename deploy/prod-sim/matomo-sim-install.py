#!/usr/bin/env python3
"""Fuehrt den Matomo-Web-Installer in der prod-sim automatisch durch (nur fuer die Simulation).

  python3 deploy/prod-sim/matomo-sim-install.py <db-passwort>

Spricht https://training.ssc-landau.de:18443/matomo/ (-> 127.0.0.1, self-signed). Legt den Super-User
admin / Sim-Passwort-123 und die Website "TOME" (https://training.ssc-landau.de) an.
"""
import http.cookiejar, re, socket, ssl, sys, urllib.parse, urllib.request

HOST, PORT = "training.ssc-landau.de", 18443
real = socket.getaddrinfo
socket.getaddrinfo = lambda h, *a, **k: real("127.0.0.1" if h == HOST else h, *a, **k)
ctx = ssl.create_default_context(); ctx.check_hostname = False; ctx.verify_mode = ssl.CERT_NONE
op = urllib.request.build_opener(urllib.request.HTTPSHandler(context=ctx),
                                 urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
BASE = f"https://{HOST}:{PORT}/matomo/"


def get(step):
    return op.open(f"{BASE}index.php?action={step}&module=Installation").read().decode()


def post(step, data):
    body = urllib.parse.urlencode(data).encode()
    return op.open(f"{BASE}index.php?action={step}&module=Installation", body).read().decode()


def nonce(html):
    m = re.search(r'name="form_nonce"[^>]*value="([^"]*)"', html) or re.search(r'value="([^"]*)"[^>]*name="form_nonce"', html)
    return m.group(1) if m else ""


def main(db_pw):
    html = get("databaseSetup")
    html = post("databaseSetup", {"host": "mysql", "username": "matomo", "password": db_pw, "dbname": "matomo",
                                  "tables_prefix": "matomo_", "adapter": "PDO\\MYSQL", "schema": "Mysql",
                                  "form_nonce": nonce(html), "submit": "Next"})
    get("tablesCreation")
    html = get("setupSuperUser")
    post("setupSuperUser", {"login": "admin", "password": "Sim-Passwort-123", "password_bis": "Sim-Passwort-123",
                            "email": "admin@example.org", "form_nonce": nonce(html), "submit": "Next"})
    html = get("firstWebsiteSetup")
    post("firstWebsiteSetup", {"siteName": "TOME", "url": "https://training.ssc-landau.de", "timezone": "Europe/Berlin",
                               "ecommerce": "0", "form_nonce": nonce(html), "submit": "Next"})
    html = get("finished")
    post("finished", {"setup_geoip2": "0", "do_not_track": "1", "anonymise_ip": "1",
                      "form_nonce": nonce(html), "submit": "Continue to Matomo"})
    print("Installer durchgelaufen")


if __name__ == "__main__":
    main(sys.argv[1])

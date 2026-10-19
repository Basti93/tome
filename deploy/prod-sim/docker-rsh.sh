#!/bin/sh
# rsync -e Adapter: fuehrt den Remote-Befehl per docker exec im Simulations-Container aus
while [ "$1" = "-l" ]; do shift 2; done
host="$1"; shift
exec docker exec -i -u tome "$host" "$@"

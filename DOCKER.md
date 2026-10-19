# Docker development setup

Local only. Production runs without Docker (see "Production / Betrieb" in `CLAUDE.md`).

`docker-compose up -d` starts nginx (http://localhost:8000), Laravel (PHP-FPM), MySQL and phpMyAdmin (http://localhost:8001, user/password `tome`).

```bash
docker-compose up -d
make docker-init                   # migrate + seed (docker/scripts/init-db.sh)
cd app && npm install && npm run build:serve   # build the SPA into server/public
```

Open http://localhost:8000. Backend code is mounted as a volume, frontend changes need `npm run build:serve` again (or use `npm run dev` on port 5173).

## Useful commands

```bash
docker-compose logs -f laravel
docker-compose exec laravel php artisan migrate
docker-compose exec laravel php artisan tinker
docker-compose exec mysql mysql -u tome -ptome tome
make db-backup                     # dump to a SQL file (gitignored)
docker-compose down                # stop, keep data
docker-compose down -v             # stop and delete the database
```

The containers read `server/.env` (`DB_HOST=mysql`). If a `tome-mysql` container already exists from another compose project, attach it with `docker network connect --alias mysql tome_tome-network tome-mysql` and start the rest with `docker-compose up -d --no-deps laravel nginx`.

Tests need their own database: `make test-db` (see `CLAUDE.md`, "Checks & Tests").

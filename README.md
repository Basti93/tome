# T.O.M.E. - Training Organization Made Easy

PWA to organize training sessions for a sport club. Trainers plan trainings and groups; members check in and out without having to register an account.

<!-- TODO: add new screenshots (docs/images was removed because the old ones showed real names) -->

## Stack

- **Frontend** (`/app`): Vue 3.5, Vite 8, Vuetify 4, Pinia 4, TypeScript
- **Backend** (`/server`): Laravel 13 (PHP 8.4), REST API at `/api/v1`, Sanctum SPA authentication (session cookie + CSRF), Spatie permissions, Firebase Cloud Messaging
- **Production**: Apache + PHP-FPM + MySQL, deployed with the scripts in `deploy/`

## Local development

Docker (nginx + Laravel + MySQL, see `DOCKER.md`):

```bash
docker-compose up -d
make docker-init            # migrate + seed
cd app && npm install && npm run build:serve
# http://localhost:8000
```

Or locally with hot reload:

```bash
cd server && composer install && php artisan serve    # http://localhost:8000
cd app && npm install && npm run dev                  # http://localhost:5173
```

Copy `app/.env.local.example` to `app/.env.local` and `server/.env.example` to `server/.env` first.

## Checks

```bash
make check      # backend: pint, phpstan, phpunit; frontend: lint, type-check, vitest, build
```

`make check SKIP_TEST_DB=1` if a MySQL test database is already running.

## Deploy

See the "Production / Betrieb" section in `CLAUDE.md`.

# CLAUDE.md

T.O.M.E. (Training Organization Made Easy): training planning, check-in and evaluation for a sports club.

## Layout
- `app/`: Vue 3.5 + TypeScript + Vuetify 4 + Pinia PWA (Vite, Vitest). Pages in `src/pages`, reusable parts in `src/components`.
- `server/`: Laravel 13 / PHP 8.4 REST API at `/api/v1` (JWT in an HttpOnly cookie, spatie permissions, FCM push).
- `deploy/`: deploy, rollback, server setup and prod simulation scripts; Apache vhost template.
- Setup and commands: see the `Makefile` (`make help`), `app/package.json`, `server/composer.json`.

## Checks
- `make check` runs what CI runs (pint, phpstan, phpunit, eslint, vue-tsc, vitest, build). `SKIP_TEST_DB=1` skips starting the MySQL test DB.
- Backend tests need MySQL (defaults in `server/phpunit.xml`, `make test-db`). Frontend tests live next to the code as `*.test.ts`.
- Account workflows have black-box e2e scripts: `app/e2e/README.md` (throwaway database only).
- Every fix gets a regression test.

## Conventions
- API JSON is camelCase. Always call the API through `httpClient` (`@/http/api`), never raw `fetch`.
- Dates: use `@/helpers/dayjs` and `parseApiDate()` for API values.
- Public (unauthenticated) endpoints return minimal data: abbreviated family names, no photos, no group `userIds`. In public views use `profileImageUrl()` and `masterData.getSimpleUsersByGroupId()`. Master data is reloaded on login/logout.
- Anonymous (cookie-user) check-in/out must keep working; do not require an account for it.
- Role `admin` passes every permission via `Gate::before`; trainers and members have explicit permissions.
- The scheduler lives in `server/routes/console.php`. Push notifications are opt-out per user via `notification_preferences`.
- Vue 3 Composition API, `<script setup lang="ts">` for new code. Custom components must declare `emits`. Vuetify 4 inputs emit `update:modelValue`, not `change`.

## Hard rules
- The repository is public: no secrets, personal data or site-specific names/hosts/domains. Site values live in the gitignored `deploy/site.env` (template `deploy/site.env.example`) and `deploy/.env.production` (template `.env.production.example`).
- The production DB holds data of minors: query aggregates only, never print names or emails.
- The production server also hosts another website: never touch it, and never change shared Apache/PHP/MySQL config without approval.

## Production
Deploy with `deploy/deploy.sh` (`--dry-run` available), roll back with `deploy/rollback.sh`; the server layout is `releases/` + `current` symlink + `shared/` (`.env`, storage). First-time server setup: `deploy/server-setup.sh`. Data retention: `php artisan tome:retention --dry-run` (see `config/tome.php`). Matomo analytics: `MATOMO_SETUP.md`.

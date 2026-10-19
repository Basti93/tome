# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

T.O.M.E. (Training Organization Made Easy) is a monorepo with a decoupled Vue.js PWA frontend (`/app`) and Laravel REST API backend (`/server`). Communication uses JWT-authenticated REST API calls.

## Development Setup

Two development workflows are available:

### Option 1: Docker (Recommended - Production-like)
Best for testing the full integrated stack with nginx proxy.

```bash
# Build and start all services (nginx, laravel, mysql)
docker-compose up -d

# Run Laravel migrations
docker-compose exec laravel php artisan migrate
docker-compose exec laravel php artisan db:seed

# Build frontend and copy to public folder
cd app && npm run build:serve

# Access at http://localhost:8000
```

Features:
- ✅ Nginx reverse proxy handles routing
- ✅ Same-origin cookies work (SameSite=Strict in Docker)
- ✅ MySQL database included
- ✅ Production-like environment

Stop with: `docker-compose down`

### Option 2: Local Development (Vite + Local Laravel)
Best for rapid development with hot reload.

```bash
# Terminal 1: Start Laravel backend
cd server && composer install && php artisan serve
# Runs on http://localhost:8000

# Terminal 2: Start Vite dev server with live reload
cd app && npm install && npm run dev
# Runs on http://localhost:5173 with auto-refresh

# Terminal 3: (Optional) Configure Laravel
php artisan migrate
php artisan db:seed
```

Features:
- ✅ Hot module reload for frontend changes
- ✅ Separate dev servers (no nginx needed)
- ✅ Vite proxy to /api/* endpoints
- ✅ SameSite=Lax cookies work with different ports

Access: `http://localhost:5173`

### Commands

#### Frontend (`/app`)
```bash
npm install              # Install dependencies
npm run dev             # Vite dev server (port 5173, hot reload)
npm run build           # Production build to /dist
npm run build:serve     # Build + copy to /server/public
npm run deploy          # Copy dist/ to /server/public
npm run lint            # ESLint with auto-fix
npm run lint:check      # ESLint, no fixing (used by CI)
npm run type-check      # vue-tsc --noEmit
npm run test            # Vitest unit tests
```

#### Backend (`/server`)
```bash
composer install              # Install dependencies
php artisan serve             # Dev server (port 8000)
php artisan migrate           # Run migrations
php artisan db:seed           # Seed database
php artisan jwt:secret -f     # Generate JWT secret
vendor/bin/phpunit            # Run tests (needs the MySQL test DB, see "Checks & Tests")
```

## Architecture

### Backend (Laravel 13, PHP 8.4)
- **API**: plain Laravel routing in `server/routes/api.php` at `/api/v1/` (Dingo was removed in the 2026 migration), public and JWT-protected routes
- **Controllers**: `/server/app/Api/V1/Controllers/` — `TrainingController` is the largest (core logic)
- **Resources**: `/server/app/Http/Resources/` — transform Eloquent models to JSON (responses are camelCase via `laravel-camelcase-json`)
- **Auth**: JWT via `php-open-source-saver/jwt-auth`; roles/permissions via `spatie/laravel-permission` (checked with `permission:` middleware in controller constructors)
- **Key models**: `Training` (core entity) → has many `TrainingParticipation`, `TrainingTrainer`; belongs to many `Group`, `Content`. Trainings are flagged via the `deleted` column by `PATCH training/{id}/deleteinfuture` (list queries filter `deleted = false`); `DELETE training/{id}` really deletes the row.
- **Integrations**: Firebase Cloud Messaging HTTP v1 via `kreait/firebase-php` (push notifications), Excel export (`maatwebsite/excel`), image processing (`intervention/image`)
- **Push Notifications**: Firebase FCM (100% free, unlimited). Users can manage preferences via `notification_preferences` JSON column (defaults all enabled).
  - Service: `/server/app/Services/NotificationPreferenceService.php` — checks if notification type enabled before sending
  - All 6 notification commands check preferences: `SendNotificationsToUsers`, `SendTrainingNotificationsToUsers`, `UpcomingTrainingNotifications`, `TrainerUpcomingTrainingNotifications`, `CancelTrainingNotification`, `AbsenceNotification`
  - Types: `trainer_upcoming`, `trainer_cancel_alert`, `participant_upcoming`, `absence_alert`, `admin_messages`
  - API: `GET|PUT /v1/user/me/notification-preferences` — manage user preferences

### Frontend (Vue 3.5, TypeScript 5.9)
- **UI**: Vuetify 4 (Material Design 3). `src/styles/vuetify-compat.css` restores the Vuetify 3 look (reset margins, uppercase buttons); the theme is pinned to `light`. Data tables: server-paginated tables use `v-data-table-server` (`items-length`, headers use `title`, sort is `[{ key, order }]`); `v-data-iterator` slot items are wrapped (`{ raw }`).
- **State**: Pinia 4 stores for auth (JWT token), data management, and UI state
- **Router**: vue-router 5, `/src/router/index.ts` — login/auth guards (return-style guard). Self-registration was removed: there is no signup page/route; admins/trainers invite users (`Einladung senden` in the user table → `POST /v1/user/{id}/invite`, see `services/userInvite.ts`).
- **HTTP**: Fetch API with JWT interceptors for API calls; `httpClient.post(url, body, { responseType: 'blob' })` returns a Blob plus response headers (used by the accounting export, which downloads the XLSX directly via `helpers/download.ts`, filename from Content-Disposition, fallback `abrechnung.xlsx`)
- **Dates**: dayjs (`@/helpers/dayjs`: German locale + plugins). Use `parseApiDate()` for API values (lenient, local time, like the old moment parsing); dayjs objects are immutable.
- **Build/Tooling**: Vite 8, Vitest 5 (jsdom 30), ESLint 10 flat config (`app/eslint.config.js`, typescript-eslint + eslint-plugin-vue), vue-tsc 3 on TypeScript 5.9 (typescript-eslint does not support TS 7 yet). `npm audit` reports 0 findings (`overrides` pins `@grpc/grpc-js`).
- **Anonymous API data**: unauthenticated `/simpleuser*` returns abbreviated family names ("M.") and `profileImageName: null`; unauthenticated `/group` returns `userIds: []`. Always render images through `profileImageUrl()` (`helpers/profile-image.ts`, null-safe, base `VITE_IMAGE_FOLDER_URL`) and list group members via `masterData.getSimpleUsersByGroupId()` (uses `SimpleUser.groupIds`), never `group.userIds`, in public views.
- **Analytics (Matomo, cookieless)**: `services/analytics.ts`. Only active with `VITE_MATOMO_ENABLED=true` + `VITE_MATOMO_URL` + `VITE_MATOMO_SITE_ID`; `disableCookies` is pushed first, Do-Not-Track is respected, no user ID or personal data in events (page views use the route name). Server settings and the consent reasoning are in `MATOMO_SETUP.md`.
- **Push notifications**: `services/push.ts` (Firebase 13 modular API): registers `public/firebase-messaging-sw.js` (no Firebase SDK inside, handles the plain Push payload), `getToken` with `VITE_FIREBASE_VAPID_KEY` + the SW registration, then `POST notifications/subscribe` `{ firebaseToken, userId }` after login or when the user grants permission (`App.vue`). Foreground messages show a snackbar; unsupported/unconfigured/denied states degrade silently.
- **Pages vs Components**: `/src/pages/` are full-screen route targets; `/src/components/` are reusable dialogs, forms, and UI elements

### Authentication Flow
Users authenticate with JWT stored in **HttpOnly cookies** (set by LoginController). The browser automatically sends the cookie with all same-origin requests. The `JwtFromCookie` middleware extracts the token and sets the Authorization header for API routes. 401 responses trigger a token refresh via HTTP interceptors. `POST /auth/refresh` (no `jwt.auth` middleware, works with an expired access token inside `jwt.refresh_ttl`) blacklists the old token and sets a fresh cookie; 401 otherwise.

### Backend notes (2026 upgrade)
- Versions: Laravel 13, spatie/laravel-permission 8, kreait/firebase-php 8, intervention/image 4, maatwebsite/excel 4, phpunit 12. `composer.json` requires PHP ^8.4 and pins `config.platform.php` to 8.4.1 so resolution is identical on every PHP >= 8.4 (Symfony 8 needs 8.4.1). `composer audit` must stay at 0 advisories.
- Admin bypass: `Gate::before` in `AuthServiceProvider` lets role `admin` pass every permission check (the admin role holds no permissions in the DB). Trainers: training/user/series/group permissions; members: read/checkin only.
- Signup is closed (no `auth/signup`). Admins/trainers (`update-user`) invite with `POST /v1/user/{id}/invite` -> German "set password" mail using the password reset token/link (`{VUE_APP_URL}/#/reset-password?token=..&email=..`); setting a password via reset/invite marks the email as verified. Login requires `email_verified_at`; migration `2026_10_19_000000` backfills existing users.
- Public (no login) endpoints return minimal data: `simpleuser*` family name as initial + no photo, `group*` without `userIds`, `training/upcoming` participants without cancel reasons. Logged-in users (`auth('api')->check()`) get everything. `storeAbsence` is public, throttled and validated (future, <= 365 days).
- Profile images: `POST user/me/uploadprofileimage` validates, re-encodes to JPEG (height 400), stores `profile_images/<40 alnum>.jpg` on the `public` disk (`storage/app/public`, served at `<APP_URL>/storage/<name>`). `profileImageName` must match `App\Support\ProfileImage::PATTERN`; only matching files are deleted.
- Accounting export: `POST trainingevaluation/exportaccountingtimes` streams an XLSX (`abrechnung_<from>_<to>.xlsx`); trainers only for themselves, admins for anyone. Nothing is stored on disk.
- Schedule lives in `routes/console.php` (tests: `ScheduleTest`); the old `Kernel.php` was never loaded in Laravel 11+. Reminders every 30 min between 6 and 22 h; users opt out via notification preferences. FCM tokens reported invalid/unregistered are deleted.
- Data retention: `php artisan tome:retention {--dry-run}` (monthly, prints counts only; rules in `App\Services\RetentionService`: users without activity for 24/12 months and not admin, old participations/cancel reasons, deleted trainings, stale tokens, auth logs > 90 days).
- `GET /v1/health` (public): `{"status":"ok"}` or 503 `{"status":"unavailable"}` after a DB ping. With `APP_DEBUG=false` no exception text reaches clients. Logs: `daily` channel, 14 days (`LOG_DAILY_DAYS`).

## Setup

### Frontend `.env.local` (Development)
Required for local development. Copy from `.env.local.example`:

```bash
cp app/.env.local.example app/.env.local
```

Then edit with your development values:

```
# Firebase Cloud Messaging (development project recommended)
VITE_FIREBASE_API_KEY=your_dev_api_key
VITE_FIREBASE_AUTH_DOMAIN=ssc-tome-dev.firebaseapp.com
VITE_FIREBASE_DATABASE_URL=https://ssc-tome-dev.firebaseio.com
VITE_FIREBASE_PROJECT_ID=ssc-tome-dev
VITE_FIREBASE_STORAGE_BUCKET=ssc-tome-dev.firebasestorage.app
VITE_FIREBASE_MESSAGING_SENDER_ID=your_dev_sender_id
VITE_FIREBASE_APP_ID=your_dev_app_id

# Web Push certificate key (Firebase Console > Project Settings > Cloud Messaging > Web Push certificates)
VITE_FIREBASE_VAPID_KEY=your_vapid_public_key

# Optional: Override API URL (defaults to /api/v1)
VITE_APP_API_URL=http://localhost:8000/api/v1

# Optional: Matomo (cookieless); all three must be set, see MATOMO_SETUP.md
VITE_MATOMO_ENABLED=true
VITE_MATOMO_URL=/matomo/
VITE_MATOMO_SITE_ID=1
```

**Never commit `.env.local`** - it contains sensitive credentials.

### Frontend `.env.production` (Optional)
For production builds (`npm run build`). If omitted, uses variables from `.env.local`:

```
VITE_FIREBASE_API_KEY=your_prod_api_key
VITE_FIREBASE_AUTH_DOMAIN=ssc-tome.firebaseapp.com
# ... other production values
```

### Backend `.env` (key vars)
```
DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD
JWT_SECRET          # generate with: php artisan jwt:secret -f
FCM_SERVER_KEY / FCM_SENDER_ID   # Firebase push notifications
MAIL_*              # Email config for password reset + monthly stats
```

## Key Conventions
- API responses are camelCase (handled automatically by `laravel-camelcase-json`)
- Trainings can be flagged `deleted` (not removed) — list endpoints filter on `deleted = false`; `DELETE` is a hard delete
- Unregistered users (cookie auth) are allowed to check in/out — do not gate this on a registered account
- Vue 3 with Composition API and TypeScript for type safety (`<script setup lang="ts">` for new code)
- No self-registration: users are created by admins/trainers and invited by e-mail
- Pinia stores for state management (replacing Vuex)
- **Always use `httpClient` (from `@/http/api`) for API calls, not raw `fetch()`** — ensures JWT auth headers and proper base path
- User preferences use JSON columns with defaults in application code (DB constraints prevent JSON defaults)
- Profile settings: Each user tab in `ProfilePage.vue` is a new `v-window-item` with its own form component
- Notification preferences: 5 types managed via `NotificationPreferencesForm.vue` component in ProfilePage tab 3

## Production

- Live at https://training.ssc-landau.de on an IONOS Ubuntu 24.04 server (Apache + PHP-FPM 8.4 + MySQL 8.0); SSH access details are in `docs/internal/`.
- **Production still runs the 2021 code** (Vue 2 SPA in `/var/www/tome`, Laravel 8 + Dingo in `/var/www/tome/api`, API under `/api/api/*`, not a git checkout). Everything on the current branches is not yet deployed.
- **The same server hosts the club's WordPress site ssc-landau.de (`/var/www/wp-website`). Never touch it**, and don't change shared Apache/PHP/MySQL config without explicit approval.
- Migration plan and security audit: `docs/internal/` (gitignored — the GitHub repo is public, never commit server details or personal data).
- The DB contains personal data of minors: query only aggregates, never print names/emails.

## Checks & Tests

`make check` runs everything CI runs (`.github/workflows/ci.yml`): backend `pint --test`, `phpstan` (larastan level 5 + baseline), `phpunit`, then frontend `lint:check`, `type-check` (vue-tsc), `vitest` and `build`. `make audit` runs `composer audit` and `npm audit --omit=dev`; it is allowed to fail.

### Backend tests (`/server/tests`)
- Suites: `Unit`, `Feature` (HTTP-level characterization of `/api/v1`), `Console` (Artisan commands). The old `tests/Functional` directory is stale and not part of any suite.
- Tests run against **MySQL** (database `tome_test`, `RefreshDatabase`), never against the dev database:
  ```bash
  make test-db                      # docker compose up -d mysql + CREATE DATABASE tome_test
  cd server && vendor/bin/phpunit   # or: php artisan test --filter=LoginTest
  ```
- Connection settings are defaults in `server/phpunit.xml` (127.0.0.1:3306, root/root); override them with the `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` environment variables (CI does this). Use `make check SKIP_TEST_DB=1` if a MySQL is already running elsewhere.
- `Tests\TestCase` seeds the production roles/permissions (admin, trainer, member), fakes mail. Firebase is never contacted: `FirebaseMessagingService` throws without credentials, and `tests/Feature/Notifications/PushDispatchTest.php` replaces it with an overload mock (hence one PHP process per test), while `tests/Unit/Services/FirebaseMessagingServiceTest.php` mocks kreait's `Messaging`. Use the factories in `server/database/factories` and the helpers `makeUser($role)`, `actingAsJwt($user)` (HttpOnly cookie) and `actingAsBearer($user)`.
- Tests that pin known bugs say `BUG` in a comment or use `markTestSkipped('BUG: ...')`.
- Static analysis: `cd server && vendor/bin/phpstan analyse` (config `phpstan.neon`, baseline `phpstan-baseline.neon`); style: `vendor/bin/pint` (laravel preset; `--test` only checks).

### Frontend tests (`/app`)
- `npm run test` (vitest, jsdom; `npm run test:watch` for watch mode). Tests live next to the code as `*.test.ts`; `@/http/api` is mocked for store tests.
- Files with pre-existing type errors carry a `// @ts-nocheck TODO(safety-net)` marker at the top of their `<script>` (2 left: `EditTrainingDialog.vue`, `TrainingCalendar.vue`); remove the marker once the file's errors are fixed.
- Services with tests: `analytics` (cookieless/DNT/no PII), `push` (mocked firebase), `userInvite`; helpers: `dayjs`, `download`, `profile-image`.

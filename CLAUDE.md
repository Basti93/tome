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
```

#### Backend (`/server`)
```bash
composer install              # Install dependencies
php artisan serve             # Dev server (port 8000)
php artisan migrate           # Run migrations
php artisan db:seed           # Seed database
php artisan jwt:secret -f     # Generate JWT secret
phpunit                       # Run tests
```

## Architecture

### Backend (Laravel 11, PHP 8.2+)
- **API**: plain Laravel routing in `server/routes/api.php` at `/api/v1/` (Dingo was removed in the 2026 migration), public and JWT-protected routes
- **Controllers**: `/server/app/Api/V1/Controllers/` — `TrainingController` is the largest (core logic)
- **Resources**: `/server/app/Http/Resources/` — transform Eloquent models to JSON (responses are camelCase via `laravel-camelcase-json`)
- **Auth**: JWT via `php-open-source-saver/jwt-auth`; roles/permissions via `spatie/laravel-permission` (checked with `permission:` middleware in controller constructors)
- **Key models**: `Training` (core entity) → has many `TrainingParticipation`, `TrainingTrainer`; belongs to many `Group`, `Content`. Trainings use soft deletes (marked, not hard-deleted).
- **Integrations**: Firebase Cloud Messaging HTTP v1 via `kreait/firebase-php` (push notifications), Excel export (`maatwebsite/excel`), image processing (`intervention/image`)
- **Push Notifications**: Firebase FCM (100% free, unlimited). Users can manage preferences via `notification_preferences` JSON column (defaults all enabled).
  - Service: `/server/app/Services/NotificationPreferenceService.php` — checks if notification type enabled before sending
  - All 6 notification commands check preferences: `SendNotificationsToUsers`, `SendTrainingNotificationsToUsers`, `UpcomingTrainingNotifications`, `TrainerUpcomingTrainingNotifications`, `CancelTrainingNotification`, `AbsenceNotification`
  - Types: `trainer_upcoming`, `trainer_cancel_alert`, `participant_upcoming`, `absence_alert`, `admin_messages`
  - API: `GET|PUT /v1/user/me/notification-preferences` — manage user preferences

### Frontend (Vue 3.4, TypeScript)
- **UI**: Vuetify 3.5 (Material Design)
- **State**: Pinia 2.1 stores for auth (JWT token), data management, and UI state
- **Router**: `/src/router/index.ts` — 18 routes with login/auth guards
- **HTTP**: Fetch API with JWT interceptors for API calls
- **Build**: Vite 5.2 for fast dev server and production builds
- **Pages vs Components**: `/src/pages/` are full-screen route targets; `/src/components/` are reusable dialogs, forms, and UI elements

### Authentication Flow
Users authenticate with JWT stored in **HttpOnly cookies** (set by LoginController). The browser automatically sends the cookie with all same-origin requests. The `JwtFromCookie` middleware extracts the token and sets the Authorization header for API routes. 401 responses trigger automatic token refresh via HTTP interceptors.

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

# Optional: Override API URL (defaults to /api/v1)
VITE_APP_API_URL=http://localhost:8000/api/v1
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
- Trainings use soft deletes — check for `markedForDeletion` flags rather than assuming hard deletion
- Unregistered users (cookie auth) are allowed to check in/out — do not gate this on a registered account
- Vue 3 with Composition API and TypeScript for type safety
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

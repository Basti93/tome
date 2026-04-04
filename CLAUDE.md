# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

T.O.M.E. (Training Organization Made Easy) is a monorepo with a decoupled Vue.js PWA frontend (`/app`) and Laravel REST API backend (`/server`). Communication uses JWT-authenticated REST API calls.

## Commands

### Frontend (`/app`)
```bash
npm install          # Install dependencies
npm run serve        # Dev server (port 8080)
npm run build        # Production build
npm run lint         # ESLint with auto-fix
```

### Backend (`/server`)
```bash
composer install                  # Install dependencies
php artisan serve                 # Dev server (port 8000)
php artisan migrate               # Run migrations
php artisan db:seed               # Seed database
php artisan jwt:secret -f         # Generate JWT secret
phpunit                           # Run tests
```

## Architecture

### Backend (Laravel 8, PHP)
- **API**: Dingo API v3 at `/api/v1/`, public and JWT-protected routes
- **Controllers**: `/server/app/Api/V1/Controllers/` — `TrainingController` is the largest (~13K lines, core logic)
- **Resources**: `/server/app/Http/Resources/` — transform Eloquent models to JSON (responses are camelCase via `laravel-camelcase-json`)
- **Auth**: JWT via `tymon/jwt-auth`; roles/permissions via `spatie/laravel-permission`
- **Key models**: `Training` (core entity) → has many `TrainingParticipation`, `TrainingTrainer`; belongs to many `Group`, `Content`. Trainings use soft deletes (marked, not hard-deleted).
- **Integrations**: Firebase Cloud Messaging (push notifications), Zoom (`laravel-zoom`), Excel export (`maatwebsite/excel`), image processing (`intervention/image`)

### Frontend (Vue 2, TypeScript/JS mix)
- **UI**: Vuetify 2 (Material Design)
- **State**: Vuex with modules: `auth` (JWT token), `cookieAuth` (unregistered user session), `masterData` (branches, groups, locations, trainers, training series), `snackbar`
- **Router**: `/src/router/index.js` — 18 routes with login/auth guards
- **Models**: TypeScript classes in `/src/models/` (Training, User, Branch, Group, etc.)
- **Axios**: `/src/axios/index.js` — interceptors auto-attach JWT and handle 401s by calling the refresh token endpoint
- **Pages vs Components**: `/src/pages/` are full-screen route targets; `/src/components/` are reusable dialogs, forms, and UI elements

### Authentication Flow
Users authenticate with JWT (stored in `localStorage`) or via cookie-based access for unregistered users. Axios interceptors auto-attach tokens; 401 responses trigger automatic token refresh.

## Setup

### Frontend `.env.local`
```
VUE_APP_API_URL=http://localhost:8000/api/v1
```

### Backend `.env` (key vars)
```
DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD
JWT_SECRET          # generate with: php artisan jwt:secret -f
FCM_SERVER_KEY / FCM_SENDER_ID   # Firebase push notifications
MAIL_*              # Email config for password reset + monthly stats
```

## Key Conventions
- Frontend is a TypeScript/JavaScript hybrid — some files are `.ts`, others are `.js`; both exist intentionally
- API responses are camelCase (handled automatically by `laravel-camelcase-json`)
- Trainings use soft deletes — check for `markedForDeletion` flags rather than assuming hard deletion
- Unregistered users (cookie auth) are allowed to check in/out — do not gate this on a registered account

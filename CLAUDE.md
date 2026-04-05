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

### Backend (Laravel 8, PHP)
- **API**: Dingo API v3 at `/api/v1/`, public and JWT-protected routes
- **Controllers**: `/server/app/Api/V1/Controllers/` — `TrainingController` is the largest (~13K lines, core logic)
- **Resources**: `/server/app/Http/Resources/` — transform Eloquent models to JSON (responses are camelCase via `laravel-camelcase-json`)
- **Auth**: JWT via `tymon/jwt-auth`; roles/permissions via `spatie/laravel-permission`
- **Key models**: `Training` (core entity) → has many `TrainingParticipation`, `TrainingTrainer`; belongs to many `Group`, `Content`. Trainings use soft deletes (marked, not hard-deleted).
- **Integrations**: Firebase Cloud Messaging (push notifications), Zoom (`laravel-zoom`), Excel export (`maatwebsite/excel`), image processing (`intervention/image`)

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

### Frontend `.env.local` (Optional)
```
# Optional: Override API URL (defaults to /api/v1 which is relative to the server)
VITE_APP_API_URL=http://localhost:8000/api/v1
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

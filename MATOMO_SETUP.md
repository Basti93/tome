# Matomo Analytics Setup Guide

## Overview

T.O.M.E. includes integrated privacy-friendly analytics using Matomo (self-hosted). This guide explains the setup and troubleshooting.

## Current Setup

### Architecture

```
┌──────────────────┐
│  Vue.js App      │
│  (5173/8000)     │
└────────┬─────────┘
         │ tracks events via /matomo/
         ▼
┌──────────────────────────────┐
│  Nginx (reverse proxy)       │
│  /matomo/ → matomo container │
└────────┬─────────────────────┘
         │
         ▼
┌──────────────────────────────┐
│  Matomo Container            │
│  (8002)                      │
│  ↓                           │
│  Shared MySQL Database       │
│  (matomo schema)             │
└──────────────────────────────┘
```

### Key Features

- ✅ Self-hosted (no 3rd party data sharing)
- ✅ Privacy-friendly (IP anonymization enabled)
- ✅ Automatic initialization on first run
- ✅ Configurable via environment variables
- ✅ Data persisted in Docker volumes

## Initial Setup

### 1. Start Fresh (Recommended)

If you have an existing but incomplete Matomo setup, clean it up first:

```bash
# Reset Matomo database and volume
make docker-matomo-reset

# Or if you prefer a complete fresh rebuild of everything:
make docker-rebuild-fresh
```

The initialization script will:
1. Create the `matomo` database
2. Run Matomo's `core:install` command with auto-configuration
3. Enable IP anonymization
4. Start Matomo ready for tracking

### 2. Verify Setup

After starting, verify that Matomo initialized correctly:

```bash
make verify-matomo
```

This script checks:
- Matomo is responding (HTTP 200)
- Database schema is complete (40+ tables)
- Required tables exist (matomo_log_visit, etc.)
- Tracking events are being recorded

Expected output:
```
✅ Matomo is responding (HTTP 200)
✅ Database schema appears complete
✅ matomo_log_visit
✅ matomo_log_action
✅ matomo_log_conversion
✅ matomo_site
...
```

### 3. Access Matomo Dashboard

Open http://localhost:8002/ in your browser

**Login credentials** (set by initialization script):
- Username: `admin`
- Password: `matomo_admin_123`

You should see:
- Matomo dashboard loaded (no setup wizard)
- Site "TOME" already configured
- Ready to track events

## Testing Event Tracking

### 1. Trigger Events in the App

In your browser at http://localhost:8000 (or http://localhost:5173 for dev):

```
1. Login with a test account
   → Tracks: Authentication > Login

2. Navigate to check-in page
   → Tracks: Page view

3. Check in to a training
   → Tracks: Training > CheckIn

4. Check out with a reason
   → Tracks: Training > CheckOut
```

### 2. Verify Events in Dashboard

1. Open http://localhost:8002/
2. Log in (admin / matomo_admin_123)
3. Go to **Reports** → **Events**
4. You should see your tracked events within seconds

### 3. Check Database Directly

```bash
# View tracked visits
docker-compose exec mysql mysql -u root -proot matomo -e "SELECT COUNT(*) as visits FROM matomo_log_visit;"

# View tracked events
docker-compose exec mysql mysql -u root -proot matomo -e "SELECT COUNT(*) as events FROM matomo_log_action;"

# View specific events
docker-compose exec mysql mysql -u root -proot matomo -e "SELECT action_name FROM matomo_log_action LIMIT 10;"
```

## Environment Variables

The Matomo container is configured via environment variables in `docker-compose.yml`:

```yaml
environment:
  - MATOMO_DB_HOST=mysql              # Database host
  - MATOMO_DB_NAME=matomo             # Database name
  - MATOMO_DB_USER=tome               # Database user
  - MATOMO_DB_PASSWORD=tome           # Database password
  - MATOMO_ADMIN_USER=admin           # Admin username
  - MATOMO_ADMIN_PASSWORD=matomo_admin_123  # Admin password
  - MATOMO_ADMIN_EMAIL=admin@tome.local     # Admin email
  - MATOMO_SITE_NAME=TOME             # Site name
  - MATOMO_SITE_URL=http://localhost:8002/  # Matomo URL
```

## Frontend Configuration

The Vue.js frontend tracks events through:

### Service: `app/src/services/analytics.ts`

```typescript
import { analytics } from '@/services/analytics'

// Initialize early in app startup
analytics.init()

// Track custom events
analytics.trackEvent('Authentication', 'Login', user.role)
analytics.trackEvent('Training', 'CheckIn', training_id)

// Track authenticated user (optional)
analytics.setUserId(user.id)

// Clear on logout
analytics.resetUserId()
```

### Environment Variables: `app/.env.production` and `app/.env.local`

```
VITE_MATOMO_ENABLED=true                    # Enable/disable tracking
VITE_MATOMO_URL=http://localhost:8002/      # Matomo server URL
VITE_MATOMO_SITE_ID=1                       # Site ID (matches Matomo config)
```

## Tracked Events

### Authentication
- `user_login_success` → triggered on login (LoginPage.vue)
- `user_signup_completed` → triggered on signup (SignupPage.vue)
- `user_logout` → triggered on logout (LogoutComponent.vue)

### Training
- `participant_checkin_completed` → triggered when checking in (TrainingCheckIn.vue)
- `participant_checkout_completed` → triggered when checking out (TrainingCheckIn.vue)
- `training_selected` → triggered when selecting a training (TrainingsCheckInPage.vue)

### Admin/Trainer
- `training_created` → triggered when creating training (EditTrainingDialog.vue)
- `training_edited` → triggered when editing training (EditTrainingDialog.vue)
- `training_evaluated` → triggered when evaluating training (TrainingsEvaluationPage.vue)

### Profile
- `profile_updated` → triggered when updating profile (ProfilePage.vue)
- `notification_preference_changed` → triggered when changing notification settings (NotificationPreferencesForm.vue)

## Troubleshooting

### Issue: "Matomo not responding" or setup wizard appears

**Cause**: Matomo initialization failed or database wasn't created properly

**Solution**:
```bash
# Option 1: Reset just Matomo (keeps app data)
make docker-matomo-reset

# Option 2: Full rebuild (deletes all data)
make docker-rebuild-fresh
```

Then verify:
```bash
make verify-matomo
```

### Issue: "No events appearing in dashboard"

1. **Check Matomo is initialized**:
   ```bash
   curl http://localhost:8002/
   # Should return HTML, not a setup form
   ```

2. **Check database schema**:
   ```bash
   docker-compose exec mysql mysql -u root -proot matomo -e "SHOW TABLES;"
   # Should show 40+ tables (matomo_log_visit, matomo_log_action, etc.)
   ```

3. **Check nginx proxy is working**:
   ```bash
   curl -I http://localhost:8000/matomo/
   # Should return 301/302 (redirect to Matomo)
   ```

4. **Check browser console** for JavaScript errors:
   - Open DevTools (F12)
   - Go to Console tab
   - Look for errors loading `/matomo/matomo.js`

5. **Check Matomo logs**:
   ```bash
   docker-compose logs matomo | tail -50
   # Look for "core:install" or error messages
   ```

### Issue: "Events recorded but dashboard shows 0"

This usually means tracking requests are reaching Matomo but the archiving/processing hasn't run.

**Solution**:
```bash
# Matomo processes data asynchronously; give it a minute
# Then refresh the dashboard: http://localhost:8002/

# Or manually trigger archiving:
docker-compose exec matomo php console core:archive --url=http://localhost:8002/
```

### Issue: Database shows visits but no actions

This can happen if archiving hasn't run. Visit the Matomo dashboard to trigger archiving:

```bash
# Open in browser
http://localhost:8002/

# Refresh a few times to trigger background archiving
# Wait 30-60 seconds for processing to complete
```

## Privacy Compliance

Matomo is configured with privacy-first settings:

✅ **Enabled**:
- IP anonymization (last 2 octets masked)
- Data stored locally (no 3rd party sharing)
- No cookie tracking for anonymous users
- GDPR compliant out of the box

⚠️ **If tracking authenticated users**:
- User IDs are linked to events (optional)
- Should be disclosed in privacy policy
- Users can opt-out via notification preferences

## Matomo Administration

### Add More Sites

```bash
# Log in to http://localhost:8002/
# Click Administration → Websites
# Click "Add a website"
```

### Configure Dashboards

```bash
# Log in to http://localhost:8002/
# Go to Home or any report
# Click "Pin/Unpin" to customize dashboard
```

### Export Data

```bash
# Reports → Any report
# Click "Export" (CSV, PDF, etc.)
```

## Files Modified

### Docker
- `docker-compose.yml` - Matomo service configuration
- `docker/nginx/default.conf` - Reverse proxy for /matomo/
- `docker/matomo/Dockerfile` - Custom Matomo image with auto-init
- `docker/matomo/docker-entrypoint.sh` - Initialization script
- `docker/mysql/init.sql` - MySQL database setup for Matomo

### Frontend
- `app/src/services/analytics.ts` - Matomo tracker wrapper
- `app/src/main.ts` - Initialize tracker on app startup
- `app/src/router/index.ts` - Track page views
- `app/.env.local` - Development configuration
- `app/.env.production` - Production configuration
- `app/src/pages/LoginPage.vue` - Login event tracking
- `app/src/pages/SignupPage.vue` - Signup event tracking
- `app/src/components/LogoutComponent.vue` - Logout event tracking
- `app/src/components/TrainingCheckIn.vue` - Check-in/out event tracking

### Makefile
- `docker-matomo-reset` - Reset Matomo to fresh state
- `verify-matomo` - Verify Matomo setup and database

## Next Steps

1. **Clean slate**: `make docker-matomo-reset`
2. **Verify setup**: `make verify-matomo`
3. **Test tracking**: Log in and perform actions in the app
4. **Check dashboard**: Visit http://localhost:8002/ and refresh to see events

## Support

For issues:
1. Check Matomo logs: `docker-compose logs matomo`
2. Check browser console (F12)
3. Verify database schema: `make verify-matomo`
4. Check Nginx proxy: `curl -I http://localhost:8000/matomo/`

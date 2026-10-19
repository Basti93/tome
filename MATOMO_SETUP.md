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
- ✅ Privacy-friendly (cookieless, IP anonymization, no user IDs)
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
   → Tracks: Authentication > Login (no user data)

2. Navigate to check-in page
   → Tracks: Page view

3. Check in to a training
   → Tracks: Training > CheckIn

4. Check out
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

The Vue.js frontend tracks **cookieless and without personal data** through
`app/src/services/analytics.ts` (standard Matomo `_paq` queue, `matomo.js` loaded from the Matomo URL).

### Environment variables (`app/.env.production` / `app/.env.local`)

```
VITE_MATOMO_ENABLED=true     # must be exactly "true", otherwise nothing is loaded
VITE_MATOMO_URL=/matomo/     # Matomo base URL (matomo.js and matomo.php are served from here)
VITE_MATOMO_SITE_ID=1        # Site ID (matches the Matomo site)
```

Tracking is only active when **all three** are set. There are no fallback defaults.

### Behaviour guaranteed by the service

- `disableCookies` is the very first command pushed to `_paq`, before any tracking call. No Matomo cookie is set.
- Do-Not-Track is respected: if the browser sends DNT, `matomo.js` is not loaded and nothing is sent.
- No `setUserId`, no custom variables, no names, e-mail addresses or user ids in events. The service has no
  user-ID API at all; e-mail-like values are dropped from event names and page names.
- Page views are reported with the neutral route name (e.g. `TrainingsCheckIn`) as custom URL/title, never the real
  URL (hash routes can contain ids, reset tokens or `nextUrl` query strings).

```typescript
import { analytics } from '@/services/analytics'

analytics.init()                              // once, in main.ts
analytics.trackEvent('Training', 'CheckIn')   // neutral events only
analytics.trackPageView('Calendar')           // route name, set by the router guard
```

## Tracked Events

| Category | Action | Where |
|----------|--------|-------|
| Authentication | Login | LoginPage.vue |
| Authentication | Logout | LogoutComponent.vue |
| Training | CheckIn | TrainingCheckIn.vue |
| Training | CheckOut | TrainingCheckIn.vue |

Plus one page view per route change (router `beforeEach`). Events carry no name or value.

## Matomo Server Settings (privacy)

Apply these in the Matomo admin UI (or via `config/config.ini.php`) for the TOME site/instance:

1. **Anonymize tracking data** (Administration > Privacy > Anonymize data):
   - *Anonymize Visitors' IP addresses*: enabled, **mask 2 bytes** (`ip_anonymisation` = 2, e.g. `192.168.0.0`).
   - *Replace User ID with a pseudonym*: enabled (irrelevant as no user ID is sent, but keeps accidental IDs pseudonymous).
   - *Anonymize Referrer*: at least "Don't record the full referrer URL" is recommended.
2. **Regularly delete old raw data** (Administration > Privacy > Delete old raw data):
   - Delete old visitor logs and raw data: enabled, older than **90 days**. Aggregated reports are kept.
   - Also delete unused log data (actions/referrers) as offered on that page.
3. **No user-ID feature**: do not enable or use the User ID feature, do not set `setUserId` in any
   custom script and do not activate plugins that import user data into visits.
4. **Respect Do Not Track**: Administration > Privacy > Users opt-out: *Support Do Not Track preference* enabled.
5. **Cookieless**: the tracker already calls `disableCookies`; in Matomo 5 you can additionally enforce it server
   side with the *Tracking > Always use cookieless tracking* setting if available in your version.
6. Keep Matomo self-hosted (as in this repo) so no data goes to a third party, and restrict the admin login.

### Is a consent banner needed?

With the configuration above, **no consent banner is required** for the tracking itself:

- No cookies or other information is stored on / read from the visitor's device (`disableCookies`; no
  localStorage, no fingerprinting IDs). The consent requirement of the ePrivacy Directive (Art. 5(3), in Germany
  Section 25 TDDDG) applies to access to the terminal device, which does not take place.
- Without a stable identifier (no cookie, no user ID) and with truncated IPs, visits cannot be linked across days or
  to a person; only aggregate statistics for the operator's own reach measurement are produced, on self-hosted
  infrastructure without any third-party sharing. Under the GDPR this is covered by legitimate interest
  (Art. 6(1)(f)); several data-protection authorities (e.g. the French CNIL's audience-measurement exemption) accept
  consent-free cookieless measurement under these conditions.
- It stops being consent-free if you add the user-ID feature, cross-site tracking, session replay, or share the
  data with third parties. Do not do that without a consent flow.

Even so, **mention the analytics in the privacy notice**: that a self-hosted Matomo is used, cookieless with
anonymized IPs, no personal data, the legal basis (legitimate interest), the 90-day retention of raw logs and the
opt-out (Do-Not-Track and the Matomo opt-out iframe/link). This is a transparency duty (Art. 13 GDPR), not a
consent requirement. This is technical guidance, not legal advice.

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

See **Matomo Server Settings (privacy)** above. In short: cookieless, no user ID, IP anonymization (2 bytes),
raw logs deleted after 90 days, Do-Not-Track respected, self-hosted, mentioned in the privacy notice.

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

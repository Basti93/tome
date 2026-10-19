# Firebase Cloud Messaging (push)

The backend sends push notifications through FCM HTTP v1 (`kreait/firebase-php`) and needs a Firebase **service account**.

1. Firebase Console > Project Settings > Service Accounts > "Generate New Private Key".
2. Put the JSON where `FIREBASE_CREDENTIALS_PATH` (in `server/.env`) points:
   - Development: `server/storage/firebase-service-account.json` (gitignored; use the dev project).
   - Production: `/var/www/tome-app/shared/firebase-service-account.json`.
3. The frontend needs the web values and the VAPID key (Project Settings > Cloud Messaging > Web Push certificates) as `VITE_FIREBASE_*` (`app/.env.local`, for production `deploy/.env.production`).

Never commit the JSON file.

Test (token from the browser, see `app/src/services/push.ts`):

```bash
cd server && php artisan notification:test "<token>" "Title" "Text"
```

Tokens that FCM reports as invalid or unregistered are deleted automatically; the app registers a new one on the next login.

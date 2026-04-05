# Firebase Cloud Messaging Setup

T.O.M.E. uses Firebase Cloud Messaging (FCM) for push notifications. The backend now uses the official Firebase PHP SDK (`kreait/firebase-php`) which requires a **Firebase service account** for authentication.

## Getting Firebase Credentials

### 1. Create a Firebase Project (if not already done)
- Go to [Firebase Console](https://console.firebase.google.com/)
- Click "Add project" or select an existing project
- Enable Cloud Messaging in the project settings

### 2. Create a Service Account
1. Go to **Project Settings** → **Service Accounts** tab
2. Click **"Generate New Private Key"** button
3. A JSON file will be downloaded with all credentials
4. **KEEP THIS FILE SECURE** - it contains sensitive credentials

### 3. Store Credentials Locally (Development)

**Do NOT commit this file to git - it's already in `.gitignore`**

Copy the downloaded service account JSON to:
```bash
cp ~/Downloads/ssc-tome-dev-[hash].json /Users/sbinder/Projects/tome/server/storage/firebase-service-account.json
```

Verify the file exists and contains the required fields:
- `type`
- `project_id`
- `private_key`
- `client_email`
- `client_id`

### 4. Update Environment Variables

The `.env.local` already has the correct setting:
```env
FIREBASE_CREDENTIALS_PATH=storage/firebase-service-account.json
```

This path is relative to the `server/` directory.

## Verify Firebase Setup

Test that everything works with the test notification command:

```bash
cd server

# With a device token from the frontend
php artisan notification:test "device-token-here" "Test Title" "Test notification body"
```

You should see output:
```
Message successfully sent to 1 recipients
Failed to send message to 0 recipients
```

## Environment-Specific Credentials

### Development
- Use the **dev** Firebase project credentials
- Service account file: `storage/firebase-service-account.json` (in `.gitignore`)

### Production
- Use the **production** Firebase project credentials
- Store the service account JSON securely (e.g., as a secret in your deployment platform)
- Update `FIREBASE_CREDENTIALS_PATH` to point to the production credentials

## Troubleshooting

**Error: "Firebase credentials file not found"**
- Verify the file path in `FIREBASE_CREDENTIALS_PATH`
- Ensure the JSON file is in the `storage/` directory
- Check file permissions: `ls -la storage/firebase-service-account.json`

**Error: "Invalid service account"**
- Verify the JSON file is valid (not corrupted)
- Check that all required fields exist in the JSON
- Regenerate the key from Firebase Console if needed

**Tokens being deleted?**
- If tokens are being deleted, they may be invalid or expired
- Frontend should re-register for a new token
- Check the app logs for Firebase error messages

## References
- [Firebase PHP SDK Documentation](https://firebase-php.readthedocs.io/)
- [Firebase Cloud Messaging Documentation](https://firebase.google.com/docs/cloud-messaging/)

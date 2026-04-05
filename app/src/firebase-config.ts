import { initializeApp } from 'firebase/app'
import { getMessaging, onMessage } from 'firebase/messaging'

// Firebase configuration from environment variables
const firebaseConfig = {
  apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
  authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
  databaseURL: import.meta.env.VITE_FIREBASE_DATABASE_URL,
  projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
  storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
  messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
  appId: import.meta.env.VITE_FIREBASE_APP_ID,
}

// Initialize Firebase
const app = initializeApp(firebaseConfig)

// Get messaging service (only in browsers that support it)
let messaging = null
if (typeof window !== 'undefined' && 'serviceWorker' in navigator) {
  try {
    messaging = getMessaging(app)

    // Handle foreground messages (when app is open)
    onMessage(messaging, (payload) => {
      console.log('Foreground message received:', payload)
      const { title, body } = payload.data || payload.notification || {}
      if (title) {
        new Notification(title, {
          body: body || '',
          icon: '/icon-192x192.png',
          badge: '/icon-192x192.png',
        })
      }
    })
  } catch (error) {
    console.error('Failed to initialize Firebase Messaging:', error)
  }
}

export default messaging

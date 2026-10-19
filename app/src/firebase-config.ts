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

console.log('Firebase config loaded:', {
  projectId: firebaseConfig.projectId,
  messagingSenderId: firebaseConfig.messagingSenderId,
})

// Initialize Firebase
const app = initializeApp(firebaseConfig)
console.log('Firebase app initialized')

let messaging: any = null
let listenerSetup = false

async function initializeMessaging() {
  if (typeof window === 'undefined') {
    console.log('Not in browser environment')
    return null
  }

  if (!('serviceWorker' in navigator)) {
    console.log('Service Worker not supported')
    return null
  }

  if (!('Notification' in window)) {
    console.log('Notification API not supported')
    return null
  }

  try {
    console.log('Initializing Firebase Messaging...')
    messaging = getMessaging(app)
    console.log('Firebase Messaging instance created')
    console.log('Current notification permission:', Notification.permission)

    // Only set up the listener ONCE
    if (!listenerSetup) {
      console.log('Setting up onMessage listener...')

      onMessage(messaging, (payload) => {
        console.log('🔔 🔔 🔔 FOREGROUND MESSAGE RECEIVED! 🔔 🔔 🔔')
        console.log('Full payload:', payload)
        console.log('notification:', payload.notification)
        console.log('data:', payload.data)

        // Try both notification and data fields
        const notifData = payload.notification || {}
        const msgData = payload.data || {}

        const title = notifData.title || msgData.title || 'New Notification'
        const body = notifData.body || msgData.body || 'You have a new message'

        console.log('Attempting to show notification:', { title, body })
        console.log('Permission status:', Notification.permission)

        // Show notification if permission granted
        if (Notification.permission === 'granted') {
          console.log('Permission granted - showing notification')
          try {
            const notification = new Notification(title, {
              body: body,
              icon: '/img/icons/icon-192x192.png',
              badge: '/img/icons/icon-72x72.png',
              tag: 'tome-notification',
              requireInteraction: true, // Keep notification visible
            })
            console.log('✅ Notification displayed successfully')

            notification.onclick = () => {
              console.log('Notification clicked')
              window.focus()
            }
          } catch (error) {
            console.error('❌ Error creating notification:', error)
          }
        } else {
          console.warn('Cannot show notification - permission not granted:', Notification.permission)
        }
      })

      listenerSetup = true
      console.log('✅ onMessage listener registered successfully')
    } else {
      console.log('onMessage listener already set up')
    }

    return messaging
  } catch (error: any) {
    console.error('❌ Error initializing Firebase Messaging:',  error.message)
    console.error('Full error:', error)
    return null
  }
}

export default messaging
export { initializeMessaging }

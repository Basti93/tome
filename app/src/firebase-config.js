import { initializeApp } from 'firebase/app'
import { getMessaging, isSupported } from 'firebase/messaging'

const firebaseConfig = {
  apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
  authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
  databaseURL: import.meta.env.VITE_FIREBASE_DATABASE_URL,
  projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
  storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
  messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
  appId: import.meta.env.VITE_FIREBASE_APP_ID
}

let messaging = null

if (import.meta.env.PROD && firebaseConfig.apiKey) {
  isSupported().then(supported => {
    if (supported) {
      console.log('Firebase messaging is supported')
      const app = initializeApp(firebaseConfig)
      messaging = getMessaging(app)
    }
  }).catch(err => {
    console.warn('Firebase messaging not supported:', err)
  })
}

export default messaging

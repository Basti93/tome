// Firebase Cloud Messaging (web push): token registration and foreground messages.
//
// Everything degrades gracefully: unsupported browsers (e.g. iOS Safari without an installed PWA),
// a missing Firebase configuration or a denied permission never throw; the result is a status.
import { initializeApp, getApps, type FirebaseApp } from 'firebase/app'
import { getMessaging, getToken, isSupported, onMessage, type Messaging } from 'firebase/messaging'
import httpClient from '@/http/api'

export type PushStatus =
  | 'subscribed' // token obtained and sent to the backend
  | 'unsupported' // no Notification/ServiceWorker/Push support (e.g. iOS without installed PWA)
  | 'unconfigured' // Firebase env / VAPID key missing
  | 'needs-permission' // permission is still "default", ask the user first
  | 'denied' // permission denied (now or earlier)
  | 'error' // anything else (network, FCM failure, ...)

export interface ForegroundMessage {
  title: string
  body: string
  data: Record<string, string>
}

const SW_PATH = 'firebase-messaging-sw.js'

function firebaseConfig() {
  const env = import.meta.env
  return {
    apiKey: env.VITE_FIREBASE_API_KEY,
    authDomain: env.VITE_FIREBASE_AUTH_DOMAIN,
    databaseURL: env.VITE_FIREBASE_DATABASE_URL,
    projectId: env.VITE_FIREBASE_PROJECT_ID,
    storageBucket: env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: env.VITE_FIREBASE_APP_ID,
  }
}

function isConfigured(): boolean {
  const config = firebaseConfig()
  return !!(config.apiKey && config.projectId && config.messagingSenderId && config.appId && import.meta.env.VITE_FIREBASE_VAPID_KEY)
}

/** Browser-level capability check (cheap, synchronous). */
export function hasPushApis(): boolean {
  return typeof window !== 'undefined' && 'Notification' in window && 'serviceWorker' in navigator && 'PushManager' in window
}

async function isPushSupported(): Promise<boolean> {
  if (!hasPushApis()) return false
  try {
    return await isSupported()
  } catch {
    return false
  }
}

let messaging: Messaging | null = null

function getMessagingInstance(): Messaging {
  if (!messaging) {
    const app: FirebaseApp = getApps()[0] ?? initializeApp(firebaseConfig())
    messaging = getMessaging(app)
  }
  return messaging
}

/** Registers the push service worker (idempotent; the browser reuses an existing registration). */
async function registerServiceWorker(): Promise<ServiceWorkerRegistration> {
  const registration = await navigator.serviceWorker.register(`${import.meta.env.BASE_URL}${SW_PATH}`)
  return registration
}

/**
 * Gets the FCM token for this browser and sends it to the backend
 * (`POST notifications/subscribe` with `{ firebaseToken, userId }`).
 *
 * @param userId the logged in (or cookie) user the token belongs to
 * @param requestPermission ask for the notification permission if it is still undecided; call this
 *        only from a user gesture (button click)
 */
export async function registerPushToken(userId: number, requestPermission = false): Promise<PushStatus> {
  if (!(await isPushSupported())) return 'unsupported'
  if (!isConfigured()) return 'unconfigured'

  try {
    let permission = Notification.permission
    if (permission === 'denied') return 'denied'
    if (permission === 'default') {
      if (!requestPermission) return 'needs-permission'
      permission = await Notification.requestPermission()
      if (permission === 'denied') return 'denied'
      if (permission !== 'granted') return 'needs-permission'
    }

    const serviceWorkerRegistration = await registerServiceWorker()
    const token = await getToken(getMessagingInstance(), {
      vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY,
      serviceWorkerRegistration,
    })
    if (!token) return 'error'

    await httpClient.post('/notifications/subscribe', { firebaseToken: token, userId })
    return 'subscribed'
  } catch (error) {
    console.error('Push registration failed:', error)
    return 'error'
  }
}

let unsubscribeForeground: (() => void) | null = null

/**
 * Calls `handler` for messages that arrive while the app is open. Returns false when push is
 * not available. Only one listener is active at a time.
 */
export async function listenForForegroundMessages(handler: (message: ForegroundMessage) => void): Promise<boolean> {
  if (!(await isPushSupported()) || !isConfigured()) return false
  try {
    unsubscribeForeground?.()
    unsubscribeForeground = onMessage(getMessagingInstance(), (payload) => {
      const data = (payload.data ?? {}) as Record<string, string>
      handler({
        title: payload.notification?.title ?? data.title ?? 'T.O.M.E.',
        body: payload.notification?.body ?? data.body ?? '',
        data,
      })
    })
    return true
  } catch (error) {
    console.error('Could not listen for push messages:', error)
    return false
  }
}

/** For tests. */
export function resetPushStateForTests() {
  messaging = null
  unsubscribeForeground = null
}

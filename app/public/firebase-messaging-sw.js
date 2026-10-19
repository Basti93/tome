// T.O.M.E. Service Worker
// Handles both PWA functionality and Firebase Cloud Messaging

const CACHE_NAME = 'tome-v1'
const RUNTIME_CACHE = 'tome-runtime'

// Files to cache on install (essential PWA files)
const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/manifest.json',
]

// Install event - cache essential files
self.addEventListener('install', (event) => {
  console.log('[SW] Installing service worker...')
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[SW] Caching static assets')
      return cache.addAll(STATIC_ASSETS)
    })
  )
  self.skipWaiting() // Activate immediately
})

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('[SW] Activating service worker...')
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME && cacheName !== RUNTIME_CACHE) {
            console.log('[SW] Deleting old cache:', cacheName)
            return caches.delete(cacheName)
          }
        })
      )
    })
  )
  self.clients.claim() // Take control immediately
})

// Fetch event - network first, then cache
self.addEventListener('fetch', (event) => {
  const { request } = event

  // Skip non-GET requests
  if (request.method !== 'GET') return

  // Skip API requests (let network handle them)
  if (request.url.includes('/api/')) {
    return event.respondWith(fetch(request))
  }

  // Network first strategy with fallback to cache
  event.respondWith(
    fetch(request)
      .then((response) => {
        // Cache successful responses
        if (response.ok) {
          const cache = caches.open(RUNTIME_CACHE)
          cache.then((c) => c.put(request, response.clone()))
        }
        return response
      })
      .catch(() => {
        // Fall back to cache on network error
        return caches.match(request).then((cached) => {
          return cached || new Response('Offline - resource not available', {
            status: 503,
            statusText: 'Service Unavailable',
            headers: new Headers({
              'Content-Type': 'text/plain',
            }),
          })
        })
      })
  )
})

// Handle Firebase notifications.
// This service worker deliberately does not load the Firebase SDK: FCM delivers web push messages
// through the standard Push API, so the payload is parsed here. This avoids coupling the worker to a
// specific (CDN) SDK version. The backend sends data-only messages ({title, body, ...} in `data`);
// `notification` and top-level payloads are accepted as well.
self.addEventListener('push', (event) => {
  if (!event.data) return

  try {
    const payload = event.data.json()
    const data = payload.data || {}
    const notification = payload.notification || {}

    const title = data.title || notification.title || payload.title || 'T.O.M.E.'
    const body = data.body || notification.body || payload.body || 'Neue Benachrichtigung'

    const options = {
      body,
      icon: '/img/icons/android-chrome-192x192.png',
      badge: '/img/icons/favicon-32x32.png',
      tag: 'tome-notification',
      data: {
        click_action: data.click_action || '/',
        ...data,
      },
    }

    event.waitUntil(self.registration.showNotification(title, options))
  } catch (error) {
    console.error('[SW] Error processing push notification:', error)
  }
})

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
  console.log('[SW] Notification clicked:', event.notification.tag)
  event.notification.close()

  const urlToOpen = event.notification.data?.click_action || '/'

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      // Try to find existing window
      for (let i = 0; i < clientList.length; i++) {
        const client = clientList[i]
        if (client.url === urlToOpen && 'focus' in client) {
          return client.focus()
        }
      }
      // Open new window if not found
      return clients.openWindow(urlToOpen)
    })
  )
})

// Handle notification close
self.addEventListener('notificationclose', (event) => {
  console.log('[SW] Notification closed:', event.notification.tag)
})

console.log('[SW] Service worker script loaded')

// T.O.M.E. Service Worker
// Handles both PWA functionality and Firebase Cloud Messaging

// Bump the version to drop all older caches on activate.
const CACHE_NAME = 'tome-v2'

// App shell, precached on install
const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/manifest.json',
]

/**
 * Decides how a request is handled. Pure function (unit tested).
 *  - 'network-only': not intercepted at all (non-GET, other origins, /api/, /matomo/, anything that is not static)
 *  - 'cache-first':  hashed build files under /assets/ (never change for a given URL)
 *  - 'network-first': app shell (navigations, index.html, manifest, icons), cached copy only as offline fallback
 * API responses contain personal data and auth dependent content and must never be cached.
 */
function routeRequest(method, urlString, origin, mode) {
  if (method !== 'GET') return 'network-only'
  let url
  try {
    url = new URL(urlString)
  } catch (e) {
    return 'network-only'
  }
  if (url.origin !== origin) return 'network-only'
  const path = url.pathname
  if (path === '/api' || path.startsWith('/api/')) return 'network-only'
  // Matomo (analytics server and admin pages) is never cached
  if (path === '/matomo' || path.startsWith('/matomo/')) return 'network-only'
  if (path.startsWith('/assets/')) return 'cache-first'
  if (mode === 'navigate' || path === '/' || path === '/index.html' || path === '/manifest.json' || path.startsWith('/img/')) {
    return 'network-first'
  }
  return 'network-only'
}

// Install event - cache the app shell
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)).catch(() => {})
  )
  self.skipWaiting() // Activate immediately
})

// Activate event - delete every cache but the current one
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) =>
      Promise.all(cacheNames.filter((name) => name !== CACHE_NAME).map((name) => caches.delete(name)))
    ).then(() => self.clients.claim())
  )
})

function storeCopy(request, response) {
  // the clone has to be taken BEFORE the response is handed to the page (its body is single-use)
  const copy = response.clone()
  return caches.open(CACHE_NAME).then((cache) => cache.put(request, copy)).catch(() => {})
}

self.addEventListener('fetch', (event) => {
  const request = event.request
  const strategy = routeRequest(request.method, request.url, self.location.origin, request.mode)
  if (strategy === 'network-only') return // let the browser handle it, nothing is cached

  if (strategy === 'cache-first') {
    event.respondWith(
      caches.match(request).then((cached) => {
        if (cached) return cached
        return fetch(request).then((response) => {
          if (response.ok) event.waitUntil(storeCopy(request, response))
          return response
        })
      })
    )
    return
  }

  // network-first
  event.respondWith(
    fetch(request)
      .then((response) => {
        if (response.ok) event.waitUntil(storeCopy(request, response))
        return response
      })
      .catch(() =>
        caches.match(request).then((cached) => cached || (request.mode === 'navigate' ? caches.match('/index.html') : null)).then((cached) =>
          cached || new Response('Offline - resource not available', {
            status: 503,
            statusText: 'Service Unavailable',
            headers: new Headers({ 'Content-Type': 'text/plain' }),
          })
        )
      )
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

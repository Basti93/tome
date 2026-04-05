// This service worker is required for Firebase Cloud Messaging
// It handles notifications when the app is in the background

importScripts('https://www.gstatic.com/firebasejs/10.8.0/firebase-app.compat.js')
importScripts('https://www.gstatic.com/firebasejs/10.8.0/firebase-messaging-compat.js')

// Firebase is initialized by the main app, we just need to get the messaging instance
let messaging = null

// Try to get the messaging instance (initialized by main app)
try {
  messaging = firebase.messaging()
} catch (error) {
  console.error('Firebase Messaging not initialized:', error)
}

// Handle background messages
if (messaging) {
  messaging.onBackgroundMessage((payload) => {
    console.log('Background message received:', payload)
    const notificationTitle = payload.data?.title || payload.notification?.title || 'T.O.M.E. Benachrichtigung'
    const notificationOptions = {
      body: payload.data?.body || payload.notification?.body || '',
      icon: '/icon-192x192.png',
      badge: '/icon-192x192.png',
      tag: 'tome-notification',
      requireInteraction: false,
    }

    if (payload.data?.click_action) {
      notificationOptions.data = {
        click_action: payload.data.click_action,
      }
    }

    self.registration.showNotification(notificationTitle, notificationOptions)
  })
} else {
  console.warn('Firebase Messaging not available in service worker')
}

// Handle notification click
self.addEventListener('notificationclick', (event) => {
  event.notification.close()
  const urlToOpen = event.notification.data?.click_action || '/'
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      // Check if there's already a window/tab open with the target URL
      for (let i = 0; i < clientList.length; i++) {
        if (clientList[i].url === urlToOpen && 'focus' in clientList[i]) {
          return clientList[i].focus()
        }
      }
      // If not, open a new window/tab
      if (clients.openWindow) {
        return clients.openWindow(urlToOpen)
      }
    })
  )
})

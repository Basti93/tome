//firebase shiat
importScripts('https://www.gstatic.com/firebasejs/4.8.1/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/4.8.1/firebase-messaging.js');
firebase.initializeApp({
    'messagingSenderId': '565456517775' //Refer to project id in console.cloud.google.com or console.firebase.google.com
});
const messaging = firebase.messaging();

messaging.setBackgroundMessageHandler(function (payload) {
    console.log('[firebase-messaging-sw.js] Received background message ', payload);
    // Customize notification here
    const notificationTitle = payload.data.title;
    const notificationOptions = {
        body: payload.data.body,
        data: { url: payload.data.click_action },
        icon: './img/icons/android-chrome-192x192.png',
        badge: './img/icons/apple-touch-icon-76x-76.png'
    };


    return self.registration.showNotification(notificationTitle,
        notificationOptions);
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(
        clients.openWindow('/')
    );
});

//other service worker

// install new service worker when ok, then reload page.
self.addEventListener("message", msg=>{
    if (msg.data.action=='skipWaiting'){
        self.skipWaiting()
    }
})

workbox.routing.registerRoute(
    new RegExp('/js/.*\.js'),
    new workbox.strategies.CacheFirst()
);
workbox.routing.registerRoute(
    new RegExp('/css/.*\.css'),
    new workbox.strategies.CacheFirst()
);

workbox.routing.registerRoute(
    new RegExp('/fonts/.*'),
    new workbox.strategies.CacheFirst()
);
workbox.routing.registerRoute(
    new RegExp('/img/.*'),
    new workbox.strategies.CacheFirst()
);



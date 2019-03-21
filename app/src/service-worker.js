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

workbox.routing.registerRoute(
    new RegExp('/'),
    new workbox.strategies.CacheFirst()
);

// T.O.M.E. KILL-SWITCH Service Worker
//
// Die alte Vue-2-App (Vue CLI + Workbox) hat unter genau diesem Pfad (/service-worker.js) einen
// Service Worker registriert, der die alte App aus dem Cache ausliefert. Die neue App registriert
// diesen Pfad NICHT selbst (sie nutzt nur /firebase-messaging-sw.js fuer Push). Diese Datei wird
// ausschliesslich vom Browser im Rahmen des normalen Update-Checks des alten Workers abgeholt:
//
//   1. install:  sofort aktivieren (skipWaiting), nicht auf das Schliessen alter Tabs warten.
//   2. activate: alle Caches loeschen, sich selbst deregistrieren, offene Fenster neu laden.
//
// Danach laeuft die neue App ohne Service Worker dieses Pfads. Die Datei kann nach einigen
// Monaten entfernt werden (siehe deploy/RUNBOOK.md).

self.addEventListener('install', () => {
  self.skipWaiting()
})

self.addEventListener('activate', (event) => {
  event.waitUntil(
    (async () => {
      try {
        const keys = await caches.keys()
        await Promise.all(keys.map((key) => caches.delete(key)))
      } catch (e) {
        // Caches nicht verfuegbar: nichts zu tun
      }

      try {
        await self.registration.unregister()
      } catch (e) {
        // ignorieren, der naechste Update-Check versucht es erneut
      }

      try {
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true })
        await Promise.all(
          windows.map((client) => client.navigate(client.url).catch(() => undefined))
        )
      } catch (e) {
        // ignorieren
      }
    })()
  )
})

// Kein fetch-Handler: Anfragen gehen immer direkt ans Netzwerk.

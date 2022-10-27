import { register } from 'register-service-worker'

if (process.env.NODE_ENV === 'production') {
  register(`${process.env.BASE_URL}firebase-messaging-sw.js`, {
    ready () {
      console.log(
          'App is being served from cache by a service worker.\n' +
          'For more details, visit https://goo.gl/AFskqB'
      )
    },
    registered () {
      console.log('Service worker has been registered.')
    },
    cached () {
      console.log('Content has been cached for offline use.')
    },
    updatefound () {
      console.log('New content is downloading.')
    },
    updated () {
      console.log('New content is available; please refresh.')
      let updateBanner = document.querySelector('#update-banner');
      if(updateBanner) {
        updateBanner.style.display = '';
      }
    },
    offline () {
      console.log('No internet connection found. App is running in offline mode.')
    },
    error (error) {
      console.error('Error during service worker registration:', error)
    }
  })

  let refreshing
  navigator.serviceWorker.addEventListener("controllerchange", () => {
    if (refreshing) return
    window.location.reload()
    refreshing = true
  })
}

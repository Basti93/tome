import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'
import axios from './axios'
import { useMasterDataStore } from './store/masterData'

async function init() {
  const app = createApp(App)
  const pinia = createPinia()

  app.use(pinia)
  app.use(router)
  app.use(vuetify)

  const masterData = useMasterDataStore()
  try {
    await masterData.loadAll(axios)
  } catch (e) {
    // offline — mount anyway
  }

  app.mount('#app')
}

init()

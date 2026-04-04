import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'
import httpClient from './http/api'
import { useMasterDataStore } from './store/masterData'
import VueApexCharts from 'vue3-apexcharts'

async function init() {
  const app = createApp(App)
  const pinia = createPinia()

  app.use(pinia)
  app.use(router)
  app.use(vuetify)
  app.component('apexchart', VueApexCharts)

  const masterData = useMasterDataStore()
  try {
    await masterData.loadAll(httpClient)
  } catch (e) {
    // offline — mount anyway
  }

  app.mount('#app')
}

init()

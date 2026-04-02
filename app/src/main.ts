import { createApp } from 'vue'
import App from './App.vue'
import vuetify from './plugins/vuetify'
import * as moment from 'moment'
import axios from './axios'
import store from './store'
import router from './router'
import VueApexCharts from 'vue-apexcharts'
import './registerServiceWorker'
import Branch from './models/Branch'
import Group from './models/Group'
import TrainingSeries from './models/TrainingSeries'
import SimpleUser from './models/SimpleUser'

// Create app instance
const app = createApp(App)

// Global properties (Vue 3 way instead of Vue.prototype)
app.config.globalProperties.$moment = moment
app.config.globalProperties.$http = axios

// Register plugins
app.use(vuetify)
app.use(store)
app.use(router)

// Register ApexCharts component
app.component('apexchart', VueApexCharts)

// Development config
app.config.productionTip = false

// Initialize data before mounting
const init = async () => {
  try {
    const branchsPromise = axios.get('/branch')
    const groupsPromise = axios.get('/group')
    const locationsPromise = axios.get('/location')
    const contentsPromise = axios.get('/content')
    const trainersPromise = axios.get('/simpleuser/trainers')
    const usersPromise = axios.get('/simpleuser')
    const trainingSeriesPromise = axios.get('/trainingSeries')

    const [resBranches, resGroups, locations, contents, trainers, resUsers, resTrainingSeries] = await Promise.all([
      branchsPromise,
      groupsPromise,
      locationsPromise,
      contentsPromise,
      trainersPromise,
      usersPromise,
      trainingSeriesPromise
    ])

    const branches = resBranches.data.data.map(b => new Branch(b.id, b.name, b.shortName, b.colorHex))
    store.commit('masterData/setBranches', branches)
    store.commit('masterData/setGroups', resGroups.data.data.map(g => new Group(g.id, g.name, g.branchId, branches.filter(b => b.id == g.branchId)[0], g.userIds)))
    store.commit('masterData/setLocations', locations.data.data)
    store.commit('masterData/setContents', contents.data)
    store.commit('masterData/setSimpleTrainers', trainers.data.data)
    store.commit('masterData/setSimpleUsers', resUsers.data.data.map(u => new SimpleUser(u.id, u.firstName, u.familyName, u.groupIds)))
    store.commit('masterData/setTrainingSeries', resTrainingSeries.data.data.map(ts => TrainingSeries.from(ts)))
  } catch (e) {
    console.error('Could not load initial data', e)
    app.config.globalProperties.$isOffline = true
  } finally {
    app.mount('#app')
  }
}

// Start initialization
init()

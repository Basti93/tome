// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import Moment from 'moment'
import App from './App'
import axios from './axios'
import Vuetify from 'vuetify'
import 'material-design-icons-iconfont/dist/material-design-icons.css'
import 'vuetify/dist/vuetify.min.css'
import store from "./store";
import router from './router'
import de from 'vuetify/src/locale/de.ts'
import VueApexCharts from 'vue-apexcharts'
import Siema from 'vue2-siema'

Vue.prototype.moment = Moment

Vue.use(Vuetify, {
  lang: {
    locales: {de},
    current: 'de'
  },
  theme: {
    primary: "#60cc69",
  }
})

Vue.use(VueApexCharts)
Vue.use(Siema)

Vue.component('apexchart', VueApexCharts)

Vue.config.productionTip = false

var p1 = axios.get('/branch').then(function (response) {
  store.commit('masterData/setBranches', response.data)
}.bind(this));
var p2 = axios.get('/group').then(function (response) {
  store.commit('masterData/setGroups', response.data)
}.bind(this));
var p3 = axios.get('/location').then(function (response) {
  store.commit('masterData/setLocations', response.data)
});
var p4 = axios.get('/content').then(function (response) {
  store.commit('masterData/setContents', response.data)
});

Promise.all([p1, p2, p3, p4]).then(function () {
  /* eslint-disable no-new */
  new Vue({
    axios,
    router,
    store,
    render: h => h(App),
  }).$mount('#app')
});



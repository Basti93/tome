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

Vue.component('apexchart', VueApexCharts)

Vue.mixin({
  methods: {
    setCookie(name, value, days) {
      var expires = "";
      if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
      }
      document.cookie = name + "=" + (value || "") + expires + "; path=/";
    },
    getCookie(name) {
      var nameEQ = name + "=";
      var ca = document.cookie.split(';');
      for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
      }
      return null;
    },
    eraseCookie(name) {
      document.cookie = name + '=; Max-Age=-99999999;';
    }
  }
})

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

router.replace('/')



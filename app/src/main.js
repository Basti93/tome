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
import './registerServiceWorker'

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


const init = async () => {
  try {
    const branchsPromise = axios.get('/branch');
    const groupsPromise = axios.get('/group');
    const locationsPromise = axios.get('/location');
    const contentsPromise = axios.get('/content');

    const [branches, groups, locations, contents] = await Promise.all([branchsPromise, groupsPromise, locationsPromise, contentsPromise]);

    store.commit('masterData/setBranches', branches.data);
    store.commit('masterData/setGroups', groups.data);
    store.commit('masterData/setLocations', locations.data);
    store.commit('masterData/setContents', contents.data);
  } catch (e) {
    console.error("Could not load initial data")
    Vue.prototype.$isOffline = true;
  } finally {
    new Vue({
      axios,
      router,
      store,
      render: h => h(App),
    }).$mount('#app')
    router.replace('/')
  }
};

init();

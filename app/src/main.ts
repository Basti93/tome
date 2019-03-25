// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import Moment from 'moment'
import App from './App.vue'
import axios from './axios'
import Vuetify from 'vuetify'
import 'material-design-icons-iconfont/dist/material-design-icons.css'
import 'vuetify/dist/vuetify.min.css'
import store from "./store";
import router from './router'
import de from 'vuetify/src/locale/de'
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
    // @ts-ignore
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

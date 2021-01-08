import Vue from 'vue'
import App from './App.vue'
import vuetify from './plugins/vuetify';
import * as moment from 'moment'
import axios from './axios'
import store from "./store";
import router from './router'
import VueApexCharts from 'vue-apexcharts'
import './registerServiceWorker'
import Branch from "./models/Branch";
import Group from "@/models/Group";


Vue.prototype.moment = moment

Vue.use(VueApexCharts)
Vue.component('apexchart', VueApexCharts)

Vue.config.productionTip = false

const init = async () => {
  try {
    const branchsPromise = axios.get('/branch');
    const groupsPromise = axios.get('/group');
    const locationsPromise = axios.get('/location');
    const contentsPromise = axios.get('/content');
    const trainersPromise = axios.get('/simpleuser/trainers');

    const [resBranches, resGroups, locations, contents, trainers] = await Promise.all([branchsPromise, groupsPromise, locationsPromise, contentsPromise, trainersPromise]);

    const branches = resBranches.data.map(b => new Branch(b.id, b.name, b.shortName));
    store.commit('masterData/setBranches', branches);
    store.commit('masterData/setGroups', resGroups.data.map(g => new Group(g.id, g.name, g.branchId, branches.filter(b => b.id == g.branchId)[0])));
    store.commit('masterData/setLocations', locations.data);
    store.commit('masterData/setContents', contents.data);
    store.commit('masterData/setSimpleTrainers', trainers.data.data);
  } catch (e) {
    console.error("Could not load initial data", e)
    Vue.prototype.$isOffline = true;
  } finally {
    // @ts-ignore

    new Vue({
      vuetify,
      axios,
      router,
      store,
      render: h => h(App),
    }).$mount('#app')
  }
};

init();

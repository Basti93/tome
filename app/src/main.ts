import Vue from 'vue'
import App from './App.vue'
import vuetify from './plugins/vuetify';
import Moment from 'moment'
import axios from './axios'
import store from "./store";
import router from './router'
import VueApexCharts from 'vue-apexcharts'
import './registerServiceWorker'
import firebase from "firebase/app";
import "firebase/messaging";


Vue.prototype.moment = Moment

Vue.use(VueApexCharts)
Vue.component('apexchart', VueApexCharts)

Vue.config.productionTip = false

// Initialize Firebase
var config = {
  apiKey: "AIzaSyCIyjn5HT_cWEZx1V3fuc1wnUUHA2EIHtA",
  authDomain: "ssc-tome.firebaseapp.com",
  databaseURL: "https://ssc-tome.firebaseio.com",
  projectId: "ssc-tome",
  storageBucket: "ssc-tome.appspot.com",
  messagingSenderId: "565456517775"
};
firebase.initializeApp(config);
Vue.use(firebase);


//load all the base data
const init = async () => {
  try {
    const branchsPromise = axios.get('/branch');
    const groupsPromise = axios.get('/group');
    const locationsPromise = axios.get('/location');
    const contentsPromise = axios.get('/content');
    const trainersPromise = axios.get('/simpleuser/trainers');

    const [branches, groups, locations, contents, trainers] = await Promise.all([branchsPromise, groupsPromise, locationsPromise, contentsPromise, trainersPromise]);

    store.commit('masterData/setBranches', branches.data);
    store.commit('masterData/setGroups', groups.data);
    store.commit('masterData/setLocations', locations.data);
    store.commit('masterData/setContents', contents.data);
    store.commit('masterData/setSimpleTrainers', trainers.data.data);
  } catch (e) {
    console.error("Could not load initial data")
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
    router.replace('/')
  }
};

init();

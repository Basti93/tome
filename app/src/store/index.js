import Vue from 'vue'
import Vuex from 'vuex'
import auth from './auth'
import masterData from './masterData'
import snackbar from './snackbar'

Vue.use(Vuex);

const state = {
  hostname: 'http://localhost:8000/api',
}


export default new Vuex.Store({
  state,
  modules: {
    auth,
    masterData,
    snackbar,
  }
})

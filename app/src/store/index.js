import Vue from 'vue'
import Vuex from 'vuex'
import auth from './auth'
import cookieAuth from './cookieAuth'
import masterData from './masterData'
import snackbar from './snackbar'

Vue.use(Vuex);

export default new Vuex.Store({
  modules: {
    auth,
    cookieAuth,
    masterData,
    snackbar,
  }
})

import { createStore } from 'vuex'
import auth from './auth'
import cookieAuth from './cookieAuth'
import masterData from './masterData'
import snackbar from './snackbar'

export default createStore({
  modules: {
    auth,
    cookieAuth,
    masterData,
    snackbar
  }
})

import User from '@/models/User'
import * as MutationTypes from './mutation-types'
import { setCookie, getCookie, eraseCookie } from "../helpers/cookie-helper"

const state = {
  cookieUser: User.from(getCookie('cookieUser'))
}

const mutations = {
  [MutationTypes.SELECT_COOKIE_USER] (state, { cookieUser }) {
    state.cookieUser = User.from(cookieUser)
    setCookie('cookieUser', cookieUser)
  },
  [MutationTypes.ERASE_COOKIE_USER] (state) {
    state.cookieUser = null
    eraseCookie('cookieUser')
  }
}

const getters = {
  cookieUser (state) {
    return state.cookieUser
  }
}

const actions = {
  selectCookieUser ({ commit }, payload) {
    commit(MutationTypes.SELECT_COOKIE_USER, payload)
  },

  eraseCookieUser ({ commit }) {
    commit(MutationTypes.ERASE_COOKIE_USER)
  }
}

export default {
  state,
  mutations,
  getters,
  actions
}

import User from '@/models/User'
import * as MutationTypes from './mutation-types'

const state = {
  user: User.from(localStorage.user),
  token: localStorage.token
}

const mutations = {
  [MutationTypes.LOGIN] (state, {
    token,
    user
  }) {
    state.token = token
    state.user = User.from(user)
    localStorage.token = token
    localStorage.user = user
  },
  [MutationTypes.UPDATE_USER] (state, { user }) {
    state.user = User.from(user)
    localStorage.user = user
  },
  [MutationTypes.LOGOUT] (state) {
    state.user = null
    state.token = null
    delete localStorage.token
    delete localStorage.user
  }
}

const getters = {
  loggedInUser (state) {
    return state.user
  },
  loggedInUserToken (state) {
    return state.token
  }
}

const actions = {
  login ({ commit }, payload) {
    commit(MutationTypes.LOGIN, payload)
  },
  updateUser ({ commit }, payload) {
    commit(MutationTypes.UPDATE_USER, payload)
  },
  logout ({ commit }) {
    commit(MutationTypes.LOGOUT)
  }
}

export default {
  state,
  mutations,
  getters,
  actions
}

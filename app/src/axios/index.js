import Vue from 'vue'
import store from '@/store'
import router from '@/router'
import axios from 'axios'
import VueAxios from 'vue-axios'


Vue.use(VueAxios, axios)

//axios configuration
Vue.axios.defaults.baseURL = process.env.VUE_APP_API_URL;
Vue.axios.defaults.headers.common = {'Content-Type': 'application/json'}

//add token to all requests
Vue.axios.interceptors.request.use(
  (config) => {
    let token = localStorage.token
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
);


// token refresh logic

let isAlreadyFetchingAccessToken = false
let subscribers = []

function onAccessTokenFetched(access_token) {
  subscribers = subscribers.filter(callback => callback(access_token))
}

function addSubscriber(callback) {
  subscribers.push(callback)
}

Vue.axios.interceptors.response.use(response => {
  return response;
}, error => {
  if (!error.response) {
    //network error
    return Promise.reject(error)
  }
  const status = error.response ? error.response.status : null
  const originalRequest = error.response ? error.response.config : null

  if (status === 401) {
    if (!isAlreadyFetchingAccessToken) {
      isAlreadyFetchingAccessToken = true
      Vue.axios.post('/auth/refresh').then(function (response) {
        isAlreadyFetchingAccessToken = false
        if (!response.data.error && response.data.token) {
          localStorage.token = response.data.token
          onAccessTokenFetched(response.data.token)
        } else {
          delete localStorage.token
          delete localStorage.user
          store.dispatch('logout')
          router.push('/login')
        }
      }).catch(function () {
        delete localStorage.token
        delete localStorage.user
        store.dispatch('logout')
        router.push('/login')
      });
    }

    const retryOriginalRequest = new Promise((resolve) => {
      addSubscriber(access_token => {
        originalRequest.headers.Authorization = 'Bearer ' + access_token
        resolve(Vue.axios(originalRequest))
      })
    })
    return retryOriginalRequest
  }
  return Promise.reject(error)
});

export default axios;

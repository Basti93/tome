import axios from 'axios'
import { useAuthStore } from '@/store/auth'

// axios configuration
axios.defaults.baseURL = import.meta.env.VITE_APP_API_URL || 'http://localhost:8000/api/v1'
axios.defaults.headers.common = {'Content-Type': 'application/json'}
axios.defaults.withCredentials = true

// token refresh logic
let isAlreadyFetchingAccessToken = false
let subscribers = []

function onAccessTokenFetched() {
  subscribers.forEach(callback => callback())
  subscribers = []
}

function addSubscriber(callback) {
  subscribers.push(callback)
}

axios.interceptors.response.use(response => {
  return response
}, error => {
  if (!error.response) {
    // network error or login page
    return Promise.reject(error)
  }

  const status = error.response?.status
  const originalRequest = error.config

  if (status === 401) {
    if (!isAlreadyFetchingAccessToken) {
      isAlreadyFetchingAccessToken = true
      axios.post('/auth/refresh').then(function (response) {
        isAlreadyFetchingAccessToken = false
        if (response.data.status === 'ok') {
          onAccessTokenFetched()
        } else {
          const authStore = useAuthStore()
          authStore.logout()
          window.location.href = '/login'
        }
      }).catch(function () {
        isAlreadyFetchingAccessToken = false
        const authStore = useAuthStore()
        authStore.logout()
        window.location.href = '/login'
      })
    }

    const retryOriginalRequest = new Promise((resolve) => {
      addSubscriber(() => {
        resolve(axios(originalRequest))
      })
    })
    return retryOriginalRequest
  }
  return Promise.reject(error.response)
})

export default axios

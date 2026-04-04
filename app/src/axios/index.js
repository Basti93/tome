import axios from 'axios'
import { useAuthStore } from '@/store/auth'

// axios configuration
axios.defaults.baseURL = import.meta.env.VITE_APP_API_URL || 'http://localhost:8000/api/v1'
axios.defaults.headers.common = {'Content-Type': 'application/json'}

// add token to all requests
axios.interceptors.request.use(
  (config) => {
    const authStore = useAuthStore()
    const token = authStore.token
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// token refresh logic
let isAlreadyFetchingAccessToken = false
let subscribers = []

function onAccessTokenFetched(access_token) {
  subscribers = subscribers.filter(callback => callback(access_token))
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
        if (!response.data.error && response.data.token) {
          const authStore = useAuthStore()
          authStore.token = response.data.token
          localStorage.setItem('token', response.data.token)
          onAccessTokenFetched(response.data.token)
        } else {
          const authStore = useAuthStore()
          authStore.logout()
          // Redirect via window.location as router may not be available
          window.location.href = '/login'
        }
      }).catch(function () {
        const authStore = useAuthStore()
        authStore.logout()
        // Redirect via window.location as router may not be available
        window.location.href = '/login'
      })
    }

    const retryOriginalRequest = new Promise((resolve) => {
      addSubscriber(access_token => {
        originalRequest.headers.Authorization = 'Bearer ' + access_token
        resolve(axios(originalRequest))
      })
    })
    return retryOriginalRequest
  }
  return Promise.reject(error.response)
})

export default axios

import axios, { AxiosInstance } from 'axios'
import store from '@/store'
import router from '@/router'

// Create axios instance
const instance: AxiosInstance = axios.create({
  baseURL: process.env.VUE_APP_API_URL || 'http://127.0.0.1:8000/api'
})

// axios configuration
instance.defaults.headers.common = { 'Content-Type': 'application/json' }

// Token refresh logic
let isAlreadyFetchingAccessToken = false
let subscribers: Array<(token: string) => void> = []

function onAccessTokenFetched(access_token: string) {
  subscribers = subscribers.filter(callback => {
    callback(access_token)
    return false
  })
}

function addSubscriber(callback: (token: string) => void) {
  subscribers.push(callback)
}

// Request interceptor - add auth token
instance.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor - handle 401 token refresh
instance.interceptors.response.use(
  (response) => {
    return response
  },
  (error) => {
    if (!error.response) {
      // network error or login page
      return Promise.reject(error)
    }

    const status = error.response?.status
    const originalRequest = error.config

    if (status === 401 && originalRequest && !originalRequest._retry) {
      if (isAlreadyFetchingAccessToken) {
        return new Promise((resolve) => {
          addSubscriber((access_token: string) => {
            originalRequest.headers['Authorization'] = `Bearer ${access_token}`
            resolve(instance(originalRequest))
          })
        })
      }

      originalRequest._retry = true
      isAlreadyFetchingAccessToken = true

      return instance
        .post('/auth/refresh')
        .then((response) => {
          isAlreadyFetchingAccessToken = false
          if (!response.data.error && response.data.token) {
            const token = response.data.token
            localStorage.setItem('token', token)
            instance.defaults.headers.common['Authorization'] = `Bearer ${token}`
            onAccessTokenFetched(token)
            originalRequest.headers['Authorization'] = `Bearer ${token}`
            return instance(originalRequest)
          } else {
            store.dispatch('logout')
            router.push('/login')
            return Promise.reject(response)
          }
        })
        .catch(() => {
          isAlreadyFetchingAccessToken = false
          store.dispatch('logout')
          router.push('/login')
          return Promise.reject(error)
        })
    }

    return Promise.reject(error)
  }
)

export default instance

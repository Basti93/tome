import { useAuthStore } from '@/store/auth'
import { useSnackbarStore } from '@/store/snackbar'

// In Docker: use relative URL (nginx proxies /api/* to Laravel)
// In local dev: use relative URL (Vite dev server proxies /api/*)
// Override: VITE_API_URL environment variable (for absolute URLs if needed)
const API_BASE_URL = import.meta.env.VITE_API_URL || import.meta.env.VITE_APP_API_URL || '/api/v1'

// Token refresh state
let isAlreadyFetchingAccessToken = false
let subscribers: Array<() => void> = []

function onAccessTokenFetched() {
  subscribers.forEach(callback => callback())
  subscribers = []
}

function addSubscriber(callback: () => void) {
  subscribers.push(callback)
}

interface RequestConfig {
  method?: string
  headers?: Record<string, string>
  body?: BodyInit
  signal?: AbortSignal
  /** 'blob' returns the body of a successful response as a Blob (file downloads) */
  responseType?: 'json' | 'blob'
}

interface FetchResponse<T = any> {
  data: T
  status: number
  statusText: string
  headers?: Headers
}

interface ApiError extends Error {
  response: {
    status: number
    statusText: string
    data: any
  }
  config: any
}

async function request<T = any>(
  url: string,
  config: RequestConfig = {}
): Promise<FetchResponse<T>> {
  const normalizedUrl = url.startsWith('/') ? url : `/${url}`
  const fullUrl = url.startsWith('http') ? url : `${API_BASE_URL}${normalizedUrl}`

  // Debug logging
  console.log('[HTTP Client]', {
    baseUrl: API_BASE_URL,
    path: url,
    fullUrl: fullUrl,
    method: config.method || 'GET'
  })

  // Setup headers - don't set Content-Type for FormData
  // The API always answers JSON. Without this header Laravel redirects failed validations, fetch follows the
  // redirect and the SPA's index.html comes back as a "successful" response. Downloads accept anything.
  const headers: Record<string, string> = {
    Accept: config.responseType === 'blob' ? '*/*' : 'application/json',
    ...config.headers,
  }

  // Only set Content-Type if not FormData (browser will set it for FormData)
  if (!(config.body instanceof FormData)) {
    headers['Content-Type'] = 'application/json'
  }

  const fetchConfig: RequestInit = {
    method: config.method || 'GET',
    headers,
    credentials: 'include',
    // never follow redirects silently: the API does not redirect, so a redirect is an error
    redirect: 'manual',
  }

  // Add body if present
  if (config.body) {
    fetchConfig.body = config.body
  }

  // Add signal if present
  if (config.signal) {
    fetchConfig.signal = config.signal
  }

  const response = await fetch(fullUrl, fetchConfig)

  if (response.type === 'opaqueredirect') {
    useSnackbarStore().show('Unerwartete Antwort vom Server (Weiterleitung)', 'error')
    throw createApiError({ status: 0, statusText: 'redirect' } as Response, null, config)
  }

  // Parse response body
  let responseData: any
  const contentType = response.headers.get('content-type')
  if (config.responseType === 'blob' && response.ok) {
    responseData = await response.blob()
  } else if (contentType && contentType.includes('application/json')) {
    responseData = await response.json()
  } else {
    responseData = await response.text()
  }

  // A JSON request answered with an HTML page (e.g. the SPA index.html) is not a success
  if (response.ok && config.responseType !== 'blob' && contentType && contentType.includes('text/html')) {
    useSnackbarStore().show('Unerwartete Antwort vom Server', 'error')
    throw createApiError(response, responseData, config)
  }

  // Handle successful response
  if (response.ok) {
    console.log('[HTTP Response]', {
      url: fullUrl,
      status: response.status,
      data: config.responseType === 'blob' ? '[Blob]' : responseData
    })
    return {
      data: responseData,
      status: response.status,
      statusText: response.statusText,
      headers: response.headers,
    }
  }

  // Handle 401 - token refresh
  if (response.status === 401) {
    if (!isAlreadyFetchingAccessToken) {
      isAlreadyFetchingAccessToken = true

      try {
        const refreshResponse = await fetch(`${API_BASE_URL}/auth/refresh`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
          credentials: 'include',
        })

        isAlreadyFetchingAccessToken = false

        if (refreshResponse.ok) {
          const refreshData = await refreshResponse.json()
          if (refreshData.status === 'ok') {
            onAccessTokenFetched()
            // Retry original request
            return request<T>(url, config)
          } else {
            const authStore = useAuthStore()
            authStore.logout()
            window.location.href = '/login'
            throw createApiError(response, responseData, config)
          }
        } else {
          isAlreadyFetchingAccessToken = false
          const authStore = useAuthStore()
          authStore.logout()
          window.location.href = '/login'
          throw createApiError(response, responseData, config)
        }
      } catch (error) {
        isAlreadyFetchingAccessToken = false
        const authStore = useAuthStore()
        authStore.logout()
        window.location.href = '/login'
        throw error
      }
    }

    // Queue request while token is being refreshed
    return new Promise((resolve, reject) => {
      addSubscriber(async () => {
        try {
          const result = await request<T>(url, config)
          resolve(result)
        } catch (error) {
          reject(error)
        }
      })
    })
  }

  // Handle other error responses
  throw createApiError(response, responseData, config)
}

function createApiError(
  response: Response,
  data: any,
  config: RequestConfig
): ApiError {
  const error = new Error(`HTTP ${response.status}`) as ApiError
  error.response = {
    status: response.status,
    statusText: response.statusText,
    data,
  }
  error.config = config
  return error
}

// HTTP method shortcuts
const httpClient = {
  get<T = any>(url: string, config?: RequestConfig): Promise<FetchResponse<T>> {
    return request<T>(url, { ...config, method: 'GET' })
  },

  post<T = any>(
    url: string,
    data?: any,
    config?: RequestConfig
  ): Promise<FetchResponse<T>> {
    // Determine body based on data type
    let body: BodyInit | undefined
    if (data instanceof FormData) {
      body = data
    } else if (data) {
      body = JSON.stringify(data)
    }

    return request<T>(url, {
      ...config,
      method: 'POST',
      body,
    })
  },

  put<T = any>(
    url: string,
    data?: any,
    config?: RequestConfig
  ): Promise<FetchResponse<T>> {
    return request<T>(url, {
      ...config,
      method: 'PUT',
      body: data ? JSON.stringify(data) : undefined,
    })
  },

  patch<T = any>(
    url: string,
    data?: any,
    config?: RequestConfig
  ): Promise<FetchResponse<T>> {
    return request<T>(url, {
      ...config,
      method: 'PATCH',
      body: data ? JSON.stringify(data) : undefined,
    })
  },

  delete<T = any>(url: string, config?: RequestConfig): Promise<FetchResponse<T>> {
    return request<T>(url, { ...config, method: 'DELETE' })
  },

  request: request,
}

export default httpClient

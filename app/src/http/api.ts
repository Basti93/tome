import { useAuthStore } from '@/store/auth'
import { useSnackbarStore } from '@/store/snackbar'

// In Docker: use relative URL (nginx proxies /api/* to Laravel)
// In local dev: use relative URL (Vite dev server proxies /api/*)
// Override: VITE_API_URL environment variable (for absolute URLs if needed)
const API_BASE_URL = import.meta.env.VITE_API_URL || import.meta.env.VITE_APP_API_URL || '/api/v1'

// Sanctum SPA authentication: the session cookie is HttpOnly (set by the login), unsafe requests carry the CSRF token
// of the `XSRF-TOKEN` cookie in the `X-XSRF-TOKEN` header. The cookie is fetched from `<api>/sanctum/csrf-cookie`.
const API_ROOT = API_BASE_URL.replace(/\/v1\/?$/, '')

function xsrfToken(): string | null {
  const match = document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='))
  return match ? decodeURIComponent(match.slice('XSRF-TOKEN='.length)) : null
}

let csrfRequest: Promise<void> | null = null
function fetchCsrfCookie(): Promise<void> {
  // one request at a time, also when several calls start together
  csrfRequest ??= fetch(`${API_ROOT}/sanctum/csrf-cookie`, { credentials: 'include', headers: { Accept: 'application/json' } })
    .then(() => undefined)
    .finally(() => { csrfRequest = null })
  return csrfRequest
}

interface RequestConfig {
  method?: string
  headers?: Record<string, string>
  body?: BodyInit
  signal?: AbortSignal
  /** 'blob' returns the body of a successful response as a Blob (file downloads) */
  responseType?: 'json' | 'blob'
  /** on 401: just reject (no logout, no redirect to the login page); used for session probes */
  silent401?: boolean
  /** internal: the request was already repeated after a CSRF failure (419) */
  csrfRetried?: boolean
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

/**
 * Sends the user to the login page and remembers where they were (`redirect`, read by the login page).
 * The router uses hash history, so the login page lives at `/#/login`.
 */
export function redirectToLogin() {
  const current = (window.location.hash || '#/').slice(1)
  // already on the login page (several requests fail at once): keep the first ?redirect=
  if (current.startsWith('/login')) return
  window.location.href = `/#/login?redirect=${encodeURIComponent(current)}`
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

  // unsafe methods need the CSRF token (also for anonymous calls such as check-in: they come from the SPA's host too)
  const method = (config.method || 'GET').toUpperCase()
  if (method !== 'GET' && method !== 'HEAD') {
    if (!xsrfToken()) await fetchCsrfCookie()
    const token = xsrfToken()
    if (token) headers['X-XSRF-TOKEN'] = token
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

  // CSRF token expired or missing (e.g. after a logout in another tab): get a new one and try once more
  if (response.status === 419 && !config.csrfRetried) {
    await fetchCsrfCookie()
    return request<T>(url, { ...config, csrfRetried: true })
  }

  // No (valid) session. Session probes (silent401) just reject; otherwise the user has to log in again and comes back.
  if (response.status === 401 && !config.silent401) {
    useAuthStore().logout()
    redirectToLogin()
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

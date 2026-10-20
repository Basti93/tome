import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import httpClient from '@/http/api'
import { useAuthStore } from '@/store/auth'
import { useSnackbarStore } from '@/store/snackbar'

function jsonResponse(body: unknown, status = 200) {
  return new Response(JSON.stringify(body), { status, headers: { 'content-type': 'application/json' } })
}

describe('httpClient', () => {
  const fetchMock = vi.fn()

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('fetch', fetchMock)
    vi.spyOn(console, 'log').mockImplementation(() => {})
    document.cookie = 'XSRF-TOKEN=test; path=/' // the CSRF cookie of an ongoing session
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('GET goes to /api/v1, sends cookies and returns parsed JSON', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse({ data: [1, 2] }))

    const res = await httpClient.get('/branch')

    expect(res).toMatchObject({ data: { data: [1, 2] }, status: 200 })
    const [url, init] = fetchMock.mock.calls[0]
    expect(url).toBe('/api/v1/branch')
    expect(init).toMatchObject({ method: 'GET', credentials: 'include', headers: { 'Content-Type': 'application/json', Accept: 'application/json' } })
  })

  it('normalizes paths without a leading slash and keeps absolute URLs', async () => {
    fetchMock.mockImplementation(async () => jsonResponse({}))

    await httpClient.get('group')
    await httpClient.get('http://example.test/x')

    expect(fetchMock.mock.calls[0][0]).toBe('/api/v1/group')
    expect(fetchMock.mock.calls[1][0]).toBe('http://example.test/x')
  })

  it('POST serializes JSON bodies, PUT/PATCH too, DELETE has none', async () => {
    fetchMock.mockImplementation(async () => jsonResponse({ status: 'ok' }))

    await httpClient.post('/training', { a: 1 })
    await httpClient.put('/training/1', { b: 2 })
    await httpClient.patch('/training/1/deleteinfuture')
    await httpClient.delete('/training/1')

    expect(fetchMock.mock.calls.map(([, init]) => [init.method, init.body])).toEqual([
      ['POST', '{"a":1}'],
      ['PUT', '{"b":2}'],
      ['PATCH', undefined],
      ['DELETE', undefined],
    ])
  })

  it('returns a Blob and the response headers for responseType blob', async () => {
    fetchMock.mockResolvedValueOnce(
      new Response('xlsx-bytes', { status: 200, headers: { 'content-disposition': 'attachment; filename="a.xlsx"' } }),
    )

    const res = await httpClient.post('/trainingevaluation/exportaccountingtimes', { userId: 1 }, { responseType: 'blob' })

    expect(res.data).toBeInstanceOf(Blob)
    expect(await res.data.text()).toBe('xlsx-bytes')
    expect(res.headers.get('content-disposition')).toBe('attachment; filename="a.xlsx"')
  })

  it('does not set a JSON content type for FormData (browser sets the multipart boundary)', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse({ status: 'ok' }, 201))
    const form = new FormData()
    form.append('profile_image', new Blob(['x']), 'x.png')

    await httpClient.post('/user/me/uploadprofileimage', form)

    const init = fetchMock.mock.calls[0][1]
    expect(init.body).toBe(form)
    expect(init.headers).not.toHaveProperty('Content-Type')
  })

  it('returns text for non-JSON responses', async () => {
    fetchMock.mockResolvedValueOnce(new Response('plain', { status: 200, headers: { 'content-type': 'text/plain' } }))

    const res = await httpClient.get('/hello')

    expect(res.data).toBe('plain')
  })

  it('rejects other error statuses with an error carrying the response', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse({ message: 'nope' }, 403))

    await expect(httpClient.get('/user')).rejects.toMatchObject({
      message: 'HTTP 403',
      response: { status: 403, data: { message: 'nope' } },
    })
  })

  it('on 401 logs the user out and sends them to the login with the current page as ?redirect=', async () => {
    const auth = useAuthStore()
    auth.logout = vi.fn()
    const location = { href: '', hash: '#/quickEvaluation/7' }
    vi.stubGlobal('window', Object.assign(Object.create(window), { location }))
    fetchMock.mockResolvedValueOnce(jsonResponse({}, 401))

    await expect(httpClient.get('/training')).rejects.toBeTruthy()

    expect(auth.logout).toHaveBeenCalled()
    expect(location.href).toBe('/#/login?redirect=%2FquickEvaluation%2F7')
    expect(fetchMock).toHaveBeenCalledTimes(1) // no refresh endpoint any more

    // a second failing request must not overwrite the redirect target
    location.hash = '#/login?redirect=%2FquickEvaluation%2F7'
    location.href = ''
    fetchMock.mockResolvedValueOnce(jsonResponse({}, 401))
    await expect(httpClient.get('/training')).rejects.toBeTruthy()
    expect(location.href).toBe('')
  })

  it('a session probe (silent401) only rejects', async () => {
    const auth = useAuthStore()
    auth.logout = vi.fn()
    fetchMock.mockResolvedValueOnce(jsonResponse({}, 401))

    await expect(httpClient.get('/auth/me', { silent401: true })).rejects.toBeTruthy()

    expect(auth.logout).not.toHaveBeenCalled()
  })

  describe('CSRF (Sanctum SPA authentication)', () => {
    beforeEach(() => {
      document.cookie = 'XSRF-TOKEN=; Max-Age=0; path=/'
    })
    afterEach(() => {
      document.cookie = 'XSRF-TOKEN=; Max-Age=0; path=/'
    })

    it('fetches the CSRF cookie once before the first unsafe request and sends it as X-XSRF-TOKEN', async () => {
      fetchMock.mockImplementation(async (url: string) => {
        if (url === '/api/sanctum/csrf-cookie') {
          document.cookie = 'XSRF-TOKEN=abc%3D%3D; path=/'
          return new Response(null, { status: 204 })
        }
        return jsonResponse({ status: 'ok' })
      })

      await httpClient.post('/auth/login', { email: 'a@example.org' })
      await httpClient.post('/training', {})

      const urls = fetchMock.mock.calls.map(([url]) => url)
      expect(urls).toEqual(['/api/sanctum/csrf-cookie', '/api/v1/auth/login', '/api/v1/training'])
      expect(fetchMock.mock.calls[1][1].headers['X-XSRF-TOKEN']).toBe('abc==')
      expect(fetchMock.mock.calls[1][1].credentials).toBe('include')
    })

    it('GET requests need no CSRF token', async () => {
      fetchMock.mockResolvedValue(jsonResponse({}))

      await httpClient.get('/branch')

      expect(fetchMock.mock.calls.map(([url]) => url)).toEqual(['/api/v1/branch'])
      expect(fetchMock.mock.calls[0][1].headers['X-XSRF-TOKEN']).toBeUndefined()
    })

    it('on 419 (token expired) gets a new CSRF cookie and repeats the request once', async () => {
      document.cookie = 'XSRF-TOKEN=old; path=/'
      fetchMock.mockImplementation(async (url: string) => {
        if (url === '/api/sanctum/csrf-cookie') {
          document.cookie = 'XSRF-TOKEN=new; path=/'
          return new Response(null, { status: 204 })
        }
        const calls = fetchMock.mock.calls.filter(([u]) => u === url).length
        return calls === 1 ? jsonResponse({ message: 'CSRF token mismatch.' }, 419) : jsonResponse({ status: 'ok' })
      })

      const res = await httpClient.post('/training', {})

      expect(res.data).toEqual({ status: 'ok' })
      const posts = fetchMock.mock.calls.filter(([u]) => u === '/api/v1/training')
      expect(posts[0][1].headers['X-XSRF-TOKEN']).toBe('old')
      expect(posts[1][1].headers['X-XSRF-TOKEN']).toBe('new')
    })
  })

  it('treats an HTML page answered to a JSON request (the SPA index.html) as an error', async () => {
    fetchMock.mockResolvedValueOnce(new Response('<!DOCTYPE html><html></html>', { status: 200, headers: { 'content-type': 'text/html' } }))

    await expect(httpClient.post('/trainingSeries', { a: 1 })).rejects.toMatchObject({ response: { status: 200 } })
    expect(useSnackbarStore().visible).toBe(true)
  })

  it('does not follow redirects and reports them as errors', async () => {
    const redirect = new Response(null, { status: 200 })
    Object.defineProperty(redirect, 'type', { value: 'opaqueredirect' })
    fetchMock.mockResolvedValueOnce(redirect)

    await expect(httpClient.post('/trainingSeries', {})).rejects.toBeTruthy()
    expect(fetchMock.mock.calls[0][1]).toMatchObject({ redirect: 'manual' })
  })

  it('accepts anything for blob downloads', async () => {
    fetchMock.mockResolvedValueOnce(new Response('x', { status: 200, headers: { 'content-type': 'text/html' } }))

    await httpClient.post('/export', {}, { responseType: 'blob' })
    expect(fetchMock.mock.calls[0][1].headers.Accept).toBe('*/*')
  })
})

import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import httpClient from '@/http/api'
import { useAuthStore } from '@/store/auth'

function jsonResponse(body: unknown, status = 200) {
  return new Response(JSON.stringify(body), { status, headers: { 'content-type': 'application/json' } })
}

describe('httpClient', () => {
  const fetchMock = vi.fn()

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('fetch', fetchMock)
    vi.spyOn(console, 'log').mockImplementation(() => {})
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
    expect(init).toMatchObject({ method: 'GET', credentials: 'include', headers: { 'Content-Type': 'application/json' } })
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

  it('on 401 refreshes the token once and retries the original request', async () => {
    fetchMock
      .mockResolvedValueOnce(jsonResponse({ message: 'Token has expired' }, 401))
      .mockResolvedValueOnce(jsonResponse({ status: 'ok' })) // POST /auth/refresh
      .mockResolvedValueOnce(jsonResponse({ id: 1 })) // retry

    const res = await httpClient.get('/training')

    expect(res.data).toEqual({ id: 1 })
    expect(fetchMock.mock.calls.map(([url]) => url)).toEqual(['/api/v1/training', '/api/v1/auth/refresh', '/api/v1/training'])
    expect(fetchMock.mock.calls[1][1]).toMatchObject({ method: 'POST', credentials: 'include' })
  })

  it('on 401 with a failed refresh logs the user out and redirects to /login', async () => {
    const auth = useAuthStore()
    auth.logout = vi.fn()
    const location = { href: '' }
    vi.stubGlobal('window', Object.assign(Object.create(window), { location }))
    fetchMock
      .mockResolvedValueOnce(jsonResponse({}, 401))
      .mockResolvedValueOnce(jsonResponse({ message: 'broken' }, 500)) // refresh fails

    await expect(httpClient.get('/training')).rejects.toBeTruthy()

    expect(auth.logout).toHaveBeenCalled()
    expect(location.href).toBe('/login')
  })
})

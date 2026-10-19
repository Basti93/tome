import { describe, expect, it, vi } from 'vitest'
import { readFileSync } from 'node:fs'
import { join } from 'node:path'

// Loads public/firebase-messaging-sw.js with mocked worker globals and drives its event listeners.
const source = readFileSync(join(__dirname, '..', 'public', 'firebase-messaging-sw.js'), 'utf8')

function loadWorker() {
  const listeners: Record<string, (event: any) => void> = {}
  const store = new Map<string, Response>()
  const cacheApi = {
    open: vi.fn(async () => ({
      put: vi.fn(async (request: Request, response: Response) => { store.set(request.url, response) }),
      addAll: vi.fn(async () => {}),
    })),
    match: vi.fn(async (request: Request | string) => store.get(typeof request === 'string' ? request : request.url)),
    keys: vi.fn(async () => ['tome-v1', 'tome-runtime', 'tome-v2']),
    delete: vi.fn(async () => true),
  }
  const fetchMock = vi.fn()
  const self = {
    addEventListener: (name: string, fn: (event: any) => void) => { listeners[name] = fn },
    location: { origin: 'https://tome.test' },
    skipWaiting: vi.fn(),
    clients: { claim: vi.fn(async () => {}) },
    registration: {},
  }
  new Function('self', 'caches', 'fetch', 'Response', 'Headers', 'URL', source)(self, cacheApi, fetchMock, Response, Headers, URL)
  return { listeners, cacheApi, fetchMock, store, self }
}

function fetchEvent(url: string, method = 'GET', mode = 'cors') {
  let responded: Promise<Response> | undefined
  const waits: Promise<unknown>[] = []
  return {
    event: {
      request: { url: `https://tome.test${url}`, method, mode },
      respondWith: (p: Promise<Response>) => { responded = p },
      waitUntil: (p: Promise<unknown>) => { waits.push(p) },
    },
    result: () => responded,
    settle: () => Promise.all(waits),
  }
}

describe('service worker', () => {
  it('never intercepts (and so never caches) API calls, other origins or non-GET requests', () => {
    const { listeners, fetchMock } = loadWorker()
    for (const [url, method] of [['/api/v1/simpleuser', 'GET'], ['/api/storage/profile_images/a.jpg', 'GET'], ['/assets/app.js', 'POST'], ['/matomo/matomo.js', 'GET']]) {
      const { event, result } = fetchEvent(url, method)
      listeners.fetch(event)
      expect(result(), `${method} ${url}`).toBeUndefined()
    }
    const foreign = { request: { url: 'https://other.test/assets/x.js', method: 'GET', mode: 'cors' }, respondWith: vi.fn(), waitUntil: vi.fn() }
    listeners.fetch(foreign)
    expect(foreign.respondWith).not.toHaveBeenCalled()
    expect(fetchMock).not.toHaveBeenCalled()
  })

  it('never intercepts Matomo pages, not even navigations', () => {
    const { listeners, fetchMock } = loadWorker()
    for (const url of ['/matomo', '/matomo/', '/matomo/index.php?module=CoreHome', '/matomo/matomo.js']) {
      const { event, result } = fetchEvent(url, 'GET', 'navigate')
      listeners.fetch(event)
      expect(result(), url).toBeUndefined()
    }
    expect(fetchMock).not.toHaveBeenCalled()
  })

  it('clones the response before returning it, so the page can still read the body', async () => {
    const { listeners, fetchMock, store } = loadWorker()
    fetchMock.mockResolvedValue(new Response('<html>shell</html>', { status: 200 }))
    const { event, result, settle } = fetchEvent('/index.html', 'GET', 'navigate')
    listeners.fetch(event)
    const response = await result()!
    expect(await response.text()).toBe('<html>shell</html>')
    await settle()
    expect(await store.get('https://tome.test/index.html')!.text()).toBe('<html>shell</html>')
  })

  it('serves hashed assets cache-first and falls back to the cache when the network fails', async () => {
    const { listeners, fetchMock, store } = loadWorker()
    store.set('https://tome.test/assets/app-1.js', new Response('cached'))
    const asset = fetchEvent('/assets/app-1.js')
    listeners.fetch(asset.event)
    expect(await (await asset.result()!).text()).toBe('cached')
    expect(fetchMock).not.toHaveBeenCalled()

    store.set('https://tome.test/manifest.json', new Response('{}'))
    fetchMock.mockRejectedValue(new Error('offline'))
    const manifest = fetchEvent('/manifest.json')
    listeners.fetch(manifest.event)
    expect(await (await manifest.result()!).text()).toBe('{}')
  })

  it('deletes every cache except the current one on activate', async () => {
    const { listeners, cacheApi } = loadWorker()
    const waits: Promise<unknown>[] = []
    listeners.activate({ waitUntil: (p: Promise<unknown>) => waits.push(p) })
    await Promise.all(waits)
    expect(cacheApi.delete).toHaveBeenCalledWith('tome-v1')
    expect(cacheApi.delete).toHaveBeenCalledWith('tome-runtime')
    expect(cacheApi.delete).not.toHaveBeenCalledWith('tome-v2')
  })
})

describe('service worker notification click', () => {
  function click(listeners: Record<string, (e: any) => void>, clientsApi: any, clickAction: string) {
    ;(globalThis as any).clients = clientsApi
    const waits: Promise<unknown>[] = []
    listeners.notificationclick({
      notification: { tag: 't', close: vi.fn(), data: { click_action: clickAction } },
      waitUntil: (p: Promise<unknown>) => waits.push(p),
    })
    return Promise.all(waits)
  }

  it('focuses and navigates an open window of the app instead of opening a second one', async () => {
    const { listeners } = loadWorker()
    const win = { url: 'https://tome.test/#/', focus: vi.fn(), navigate: vi.fn(async () => win) } as any
    win.focus.mockResolvedValue(win)
    const clientsApi = { matchAll: vi.fn(async () => [{ url: 'https://other.test/' }, win]), openWindow: vi.fn() }

    await click(listeners, clientsApi, 'https://tome.test/#/quickEvaluation/9')

    expect(win.focus).toHaveBeenCalled()
    expect(win.navigate).toHaveBeenCalledWith('https://tome.test/#/quickEvaluation/9')
    expect(clientsApi.openWindow).not.toHaveBeenCalled()
  })

  it('opens a window when the app is not open', async () => {
    const { listeners } = loadWorker()
    const clientsApi = { matchAll: vi.fn(async () => []), openWindow: vi.fn(async () => ({})) }

    await click(listeners, clientsApi, 'https://tome.test/#/quickEvaluation/9')

    expect(clientsApi.openWindow).toHaveBeenCalledWith('https://tome.test/#/quickEvaluation/9')
  })
})

import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('firebase/app', () => ({
  initializeApp: vi.fn(() => ({ name: 'app' })),
  getApps: vi.fn(() => []),
}))
vi.mock('firebase/messaging', () => ({
  getMessaging: vi.fn(() => ({ messaging: true })),
  getToken: vi.fn(),
  isSupported: vi.fn(async () => true),
  onMessage: vi.fn(() => vi.fn()),
}))
vi.mock('@/http/api', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}))

import { getMessaging, getToken, isSupported, onMessage } from 'firebase/messaging'
import httpClient from '@/http/api'
import { listenForForegroundMessages, registerPushToken, resetPushStateForTests } from '@/services/push'

const registration = { scope: '/' } as unknown as ServiceWorkerRegistration
const registerSw = vi.fn()

function stubBrowser(permission: NotificationPermission, requestResult: NotificationPermission = 'granted') {
  const NotificationMock = Object.assign(function () {}, {
    permission,
    requestPermission: vi.fn(async () => requestResult),
  })
  vi.stubGlobal('Notification', NotificationMock)
  vi.stubGlobal('PushManager', function () {})
  Object.defineProperty(navigator, 'serviceWorker', { value: { register: registerSw }, configurable: true })
  return NotificationMock
}

function stubFirebaseEnv() {
  vi.stubEnv('VITE_FIREBASE_API_KEY', 'key')
  vi.stubEnv('VITE_FIREBASE_PROJECT_ID', 'proj')
  vi.stubEnv('VITE_FIREBASE_MESSAGING_SENDER_ID', '123')
  vi.stubEnv('VITE_FIREBASE_APP_ID', 'app')
  vi.stubEnv('VITE_FIREBASE_VAPID_KEY', 'vapid-key')
}

describe('registerPushToken', () => {
  beforeEach(() => {
    resetPushStateForTests()
    registerSw.mockResolvedValue(registration)
    vi.mocked(isSupported).mockResolvedValue(true)
    vi.spyOn(console, 'error').mockImplementation(() => {})
    stubFirebaseEnv()
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
    // @ts-expect-error cleanup of the stub
    delete navigator.serviceWorker
  })

  it('gets a token with the VAPID key + SW registration and posts it to notifications/subscribe', async () => {
    stubBrowser('granted')
    vi.mocked(getToken).mockResolvedValue('fcm-token')
    vi.mocked(httpClient.post).mockResolvedValue({ data: { status: 'ok' }, status: 201, statusText: 'Created' })

    expect(await registerPushToken(42)).toBe('subscribed')

    expect(registerSw).toHaveBeenCalledWith('/firebase-messaging-sw.js')
    expect(getToken).toHaveBeenCalledWith({ messaging: true }, { vapidKey: 'vapid-key', serviceWorkerRegistration: registration })
    expect(httpClient.post).toHaveBeenCalledWith('/notifications/subscribe', { firebaseToken: 'fcm-token', userId: 42 })
    expect(getMessaging).toHaveBeenCalledTimes(1)
  })

  it('asks the user first instead of prompting when the permission is undecided', async () => {
    const notification = stubBrowser('default')
    expect(await registerPushToken(1)).toBe('needs-permission')
    expect(notification.requestPermission).not.toHaveBeenCalled()
    expect(getToken).not.toHaveBeenCalled()
    expect(httpClient.post).not.toHaveBeenCalled()
  })

  it('requests the permission on demand and subscribes after it is granted', async () => {
    const notification = stubBrowser('default', 'granted')
    vi.mocked(getToken).mockResolvedValue('fcm-token')
    vi.mocked(httpClient.post).mockResolvedValue({ data: {}, status: 201, statusText: '' })

    expect(await registerPushToken(1, true)).toBe('subscribed')
    expect(notification.requestPermission).toHaveBeenCalled()
    expect(httpClient.post).toHaveBeenCalledTimes(1)
  })

  it('reports denied without contacting Firebase or the backend', async () => {
    stubBrowser('denied')
    expect(await registerPushToken(1)).toBe('denied')

    resetPushStateForTests()
    stubBrowser('default', 'denied')
    expect(await registerPushToken(1, true)).toBe('denied')
    expect(getToken).not.toHaveBeenCalled()
    expect(httpClient.post).not.toHaveBeenCalled()
  })

  it('degrades to unsupported when the Push APIs are missing (e.g. iOS without installed PWA)', async () => {
    // jsdom has neither Notification nor PushManager
    expect(await registerPushToken(1)).toBe('unsupported')

    stubBrowser('granted')
    vi.mocked(isSupported).mockResolvedValue(false)
    expect(await registerPushToken(1)).toBe('unsupported')
    expect(getToken).not.toHaveBeenCalled()
  })

  it('is unconfigured without the VAPID key', async () => {
    stubBrowser('granted')
    vi.stubEnv('VITE_FIREBASE_VAPID_KEY', '')
    expect(await registerPushToken(1)).toBe('unconfigured')
    expect(getToken).not.toHaveBeenCalled()
  })

  it('returns error (and does not throw) when FCM or the backend fail', async () => {
    stubBrowser('granted')
    vi.mocked(getToken).mockRejectedValue(new Error('fcm down'))
    expect(await registerPushToken(1)).toBe('error')

    vi.mocked(getToken).mockResolvedValue('fcm-token')
    vi.mocked(httpClient.post).mockRejectedValue(new Error('500'))
    expect(await registerPushToken(1)).toBe('error')
  })

  it('returns error when no token is issued', async () => {
    stubBrowser('granted')
    vi.mocked(getToken).mockResolvedValue('')
    expect(await registerPushToken(1)).toBe('error')
    expect(httpClient.post).not.toHaveBeenCalled()
  })
})

describe('listenForForegroundMessages', () => {
  beforeEach(() => {
    resetPushStateForTests()
    vi.mocked(isSupported).mockResolvedValue(true)
    stubFirebaseEnv()
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
    // @ts-expect-error cleanup of the stub
    delete navigator.serviceWorker
  })

  it('maps data-only and notification payloads to title/body', async () => {
    stubBrowser('granted')
    const handler = vi.fn()
    expect(await listenForForegroundMessages(handler)).toBe(true)

    const callback = vi.mocked(onMessage).mock.calls[0][1] as (payload: any) => void
    callback({ data: { title: 'Training', body: 'Heute 18 Uhr' } })
    callback({ notification: { title: 'Abgesagt', body: 'Training fällt aus' } })

    expect(handler).toHaveBeenNthCalledWith(1, { title: 'Training', body: 'Heute 18 Uhr', data: { title: 'Training', body: 'Heute 18 Uhr' } })
    expect(handler).toHaveBeenNthCalledWith(2, { title: 'Abgesagt', body: 'Training fällt aus', data: {} })
  })

  it('returns false when push is unsupported', async () => {
    expect(await listenForForegroundMessages(vi.fn())).toBe(false)
    expect(onMessage).not.toHaveBeenCalled()
  })
})

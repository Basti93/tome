import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { MatomoAnalytics } from '@/services/analytics'

function enable() {
  vi.stubEnv('VITE_MATOMO_ENABLED', 'true')
  vi.stubEnv('VITE_MATOMO_URL', '/matomo')
  vi.stubEnv('VITE_MATOMO_SITE_ID', '3')
}

describe('MatomoAnalytics', () => {
  beforeEach(() => {
    delete window._paq
    document.head.innerHTML = ''
    vi.stubEnv('VITE_MATOMO_ENABLED', '')
    vi.stubEnv('VITE_MATOMO_URL', '')
    vi.stubEnv('VITE_MATOMO_SITE_ID', '')
  })

  afterEach(() => {
    vi.unstubAllEnvs()
    vi.unstubAllGlobals()
  })

  it('stays disabled and loads nothing when the env is not set', () => {
    const a = new MatomoAnalytics()
    a.init()
    a.trackEvent('Training', 'CheckIn')
    a.trackPageView('Home')
    expect(a.isActive).toBe(false)
    expect(window._paq).toBeUndefined()
    expect(document.head.querySelector('script')).toBeNull()
  })

  it.each([
    ['VITE_MATOMO_ENABLED', 'false'],
    ['VITE_MATOMO_URL', ''],
    ['VITE_MATOMO_SITE_ID', ''],
  ])('is disabled when %s is %j', (key, value) => {
    enable()
    vi.stubEnv(key, value)
    const a = new MatomoAnalytics()
    a.init()
    expect(a.isActive).toBe(false)
    expect(window._paq).toBeUndefined()
  })

  it('pushes disableCookies first, then configures tracker and loads matomo.js', () => {
    enable()
    const a = new MatomoAnalytics()
    a.init()
    a.trackPageView('Calendar')

    expect(a.isActive).toBe(true)
    expect(window._paq![0]).toEqual(['disableCookies'])
    expect(window._paq).toContainEqual(['setTrackerUrl', '/matomo/matomo.php'])
    expect(window._paq).toContainEqual(['setSiteId', '3'])
    const firstTrackingIndex = window._paq!.findIndex(c => ['trackPageView', 'trackEvent'].includes(String(c[0])))
    expect(firstTrackingIndex).toBeGreaterThan(0)
    expect(document.head.querySelector('script')?.getAttribute('src')).toBe('/matomo/matomo.js')
  })

  it('does not load or track when Do-Not-Track is enabled', () => {
    enable()
    vi.stubGlobal('navigator', { ...navigator, doNotTrack: '1' })
    const a = new MatomoAnalytics()
    a.init()
    a.trackEvent('Training', 'CheckIn')
    expect(a.isActive).toBe(false)
    expect(window._paq).toBeUndefined()
    expect(document.head.querySelector('script')).toBeNull()
  })

  it('never uses user ids and has no setUserId API', () => {
    enable()
    const a = new MatomoAnalytics()
    a.init()
    a.trackEvent('Authentication', 'Login')
    a.trackPageView('Login')
    expect((a as any).setUserId).toBeUndefined()
    expect((a as any).resetUserId).toBeUndefined()
    expect(window._paq!.some(c => ['setUserId', 'resetUserId', 'setCustomVariable'].includes(String(c[0])))).toBe(false)
  })

  it('does not send e-mail addresses as event names or page names, and reports only the neutral page name', () => {
    enable()
    const a = new MatomoAnalytics()
    a.init()
    a.trackEvent('Authentication', 'Login', 'max@example.com', 1)
    a.trackPageView('user@example.com')
    const sent = JSON.stringify(window._paq)
    expect(sent).not.toContain('@example.com')

    window._paq = []
    a.trackPageView('TrainingsCheckIn')
    expect(window._paq).toContainEqual(['setCustomUrl', `${window.location.origin}/TrainingsCheckIn`])
    expect(window._paq).toContainEqual(['trackPageView'])
  })

  it('tracks neutral events', () => {
    enable()
    const a = new MatomoAnalytics()
    a.init()
    window._paq = []
    a.trackEvent('Training', 'CheckIn')
    expect(window._paq).toEqual([['trackEvent', 'Training', 'CheckIn']])
  })
})

// Matomo analytics, cookieless and free of personal data.
//
// - Only active when VITE_MATOMO_ENABLED=true and VITE_MATOMO_URL + VITE_MATOMO_SITE_ID are set.
// - `disableCookies` is always the first command pushed, before any tracking call.
// - Do-Not-Track is respected: with DNT enabled nothing is loaded or sent.
// - No user ID, names, e-mail addresses or user ids are ever sent. Page views are reported with the
//   neutral route name instead of the real URL (which can contain tokens, ids or query strings).
//   The service intentionally has no setUserId/resetUserId.

type PaqCommand = Array<string | number | boolean>

declare global {
  interface Window {
    _paq?: PaqCommand[]
    doNotTrack?: string
    msDoNotTrack?: string
  }
}

interface MatomoConfig {
  trackerUrl: string
  siteId: string
}

export function readMatomoConfig(): MatomoConfig | null {
  const env = import.meta.env
  const url = String(env.VITE_MATOMO_URL ?? '').trim()
  const siteId = String(env.VITE_MATOMO_SITE_ID ?? '').trim()
  if (String(env.VITE_MATOMO_ENABLED) !== 'true' || !url || !siteId) return null
  return { trackerUrl: url.endsWith('/') ? url : url + '/', siteId }
}

export function isDoNotTrackEnabled(): boolean {
  if (typeof navigator === 'undefined') return false
  const dnt = (navigator as any).doNotTrack ?? (window as any).doNotTrack ?? (navigator as any).msDoNotTrack
  return dnt === '1' || dnt === 'yes' || dnt === 1 || dnt === true
}

/** Drops values that look like personal data (e-mail addresses). */
function sanitize(value: string | undefined): string | undefined {
  if (!value || value.includes('@')) return undefined
  return value
}

export class MatomoAnalytics {
  private active = false

  /** True once Matomo is configured, not blocked by DNT and initialised. */
  get isActive(): boolean {
    return this.active
  }

  init(): void {
    if (this.active) return
    const config = readMatomoConfig()
    if (!config || isDoNotTrackEnabled()) return

    const paq = (window._paq = window._paq || [])
    // must come before any tracking call
    paq.push(['disableCookies'])
    paq.push(['setDoNotTrack', true])
    paq.push(['setTrackerUrl', `${config.trackerUrl}matomo.php`])
    paq.push(['setSiteId', config.siteId])
    this.active = true

    if (typeof document !== 'undefined') {
      const script = document.createElement('script')
      script.async = true
      script.src = `${config.trackerUrl}matomo.js`
      document.head.appendChild(script)
    }
  }

  /**
   * Track a neutral event, e.g. ('Training', 'CheckIn'). Do not pass names, e-mail addresses or ids.
   */
  trackEvent(category: string, action: string, name?: string, value?: number): void {
    if (!this.active || !window._paq) return
    const cmd: PaqCommand = ['trackEvent', category, action]
    const safeName = sanitize(name)
    if (safeName !== undefined) cmd.push(safeName)
    if (typeof value === 'number' && safeName !== undefined) cmd.push(value)
    window._paq.push(cmd)
  }

  /** Track a page view by neutral page name (route name); the real URL is never sent. */
  trackPageView(pageName: string): void {
    if (!this.active || !window._paq) return
    const name = sanitize(pageName) ?? 'Page'
    window._paq.push(['setCustomUrl', `${window.location.origin}/${encodeURIComponent(name)}`])
    window._paq.push(['setDocumentTitle', name])
    window._paq.push(['trackPageView'])
  }
}

export const analytics = new MatomoAnalytics()

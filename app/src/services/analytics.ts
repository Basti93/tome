// Matomo Analytics Tracker
// Privacy-friendly event tracking with configurable environment variables

declare global {
  interface Window {
    _paq?: Array<string | string[] | number | boolean>
  }
}

export class MatomoAnalytics {
  private siteId: string
  private trackerUrl: string
  private initialized = false
  private enabled = false

  constructor() {
    // Load configuration from environment variables
    this.enabled = import.meta.env.VITE_MATOMO_ENABLED === 'true'
    this.siteId = import.meta.env.VITE_MATOMO_SITE_ID || '1'
    // Use relative path through nginx proxy (/matomo/) instead of absolute URL
    // This ensures same-origin requests, no CORS issues, and proper cookie handling
    this.trackerUrl = import.meta.env.VITE_MATOMO_URL || '/matomo/'

    if (!this.trackerUrl.endsWith('/')) {
      this.trackerUrl += '/'
    }
  }

  init() {
    if (!this.enabled || this.initialized) return

    // Initialize _paq queue if not already present
    window._paq = window._paq || []

    // Configure Matomo
    window._paq.push(['trackPageView'])
    window._paq.push(['enableLinkTracking'])
    window._paq.push([
      'setTrackerUrl',
      `${this.trackerUrl}index.php`
    ])
    window._paq.push(['setSiteId', this.siteId])
    // Note: IP anonymization is configured in Matomo admin settings, not via _paq

    this.initialized = true
    console.log('✅ Matomo analytics initialized (image beacon mode)', {
      url: this.trackerUrl,
      siteId: this.siteId
    })

    // Send initial tracking request after DOM is ready
    this.sendBeacon('pageview')
  }

  /**
   * Send tracking request to Matomo via image beacon (GET request)
   * Uses piwik.php endpoint which returns a tracking pixel
   */
  private sendBeacon(action: string) {
    if (!this.enabled) return

    const baseUrl = this.trackerUrl.startsWith('/') ? `${window.location.origin}${this.trackerUrl}` : this.trackerUrl
    const beaconUrl = `${baseUrl}piwik.php?rec=1&idsite=${this.siteId}&action_name=${encodeURIComponent(action)}&rand=${Math.random()}`

    console.log('📊 Sending Matomo beacon:', beaconUrl)

    // Use image pixel for tracking - simple, reliable, no CORS issues
    try {
      const img = new Image(1, 1)
      img.onload = () => console.log('✅ Matomo beacon sent')
      img.onerror = () => console.warn('⚠️ Matomo beacon failed')
      img.src = beaconUrl
    } catch (error) {
      console.error('❌ Failed to send beacon:', error)
    }
  }

  /**
   * Get or create a persistent visitor ID
   */
  private getOrCreateVisitorId(): string {
    let visitorId = localStorage.getItem('matomo_visitor_id')
    if (!visitorId) {
      visitorId = Math.random().toString(36).substr(2, 16)
      localStorage.setItem('matomo_visitor_id', visitorId)
    }
    return visitorId
  }

  /**
   * Track a custom event via image beacon (GET request)
   * @param category Event category (e.g., 'Authentication', 'Training')
   * @param action Event action (e.g., 'Login', 'CheckIn')
   * @param name Optional event name for additional context
   * @param value Optional numeric value for metrics
   */
  trackEvent(category: string, action: string, name?: string, value?: number) {
    if (!this.enabled) return

    const baseUrl = this.trackerUrl.startsWith('/') ? `${window.location.origin}${this.trackerUrl}` : this.trackerUrl
    const eventName = `${category}/${action}${name ? '/' + name : ''}`
    let beaconUrl = `${baseUrl}piwik.php?rec=1&idsite=${this.siteId}&action_name=${encodeURIComponent(eventName)}&e_c=${encodeURIComponent(category)}&e_a=${encodeURIComponent(action)}&rand=${Math.random()}`

    if (name) {
      beaconUrl += `&e_n=${encodeURIComponent(name)}`
    }
    if (value) {
      beaconUrl += `&e_v=${value}`
    }

    console.log('📊 Tracking event:', { category, action, name, value })

    // Use image pixel for tracking - simple, reliable, no CORS issues
    try {
      const img = new Image(1, 1)
      img.onload = () => console.log('✅ Event tracked')
      img.onerror = () => console.warn('⚠️ Event tracking failed')
      img.src = beaconUrl
    } catch (error) {
      console.error('❌ Failed to track event:', error)
    }
  }

  /**
   * Set user ID for authenticated users (optional)
   * @param userId Unique user identifier
   */
  setUserId(userId: string | number) {
    if (!this.enabled || !window._paq) return
    window._paq.push(['setUserId', userId.toString()])
  }

  /**
   * Clear user ID on logout
   */
  resetUserId() {
    if (!this.enabled || !window._paq) return
    window._paq.push(['resetUserId'])
  }

  /**
   * Track page view manually
   * @param pageTitle Optional page title
   */
  trackPageView(pageTitle?: string) {
    if (!this.enabled) return

    // Push to _paq queue if available (for matomo.js compatibility)
    if (window._paq) {
      if (pageTitle) {
        window._paq.push(['setDocumentTitle', pageTitle])
      }
      window._paq.push(['trackPageView'])
    }

    // Also send beacon directly (for image beacon tracking)
    const actionName = pageTitle || window.location.pathname || 'pageview'
    this.sendBeacon(actionName)
  }
}

// Export singleton instance
export const analytics = new MatomoAnalytics()

import { describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

vi.mock('@/http/api', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}))

import httpClient from '@/http/api'
import InfoPage from './InfoPage.vue'

describe('InfoPage', () => {
  it('renders static information without release notes and without FAQ requests', () => {
    const w = mount(InfoPage, { global: { plugins: [createVuetify({ components, directives })] } })
    expect(w.text()).toContain('Informationen')
    expect(w.text()).not.toContain('Informationent')
    expect(w.text()).not.toContain('Release Notes')
    expect(w.text()).toContain('Was ist T.O.M.E.?')
    expect(w.text()).toContain('Anonyme Nutzungsstatistik ohne Cookies (Matomo)')
    expect(httpClient.get).not.toHaveBeenCalled()
  })
})

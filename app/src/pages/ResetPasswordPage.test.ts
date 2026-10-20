import { describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { createMemoryHistory, createRouter } from 'vue-router'

vi.mock('@/http/api', () => ({ default: { get: vi.fn(), post: vi.fn() } }))

import ResetPasswordPage from './ResetPasswordPage.vue'

describe('ResetPasswordPage', () => {
  it('switches to the password form when a reset link is opened while the page is shown', async () => {
    const router = createRouter({ history: createMemoryHistory(), routes: [{ path: '/reset-password', component: ResetPasswordPage }] })
    await router.push('/reset-password')
    await router.isReady()
    const w = mount(ResetPasswordPage, { global: { plugins: [createPinia(), createVuetify({ components, directives }), router] } })
    expect(w.text()).toContain('Link senden')

    await router.push('/reset-password?token=abc&email=a%40example.org')
    await flushPromises()

    expect(w.text()).toContain('Passwort speichern')
    expect((w.vm as any).token).toBe('abc')
    expect((w.vm as any).email).toBe('a@example.org')
  })

  it('uses the password policy for the new password', async () => {
    const router = createRouter({ history: createMemoryHistory(), routes: [{ path: '/reset-password', component: ResetPasswordPage }] })
    await router.push('/reset-password?token=abc&email=a%40example.org')
    const w = mount(ResetPasswordPage, { global: { plugins: [createPinia(), createVuetify({ components, directives }), router] } })
    const rules = (w.vm as any).passwordRules as Array<(v: string) => true | string>
    const firstError = (value: string) => rules.map((r) => r(value)).find((r) => r !== true)
    expect(firstError('schwach')).toBeTruthy()
    expect(firstError('Neu12345!x')).toBeUndefined()
  })
})

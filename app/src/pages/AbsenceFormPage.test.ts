import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

vi.mock('@/http/api', () => ({ default: { get: vi.fn().mockResolvedValue({ data: { data: [] } }), post: vi.fn() } }))

import httpClient from '@/http/api'
import AbsenceFormPage from './AbsenceFormPage.vue'
import { useAuthStore } from '@/store/auth'
import User from '@/models/User'

const get = httpClient.get as unknown as ReturnType<typeof vi.fn>

function mountPage() {
  return mount(AbsenceFormPage, { global: { plugins: [createVuetify({ components, directives })] }, attachTo: document.body })
}

describe('AbsenceFormPage', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    get.mockClear()
  })

  it('does not request the trainer-only absence list for anonymous visitors (it would be a 401)', async () => {
    const w = mountPage()
    await flushPromises()

    expect(get).not.toHaveBeenCalledWith('user/allAbsence')
    expect(w.text()).toContain('Beginn der Abwesenheit')
    w.unmount()
  })

  it('loads the absence list for a logged-in user', async () => {
    useAuthStore().user = { id: 1, firstName: 'T', familyName: 'T', isTrainer: true } as unknown as User
    const w = mountPage()
    await flushPromises()

    expect(get).toHaveBeenCalledWith('user/allAbsence')
    w.unmount()
  })
})

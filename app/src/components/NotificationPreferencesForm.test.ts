import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

vi.mock('@/http/api', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}))

import httpClient from '@/http/api'
import NotificationPreferencesForm from './NotificationPreferencesForm.vue'

const vuetify = createVuetify({ components, directives })
const http = httpClient as unknown as { get: ReturnType<typeof vi.fn> }

describe('NotificationPreferencesForm', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    document.body.innerHTML = ''
  })

  it('shows the stored (camelCase) API values, including the evaluation reminder', async () => {
    http.get.mockResolvedValue({
      data: {
        notificationPreferences: {
          trainerUpcoming: true,
          trainerCancelAlert: true,
          participantUpcoming: true,
          absenceAlert: true,
          adminMessages: true,
          trainerEvaluationReminder: false,
        },
      },
    })
    const w = mount(NotificationPreferencesForm, { global: { plugins: [vuetify] }, attachTo: document.body })
    await flushPromises()

    expect(w.text()).toContain('Erinnerung an nicht abgeschlossene Trainings')
    expect((w.vm as any).preferences.trainer_evaluation_reminder).toBe(false)
    expect((w.vm as any).preferences.trainer_upcoming).toBe(true)
  })
})

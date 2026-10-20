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

  it('has separate push and e-mail switches; the e-mail switch is off for older stored preferences', async () => {
    http.get.mockResolvedValue({
      data: {
        notificationPreferences: {
          trainerUpcoming: true,
          trainerCancelAlert: true,
          participantUpcoming: true,
          absenceAlert: true,
          adminMessages: true,
          trainerEvaluationReminder: true,
        },
      },
    })
    const w = mount(NotificationPreferencesForm, { global: { plugins: [vuetify] }, attachTo: document.body })
    await flushPromises()

    expect(w.text()).toContain('per Push-Benachrichtigung')
    expect(w.text()).toContain('per E-Mail')
    expect((w.vm as any).preferences.trainer_evaluation_reminder).toBe(true)
    expect((w.vm as any).preferences.trainer_evaluation_reminder_email).toBe(false)

    const emailBox = w.findAll('input[type=checkbox]').find((i) => i.element.closest('.v-input')?.textContent?.includes('per E-Mail'))!
    await emailBox.setValue(true)
    expect((w.vm as any).preferences.trainer_evaluation_reminder_email).toBe(true)
    expect((w.vm as any).hasChanges).toBe(true)
  })

  it('has a birthday push checkbox that is on by default and saved with the other preferences', async () => {
    http.get.mockResolvedValue({ data: { notificationPreferences: { trainerUpcoming: false, trainerBirthday: true } } })
    http.put = vi.fn().mockResolvedValue({ data: { status: 'ok', notificationPreferences: { trainerBirthday: false } } })
    const w = mount(NotificationPreferencesForm, { global: { plugins: [vuetify] }, attachTo: document.body })
    await flushPromises()

    expect(w.text()).toContain('Geburtstage meiner Sportler (Push)')
    expect((w.vm as any).preferences.trainer_birthday).toBe(true)

    const box = w.findAll('input[type=checkbox]').find((i) => i.element.closest('.v-input')?.textContent?.includes('Geburtstage meiner Sportler'))!
    await box.setValue(false)
    await (w.vm as any).save()

    expect(http.put).toHaveBeenCalledWith('/user/me/notification-preferences', { notification_preferences: expect.objectContaining({ trainer_birthday: false }) })
  })
})

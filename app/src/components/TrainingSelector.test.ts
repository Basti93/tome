import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

vi.mock('@/http/api', () => ({ default: { get: vi.fn(), post: vi.fn() } }))

import TrainingSelector from './TrainingSelector.vue'
import dayjs from '@/helpers/dayjs'

// a phone-sized window: md starts far above the jsdom width
const phone = () => createVuetify({ components, directives, display: { thresholds: { xs: 0, sm: 600, md: 5000, lg: 6000, xl: 7000, xxl: 8000 } } })

function training(id: number) {
  return { id, start: dayjs('2030-01-0' + id + ' 18:00'), end: dayjs('2030-01-0' + id + ' 19:00'), groupIds: [], locationId: 1, participants: [], trainerIds: [], evaluated: false }
}

function mountSelector(count: number) {
  return mount(TrainingSelector, {
    props: { trainings: Array.from({ length: count }, (_, i) => training(i + 1)) },
    global: { plugins: [createPinia(), phone()] },
    attachTo: document.body,
  })
}

describe('TrainingSelector on small screens', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    document.body.innerHTML = ''
    document.body.className = ''
  })

  it('shows the "Anderes Training" button when there is more than one training', () => {
    const w = mountSelector(2)

    expect(w.text()).toContain('Anderes Training')
    expect(document.body.classList.contains('has-training-switch')).toBe(true)
    w.unmount()
    expect(document.body.classList.contains('has-training-switch')).toBe(false)
  })

  it.each([0, 1])('hides the button with %i trainings', (count) => {
    const w = mountSelector(count)

    expect(w.text()).not.toContain('Anderes Training')
    expect(document.body.classList.contains('has-training-switch')).toBe(false)
    w.unmount()
  })
})

import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

vi.mock('@/http/api', () => ({ default: { get: vi.fn() } }))

import httpClient from '@/http/api'
import GroupNotes from './GroupNotes.vue'

const http = httpClient as unknown as { get: ReturnType<typeof vi.fn> }
const vuetify = createVuetify({ components, directives })
const rows = [
  { trainingId: 2, start: '2026-10-08T18:00:00+02:00', groupIds: [1], note: 'Neuer' },
  { trainingId: 1, start: '2026-10-01T18:00:00+02:00', groupIds: [1], note: 'Aelter' },
]

describe('GroupNotes', () => {
  beforeEach(() => http.get.mockReset())

  it('lists the notes with their date', async () => {
    http.get.mockResolvedValue({ data: { data: rows } })
    const w = mount(GroupNotes, { props: { groupIds: [1] }, global: { plugins: [vuetify] } })
    await flushPromises()
    expect(w.findAll('[data-test="group-note"]')).toHaveLength(2)
    expect(w.text()).toContain('08.10.2026')
    expect(w.text()).toContain('Aelter')
  })

  it('compact shows only the last note', async () => {
    http.get.mockResolvedValue({ data: { data: rows } })
    const w = mount(GroupNotes, { props: { groupIds: [1], compact: true }, global: { plugins: [vuetify] } })
    await flushPromises()
    expect(w.find('[data-test="last-note"]').text()).toContain('Letztes Mal (08.10.)')
    expect(w.text()).toContain('Neuer')
    expect(w.text()).not.toContain('Aelter')
  })

  it('renders nothing without notes', async () => {
    http.get.mockResolvedValue({ data: { data: [] } })
    const w = mount(GroupNotes, { props: { groupIds: [1] }, global: { plugins: [vuetify] } })
    await flushPromises()
    expect(w.find('[data-test="group-notes"]').exists()).toBe(false)
  })
})

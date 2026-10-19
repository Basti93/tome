import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import dayjs from '@/helpers/dayjs'

vi.mock('@/http/api', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}))

import httpClient from '@/http/api'
import TrainingCalendar from './TrainingCalendar.vue'
import { useMasterDataStore } from '@/store/masterData'

describe('TrainingCalendar', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    const store = useMasterDataStore()
    store.setBranches([{ id: 1, name: 'Judo', shortName: 'JUD', colorHex: '#112233' }] as any)
    store.setGroups([{ id: 10, name: 'Kids', branchId: 1, branch: { id: 1, name: 'Judo', shortName: 'JUD', colorHex: '#112233' } }] as any)
    const day = dayjs().format('YYYY-MM-15')
    vi.mocked(httpClient.get).mockImplementation(async (url: string) => {
      if (url.startsWith('/training/simplecalendar') && !url.includes('planned')) {
        return { data: { data: [{ id: 1, start: `${day} 18:00:00`, end: `${day} 19:00:00`, locationId: 1, groupIds: [10], contentIds: [], trainerIds: [], participants: [] }] } } as any
      }
      return { data: { data: [] } } as any
    })
  })

  function mountCalendar() {
    return mount(TrainingCalendar, {
      global: { plugins: [createVuetify({ components, directives })] },
      attachTo: document.body,
    })
  }

  it('loads the events and opens the event details with the Vuetify 4 (nativeEvent, scope) handler signature', async () => {
    const w = mountCalendar()
    await flushPromises()
    await flushPromises()
    expect(httpClient.get).toHaveBeenCalled()
    const vm = w.vm as any
    expect(vm.events).toHaveLength(1)
    // the handlers receive (nativeEvent, scope)
    vm.showEvent({ target: document.body, stopPropagation() {} }, { event: vm.events[0] })
    expect(vm.selectedEvent.branchName).toBe('Judo')
    vm.viewDay(new Event('click'), { date: '2026-01-05' })
    expect(vm.type).toBe('day')
    expect(vm.focus).toBe('2026-01-05')
    // helpers used by the template exist
    expect(vm.getGroupById(10).name).toBe('Kids')
    expect(vm.dayjs('2026-01-05').format('YYYY')).toBe('2026')
    w.unmount()
  })
})

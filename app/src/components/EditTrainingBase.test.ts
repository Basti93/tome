import { describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import EditTrainingBase from './EditTrainingBase.vue'

describe('EditTrainingBase', () => {
  it('renders without Vue warnings (template refs must not shadow the menu state) and reports edits', async () => {
    setActivePinia(createPinia())
    const warn = vi.spyOn(console, 'warn').mockImplementation(() => {})
    const w = mount(EditTrainingBase, {
      props: { date: '2026-10-23', start: '17:00', end: '18:30', trainers: [], groups: [], groupIds: [], trainerIds: [], contentIds: [] },
      global: { plugins: [createVuetify({ components, directives })] },
    })
    await w.vm.$nextTick()
    const vm = w.vm as any
    expect(vm.dateMenuOpened).toBe(false)
    expect(vm.startMenuOpened).toBe(false)
    expect(vm.endMenuOpened).toBe(false)
    vm.startTime = '08:15'
    await w.vm.$nextTick()
    const events = w.emitted('change')!
    expect(events[events.length - 1][0]).toMatchObject({ date: '2026-10-23', start: '08:15', end: '18:30' })
    expect(warn).not.toHaveBeenCalled()
    w.unmount()
  })
})

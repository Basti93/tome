import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { createPinia, setActivePinia } from 'pinia'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

vi.mock('@/http/api', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}))

import StatisticsFilter1 from './StatisticsFilter1.vue'
import { useMasterDataStore } from '@/store/masterData'

describe('StatisticsFilter1', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    const store = useMasterDataStore()
    store.setBranches([{ id: 1, name: 'Judo' }] as any)
    store.setGroups([{ id: 10, name: 'Kids', branchId: 1 }] as any)
  })

  it('emits yearSelected / groupsSelected with the NEW value when a select changes', async () => {
    const w = mount(StatisticsFilter1, {
      props: { branchIds: [1], year: 2025 },
      global: { plugins: [createVuetify({ components, directives })] },
    })
    w.findAllComponents({ name: 'VSelect' })[1].vm.$emit('update:modelValue', 2024)
    await nextTick()
    expect(w.emitted('yearSelected')?.at(-1)).toEqual([2024])
    w.findComponent({ name: 'VAutocomplete' }).vm.$emit('update:modelValue', [10])
    await nextTick()
    expect(w.emitted('groupsSelected')?.at(-1)).toEqual([[10]])
  })
})

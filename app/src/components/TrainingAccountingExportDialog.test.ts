import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

vi.mock('@/http/api', () => ({ default: { get: vi.fn(), post: vi.fn() } }))

import dayjs from '@/helpers/dayjs'
import TrainingAccountingExportDialog from './TrainingAccountingExportDialog.vue'

describe('TrainingAccountingExportDialog', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    document.body.innerHTML = ''
  })

  it('opens with the period given by the hours page, otherwise with the last 6 months', async () => {
    const w = mount(TrainingAccountingExportDialog, {
      props: { visible: false, from: '2025-01-01', to: '2025-12-31' },
      global: { plugins: [createVuetify({ components, directives })] },
      attachTo: document.body,
    })

    await w.setProps({ visible: true })
    expect((w.vm as any).dateFrom).toBe('2025-01-01')
    expect((w.vm as any).dateTo).toBe('2025-12-31')

    await w.setProps({ visible: false, from: undefined, to: undefined })
    await w.setProps({ visible: true })
    expect((w.vm as any).dateTo).toBe(dayjs().format('YYYY-MM-DD'))
    w.unmount()
  })
})

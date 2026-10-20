import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { createMemoryHistory, createRouter } from 'vue-router'
import dayjs from '@/helpers/dayjs'

vi.mock('@/http/api', () => ({ default: { get: vi.fn() } }))

import httpClient from '@/http/api'
import TodayPage from './TodayPage.vue'
import { useAuthStore } from '@/store/auth'
import { useMasterDataStore } from '@/store/masterData'

const get = httpClient.get as unknown as ReturnType<typeof vi.fn>

async function mountPage(responses: Record<string, unknown>) {
  setActivePinia(createPinia())
  useAuthStore().user = { id: 1, firstName: 'T', familyName: 'T', isTrainer: true, trainerBranchIds: [5] } as any
  const store = useMasterDataStore()
  store.setGroups([{ id: 10, name: 'Kinder', branchId: 5 }] as any)
  store.simpleUsers = [
    { id: 100, firstName: 'Mia', familyName: 'Schäfer', groupIds: [10], birthdate: dayjs().format('2015-MM-DD') },
    { id: 101, firstName: 'Leo', familyName: 'Klein', groupIds: [10], birthdate: null },
  ] as any
  get.mockImplementation(async (url: string) => {
    const key = Object.keys(responses).find((k) => url.startsWith(k))
    return { data: key ? responses[key] : {} }
  })
  const router = createRouter({ history: createMemoryHistory(), routes: [{ path: '/:p(.*)*', component: { template: '<div/>' } }] })
  const w = mount(TodayPage, { global: { plugins: [createVuetify({ components, directives }), router] }, attachTo: document.body })
  await flushPromises()
  return w
}

const start = dayjs().add(1, 'day').format('YYYY-MM-DDT18:00:00')
const end = dayjs().add(1, 'day').format('YYYY-MM-DDT19:30:00')

describe('TodayPage', () => {
  beforeEach(() => {
    document.body.innerHTML = ''
  })

  it('shows the own next training with counts, cancellation reasons and the unevaluated trainings', async () => {
    const w = await mountPage({
      '/trainingprepare/1': {
        data: [{ id: 7, start, end, locationId: 1, groupIds: [10], trainerIds: [1], participants: [
          { userId: 100, attend: 1 }, { userId: 101, attend: 0, cancelreason: 'Krank' },
        ] }],
      },
      '/user/me/hours': { unevaluated: [{ id: 3, start: '2026-10-12T18:00:00', end: '2026-10-12T19:30:00', groups: ['Kinder'] }] },
    })

    const next = w.find('[data-test=next-training]')
    expect(next.text()).toContain('Dein nächstes Training')
    expect(next.find('[data-test=counts]').text()).toBe('1 zugesagt, 1 abgesagt')
    expect(next.find('[data-test=cancellations]').text()).toContain('Leo Klein')
    expect(next.find('[data-test=cancellations]').text()).toContain('Krank')
    expect(next.html()).toContain('/trainingsPrepare?training=7')
    expect(w.find('[data-test=unevaluated]').html()).toContain('/quickEvaluation/3')
    expect(w.find('[data-test=birthdays]').text()).toContain('Mia Schäfer')
    w.unmount()
  })

  it('falls back to the next training of the trainer branches when nothing is assigned', async () => {
    const w = await mountPage({
      '/trainingprepare/1': { data: [] },
      '/training/upcoming?groupIds=10': { data: [{ id: 9, start, end, groupIds: [10], trainerIds: [2], participants: [] }] },
      '/user/me/hours': { unevaluated: [] },
    })

    expect(w.find('[data-test=next-training]').text()).toContain('Nächstes Training deiner Sparten')
    expect(w.find('[data-test=next-training]').html()).not.toContain('/trainingsPrepare')
    expect(w.find('[data-test=unevaluated]').text()).toContain('Alles abgeschlossen')
    w.unmount()
  })
})

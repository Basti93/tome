import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

vi.mock('@/http/api', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}))
const push = vi.fn()
vi.mock('vue-router', () => ({
  useRoute: () => ({ params: { id: '7' } }),
  useRouter: () => ({ push }),
}))

import httpClient from '@/http/api'
import TrainingQuickEvaluationPage from './TrainingQuickEvaluationPage.vue'
import { useMasterDataStore } from '@/store/masterData'
import { useSnackbarStore } from '@/store/snackbar'

const vuetify = createVuetify({ components, directives })
const http = httpClient as unknown as { get: ReturnType<typeof vi.fn>; post: ReturnType<typeof vi.fn> }

function trainingResponse(overrides: Record<string, unknown> = {}) {
  return {
    data: {
      data: {
        id: 7,
        start: '2099-01-10T18:00:00+01:00',
        end: '2099-01-10T19:30:00+01:00',
        locationId: 1,
        groupIds: [10],
        trainers: [{ trainingId: 7, userId: 1, accountingTimeStart: null, accountingTimeEnd: null }],
        // Anna is pre-registered, Berta is absent, Carl (not in the group) already has a row
        participants: [
          { trainingId: 7, userId: 101, attend: 1 },
          { trainingId: 7, userId: 102, attend: 0 },
          { trainingId: 7, userId: 300, attend: 1 },
        ],
        evaluated: 0,
        ...overrides,
      },
    },
  }
}

async function mountPage() {
  const w = mount(TrainingQuickEvaluationPage, { global: { plugins: [vuetify] }, attachTo: document.body })
  await flushPromises()
  return w
}

describe('TrainingQuickEvaluationPage', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    document.body.innerHTML = ''
    const store = useMasterDataStore()
    store.setGroups([{ id: 10, name: 'Kids', branchId: 1 }] as any)
    store.setLocations([{ id: 1, name: 'Halle 1' }])
    store.setSimpleTrainers([{ id: 1, firstName: 'Tina', familyName: 'Trainer' }])
    store.simpleUsers = [
      { id: 101, firstName: 'Anna', familyName: 'Alt', groupIds: [10] },
      { id: 102, firstName: 'Berta', familyName: 'Bunt', groupIds: [10] },
      { id: 103, firstName: 'Dora', familyName: 'Dritte', groupIds: [10] },
      { id: 300, firstName: 'Carl', familyName: 'Chaos', groupIds: [20] },
      { id: 400, firstName: 'Emil', familyName: 'Extern', groupIds: [20] },
    ] as any
    http.get.mockResolvedValue(trainingResponse())
    http.post.mockResolvedValue({ data: {} })
  })

  const pressed = (w: any, id: number) => w.find(`[data-test="toggle-${id}"]`).attributes('aria-pressed')

  it('lists group members plus people with a participation and prefills the toggles', async () => {
    const w = await mountPage()

    expect(http.get).toHaveBeenCalledWith('/trainingevaluation/7/detail')
    expect(w.findAll('[data-test="member-row"]')).toHaveLength(4)
    expect(pressed(w, 101)).toBe('true')
    expect(pressed(w, 102)).toBe('false')
    expect(pressed(w, 103)).toBe('false')
    expect(pressed(w, 300)).toBe('true')
    expect(w.text()).toContain('Halle 1')
    expect(w.text()).toContain('Kids')
    expect(w.find('[data-test="present-count"]').text()).toBe('2 von 4 da')
  })

  it('prefills the trainer times with the training times', async () => {
    const w = await mountPage()

    expect((w.find('[data-test="trainer-start-1"] input').element as HTMLInputElement).value).toBe('18:00')
    expect((w.find('[data-test="trainer-end-1"] input').element as HTMLInputElement).value).toBe('19:30')
    expect(w.text()).toContain('Tina Trainer')
  })

  it('uses existing accounting times when there are some', async () => {
    http.get.mockResolvedValue(trainingResponse({
      trainers: [{ trainingId: 7, userId: 1, accountingTimeStart: '2099-01-10T18:15:00+01:00', accountingTimeEnd: '2099-01-10T19:00:00+01:00' }],
    }))
    const w = await mountPage()

    expect((w.find('[data-test="trainer-start-1"] input').element as HTMLInputElement).value).toBe('18:15')
    expect((w.find('[data-test="trainer-end-1"] input').element as HTMLInputElement).value).toBe('19:00')
  })

  it('toggles presence per person', async () => {
    const w = await mountPage()

    await w.find('[data-test="toggle-103"]').trigger('click')
    await w.find('[data-test="toggle-101"]').trigger('click')

    expect(pressed(w, 103)).toBe('true')
    expect(pressed(w, 101)).toBe('false')
  })

  it('submits the attendees and trainer times and returns to the list', async () => {
    const w = await mountPage()
    await w.find('[data-test="toggle-103"]').trigger('click')
    await w.find('[data-test="toggle-101"]').trigger('click')
    await w.find('[data-test="trainer-end-1"] input').setValue('19:00')

    await w.find('[data-test="complete"]').trigger('click')
    await flushPromises()

    expect(http.post).toHaveBeenCalledTimes(1)
    const [url, body] = http.post.mock.calls[0]
    expect(url).toBe('/trainingevaluation/7/complete')
    expect([...body.attendeeIds].sort()).toEqual([103, 300])
    expect(body.trainerTimes).toHaveLength(1)
    expect(body.trainerTimes[0].userId).toBe(1)
    expect(body.trainerTimes[0].start).toMatch(/^2099-01-10T18:00:00/)
    expect(body.trainerTimes[0].end).toMatch(/^2099-01-10T19:00:00/)
    expect(useSnackbarStore().text).toBe('Training abgeschlossen')
    expect(push).toHaveBeenCalledWith('/trainingsEvaluation')
  })

  it('sends the trimmed note with the completion and prefills an existing one', async () => {
    http.get.mockResolvedValue(trainingResponse({ note: 'Alt' }))
    const w = await mountPage()
    const field = w.find('[data-test="training-note"] textarea')
    expect((field.element as HTMLTextAreaElement).value).toBe('Alt')

    await field.setValue('  Aufwärmen kürzen ')
    await w.find('[data-test="complete"]').trigger('click')
    await flushPromises()

    expect(http.post.mock.calls[0][1].note).toBe('Aufwärmen kürzen')
  })

  it('does not submit when the end is not after the start', async () => {
    const w = await mountPage()
    await w.find('[data-test="trainer-end-1"] input').setValue('17:00')

    await w.find('[data-test="complete"]').trigger('click')
    await flushPromises()

    expect(http.post).not.toHaveBeenCalled()
    expect(useSnackbarStore().color).toBe('error')
    expect(push).not.toHaveBeenCalled()
  })

  it('adds a person from outside the groups as present', async () => {
    const w = await mountPage()
    const autocomplete = w.findComponent('[data-test="add-person"]') as any

    expect(autocomplete.props('items').map((i: any) => i.id)).toEqual([400])
    await autocomplete.vm.$emit('update:modelValue', 400)
    await flushPromises()

    expect(pressed(w, 400)).toBe('true')
    await w.find('[data-test="complete"]').trigger('click')
    await flushPromises()
    expect([...http.post.mock.calls[0][1].attendeeIds].sort()).toEqual([101, 300, 400])
  })

  it('stays on the page and shows an error when saving fails', async () => {
    http.post.mockRejectedValue({ response: { status: 403 } })
    const w = await mountPage()

    await w.find('[data-test="complete"]').trigger('click')
    await flushPromises()

    expect(push).not.toHaveBeenCalled()
    expect(useSnackbarStore().text).toContain('nicht abschließen')
  })

  it('explains a 403 on load', async () => {
    http.get.mockRejectedValue({ response: { status: 403 } })
    const w = await mountPage()

    expect(w.find('[data-test="load-error"]').text()).toContain('nicht als Trainer zugeordnet')
    expect(w.find('[data-test="complete"]').exists()).toBe(false)
  })
})

import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { createMemoryHistory, createRouter } from 'vue-router'
import AnnouncementDialog from './AnnouncementDialog.vue'
import { useAuthStore } from '@/store/auth'

async function mountDialog(user: unknown, path = '/') {
  setActivePinia(createPinia())
  useAuthStore().user = user as any
  const router = createRouter({
    history: createMemoryHistory(),
    routes: ['/', '/login', '/attendance'].map((p) => ({ path: p, component: { template: '<div/>' } })),
  })
  await router.push(path)
  const w = mount(AnnouncementDialog, { global: { plugins: [createVuetify({ components, directives }), router] }, attachTo: document.body })
  await flushPromises()
  return { w, router }
}

describe('AnnouncementDialog', () => {
  beforeEach(() => {
    localStorage.clear()
    document.body.innerHTML = ''
  })

  it('shows the attendance hint to a trainer once and remembers it', async () => {
    const { w } = await mountDialog({ id: 1, isTrainer: true })
    expect(document.body.textContent).toContain('Neu: Anwesenheits-Übersicht')

    ;(w.vm as any).close()
    await flushPromises()
    expect(document.body.textContent).not.toContain('Neu: Anwesenheits-Übersicht')
    w.unmount()

    const again = await mountDialog({ id: 1, isTrainer: true })
    expect(document.body.textContent).not.toContain('Neu: Anwesenheits-Übersicht')
    again.w.unmount()
  })

  it('"Ansehen" opens the page and marks the hint as seen', async () => {
    const { w, router } = await mountDialog({ id: 4, isAdmin: true })

    ;(w.vm as any).openLink()
    await flushPromises()

    await vi.waitFor(() => expect(router.currentRoute.value.path).toBe('/attendance'))
    expect(document.body.textContent).not.toContain('Neu: Anwesenheits-Übersicht')
    w.unmount()
  })

  it.each([
    ['a member', { id: 2, isTrainer: false, isAdmin: false }, '/'],
    ['an anonymous visitor', null, '/'],
    ['a trainer on the login page', { id: 3, isTrainer: true }, '/login'],
  ])('does not show it to %s', async (_name, user, path) => {
    const { w } = await mountDialog(user, path)

    expect(document.body.textContent).not.toContain('Neu: Anwesenheits-Übersicht')
    w.unmount()
  })
})

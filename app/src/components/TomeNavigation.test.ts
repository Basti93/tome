import { beforeEach, describe, expect, it } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { createMemoryHistory, createRouter } from 'vue-router'
import { defineComponent, h } from 'vue'
import TomeNavigation from './TomeNavigation.vue'
import { useAuthStore } from '@/store/auth'

// Vuetify layout components need a v-layout around them
const Layout = defineComponent({ components: { TomeNavigation }, template: '<v-layout><TomeNavigation /></v-layout>' })

async function mountNav(user: unknown, width = 1400) {
  setActivePinia(createPinia())
  useAuthStore().user = user as any
  const router = createRouter({ history: createMemoryHistory(), routes: [{ path: '/:p(.*)*', component: { render: () => h('div') } }] })
  await router.push('/')
  const vuetify = createVuetify({ components, directives, display: { thresholds: { xs: 0, sm: 600, md: width > 1000 ? 960 : 5000, lg: 6000, xl: 7000, xxl: 8000 } } })
  const w = mount({ components: { Layout }, template: '<Layout />' }, { global: { plugins: [vuetify, router] }, attachTo: document.body })
  await flushPromises()
  return w
}

describe('TomeNavigation', () => {
  beforeEach(() => {
    localStorage.clear()
    document.body.innerHTML = ''
    document.body.className = ''
  })

  it('shows "Abmelden" outside of every collapsible group for logged-in users', async () => {
    for (const user of [{ id: 1, isTrainer: true }, { id: 2, isAdmin: true }, { id: 3 }]) {
      const w = await mountNav(user)
      const item = [...document.querySelectorAll('.v-list-item')].find((el) => el.textContent?.includes('Abmelden'))

      expect(item, JSON.stringify(user)).toBeTruthy()
      expect(item!.closest('.v-list-group')).toBeNull()
      expect(document.body.textContent).not.toContain('Anmelden')
      w.unmount()
    }
  })

  it('shows "Anmelden" and no "Abmelden" for anonymous visitors', async () => {
    const w = await mountNav(null)

    expect(document.body.textContent).toContain('Anmelden')
    expect(document.body.textContent).not.toContain('Abmelden')
    expect(document.body.textContent).not.toContain('Verwaltung')
    w.unmount()
  })

  it('trainers get the Training / Verwaltung / Persönlich sections with their entries', async () => {
    const w = await mountNav({ id: 1, isTrainer: true })
    const text = document.body.textContent ?? ''

    for (const label of ['Training', 'Verwaltung', 'Persönlich', 'Heute', 'Vorbereiten', 'Nachbereiten', 'Anwesenheit', 'Meine Stunden', 'Profileinstellungen']) {
      expect(text).toContain(label)
    }
    w.unmount()
  })

  it('members do not see the trainer entries', async () => {
    const w = await mountNav({ id: 3 })
    const text = document.body.textContent ?? ''

    for (const label of ['Heute', 'Vorbereiten', 'Nachbereiten', 'Anwesenheit', 'Meine Stunden']) {
      expect(text).not.toContain(label)
    }
    expect(text).toContain('Statistiken')
    w.unmount()
  })

  it('shows the bottom navigation on phones for trainers only', async () => {
    const trainer = await mountNav({ id: 1, isTrainer: true }, 500)
    expect(document.querySelector('.tome-bottom-nav')).toBeTruthy()
    expect(document.body.classList.contains('has-bottom-nav')).toBe(true)
    expect(document.querySelector('.tome-bottom-nav')!.textContent).toContain('Mehr')
    trainer.unmount()

    const member = await mountNav({ id: 3 }, 500)
    expect(document.querySelector('.tome-bottom-nav')).toBeNull()
    member.unmount()

    const desktop = await mountNav({ id: 1, isTrainer: true }, 1400)
    expect(document.querySelector('.tome-bottom-nav')).toBeNull()
    desktop.unmount()
  })
})

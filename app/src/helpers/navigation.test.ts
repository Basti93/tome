import { describe, expect, it } from 'vitest'
import { isVisible, startPageRedirect, visibleSections } from './navigation'

const titles = (user: any) => visibleSections(user).flatMap((s) => s.items.map((i) => i.title))

describe('navigation visibility', () => {
  it('anonymous visitors see the public entries only', () => {
    expect(titles(null)).toEqual(['Trainingsanmeldung', 'Kalender', 'Abwesenheit eintragen', 'Gruppenübersicht', 'Über die App'])
    expect(visibleSections(null).map((s) => s.id)).toEqual(['training', 'personal'])
  })

  it('members see statistics and their profile, but nothing of the trainer area', () => {
    const t = titles({ isTrainer: false, isAdmin: false })

    expect(t).toContain('Statistiken')
    expect(t).toContain('Profileinstellungen')
    for (const hidden of ['Heute', 'Vorbereiten', 'Nachbereiten', 'Anwesenheit', 'Meine Stunden', 'Trainings', 'Serien', 'Benutzer', 'Gruppen', 'Sparten', 'Orte']) {
      expect(t).not.toContain(hidden)
    }
  })

  it('trainers and admins see everything, grouped in Training / Verwaltung / Persönlich', () => {
    for (const user of [{ isTrainer: true }, { isAdmin: true }]) {
      const sections = visibleSections(user)
      expect(sections.map((s) => s.id)).toEqual(['training', 'admin', 'personal'])
      expect(sections[0].items[0].title).toBe('Heute')
      expect(sections.find((s) => s.id === 'admin')?.collapsible).toBe(true)
      expect(titles(user)).toEqual(expect.arrayContaining(['Meine Stunden', 'Anwesenheit', 'Trainings', 'Serien', 'Benutzer', 'Sparten', 'Orte']))
    }
  })

  it('isVisible', () => {
    expect(isVisible('always', null)).toBe(true)
    expect(isVisible('loggedIn', null)).toBe(false)
    expect(isVisible('trainer', {})).toBe(false)
  })
})

describe('start page for trainers and admins', () => {
  const trainer = { isTrainer: true }

  it('redirects "/" to "Heute" when the app starts and after the login', () => {
    expect(startPageRedirect({ path: '/' }, null, trainer)).toBe('/today')
    expect(startPageRedirect({ path: '/' }, '/login', trainer)).toBe('/today')
    expect(startPageRedirect({ path: '/' }, '/login?redirect=%2F', { isAdmin: true })).toBe('/today')
  })

  it('leaves later visits of the check-in page, members and anonymous visitors alone', () => {
    expect(startPageRedirect({ path: '/' }, '/today', trainer)).toBeNull()
    expect(startPageRedirect({ path: '/' }, '/calendar', trainer)).toBeNull()
    expect(startPageRedirect({ path: '/' }, null, { isTrainer: false })).toBeNull()
    expect(startPageRedirect({ path: '/' }, null, null)).toBeNull()
    expect(startPageRedirect({ path: '/calendar' }, null, trainer)).toBeNull()
  })
})

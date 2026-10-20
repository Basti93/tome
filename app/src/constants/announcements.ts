export interface Announcement {
  /** unique and stable: it is the key of the "seen" marker */
  id: string
  /** shown to users with one of these roles */
  roles: ('trainer' | 'admin')[]
  title: string
  text: string
  link?: string
  linkText?: string
}

/** Hints about new features. One entry per hint; every user sees an entry once per device (newest last). */
export const announcements: Announcement[] = [
  {
    id: 'attendance-2026-10',
    roles: ['trainer', 'admin'],
    title: 'Neu: Anwesenheits-Übersicht',
    text: 'Unter „Anwesenheit“ im Menü siehst du für deine Gruppen, wer wann da war, mit Anwesenheitsquote pro Sportler.',
    link: '/attendance',
    linkText: 'Ansehen',
  },
  {
    id: 'hours-birthdays-2026-10',
    roles: ['trainer', 'admin'],
    title: 'Neu: Meine Stunden und Geburtstage',
    text: 'Unter „Meine Stunden“ im Menü siehst du deine ÜL-Stunden pro Monat und kannst die Abrechnung exportieren. Außerdem zeigt ein 🎂, wer Geburtstag hat, und morgens gibt es eine Push (abschaltbar im Profil).',
    link: '/hours',
    linkText: 'Ansehen',
  },
]

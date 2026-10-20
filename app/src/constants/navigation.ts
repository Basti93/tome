/** Who sees a navigation item. `loggedIn` = everybody with an account, `trainer` = trainers and admins. */
export type NavVisibility = 'always' | 'loggedIn' | 'trainer'

export interface NavItem {
  to: string
  icon: string
  title: string
  visible: NavVisibility
}

export interface NavSection {
  id: 'training' | 'admin' | 'personal'
  title: string
  /** collapsible section, collapsed by default (the open state is remembered) */
  collapsible?: boolean
  items: NavItem[]
}

export const navSections: NavSection[] = [
  {
    id: 'training',
    title: 'Training',
    items: [
      { to: '/today', icon: 'mdi-home-outline', title: 'Heute', visible: 'trainer' },
      { to: '/', icon: 'mdi-note-text', title: 'Trainingsanmeldung', visible: 'always' },
      { to: '/trainingsPrepare', icon: 'mdi-calendar', title: 'Vorbereiten', visible: 'trainer' },
      { to: '/trainingsEvaluation', icon: 'mdi-check-circle', title: 'Nachbereiten', visible: 'trainer' },
      { to: '/attendance', icon: 'mdi-table-check', title: 'Anwesenheit', visible: 'trainer' },
      { to: '/hours', icon: 'mdi-clock-outline', title: 'Meine Stunden', visible: 'trainer' },
      { to: '/calendar', icon: 'mdi-calendar-month', title: 'Kalender', visible: 'always' },
    ],
  },
  {
    id: 'admin',
    title: 'Verwaltung',
    collapsible: true,
    items: [
      { to: '/trainings', icon: 'mdi-format-list-bulleted', title: 'Trainings', visible: 'trainer' },
      { to: '/trainingSeries', icon: 'mdi-refresh', title: 'Serien', visible: 'trainer' },
      { to: '/users', icon: 'mdi-account', title: 'Benutzer', visible: 'trainer' },
      { to: '/groups', icon: 'mdi-account-multiple', title: 'Gruppen', visible: 'trainer' },
      { to: '/branches', icon: 'mdi-chart-bubble', title: 'Sparten', visible: 'trainer' },
      { to: '/locations', icon: 'mdi-map-marker', title: 'Orte', visible: 'trainer' },
      { to: '/statistics', icon: 'mdi-chart-bar', title: 'Statistiken', visible: 'loggedIn' },
    ],
  },
  {
    id: 'personal',
    title: 'Persönlich',
    items: [
      { to: '/profile', icon: 'mdi-cog', title: 'Profileinstellungen', visible: 'loggedIn' },
      { to: '/absenceForm', icon: 'mdi-car', title: 'Abwesenheit eintragen', visible: 'always' },
      { to: '/groupsOverview', icon: 'mdi-account-multiple', title: 'Gruppenübersicht', visible: 'always' },
      { to: '/info', icon: 'mdi-information', title: 'Über die App', visible: 'always' },
    ],
  },
]

/** Entries of the bottom navigation on phones (trainers and admins only); "Mehr" opens the drawer. */
export const bottomNavItems = [
  { to: '/today', icon: 'mdi-home-outline', title: 'Heute' },
  { to: '/trainingsEvaluation', icon: 'mdi-check-circle', title: 'Nachbereiten' },
  { to: '/attendance', icon: 'mdi-table-check', title: 'Anwesenheit' },
]

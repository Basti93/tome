import { navSections, type NavItem, type NavSection, type NavVisibility } from '@/constants/navigation'

export interface NavUser {
  isTrainer?: boolean
  isAdmin?: boolean
}

export function isVisible(visible: NavVisibility, user: NavUser | null | undefined): boolean {
  if (visible === 'always') return true
  if (!user) return false
  if (visible === 'loggedIn') return true
  return !!(user.isTrainer || user.isAdmin)
}

export interface VisibleSection extends Omit<NavSection, 'items'> {
  items: NavItem[]
}

/** The sections with the items this user may see; sections without any visible item are dropped. */
export function visibleSections(user: NavUser | null | undefined, sections: NavSection[] = navSections): VisibleSection[] {
  return sections
    .map((section) => ({ ...section, items: section.items.filter((item) => isVisible(item.visible, user)) }))
    .filter((section) => section.items.length > 0)
}

export const isTrainerOrAdmin = (user: NavUser | null | undefined) => !!(user && (user.isTrainer || user.isAdmin))

/**
 * Trainers and admins start on "Heute" instead of the check-in page: when the app is opened on "/" or right after the
 * login. A later click on "Trainingsanmeldung" (also "/") is a normal navigation and stays on the check-in page.
 */
export function startPageRedirect(to: { path: string }, fromPath: string | null, user: NavUser | null | undefined): string | null {
  if (to.path !== '/' || !isTrainerOrAdmin(user)) return null
  const appStart = fromPath === null
  const afterLogin = fromPath !== null && fromPath.startsWith('/login')
  return appStart || afterLogin ? '/today' : null
}

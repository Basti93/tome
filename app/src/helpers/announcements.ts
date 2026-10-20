import { announcements, type Announcement } from '@/constants/announcements'

interface AnnouncementUser {
  id: number
  isTrainer?: boolean
  isAdmin?: boolean
}

const key = (userId: number, announcementId: string) => `announcement-seen:${userId}:${announcementId}`

export function isSeen(userId: number, announcementId: string): boolean {
  try {
    return localStorage.getItem(key(userId, announcementId)) === '1'
  } catch {
    return false
  }
}

export function markSeen(userId: number, announcementId: string): void {
  try {
    localStorage.setItem(key(userId, announcementId), '1')
  } catch {
    // storage unavailable (private mode): the hint may show again, that is fine
  }
}

/** The first announcement this (logged in) user has not seen yet and may see, or null. At most one at a time. */
export function nextAnnouncement(user: AnnouncementUser | null | undefined, list: Announcement[] = announcements): Announcement | null {
  if (!user) return null
  const roles = [user.isTrainer ? 'trainer' : null, user.isAdmin ? 'admin' : null]
  return list.find((a) => a.roles.some((r) => roles.includes(r)) && !isSeen(user.id, a.id)) ?? null
}

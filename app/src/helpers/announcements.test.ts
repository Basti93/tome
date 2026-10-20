import { beforeEach, describe, expect, it } from 'vitest'
import { isSeen, markSeen, nextAnnouncement } from './announcements'
import type { Announcement } from '@/constants/announcements'

const list: Announcement[] = [
  { id: 'a', roles: ['trainer', 'admin'], title: 'A', text: 'a' },
  { id: 'b', roles: ['admin'], title: 'B', text: 'b' },
]
const trainer = { id: 1, isTrainer: true, isAdmin: false }
const admin = { id: 2, isTrainer: false, isAdmin: true }
const member = { id: 3, isTrainer: false, isAdmin: false }

describe('nextAnnouncement', () => {
  beforeEach(() => localStorage.clear())

  it('shows a hint to the roles it is for, not to members or anonymous users', () => {
    expect(nextAnnouncement(trainer, list)?.id).toBe('a')
    expect(nextAnnouncement(admin, list)?.id).toBe('a')
    expect(nextAnnouncement(member, list)).toBeNull()
    expect(nextAnnouncement(null, list)).toBeNull()
    expect(nextAnnouncement(undefined, list)).toBeNull()
  })

  it('does not show a hint again after it was seen, and then moves on to the next one (one at a time)', () => {
    markSeen(2, 'a')

    expect(isSeen(2, 'a')).toBe(true)
    expect(nextAnnouncement(admin, list)?.id).toBe('b')
    markSeen(2, 'b')
    expect(nextAnnouncement(admin, list)).toBeNull()
  })

  it('is remembered per user', () => {
    markSeen(1, 'a')

    expect(nextAnnouncement(trainer, list)).toBeNull()
    expect(nextAnnouncement({ id: 9, isTrainer: true }, list)?.id).toBe('a')
  })

  it('still works when localStorage is unavailable', () => {
    const original = Storage.prototype.getItem
    Storage.prototype.getItem = () => { throw new Error('denied') }
    try {
      expect(nextAnnouncement(trainer, list)?.id).toBe('a')
      expect(() => markSeen(1, 'a')).not.toThrow()
    } finally {
      Storage.prototype.getItem = original
    }
  })
})

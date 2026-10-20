import { describe, expect, it } from 'vitest'
import dayjs from '@/helpers/dayjs'
import { participationSummary, pickNextTraining, todaysBirthdays, type UpcomingTraining } from './today'

const t = (id: number): UpcomingTraining => ({ id, start: '', end: '', groupIds: [], trainerIds: [], participants: [] })

describe('pickNextTraining', () => {
  it('prefers the next own training, then the next one of the branches, else null', () => {
    expect(pickNextTraining([t(1), t(2)], [t(3)])).toEqual({ training: t(1), own: true })
    expect(pickNextTraining([], [t(3), t(4)])).toEqual({ training: t(3), own: false })
    expect(pickNextTraining([], [])).toBeNull()
  })
})

describe('participationSummary', () => {
  it('counts the confirmations and lists the cancellations with their reasons', () => {
    const s = participationSummary([
      { userId: 1, attend: 1 },
      { userId: 2, attend: true },
      { userId: 3, attend: 0, cancelreason: ' Krank ' },
      { userId: 4, attend: false, cancelreason: '' },
    ])

    expect(s.yes).toBe(2)
    expect(s.cancellations).toEqual([{ userId: 3, reason: 'Krank' }, { userId: 4, reason: null }])
  })

  it('handles missing participants', () => {
    expect(participationSummary(undefined as any)).toEqual({ yes: 0, cancellations: [] })
  })
})

describe('todaysBirthdays', () => {
  const users = [
    { id: 1, firstName: 'Mia', familyName: 'Schäfer', groupIds: [10], birthdate: '2015-10-20' },
    { id: 2, firstName: 'Leo', familyName: 'Klein', groupIds: [11], birthdate: '2012-10-20' },
    { id: 3, firstName: 'Ben', familyName: 'Weber', groupIds: [10], birthdate: '2013-10-19' },
    { id: 4, firstName: 'Zoe', familyName: 'Ohne', groupIds: [10], birthdate: null },
  ]

  it('lists only birthdays of today in the given groups', () => {
    expect(todaysBirthdays(users, [10], dayjs('2026-10-20'))).toEqual(['Mia Schäfer'])
    expect(todaysBirthdays(users, [10, 11], dayjs('2026-10-20'))).toEqual(['Leo Klein', 'Mia Schäfer'])
    expect(todaysBirthdays(users, [10], dayjs('2026-10-21'))).toEqual([])
  })
})

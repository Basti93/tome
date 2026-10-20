import { describe, expect, it } from 'vitest'
import dayjs from '@/helpers/dayjs'
import { birthdayHint } from './birthday'

const d = (s: string) => dayjs(s)

describe('birthdayHint', () => {
  it('has a hint on the day itself ("heute") with the age', () => {
    const hint = birthdayHint('2015-10-20', d('2026-10-20'), { today: d('2026-10-20') })

    expect(hint).toMatchObject({ when: 'today', age: 11, label: 'Hat heute Geburtstag (11)' })
  })

  it('has a hint for a birthday in the days before the training (yesterday, a week ago) but not earlier or later', () => {
    const today = d('2026-10-20')

    expect(birthdayHint('2015-10-19', today, { today })?.label).toBe('Hatte am 19.10. Geburtstag (11)')
    expect(birthdayHint('2015-10-13', today, { today })?.when).toBe('past')
    expect(birthdayHint('2015-10-12', today, { today })).toBeNull()
    expect(birthdayHint('2015-10-21', today, { today })).toBeNull()
  })

  it('uses the training day as reference, also for a training in the future', () => {
    const today = d('2026-10-20')
    const hint = birthdayHint('2015-10-22', d('2026-10-22'), { today })

    expect(hint).toMatchObject({ when: 'training', label: 'Hat am 22.10. Geburtstag (11)' })
    expect(birthdayHint('2015-10-22', d('2026-10-21'), { today })).toBeNull()
  })

  it('wraps the year: a training in January finds a birthday in late December', () => {
    const today = d('2027-01-03')

    expect(birthdayHint('2014-12-30', today, { today })).toMatchObject({ age: 12, label: 'Hatte am 30.12. Geburtstag (12)' })
    expect(birthdayHint('2014-12-20', today, { today })).toBeNull()
  })

  it('celebrates 29 February on 28 February in non-leap years and on the 29th in leap years', () => {
    expect(birthdayHint('2016-02-29', d('2027-02-28'), { today: d('2027-02-28') })).toMatchObject({ when: 'today', age: 11 })
    expect(birthdayHint('2016-02-29', d('2027-03-02'), { today: d('2027-03-02') })?.label).toBe('Hatte am 28.02. Geburtstag (11)')
    expect(birthdayHint('2016-02-29', d('2028-02-29'), { today: d('2028-02-29') })).toMatchObject({ when: 'today', age: 12 })
  })

  it('has no hint without (a valid) birthdate', () => {
    expect(birthdayHint(null, d('2026-10-20'))).toBeNull()
    expect(birthdayHint(undefined, d('2026-10-20'))).toBeNull()
    expect(birthdayHint('', d('2026-10-20'))).toBeNull()
    expect(birthdayHint('kaputt', d('2026-10-20'))).toBeNull()
  })
})

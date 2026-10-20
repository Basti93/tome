import { describe, expect, it } from 'vitest'
import dayjs from '@/helpers/dayjs'
import { attendanceRate, rangeFor, sortAthletes, type AttendanceAthlete } from './attendance'

const athlete = (id: number, familyName: string, firstName: string, rate: number | null): AttendanceAthlete => ({
  id, familyName, firstName, presentCount: 0, rate, cells: [],
})

describe('attendanceRate', () => {
  it('is present / total in percent, rounded, null without trainings', () => {
    expect(attendanceRate(2, 4)).toBe(50)
    expect(attendanceRate(1, 3)).toBe(33)
    expect(attendanceRate(2, 3)).toBe(67)
    expect(attendanceRate(0, 5)).toBe(0)
    expect(attendanceRate(0, 0)).toBeNull()
  })
})

describe('sortAthletes', () => {
  const list = [athlete(1, 'Müller', 'Max', 50), athlete(2, 'Ärmel', 'Zoe', 90), athlete(3, 'Bauer', 'Ann', null), athlete(4, 'Bauer', 'Ben', 50)]

  it('sorts by name (German collation, family name first) in both directions', () => {
    expect(sortAthletes(list, { key: 'name', dir: 'asc' }).map((a) => a.id)).toEqual([2, 3, 4, 1])
    expect(sortAthletes(list, { key: 'name', dir: 'desc' }).map((a) => a.id)).toEqual([1, 4, 3, 2])
  })

  it('sorts by rate, ties by name, athletes without a rate always last', () => {
    expect(sortAthletes(list, { key: 'rate', dir: 'desc' }).map((a) => a.id)).toEqual([2, 4, 1, 3])
    expect(sortAthletes(list, { key: 'rate', dir: 'asc' }).map((a) => a.id)).toEqual([4, 1, 2, 3])
  })

  it('does not change the original array', () => {
    const copy = [...list]
    sortAthletes(list, { key: 'rate', dir: 'asc' })
    expect(list).toEqual(copy)
  })
})

describe('rangeFor', () => {
  const today = dayjs('2030-05-15')

  it('counts the presets back from today', () => {
    expect(rangeFor('4weeks', today)).toEqual({ from: '2030-04-17', to: '2030-05-15' })
    expect(rangeFor('3months', today)).toEqual({ from: '2030-02-15', to: '2030-05-15' })
    expect(rangeFor('year', today)).toEqual({ from: '2030-01-01', to: '2030-05-15' })
  })
})

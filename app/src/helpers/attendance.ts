import dayjs from '@/helpers/dayjs'

export type CellStatus = 'present' | 'excused' | 'absent' | 'none'

export interface AttendanceAthlete {
  id: number
  firstName: string
  familyName: string
  presentCount: number
  rate: number | null
  cells: { status: CellStatus; reason: string | null }[]
}

/** Attendance in percent (present / evaluated trainings), null without trainings. */
export function attendanceRate(present: number, total: number): number | null {
  return total > 0 ? Math.round((present / total) * 100) : null
}

export type AthleteSort = { key: 'name' | 'rate'; dir: 'asc' | 'desc' }

const byName = (a: AttendanceAthlete, b: AttendanceAthlete) =>
  a.familyName.localeCompare(b.familyName, 'de') || a.firstName.localeCompare(b.firstName, 'de')

/** Sorted copy: by name, or by rate (athletes without a rate always last, ties by name). */
export function sortAthletes(athletes: AttendanceAthlete[], { key, dir }: AthleteSort): AttendanceAthlete[] {
  const factor = dir === 'asc' ? 1 : -1
  return [...athletes].sort((a, b) => {
    if (key === 'name') return factor * byName(a, b)
    if (a.rate === null && b.rate === null) return byName(a, b)
    if (a.rate === null) return 1
    if (b.rate === null) return -1
    return factor * (a.rate - b.rate) || byName(a, b)
  })
}

export type RangePreset = '4weeks' | '3months' | 'year'

/** from/to (YYYY-MM-DD) of a preset, counted back from today. */
export function rangeFor(preset: RangePreset, today = dayjs()): { from: string; to: string } {
  const to = today.format('YYYY-MM-DD')
  const from =
    preset === '4weeks' ? today.subtract(28, 'day') : preset === '3months' ? today.subtract(3, 'month') : today.startOf('year')
  return { from: from.format('YYYY-MM-DD'), to }
}

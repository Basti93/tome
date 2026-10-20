export interface HoursEntry {
  trainingId: number
  start: string
  end: string
  minutes: number
  hours: number
  groups: string[]
}

export interface HoursMonth {
  month: number
  trainingCount: number
  minutes: number
  hours: number
  entries: HoursEntry[]
}

export interface HoursQuarter {
  quarter: number
  trainingCount: number
  minutes: number
  hours: number
}

const nf = new Intl.NumberFormat('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

/** ÜL-Stunden (45 min) as German decimal, e.g. 3,33 */
export function formatHours(hours: number): string {
  return nf.format(hours)
}

/** 150 -> "2:30 h" */
export function formatMinutes(minutes: number): string {
  return `${Math.floor(minutes / 60)}:${String(minutes % 60).padStart(2, '0')} h`
}

const MONTHS = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember']
export const monthName = (month: number) => MONTHS[month - 1]

/** Quarter (1-4) a month (1-12) belongs to. */
export const quarterOf = (month: number) => Math.floor((month - 1) / 3) + 1

/** Months of a quarter (for the rows of the table), skipping empty months only when asked to. */
export function monthsOfQuarter(months: HoursMonth[], quarter: number): HoursMonth[] {
  return months.filter((m) => quarterOf(m.month) === quarter)
}

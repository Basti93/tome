import dayjs, { type Dayjs } from '@/helpers/dayjs'

export interface BirthdayHint {
  /** the birthday (this or the previous year) the hint is about */
  date: Dayjs
  age: number
  /** 'today' (really today), 'training' (on the day of the training, not today) or 'past' */
  when: 'today' | 'training' | 'past'
  label: string
}

/** The birthday of a person in a given year; 29 February is celebrated on 28 February in non-leap years. */
function birthdayIn(birth: Dayjs, year: number): Dayjs {
  const month = birth.month()
  const day = birth.date()
  const candidate = dayjs(new Date(year, month, day))
  return candidate.month() === month ? candidate : dayjs(new Date(year, month, 28))
}

/**
 * Hint for the trainer lists: the person has a birthday today, on the day of the training or within the
 * `days` days before the training day (default 7). Returns null without birthdate or when no birthday is in that window.
 * `reference` is the training day, `today` is injectable for tests.
 */
export function birthdayHint(
  birthdate: string | null | undefined,
  reference: Dayjs | string | Date,
  options: { days?: number; today?: Dayjs } = {},
): BirthdayHint | null {
  if (!birthdate) return null
  const birth = dayjs(birthdate)
  if (!birth.isValid()) return null
  const day = dayjs(reference).startOf('day')
  const today = (options.today ?? dayjs()).startOf('day')
  const days = options.days ?? 7

  // year wrap: a training on 3 January looks at 30 December of the year before
  for (const year of [day.year(), day.year() - 1]) {
    const date = birthdayIn(birth, year)
    const diff = day.diff(date, 'day')
    if (diff >= 0 && diff <= days) {
      const age = year - birth.year()
      const formatted = date.format('DD.MM.')
      const isToday = date.isSame(today, 'day')
      const when = isToday ? 'today' : diff === 0 ? 'training' : 'past'
      const label = isToday ? `Hat heute Geburtstag (${age})` : diff === 0 ? `Hat am ${formatted} Geburtstag (${age})` : `Hatte am ${formatted} Geburtstag (${age})`
      return { date, age, when, label }
    }
  }
  return null
}

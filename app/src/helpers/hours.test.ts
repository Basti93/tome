import { describe, expect, it } from 'vitest'
import { formatHours, formatMinutes, monthName, monthsOfQuarter, quarterOf, type HoursMonth } from './hours'

describe('hours helpers', () => {
  it('formats ÜL-Stunden with a German decimal comma', () => {
    expect(formatHours(3.33)).toBe('3,33')
    expect(formatHours(0)).toBe('0,00')
    expect(formatHours(1234.5)).toBe('1.234,50')
  })

  it('formats minutes as h:mm', () => {
    expect(formatMinutes(150)).toBe('2:30 h')
    expect(formatMinutes(45)).toBe('0:45 h')
    expect(formatMinutes(0)).toBe('0:00 h')
  })

  it('knows month names and quarters', () => {
    expect(monthName(1)).toBe('Januar')
    expect(monthName(3)).toBe('März')
    expect([1, 3, 4, 6, 7, 9, 10, 12].map(quarterOf)).toEqual([1, 1, 2, 2, 3, 3, 4, 4])
  })

  it('selects the months of a quarter', () => {
    const months = Array.from({ length: 12 }, (_, i) => ({ month: i + 1, trainingCount: 0, minutes: 0, hours: 0, entries: [] }) as HoursMonth)

    expect(monthsOfQuarter(months, 2).map((m) => m.month)).toEqual([4, 5, 6])
    expect(monthsOfQuarter(months, 4).map((m) => m.month)).toEqual([10, 11, 12])
  })
})

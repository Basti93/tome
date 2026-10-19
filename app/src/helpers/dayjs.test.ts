import { describe, it, expect } from 'vitest'
import dayjs, { parseApiDate } from '@/helpers/dayjs'

describe('dayjs helper', () => {
  it('parses API timestamps with the legacy moment format string as local time', () => {
    const d = dayjs('2024-03-05T18:30:00', 'YYYY-MM-DDTHH:mm')
    expect(d.isValid()).toBe(true)
    expect(d.format('HH:mm')).toBe('18:30')
    expect(d.format('DD.MM.YYYY')).toBe('05.03.2024')
  })

  it('parses "YYYY-MM-DD HH:mm:ss" strings with the same format', () => {
    expect(dayjs('2024-03-05 07:05:00', 'YYYY-MM-DDTHH:mm').format('HH:mm')).toBe('07:05')
  })

  it('accepts dayjs instances together with a format', () => {
    const d = dayjs('2024-03-05T18:30:00', 'YYYY-MM-DDTHH:mm')
    expect(dayjs(d, 'YYYY-MM-DDTHH:mm').format('YYYY-MM-DD')).toBe('2024-03-05')
  })

  it('uses the German locale', () => {
    const d = dayjs('2024-03-05', 'YYYY-MM-DD')
    expect(d.format('MMMM')).toBe('März')
    expect(d.format('dd')).toBe('Di')
    expect(d.format('dddd [den] DD.MM.YYYY')).toBe('Dienstag den 05.03.2024')
  })

  it('supports relative time (fromNow) and ordinal day tokens in German', () => {
    expect(dayjs().add(2, 'hour').fromNow()).toBe('in 2 Stunden')
    expect(dayjs('2024-03-05', 'YYYY-MM-DD').format('dddd, Do MMMM')).toBe('Dienstag, 5. März')
  })

  it('format() without arguments returns an ISO string with offset', () => {
    expect(dayjs('2024-03-05', 'YYYY-MM-DD').format()).toMatch(/^2024-03-05T00:00:00[+-]\d\d:\d\d$/)
  })

  it('compares with startOf(day) via valueOf', () => {
    expect(dayjs() >= dayjs().startOf('day')).toBe(true)
  })

  it('computes age in years', () => {
    expect(dayjs().diff(dayjs().subtract(30, 'year'), 'years')).toBe(30)
  })

  describe('parseApiDate', () => {
    it.each([
      ['2024-03-05T18:30:00+01:00', '2024-03-05 18:30'],
      ['2024-03-05 07:05:00', '2024-03-05 07:05'],
      ['2010-05-01T00:00:00.000000Z', '2010-05-01 00:00'],
      ['2010-05-01', '2010-05-01 00:00'],
    ])('parses %s as local time ignoring offset and seconds', (input, expected) => {
      expect(parseApiDate(input).format('YYYY-MM-DD HH:mm')).toBe(expected)
    })

    it('clones dayjs values and yields invalid dates for empty or garbage input', () => {
      expect(parseApiDate(dayjs('2024-03-05', 'YYYY-MM-DD')).format('YYYY-MM-DD')).toBe('2024-03-05')
      expect(parseApiDate(null).isValid()).toBe(false)
      expect(parseApiDate(undefined).isValid()).toBe(false)
      expect(parseApiDate('not a date').isValid()).toBe(false)
    })
  })
})

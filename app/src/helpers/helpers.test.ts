import { afterEach, describe, expect, it, vi } from 'vitest'
import { dayArrayToString, dayNameByNumber, formatDate, parseDate } from '@/helpers/date-helpers'
import { getRandomColor } from '@/helpers/color-helper'
import { eraseCookie, getCookie, setCookie } from '@/helpers/cookie-helper'
import { getPasswordHint, getPasswordRequirements, getPasswordValidationRules, PASSWORD_POLICY } from '@/constants/passwordPolicy'

describe('date helpers', () => {
  it('formatDate turns ISO dates into German dates', () => {
    expect(formatDate('2030-04-09')).toBe('09.04.2030')
  })

  it('formatDate handles Date objects with local time and moment-like objects', () => {
    expect(formatDate(new Date(2030, 3, 9))).toBe('09.04.2030')
    expect(formatDate({ format: (f: string) => (f === 'YYYY-MM-DD' ? '2030-12-31' : '') })).toBe('31.12.2030')
  })

  it('formatDate returns null for empty or unsupported values', () => {
    expect(formatDate(null)).toBeNull()
    expect(formatDate('')).toBeNull()
    expect(formatDate(42)).toBeNull()
  })

  it('parseDate is the inverse and pads day/month', () => {
    expect(parseDate('09.04.2030')).toBe('2030-04-09')
    expect(parseDate('9.4.2030')).toBe('2030-04-09')
    expect(parseDate(null)).toBeNull()
  })

  it('dayNameByNumber maps 1..7 to Mo..So', () => {
    expect([1, 2, 3, 4, 5, 6, 7].map(dayNameByNumber)).toEqual(['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'])
    expect(dayNameByNumber(8)).toBeUndefined()
  })

  it('dayArrayToString joins the names', () => {
    expect(dayArrayToString([1, 3, 5])).toBe('Mo, Mi, Fr')
    expect(dayArrayToString([])).toBe('')
  })
})

describe('color helper', () => {
  afterEach(() => vi.restoreAllMocks())

  it('getRandomColor returns a #-prefixed 6 digit color (digits only)', () => {
    expect(getRandomColor()).toMatch(/^#[0-9]{6}$/)
  })

  it('is deterministic for a fixed random source', () => {
    vi.spyOn(Math, 'random').mockReturnValue(0.5)
    expect(getRandomColor()).toBe('#555555')
  })
})

describe('cookie helper', () => {
  afterEach(() => {
    eraseCookie('a')
    eraseCookie('b')
  })

  it('sets, reads and erases cookies', () => {
    setCookie('a', 'one', 0)
    setCookie('b', 'two', 1)

    expect(getCookie('a')).toBe('one')
    expect(getCookie('b')).toBe('two')
    expect(getCookie('missing')).toBeNull()

    eraseCookie('a')
    expect(getCookie('a')).toBeNull()
    expect(getCookie('b')).toBe('two')
  })
})

describe('password policy (must mirror server/config/boilerplate.php)', () => {
  it('matches the backend defaults', () => {
    expect(PASSWORD_POLICY).toEqual({
      min_length: 8,
      require_lowercase: true,
      require_uppercase: true,
      require_digits: true,
      require_special: true,
      special_chars: '@$!%*?&.',
    })
  })

  it('describes the requirements', () => {
    expect(getPasswordRequirements()).toEqual(['Kleinbuchstaben', 'Großbuchstaben', 'Zahlen', 'Sonderzeichen (@$!%*?&.)'])
    expect(getPasswordHint()).toBe('Min. 8 Zeichen: Kleinbuchstaben, Großbuchstaben, Zahlen, Sonderzeichen (@$!%*?&.)')
  })

  const validate = (value: string) => getPasswordValidationRules().map(rule => rule(value)).filter(result => result !== true)

  it('accepts a strong password', () => {
    expect(validate('Secret123!')).toEqual([])
  })

  it.each([
    ['', 'Passwort wird benötigt'],
    ['Ab1!', 'Mindestens 8 Zeichen erforderlich'],
    ['SECRET123!', 'Mindestens ein Kleinbuchstabe erforderlich'],
    ['secret123!', 'Mindestens ein Großbuchstabe erforderlich'],
    ['Secret!!!!', 'Mindestens eine Ziffer erforderlich'],
    ['Secret1234', 'Mindestens ein Sonderzeichen (@$!%*?&.) erforderlich'],
  ])('rejects %j', (password, message) => {
    expect(validate(password)).toContain(message)
  })
})

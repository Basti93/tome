import { describe, expect, it } from 'vitest'
import { apiErrorMessage } from './apiError'

describe('apiErrorMessage', () => {
  it('prefers the first validation error, then the message, then the fallback', () => {
    const withErrors = { response: { data: { message: 'x', errors: { email: ['Diese E-Mail-Adresse wird bereits verwendet.'] } } } }
    expect(apiErrorMessage(withErrors, 'fallback')).toBe('Diese E-Mail-Adresse wird bereits verwendet.')
    expect(apiErrorMessage({ response: { data: { message: 'Der Link ist ungültig.' } } }, 'fallback')).toBe('Der Link ist ungültig.')
    expect(apiErrorMessage({ response: { data: {} } }, 'fallback')).toBe('fallback')
    expect(apiErrorMessage(new Error('boom'), 'fallback')).toBe('fallback')
    expect(apiErrorMessage(undefined, 'fallback')).toBe('fallback')
  })
})

import { beforeEach, describe, expect, it, vi } from 'vitest'
import { readThemeMode, resolveTheme, saveThemeMode, setThemeMode, themeMode } from './themeMode'

describe('themeMode', () => {
  beforeEach(() => localStorage.clear())

  it('system follows prefers-color-scheme', () => {
    expect(resolveTheme('system', true)).toBe('dark')
    expect(resolveTheme('system', false)).toBe('light')
  })

  it('light and dark override the system setting', () => {
    expect(resolveTheme('light', true)).toBe('light')
    expect(resolveTheme('dark', false)).toBe('dark')
  })

  it('defaults to system and ignores unknown values', () => {
    expect(readThemeMode()).toBe('system')
    localStorage.setItem('theme-mode', 'purple')
    expect(readThemeMode()).toBe('system')
  })

  it('persists the choice and resets on system', () => {
    saveThemeMode('dark')
    expect(readThemeMode()).toBe('dark')
    saveThemeMode('system')
    expect(localStorage.getItem('theme-mode')).toBeNull()
  })

  it('setThemeMode updates the shared ref and storage', () => {
    setThemeMode('light')
    expect(themeMode.value).toBe('light')
    expect(readThemeMode()).toBe('light')
  })

  it('survives unavailable storage', () => {
    const spy = vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
      throw new Error('blocked')
    })
    expect(readThemeMode()).toBe('system')
    spy.mockRestore()
  })
})

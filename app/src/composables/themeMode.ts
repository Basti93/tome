import { onBeforeUnmount, ref, watch } from 'vue'
import { useTheme } from 'vuetify'

export type ThemeMode = 'system' | 'light' | 'dark'

const KEY = 'theme-mode'

export function readThemeMode(): ThemeMode {
  try {
    const value = localStorage.getItem(KEY)
    return value === 'light' || value === 'dark' ? value : 'system'
  } catch {
    return 'system'
  }
}

export function saveThemeMode(mode: ThemeMode): void {
  try {
    if (mode === 'system') localStorage.removeItem(KEY)
    else localStorage.setItem(KEY, mode)
  } catch {
    // storage unavailable: the choice only lasts until the next start
  }
}

export const systemPrefersDark = (): boolean =>
  typeof window !== 'undefined' && typeof window.matchMedia === 'function' && window.matchMedia('(prefers-color-scheme: dark)').matches

/** The Vuetify theme for a mode: "system" follows prefers-color-scheme. */
export function resolveTheme(mode: ThemeMode, prefersDark: boolean): 'light' | 'dark' {
  return mode === 'system' ? (prefersDark ? 'dark' : 'light') : mode
}

/** Theme at app start (also used as the Vuetify default theme, so there is no flash of the wrong theme). */
export const initialTheme = (): 'light' | 'dark' => resolveTheme(readThemeMode(), systemPrefersDark())

// shared, so the setting in the profile and the app agree
const mode = ref<ThemeMode>(readThemeMode())

/** Applies the chosen mode to Vuetify and follows the system setting live. Call once in the App; the profile uses setMode. */
export function useThemeMode() {
  const theme = useTheme()
  const apply = () => theme.change(resolveTheme(mode.value, systemPrefersDark()))

  watch(mode, apply, { immediate: true })

  const query = typeof window !== 'undefined' && typeof window.matchMedia === 'function' ? window.matchMedia('(prefers-color-scheme: dark)') : null
  const onSystemChange = () => {
    if (mode.value === 'system') apply()
  }
  query?.addEventListener?.('change', onSystemChange)
  onBeforeUnmount(() => query?.removeEventListener?.('change', onSystemChange))

  return { mode, setMode: setThemeMode }
}

/** The chosen mode (profile setting) and how to change it; the App applies it. */
export const themeMode = mode
export function setThemeMode(value: ThemeMode) {
  mode.value = value
  saveThemeMode(value)
}

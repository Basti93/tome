import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useSnackbarStore } from '@/store/snackbar'

describe('snackbar store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('is hidden initially with info defaults', () => {
    const store = useSnackbarStore()

    expect(store.visible).toBe(false)
    expect(store.color).toBe('info')
    expect(store.timeout).toBe(6000)
  })

  it('show displays text with defaults', () => {
    const store = useSnackbarStore()

    store.show('Gespeichert')

    expect(store).toMatchObject({ visible: true, text: 'Gespeichert', color: 'info', timeout: 6000 })
  })

  it('show accepts a color and a timeout', () => {
    const store = useSnackbarStore()

    store.show('Fehler', 'error', 1000)

    expect(store).toMatchObject({ visible: true, text: 'Fehler', color: 'error', timeout: 1000 })
  })

  it('hide only toggles visibility', () => {
    const store = useSnackbarStore()
    store.show('x', 'success')

    store.hide()

    expect(store.visible).toBe(false)
    expect(store.text).toBe('x')
  })

  it('every show() bumps a counter so a repeated message restarts the display', () => {
    const store = useSnackbarStore()

    store.show('Fehler', 'error')
    const first = store.count
    store.show('Fehler', 'error')

    expect(store.count).toBe(first + 1)
    expect(store.visible).toBe(true)
  })
})

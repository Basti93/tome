import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

vi.mock('@/http/api', () => ({ default: { get: vi.fn(), post: vi.fn() } }))

import httpClient from '@/http/api'
import ChangePasswordDialog from './ChangePasswordDialog.vue'
import { useSnackbarStore } from '@/store/snackbar'

const post = httpClient.post as unknown as ReturnType<typeof vi.fn>

function mountDialog() {
  return mount(ChangePasswordDialog, { props: { visible: true }, global: { plugins: [createVuetify({ components, directives })] }, attachTo: document.body })
}

describe('ChangePasswordDialog', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    document.body.innerHTML = ''
    post.mockReset()
  })

  it('asks for the current password and sends it with the new one', async () => {
    post.mockResolvedValue({ data: { status: 'ok' } })
    const w = mountDialog()
    const vm = w.vm as any
    expect(document.body.textContent).toContain('Aktuelles Passwort')

    vm.currentPassword = 'Alt-Passwort1!'
    vm.password = 'Neu12345!x'
    vm.passwordConfirm = 'Neu12345!x'
    await vm.save()

    expect(post).toHaveBeenCalledWith('/user/me/changepassword', { current_password: 'Alt-Passwort1!', password: 'Neu12345!x' })
    expect(useSnackbarStore().text).toBe('Passwort geändert')
    w.unmount()
  })

  it('shows the German error when the current password is wrong', async () => {
    post.mockRejectedValue({ response: { status: 422, data: { message: 'Das aktuelle Passwort ist falsch.', errors: { current_password: ['Das aktuelle Passwort ist falsch.'] } } } })
    const w = mountDialog()
    const vm = w.vm as any
    vm.currentPassword = 'falsch'
    vm.password = 'Neu12345!x'
    vm.passwordConfirm = 'Neu12345!x'
    await vm.save()
    await flushPromises()

    expect(useSnackbarStore().text).toBe('Das aktuelle Passwort ist falsch.')
    w.unmount()
  })
})

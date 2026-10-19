import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/http/api', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}))

import httpClient from '@/http/api'
import { useSnackbarStore } from '@/store/snackbar'
import { inviteUser } from '@/services/userInvite'

describe('inviteUser', () => {
  beforeEach(() => setActivePinia(createPinia()))

  it('posts to /user/{id}/invite and shows a success snackbar', async () => {
    vi.mocked(httpClient.post).mockResolvedValue({ data: { status: 'ok' }, status: 200, statusText: 'OK' })
    expect(await inviteUser(7, 'Max M.')).toBe(true)
    expect(httpClient.post).toHaveBeenCalledWith('/user/7/invite')
    const snackbar = useSnackbarStore()
    expect(snackbar.color).toBe('success')
    expect(snackbar.text).toContain('Max M.')
  })

  it('explains a 422 (no e-mail address)', async () => {
    vi.mocked(httpClient.post).mockRejectedValue({ response: { status: 422, data: {} } })
    expect(await inviteUser(7)).toBe(false)
    const snackbar = useSnackbarStore()
    expect(snackbar.color).toBe('error')
    expect(snackbar.text).toContain('E-Mail')
  })

  it('shows a generic error for other failures', async () => {
    vi.mocked(httpClient.post).mockRejectedValue(new Error('network'))
    expect(await inviteUser(7)).toBe(false)
    expect(useSnackbarStore().color).toBe('error')
  })
})

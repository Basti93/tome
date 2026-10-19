import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/http/api', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}))

import httpClient from '@/http/api'
import { refreshMasterData } from './refreshMasterData'
import { useMasterDataStore } from '@/store/masterData'

let anonymous = true
function mockApi() {
  vi.mocked(httpClient.get).mockImplementation(async (url: string) => {
    if (url === '/simpleuser') {
      return { data: { data: [{ id: 1, firstName: 'Anna', familyName: anonymous ? 'M.' : 'Meier', groupIds: [] }] } } as any
    }
    return { data: { data: [] } } as any
  })
}

describe('refreshMasterData', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    mockApi()
  })

  it('replaces the abbreviated names loaded at app start with the ones for the logged-in user and back', async () => {
    anonymous = true
    expect(await refreshMasterData()).toBe(true)
    expect(useMasterDataStore().simpleUsers[0].familyName).toBe('M.')
    anonymous = false // login
    await refreshMasterData()
    expect(useMasterDataStore().simpleUsers[0].familyName).toBe('Meier')
    anonymous = true // logout
    await refreshMasterData()
    expect(useMasterDataStore().simpleUsers[0].familyName).toBe('M.')
  })

  it('does not throw when the reload fails', async () => {
    vi.spyOn(console, 'error').mockImplementation(() => {})
    vi.mocked(httpClient.get).mockRejectedValue(new Error('offline'))
    expect(await refreshMasterData()).toBe(false)
  })
})

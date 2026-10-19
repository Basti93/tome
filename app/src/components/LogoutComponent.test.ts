import { describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia } from 'pinia'

const push = vi.hoisted(() => vi.fn())
vi.mock('vue-router', () => ({ useRouter: () => ({ push }) }))
vi.mock('@/http/api', () => ({ default: { get: vi.fn(), post: vi.fn().mockResolvedValue({ data: {} }) } }))
vi.mock('@/helpers/refreshMasterData', () => ({ refreshMasterData: vi.fn().mockResolvedValue(true) }))
vi.mock('@/services/analytics', () => ({ analytics: { trackEvent: vi.fn() } }))

import LogoutComponent from './LogoutComponent.vue'
import { refreshMasterData } from '@/helpers/refreshMasterData'
import { useAuthStore } from '@/store/auth'

describe('LogoutComponent', () => {
  it('clears the session and reloads the master data so full names disappear again', async () => {
    const pinia = createPinia()
    mount(LogoutComponent, { global: { plugins: [pinia] } })
    await flushPromises()
    expect(useAuthStore().user).toBeNull()
    expect(refreshMasterData).toHaveBeenCalled()
    expect(push).toHaveBeenCalledWith('/')
  })
})

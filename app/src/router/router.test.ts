import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/http/api', () => ({
  default: { get: vi.fn(), post: vi.fn() },
}))
vi.mock('@/services/analytics', () => ({ analytics: { trackPageView: vi.fn(), trackEvent: vi.fn() } }))

import httpClient from '@/http/api'
import router from '@/router'
import { useAuthStore } from '@/store/auth'

const trainer = { id: 5, firstName: 'T', familyName: 'Trainer', roleNames: ['trainer'] }

describe('router auth guard', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    useAuthStore().logout()
    vi.mocked(httpClient.get).mockReset()
    await router.replace('/')
  })

  it('sends anonymous visitors of a protected page to the login with ?redirect= (the name LoginPage reads)', async () => {
    vi.mocked(httpClient.get).mockRejectedValue(new Error('401'))

    await router.push('/quickEvaluation/12')

    expect(router.currentRoute.value.path).toBe('/login')
    expect(router.currentRoute.value.query).toEqual({ redirect: '/quickEvaluation/12' })
    expect(httpClient.get).toHaveBeenCalledWith('/auth/me', { silent401: true })
  })

  it('restores the session from the cookie when the persisted user is missing (cold start from a push)', async () => {
    vi.mocked(httpClient.get).mockResolvedValue({ data: trainer } as any)

    await router.push('/quickEvaluation/12')

    expect(useAuthStore().user?.id).toBe(5)
    expect(router.currentRoute.value.path).toBe('/quickEvaluation/12')
  })

  it('does not probe the session when the user is already restored from storage', async () => {
    useAuthStore().user = { ...trainer, isTrainer: true } as any

    await router.push('/quickEvaluation/12')

    expect(httpClient.get).not.toHaveBeenCalled()
    expect(router.currentRoute.value.path).toBe('/quickEvaluation/12')
  })
})

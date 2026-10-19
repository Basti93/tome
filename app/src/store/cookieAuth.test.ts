import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { eraseCookie, setCookie } from '@/helpers/cookie-helper'

const cookieUser = { id: 3, firstName: 'Uli', familyName: 'Unregistered', groupIds: [1], active: 1, registered: 0 }

async function freshStore() {
  setActivePinia(createPinia())
  const { useCookieAuthStore } = await import('@/store/cookieAuth')
  return useCookieAuthStore()
}

describe('cookieAuth store', () => {
  beforeEach(() => {
    eraseCookie('cookieUser')
  })

  it('has no cookie user by default', async () => {
    const store = await freshStore()
    expect(store.cookieUser).toBeNull()
  })

  it('selectCookieUser accepts an object, stores it and persists a cookie', async () => {
    const store = await freshStore()

    store.selectCookieUser(cookieUser)

    expect(store.cookieUser?.id).toBe(3)
    expect(store.cookieUser?.registered).toBe(false)
    expect(document.cookie).toContain('cookieUser=')
  })

  it('selectCookieUser accepts an already serialized user', async () => {
    const store = await freshStore()

    store.selectCookieUser(JSON.stringify(cookieUser))

    expect(store.cookieUser?.firstName).toBe('Uli')
  })

  it('eraseCookieUser removes the user and the cookie', async () => {
    const store = await freshStore()
    store.selectCookieUser(cookieUser)

    store.eraseCookieUser()

    expect(store.cookieUser).toBeNull()
    expect(document.cookie).not.toContain('cookieUser=')
  })

  it('restores the cookie user on creation', async () => {
    setCookie('cookieUser', JSON.stringify(cookieUser), 0)

    const store = await freshStore()

    expect(store.cookieUser?.id).toBe(3)
  })
})

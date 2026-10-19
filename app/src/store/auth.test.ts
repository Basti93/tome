import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import User from '@/models/User'

const storedUser = {
  id: 7,
  email: 'trainer@example.com',
  firstName: 'Tina',
  familyName: 'Trainer',
  birthdate: '1990-01-01',
  active: 1,
  groupIds: [1, 2],
  roleNames: ['trainer'],
  trainerBranchIds: [3],
  registered: 1,
  profileImageName: null,
  absenceStart: null,
  absenceEnd: null,
  absenceReason: null,
}

// the store reads localStorage when it is created, so import it freshly per test
async function freshStore() {
  setActivePinia(createPinia())
  const { useAuthStore } = await import('@/store/auth')
  return useAuthStore()
}

describe('auth store', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  it('starts without a user when nothing is stored', async () => {
    const store = await freshStore()
    expect(store.user).toBeNull()
  })

  it('restores the user from localStorage', async () => {
    localStorage.setItem('user', JSON.stringify(storedUser))

    const store = await freshStore()

    expect(store.user).toBeInstanceOf(User)
    expect(store.user?.id).toBe(7)
    expect(store.user?.getFullName()).toBe('Tina Trainer')
    expect(store.user?.isTrainer).toBe(true)
    expect(store.user?.isAdmin).toBe(false)
    expect(store.user?.active).toBe(true)
  })

  it('ignores corrupt localStorage content', async () => {
    localStorage.setItem('user', '{not json')

    const store = await freshStore()

    expect(store.user).toBeNull()
  })

  it('login stores the user in state and localStorage', async () => {
    const store = await freshStore()
    const user = User.from(JSON.stringify(storedUser)) as User

    store.login(user)

    expect(store.user).toStrictEqual(user)
    expect(JSON.parse(localStorage.getItem('user') as string).email).toBe('trainer@example.com')
  })

  it('updateUser replaces the stored user', async () => {
    const store = await freshStore()
    store.login(User.from(JSON.stringify(storedUser)) as User)

    store.updateUser(User.from(JSON.stringify({ ...storedUser, firstName: 'Neu' })) as User)

    expect(store.user?.firstName).toBe('Neu')
    expect(JSON.parse(localStorage.getItem('user') as string).firstName).toBe('Neu')
  })

  it('logout clears state and localStorage', async () => {
    const store = await freshStore()
    store.login(User.from(JSON.stringify(storedUser)) as User)

    store.logout()

    expect(store.user).toBeNull()
    expect(localStorage.getItem('user')).toBeNull()
  })
})

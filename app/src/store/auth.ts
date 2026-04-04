import { defineStore } from 'pinia'
import type User from '@/models/User'

export const useAuthStore = defineStore('auth', {
  state: () => {
    const stored = localStorage.getItem('user')
    return {
      user: stored ? JSON.parse(stored) as User : null as User | null,
      token: localStorage.getItem('token') as string | null
    }
  },
  actions: {
    login(user: User, token: string) {
      this.user = user
      this.token = token
      localStorage.setItem('user', JSON.stringify(user))
      localStorage.setItem('token', token)
    },
    updateUser(user: User) {
      this.user = user
      localStorage.setItem('user', JSON.stringify(user))
    },
    logout() {
      this.user = null
      this.token = null
      localStorage.removeItem('user')
      localStorage.removeItem('token')
    }
  }
})

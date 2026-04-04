import { defineStore } from 'pinia'
import User from '@/models/User'

export const useAuthStore = defineStore('auth', {
  state: () => {
    const stored = localStorage.getItem('user')
    return {
      user: stored ? User.from(stored) : null,
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

import { defineStore } from 'pinia'
import User from '@/models/User'

export const useAuthStore = defineStore('auth', {
  state: () => {
    const stored = localStorage.getItem('user')
    return {
      user: stored ? User.from(stored) : null,
    }
  },
  actions: {
    login(user: User) {
      this.user = user
      localStorage.setItem('user', JSON.stringify(user))
    },
    updateUser(user: User) {
      this.user = user
      localStorage.setItem('user', JSON.stringify(user))
    },
    logout() {
      this.user = null
      localStorage.removeItem('user')
    }
  }
})

import { defineStore } from 'pinia'
import User from '@/models/User'
import { setCookie, getCookie, eraseCookie } from '@/helpers/cookie-helper'

export const useCookieAuthStore = defineStore('cookieAuth', {
  state: () => ({
    cookieUser: User.from(getCookie('cookieUser')) as User | null
  }),
  actions: {
    selectCookieUser(cookieUser: unknown) {
      const serialized = typeof cookieUser === 'string' ? cookieUser : JSON.stringify(cookieUser)
      this.cookieUser = User.from(serialized)
      setCookie('cookieUser', serialized, 0)
    },
    eraseCookieUser() {
      this.cookieUser = null
      eraseCookie('cookieUser')
    }
  }
})

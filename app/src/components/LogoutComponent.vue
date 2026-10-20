<template>
  <div></div>
</template>
<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import { useSnackbarStore } from '@/store/snackbar'
import httpClient from '@/http/api'
import { refreshMasterData } from '@/helpers/refreshMasterData'
import { analytics } from '@/services/analytics'

const router = useRouter()

async function logout() {
  try {
    await httpClient.post('/auth/logout', undefined, { silent401: true })
  } catch {
    // ignore errors, still clear local state
  }
  // Track logout event
  analytics.trackEvent('Authentication', 'Logout')
  useAuthStore().logout()
  // drop the full names that were loaded for the logged-in user
  await refreshMasterData()
  useSnackbarStore().show('Erfolgreich abgemeldet', 'info')
  router.push('/')
}

logout()
</script>

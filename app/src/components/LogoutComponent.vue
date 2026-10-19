<template>
  <div></div>
</template>
<script>
  import { useAuthStore } from '@/store/auth'
  import { useSnackbarStore } from '@/store/snackbar'
  import httpClient from '@/http/api'
  import { refreshMasterData } from '@/helpers/refreshMasterData'
  import { analytics } from '@/services/analytics'

  export default {
    name: 'LogoutComponent',
    async created () {
      try {
        await httpClient.post('/auth/logout')
      } catch (e) {
        // ignore errors, still clear local state
      }
      // Track logout event
      analytics.trackEvent('Authentication', 'Logout')
      useAuthStore().logout()
      // drop the full names that were loaded for the logged-in user
      await refreshMasterData()
      useSnackbarStore().show("Erfolgreich abgemeldet", "info");
      this.$router.push('/')
    }
  }
</script>

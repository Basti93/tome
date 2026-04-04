<template>
  <div></div>
</template>
<script>
  import { useAuthStore } from '@/store/auth'
  import { useSnackbarStore } from '@/store/snackbar'
  import httpClient from '@/http/api'

  export default {
    name: 'LogoutComponent',
    async created () {
      try {
        await httpClient.post('/auth/logout')
      } catch (e) {
        // ignore errors, still clear local state
      }
      useAuthStore().logout()
      useSnackbarStore().show("Erfolgreich abgemeldet", "info");
      this.$router.push('/')
    }
  }
</script>

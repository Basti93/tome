<template>
  <div></div>
</template>
<script>
  import { useAuthStore } from '@/store/auth'
  import { useSnackbarStore } from '@/store/snackbar'

  export default {
    name: 'LogoutComponent',
    created () {
      useAuthStore().logout()
      useSnackbarStore().show("Erfolgreich abgemeldet", "info");
      this.$router.push('/')
    }
  }
</script>

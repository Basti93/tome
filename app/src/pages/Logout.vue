<template></template>

<script>
  export default {
    name: 'Logout',
    created () {
      delete localStorage.token
      delete localStorage.user
      this.$store.dispatch('logout')
      this.$emit("showSnackbar", "Erfolgreich abgemeldet", "info");
      this.$router.push('/')
    }
  }
</script>

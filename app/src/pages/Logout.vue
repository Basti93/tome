<template></template>

<script>
  export default {
    name: 'Logout',
    created () {
      this.$store.dispatch('logout')
      this.$emit("showSnackbar", "Erfolgreich abgemeldet", "info");
      this.$router.push('/')
    }
  }
</script>

<template>
  <div></div>
</template>
<script>
  export default {
    name: 'LogoutComponent',
    created () {
      this.$store.dispatch('logout')
      this.$emit("showSnackbar", "Erfolgreich abgemeldet", "info");
      this.$router.push('/')
    }
  }
</script>

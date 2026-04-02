<template>
  <div></div>
</template>
<script>
import { defineComponent } from "vue"
  export default defineComponent({
    name: 'LogoutComponent',
    created () {
      this.$store.dispatch('logout')
      this.$emit("showSnackbar", "Erfolgreich abgemeldet", "info");
      this.$router.push('/')
    })
  }
</script>

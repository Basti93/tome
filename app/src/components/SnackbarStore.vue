<template>
  <v-snackbar
    bottom
    right
    v-model="snackbar.visible"
    :timeout="snackbar.timeout"
    v-bind:color="snackbar.color">
    {{ snackbar.text }}
  </v-snackbar>
</template>
<script>
  import { mapMutations } from "vuex"
  export default {
    computed: {
      snackbar() {
        return this.$store.state.snackbar
      }
    },
    methods: {
      ...mapMutations(["closeSnackbar"])
    }
  }
</script>

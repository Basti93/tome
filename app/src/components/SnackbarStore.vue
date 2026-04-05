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
  import { useSnackbarStore } from '@/store/snackbar'

  export default {
    computed: {
      snackbar() {
        return useSnackbarStore()
      }
    },
    methods: {
      closeSnackbar() {
        useSnackbarStore().hide()
      }
    }
  }
</script>

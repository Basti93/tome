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
import { defineComponent } from "vue"
  import { mapMutations } from "vuex"
  export default defineComponent({
    computed: {
      snackbar() {
        return this.$store.state.snackbar
    },
    methods: {
      ...mapMutations(["closeSnackbar"])
    })
  }
}
</script>

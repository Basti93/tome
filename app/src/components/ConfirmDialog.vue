<template>
  <v-dialog v-model="show" width="500">
    <v-card>
      <v-toolbar flat>
        <v-toolbar-title>{{ actionText }} Bestätigen</v-toolbar-title>
      </v-toolbar>
      <v-card-text>
        Bitte bestätige das {{ actionText }}
      </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn
            text
            v-on:click="cancel()"
        >
          <v-icon left>cancel</v-icon>
          Abbrechen
        </v-btn>
        <v-btn
            text
            v-on:click="confirm()"
        >
          <v-icon left>check</v-icon>
          Bestätigen
        </v-btn>

      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script>
export default {
  name: "ConfirmDialog",
  props: {
    show: Boolean,
    actionText: String,
  },
  data: function () {
    return {}

  },
  methods: {
    confirm() {
      this.$emit('confirmed')
    },
    cancel() {
      this.$emit('canceled')
    }
  }
}
</script>

<style scoped>

</style>
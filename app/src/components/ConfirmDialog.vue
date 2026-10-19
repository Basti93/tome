<template>
  <v-dialog v-model="visible" width="500">
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
            v-on:click="cancel()" variant="text"
        >
          <v-icon start>mdi-cancel</v-icon>
          Abbrechen
        </v-btn>
        <v-btn
            v-on:click="confirm()" variant="text"
        >
          <v-icon start>mdi-check</v-icon>
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
  computed: {
    visible: {
      get() {
        return this.show;
      },
      set(value) {
        if (!value) {
          this.$emit('canceled')
        }
      }
    }
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
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

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  show?: boolean
  actionText?: string
}>()

const emit = defineEmits<{
  canceled: []
  confirmed: []
}>()

const visible = computed({
  get: () => props.show,
  set: (value) => {
    if (!value) {
      emit('canceled')
    }
  }
})

function confirm() {
  emit('confirmed')
}

function cancel() {
  emit('canceled')
}
</script>

<style scoped>

</style>
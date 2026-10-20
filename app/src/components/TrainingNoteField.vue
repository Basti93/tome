<template>
  <v-textarea
    :model-value="modelValue"
    label="Notiz zum Training (optional)"
    hint="Nur für Trainer sichtbar, z. B. was gut lief oder worauf das nächste Mal geachtet werden soll."
    persistent-hint
    :counter="NOTE_MAX_LENGTH"
    :maxlength="NOTE_MAX_LENGTH"
    rows="2"
    auto-grow
    variant="outlined"
    data-test="training-note"
    @update:model-value="emit('update:modelValue', $event)"
  ></v-textarea>
</template>

<script setup lang="ts">
import { NOTE_MAX_LENGTH } from '@/helpers/trainingNotes'

defineProps<{ modelValue: string }>()
const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>()
</script>

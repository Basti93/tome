<template>
  <div v-if="notes.length" data-test="group-notes">
    <div v-if="compact" class="text-body-2" data-test="last-note">
      <v-icon size="small">mdi-note-text-outline</v-icon>
      <strong>Letztes Mal ({{ notes[0].start.format('DD.MM.') }}):</strong>
      <span class="note-text"> {{ notes[0].note }}</span>
    </div>
    <v-card v-else class="ma-1">
      <v-card-title><h6>Notizen aus früheren Trainings</h6></v-card-title>
      <v-divider></v-divider>
      <v-list lines="three" density="compact">
        <v-list-item v-for="n in notes" :key="n.trainingId" data-test="group-note">
          <v-list-item-title>{{ n.start.format('dddd, DD.MM.YYYY') }}</v-list-item-title>
          <div class="note-text text-body-2">{{ n.note }}</div>
        </v-list-item>
      </v-list>
    </v-card>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { fetchGroupNotes, type GroupNote } from '@/helpers/trainingNotes'

const props = defineProps<{ groupIds: number[]; compact?: boolean }>()
const notes = ref<GroupNote[]>([])

watch(
  () => props.groupIds.join(','),
  async () => {
    const wanted = props.groupIds.join(',')
    try {
      const result = await fetchGroupNotes(props.groupIds)
      if (wanted === props.groupIds.join(',')) notes.value = result
    } catch {
      notes.value = []
    }
  },
  { immediate: true },
)
</script>

<style scoped>
.note-text {
  white-space: pre-line;
}
</style>

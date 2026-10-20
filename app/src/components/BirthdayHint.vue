<template>
  <v-icon
      v-if="hint"
      icon="mdi-cake-variant"
      size="small"
      color="warning"
      class="ml-1 birthday-hint"
      role="img"
      :aria-label="hint.label"
      :title="hint.label"
      tabindex="0"
  >
    <v-tooltip activator="parent" location="top" open-on-click :text="hint.label"></v-tooltip>
  </v-icon>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Dayjs } from '@/helpers/dayjs'
import { birthdayHint } from '@/helpers/birthday'
import { useMasterDataStore } from '@/store/masterData'

// Birthday hint for the trainer views. The birthdate only exists in the master data of logged in trainers/admins
// (the public endpoints never send it), so without it nothing is rendered.
const props = defineProps<{ userId: number; date: Dayjs | string | Date }>()

const masterData = useMasterDataStore()
const hint = computed(() => birthdayHint(masterData.simpleUsers.find((u) => u.id === props.userId)?.birthdate, props.date))
</script>

<template>
  <v-dialog v-model="visible" max-width="440" persistent>
    <v-card v-if="current">
      <v-card-title>{{ current.title }}</v-card-title>
      <v-card-text>{{ current.text }}</v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn variant="text" @click="close">Schließen</v-btn>
        <v-btn v-if="current.link" color="primary" variant="elevated" @click="openLink">{{ current.linkText ?? 'Ansehen' }}</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import { markSeen, nextAnnouncement } from '@/helpers/announcements'
import type { Announcement } from '@/constants/announcements'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const current = ref<Announcement | null>(null)
const visible = computed({ get: () => !!current.value, set: () => close() })

// only for logged in users (not for cookie users), and not on the login page itself
watch(
  () => [auth.user?.id, route.path] as const,
  () => {
    if (!current.value) evaluate()
  },
  { immediate: true },
)

function evaluate() {
  current.value = route.path.startsWith('/login') ? null : nextAnnouncement(auth.user)
}

/** marks the shown hint as seen and shows the next one, if any */
function close() {
  if (current.value && auth.user) markSeen(auth.user.id, current.value.id)
  current.value = null
  evaluate()
}

function openLink() {
  const link = current.value?.link
  close()
  if (link) router.push(link)
}
</script>

<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Heute</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-btn variant="text" color="primary" to="/" prepend-icon="mdi-note-text">Trainingsanmeldung</v-btn>
          </v-toolbar>
          <v-card-text class="pa-0 pa-md-4">
            <v-progress-linear v-if="loading" indeterminate></v-progress-linear>

            <v-card class="ma-1 mb-2" data-test="next-training">
              <v-card-title>{{ next?.own === false ? 'Nächstes Training deiner Sparten' : 'Dein nächstes Training' }}</v-card-title>
              <v-card-text v-if="next">
                <div class="text-h6">
                  {{ parseApiDate(next.training.start).format('dddd, DD.MM.') }} · {{ parseApiDate(next.training.start).format('HH:mm') }}–{{ parseApiDate(next.training.end).format('HH:mm') }}
                  <span v-if="startsIn" class="text-body-2 text-medium-emphasis">({{ startsIn }})</span>
                </div>
                <div v-if="locationName" class="mb-1"><v-icon size="small">mdi-map-marker</v-icon> {{ locationName }}</div>
                <div class="mb-2"><v-icon size="small">mdi-account-group</v-icon> {{ groupNames }}</div>
                <GroupNotes class="mb-2" compact :group-ids="next.training.groupIds ?? []"></GroupNotes>
                <div data-test="counts">
                  <strong>{{ summary.yes }}</strong> zugesagt, <strong>{{ summary.cancellations.length }}</strong> abgesagt
                </div>
                <v-list v-if="summary.cancellations.length" density="compact" class="pa-0" data-test="cancellations">
                  <v-list-item v-for="c in summary.cancellations" :key="c.userId" class="px-0">
                    <v-list-item-title>{{ nameOf(c.userId) }}<BirthdayHint :user-id="c.userId" :date="next.training.start" /></v-list-item-title>
                    <v-list-item-subtitle v-if="c.reason">{{ c.reason }}</v-list-item-subtitle>
                  </v-list-item>
                </v-list>
              </v-card-text>
              <v-card-text v-else-if="!loading">Keine kommenden Trainings gefunden.</v-card-text>
              <v-card-actions v-if="next">
                <v-btn v-if="next.own" color="primary" variant="elevated" :to="'/trainingsPrepare?training=' + next.training.id">Vorbereiten</v-btn>
                <v-btn variant="text" to="/">Anmeldung ansehen</v-btn>
              </v-card-actions>
            </v-card>

            <v-card v-if="birthdays.length" class="ma-1 mb-2" data-test="birthdays">
              <v-card-text>
                <v-icon color="warning" size="small">mdi-cake-variant</v-icon>
                Heute hat Geburtstag: <strong>{{ birthdays.join(', ') }}</strong>
              </v-card-text>
            </v-card>

            <v-card class="ma-1" data-test="unevaluated">
              <v-card-title>Noch nicht abgeschlossen</v-card-title>
              <v-card-text v-if="!unevaluated.length && !loading">Alles abgeschlossen, danke!</v-card-text>
              <v-list v-else>
                <v-list-item v-for="t in unevaluated" :key="t.id">
                  <v-list-item-title>{{ dayjs(t.start).format('dd DD.MM. HH:mm') }}–{{ dayjs(t.end).format('HH:mm') }}</v-list-item-title>
                  <v-list-item-subtitle>{{ t.groups.join(', ') }}</v-list-item-subtitle>
                  <template v-slot:append>
                    <v-btn size="small" color="primary" variant="tonal" :to="'/quickEvaluation/' + t.id">Schnell abschließen</v-btn>
                  </template>
                </v-list-item>
              </v-list>
            </v-card>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import dayjs, { parseApiDate } from '@/helpers/dayjs'
import httpClient from '@/http/api'
import BirthdayHint from '@/components/BirthdayHint.vue'
import GroupNotes from '@/components/GroupNotes.vue'
import { useAuthStore } from '@/store/auth'
import { useMasterDataStore } from '@/store/masterData'
import { participationSummary, pickNextTraining, todaysBirthdays, type UpcomingTraining } from '@/helpers/today'

const authStore = useAuthStore()
const masterData = useMasterDataStore()

const loading = ref(true)
const own = ref<UpcomingTraining[]>([])
const ofBranches = ref<UpcomingTraining[]>([])
const unevaluated = ref<{ id: number; start: string; end: string; groups: string[] }[]>([])

// groups the trainer is responsible for: the groups of the trainer branches (admins: all)
const groupIds = computed(() => {
  const user = authStore.user
  const branchIds = (user?.trainerBranchIds ?? []) as number[]
  return masterData.groups.filter((g) => user?.isAdmin || branchIds.includes(g.branchId)).map((g) => g.id)
})

const next = computed(() => pickNextTraining(own.value, ofBranches.value))
const summary = computed(() => participationSummary(next.value?.training.participants ?? []))
const locationName = computed(() => (next.value?.training.locationId ? masterData.getLocationNameById(next.value.training.locationId) : ''))
const groupNames = computed(() => (next.value?.training.groupIds ?? []).map((id) => masterData.getGroupById(id)?.name).filter(Boolean).join(', '))
const startsIn = computed(() => (next.value ? parseApiDate(next.value.training.start).fromNow() : ''))
const birthdays = computed(() => todaysBirthdays(masterData.simpleUsers as any, groupIds.value))

const nameOf = (userId: number) => {
  const u = masterData.simpleUsers.find((s) => s.id === userId)
  return u ? `${u.firstName} ${u.familyName}`.trim() : `Person #${userId}`
}

onMounted(async () => {
  const user = authStore.user
  if (!user) return
  try {
    const [mine, hours] = await Promise.all([
      httpClient.get(`/trainingprepare/${user.id}`),
      httpClient.get('/user/me/hours'),
    ])
    own.value = mine.data.data ?? []
    unevaluated.value = hours.data.unevaluated ?? []
    if (!own.value.length && groupIds.value.length) {
      const res = await httpClient.get(`/training/upcoming?groupIds=${groupIds.value.join(',')}`)
      ofBranches.value = res.data.data ?? []
    }
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
})
</script>

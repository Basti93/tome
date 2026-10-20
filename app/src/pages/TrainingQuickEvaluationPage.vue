<template>
  <v-container class="quick-evaluation">
    <v-progress-linear v-if="loading" indeterminate></v-progress-linear>

    <v-alert v-else-if="loadError" type="error" variant="tonal" data-test="load-error">
      {{ loadError }}
    </v-alert>

    <template v-else-if="training">
      <v-card class="mb-3">
        <v-card-title class="text-wrap">{{ training.start.format('dddd, D. MMMM YYYY') }}</v-card-title>
        <v-card-subtitle class="text-wrap">
          {{ training.start.format('HH:mm') }} - {{ training.end.format('HH:mm') }}
          <span v-if="locationName"> &bull; {{ locationName }}</span>
        </v-card-subtitle>
        <v-card-text v-if="groupNames.length" class="pt-2">
          <v-chip v-for="name in groupNames" :key="name" size="small" class="mr-1">{{ name }}</v-chip>
        </v-card-text>
        <v-alert v-if="training.evaluated" type="info" variant="tonal" density="compact" class="mx-4 mb-4">
          Dieses Training ist schon abgeschlossen. Du kannst die Angaben hier noch korrigieren.
        </v-alert>
      </v-card>

      <v-card class="mb-3">
        <v-card-title class="text-subtitle-1">Trainer (ÜL-Abrechnung)</v-card-title>
        <v-card-text>
          <div v-for="t in trainerRows" :key="t.userId" class="d-flex align-center flex-wrap ga-2 mb-2" data-test="trainer-row">
            <div class="trainer-name">{{ t.name }}</div>
            <div class="d-flex ga-2 flex-grow-1 justify-end">
            <v-text-field
              v-model="t.start"
              type="time"
              label="Von"
              density="compact"
              variant="outlined"
              hide-details
              class="time-field"
              :data-test="`trainer-start-${t.userId}`"
            ></v-text-field>
            <v-text-field
              v-model="t.end"
              type="time"
              label="Bis"
              density="compact"
              variant="outlined"
              hide-details
              class="time-field"
              :data-test="`trainer-end-${t.userId}`"
            ></v-text-field>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-card class="mb-3">
        <v-card-title class="text-subtitle-1">
          Anwesenheit
          <span class="text-medium-emphasis text-body-2 ml-2" data-test="present-count">
            {{ presentIds.size }} von {{ members.length }} da
          </span>
        </v-card-title>
        <v-list lines="one">
          <v-list-item
            v-for="m in members"
            :key="m.id"
            :subtitle="m.extra ? 'Zusätzlich hinzugefügt' : undefined"
            data-test="member-row"
            @click="toggle(m.id)"
          >
            <template #title>{{ m.name }}<BirthdayHint v-if="training" :user-id="m.id" :date="training.start" /></template>
            <template #append>
              <v-btn
                :data-test="`toggle-${m.id}`"
                :aria-pressed="presentIds.has(m.id)"
                :color="presentIds.has(m.id) ? 'success' : undefined"
                :variant="presentIds.has(m.id) ? 'flat' : 'outlined'"
                :icon="presentIds.has(m.id) ? 'mdi-check' : 'mdi-close'"
                size="default"
                :aria-label="presentIds.has(m.id) ? `${m.name} ist da` : `${m.name} fehlt`"
                @click.stop="toggle(m.id)"
              ></v-btn>
            </template>
          </v-list-item>
          <v-list-item v-if="!members.length" title="Keine Mitglieder in den Gruppen dieses Trainings"></v-list-item>
        </v-list>
        <v-card-text>
          <v-autocomplete
            v-model="addCandidate"
            :items="addableUsers"
            item-title="name"
            item-value="id"
            label="Weitere Person hinzufügen"
            prepend-inner-icon="mdi-account-plus"
            density="comfortable"
            variant="outlined"
            hide-details
            clearable
            no-data-text="Keine Person gefunden"
            data-test="add-person"
            @update:model-value="addPerson"
          ></v-autocomplete>
        </v-card-text>
      </v-card>

      <v-btn
        color="primary"
        size="x-large"
        block
        :loading="saving"
        :disabled="saving"
        data-test="complete"
        @click="complete"
      >
        Abschließen
      </v-btn>
    </template>
  </v-container>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import httpClient from '@/http/api'
import { parseApiDate, type Dayjs } from '@/helpers/dayjs'
import BirthdayHint from '@/components/BirthdayHint.vue'
import { useMasterDataStore } from '@/store/masterData'
import { useSnackbarStore } from '@/store/snackbar'

interface Member { id: number; name: string; extra: boolean }
interface TrainerRow { userId: number; name: string; start: string; end: string }
interface LoadedTraining {
  id: number
  start: Dayjs
  end: Dayjs
  locationId: number | null
  groupIds: number[]
  evaluated: boolean
}

const route = useRoute()
const router = useRouter()
const masterData = useMasterDataStore()

const loading = ref(true)
const loadError = ref('')
const saving = ref(false)
const training = ref<LoadedTraining | null>(null)
const trainerRows = ref<TrainerRow[]>([])
const presentIds = ref<Set<number>>(new Set())
const baseMemberIds = ref<number[]>([])
const extraIds = ref<number[]>([])
const addCandidate = ref<number | null>(null)

const fullName = (u: { firstName?: string; familyName?: string } | undefined, id: number) =>
  u ? `${u.firstName ?? ''} ${u.familyName ?? ''}`.trim() : `Person #${id}`

const nameOf = (id: number) => fullName(masterData.simpleUsers.find(u => u.id === id), id)

const members = computed<Member[]>(() => {
  const list = [...baseMemberIds.value, ...extraIds.value].map(id => ({
    id,
    name: nameOf(id),
    extra: extraIds.value.includes(id),
  }))
  return list.sort((a, b) => Number(a.extra) - Number(b.extra) || a.name.localeCompare(b.name, 'de'))
})

const addableUsers = computed(() => {
  const taken = new Set([...baseMemberIds.value, ...extraIds.value])
  return masterData.simpleUsers
    .filter(u => !taken.has(u.id))
    .map(u => ({ id: u.id, name: fullName(u, u.id) }))
    .sort((a, b) => a.name.localeCompare(b.name, 'de'))
})

const groupNames = computed(() =>
  (training.value?.groupIds ?? []).map(id => masterData.getGroupById(id)?.name as string | undefined).filter(Boolean) as string[]
)
const locationName = computed(() =>
  training.value?.locationId ? masterData.getLocationNameById(training.value.locationId) : ''
)

function toggle(id: number) {
  const next = new Set(presentIds.value)
  if (next.has(id)) next.delete(id)
  else next.add(id)
  presentIds.value = next
}

function addPerson(id: number | null) {
  if (id == null) return
  extraIds.value = [...extraIds.value, id]
  presentIds.value = new Set(presentIds.value).add(id)
  addCandidate.value = null
}

async function load() {
  try {
    const { data } = await httpClient.get(`/trainingevaluation/${route.params.id}/detail`)
    const t = data.data
    const start = parseApiDate(t.start)
    const end = parseApiDate(t.end)
    training.value = {
      id: t.id,
      start,
      end,
      locationId: t.locationId ?? null,
      groupIds: t.groupIds ?? [],
      evaluated: t.evaluated === 1 || t.evaluated === true,
    }

    trainerRows.value = (t.trainers ?? []).map((tr: any) => {
      const trainer = masterData.getSimpleTrainerById(tr.userId)
      return {
        userId: tr.userId,
        name: fullName(trainer, tr.userId),
        start: (tr.accountingTimeStart ? parseApiDate(tr.accountingTimeStart) : start).format('HH:mm'),
        end: (tr.accountingTimeEnd ? parseApiDate(tr.accountingTimeEnd) : end).format('HH:mm'),
      }
    })

    // everybody in the groups, plus anybody who already has a participation row
    const groupMemberIds = masterData.simpleUsers
      .filter(u => (u.groupIds ?? []).some(g => training.value!.groupIds.includes(g)))
      .map(u => u.id)
    const participations: { userId: number; attend: number | boolean }[] = t.participants ?? []
    const known = new Set(groupMemberIds)
    baseMemberIds.value = [...groupMemberIds, ...participations.map(p => p.userId).filter(id => !known.has(id))]
    presentIds.value = new Set(participations.filter(p => p.attend === 1 || p.attend === true).map(p => p.userId))
  } catch (e: any) {
    loadError.value = e?.response?.status === 403
      ? 'Du bist diesem Training nicht als Trainer zugeordnet.'
      : e?.response?.status === 404
        ? 'Dieses Training gibt es nicht (mehr).'
        : 'Das Training konnte nicht geladen werden.'
  } finally {
    loading.value = false
  }
}

function toIso(base: Dayjs, time: string): Dayjs | null {
  const m = /^(\d{1,2}):(\d{2})/.exec(time || '')
  if (!m) return null
  return base.hour(Number(m[1])).minute(Number(m[2])).second(0).millisecond(0)
}

async function complete() {
  if (!training.value) return
  const snackbar = useSnackbarStore()

  const trainerTimes = []
  for (const row of trainerRows.value) {
    const start = toIso(training.value.start, row.start)
    const end = toIso(training.value.start, row.end)
    if (!start || !end || !end.isAfter(start)) {
      snackbar.show(`Die Zeiten von ${row.name} sind ungültig: Ende muss nach dem Beginn liegen.`, 'error')
      return
    }
    trainerTimes.push({ userId: row.userId, start: start.format(), end: end.format() })
  }

  saving.value = true
  try {
    await httpClient.post(`/trainingevaluation/${training.value.id}/complete`, {
      attendeeIds: [...presentIds.value],
      trainerTimes,
    })
    snackbar.show('Training abgeschlossen', 'success')
    router.push('/trainingsEvaluation')
  } catch (e: any) {
    const status = e?.response?.status
    snackbar.show(
      status === 403 ? 'Du darfst dieses Training nicht abschließen.' : 'Abschließen hat nicht geklappt. Bitte erneut versuchen.',
      'error'
    )
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.quick-evaluation {
  max-width: 640px;
}
.time-field {
  max-width: 140px;
  min-width: 100px;
}
.trainer-name {
  flex: 1 1 100%;
}
@media (min-width: 600px) {
  .trainer-name {
    flex: 1 1 0;
  }
}
</style>

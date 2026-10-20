<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Anwesenheit</v-toolbar-title>
          </v-toolbar>
          <v-card-text class="pa-0 pa-md-4">
            <v-card class="ma-1">
              <v-card-text>
                <v-row>
                  <v-col cols="12" md="5">
                    <v-select
                        v-model="groupId"
                        :items="groupItems"
                        item-title="title"
                        item-value="id"
                        label="Gruppe"
                        prepend-icon="mdi-account-group"
                        hide-details
                    ></v-select>
                  </v-col>
                  <v-col cols="6" md="3">
                    <v-text-field v-model="from" type="date" label="Von" hide-details></v-text-field>
                  </v-col>
                  <v-col cols="6" md="3">
                    <v-text-field v-model="to" type="date" label="Bis" hide-details></v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-chip-group v-model="preset" selected-class="text-primary" column>
                      <v-chip value="4weeks" variant="outlined">Letzte 4 Wochen</v-chip>
                      <v-chip value="3months" variant="outlined">3 Monate</v-chip>
                      <v-chip value="year" variant="outlined">Dieses Jahr</v-chip>
                    </v-chip-group>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>

            <v-alert v-if="!groupItems.length" type="info" variant="outlined" class="ma-1 mt-2">
              Dir sind keine Gruppen zugeordnet.
            </v-alert>
            <v-alert v-else-if="errorMessage" type="error" variant="outlined" class="ma-1 mt-2">{{ errorMessage }}</v-alert>
            <v-progress-linear v-if="loading" indeterminate class="mt-2"></v-progress-linear>

            <v-card v-if="data && !loading" class="ma-1 mt-2">
              <v-card-text v-if="!data.trainings.length">
                Im gewählten Zeitraum gibt es keine abgeschlossenen Trainings dieser Gruppe.
              </v-card-text>
              <template v-else>
                <v-card-text class="d-flex flex-wrap align-center ga-4 pb-0">
                  <span>{{ data.trainings.length }} abgeschlossene Trainings, {{ data.athletes.length }} Mitglieder</span>
                  <v-spacer></v-spacer>
                  <v-btn-toggle v-model="sortChoice" density="compact" variant="outlined" divided mandatory>
                    <v-btn value="name-asc" size="small">Name</v-btn>
                    <v-btn value="rate-desc" size="small">Quote</v-btn>
                  </v-btn-toggle>
                </v-card-text>

                <!-- phones: compact list -->
                <v-list v-if="xs" lines="two">
                  <v-list-item v-for="a in sortedAthletes" :key="a.id">
                    <v-list-item-title>{{ a.firstName }} {{ a.familyName }}</v-list-item-title>
                    <v-list-item-subtitle>{{ a.presentCount }} von {{ data.trainings.length }} Trainings</v-list-item-subtitle>
                    <template v-slot:append>
                      <div class="attendance-rate-cell">
                        <strong>{{ a.rate }}%</strong>
                        <v-progress-linear :model-value="a.rate ?? 0" color="primary" height="6" rounded></v-progress-linear>
                      </div>
                    </template>
                  </v-list-item>
                </v-list>

                <!-- matrix: scrolls horizontally, the name column stays -->
                <div v-else class="attendance-scroll">
                  <table class="attendance-table">
                    <thead>
                      <tr>
                        <th class="attendance-name">Name</th>
                        <th class="attendance-rate">Quote</th>
                        <th v-for="t in data.trainings" :key="t.id" class="attendance-day">
                          <div>{{ dayjs(t.start).format('DD.MM.') }}</div>
                          <div class="text-caption text-medium-emphasis">{{ dayjs(t.start).format('dd') }}</div>
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="a in sortedAthletes" :key="a.id">
                        <td class="attendance-name">{{ a.familyName }}, {{ a.firstName }}</td>
                        <td class="attendance-rate">
                          <strong>{{ a.rate }}%</strong>
                          <v-progress-linear :model-value="a.rate ?? 0" color="primary" height="4"></v-progress-linear>
                        </td>
                        <td v-for="(cell, i) in a.cells" :key="data.trainings[i].id" class="attendance-day">
                          <v-icon
                              :icon="cellIcon[cell.status]"
                              :color="cellColor[cell.status]"
                              size="small"
                              :aria-label="cellLabel(cell)"
                              :title="cellLabel(cell)"
                          ></v-icon>
                        </td>
                      </tr>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th class="attendance-name">Anwesend</th>
                        <th class="attendance-rate"></th>
                        <th v-for="t in data.trainings" :key="t.id" class="attendance-day">{{ t.presentCount }}</th>
                      </tr>
                    </tfoot>
                  </table>
                </div>

                <v-card-text v-if="!xs" class="d-flex flex-wrap ga-4 text-caption">
                  <span><v-icon icon="mdi-check-circle" color="success" size="small"></v-icon> anwesend</span>
                  <span><v-icon icon="mdi-message-text-outline" color="warning" size="small"></v-icon> entschuldigt (Grund beim Antippen/Hovern)</span>
                  <span><v-icon icon="mdi-close-circle" color="error" size="small"></v-icon> nicht erschienen</span>
                  <span><v-icon icon="mdi-minus" color="grey" size="small"></v-icon> nicht eingeplant</span>
                </v-card-text>
              </template>
            </v-card>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useDisplay } from 'vuetify'
import dayjs from '@/helpers/dayjs'
import httpClient from '@/http/api'
import { useAuthStore } from '@/store/auth'
import { useMasterDataStore } from '@/store/masterData'
import { apiErrorMessage } from '@/helpers/apiError'
import { rangeFor, sortAthletes, type AttendanceAthlete, type CellStatus, type RangePreset } from '@/helpers/attendance'

interface AttendanceData {
  group: { id: number; name: string }
  trainings: { id: number; start: string; presentCount: number }[]
  athletes: AttendanceAthlete[]
}

const { xs } = useDisplay()
const authStore = useAuthStore()
const masterData = useMasterDataStore()

// trainers see the groups of their branches, admins all
const groupItems = computed(() => {
  const user = authStore.user
  const branchIds: number[] = (user?.trainerBranchIds ?? []) as number[]
  return masterData.groups
    .filter((g) => user?.isAdmin || branchIds.includes(g.branchId))
    .map((g) => ({ id: g.id, title: g.getWithBranchName() }))
})

const groupId = ref<number | null>(groupItems.value[0]?.id ?? null)
const initial = rangeFor('3months')
const from = ref(initial.from)
const to = ref(initial.to)
const preset = ref<RangePreset | undefined>('3months')
const data = ref<AttendanceData | null>(null)
const loading = ref(false)
const errorMessage = ref('')
const sortChoice = ref('name-asc')

const sortedAthletes = computed(() => {
  const [key, dir] = sortChoice.value.split('-') as ['name' | 'rate', 'asc' | 'desc']
  return sortAthletes(data.value?.athletes ?? [], { key, dir })
})

watch(preset, (value) => {
  if (!value) return
  const range = rangeFor(value)
  from.value = range.from
  to.value = range.to
})

let requestId = 0
async function load() {
  if (!groupId.value || !from.value || !to.value || from.value > to.value) return
  const current = ++requestId
  loading.value = true
  errorMessage.value = ''
  try {
    const { data: result } = await httpClient.get(`/attendance?groupId=${groupId.value}&from=${from.value}&to=${to.value}`)
    if (current === requestId) data.value = result
  } catch (error) {
    if (current === requestId) {
      data.value = null
      errorMessage.value = apiErrorMessage(error, 'Die Anwesenheit konnte nicht geladen werden.')
    }
  } finally {
    if (current === requestId) loading.value = false
  }
}

watch([groupId, from, to], load, { immediate: true })

const cellIcon: Record<CellStatus, string> = {
  present: 'mdi-check-circle',
  excused: 'mdi-message-text-outline',
  absent: 'mdi-close-circle',
  none: 'mdi-minus',
}
const cellColor: Record<CellStatus, string> = { present: 'success', excused: 'warning', absent: 'error', none: 'grey' }

function cellLabel(cell: { status: CellStatus; reason: string | null }) {
  switch (cell.status) {
    case 'present': return 'anwesend'
    case 'excused': return 'entschuldigt: ' + cell.reason
    case 'absent': return 'nicht erschienen'
    default: return 'nicht eingeplant'
  }
}
</script>

<style scoped>
.attendance-scroll {
  overflow-x: auto;
}
.attendance-table {
  border-collapse: separate;
  border-spacing: 0;
  width: max-content;
  min-width: 100%;
}
.attendance-table th,
.attendance-table td {
  padding: 6px 8px;
  text-align: center;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  white-space: nowrap;
}
.attendance-table .attendance-name {
  position: sticky;
  left: 0;
  z-index: 1;
  background: rgb(var(--v-theme-surface));
  text-align: left;
  min-width: 150px;
  border-right: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.attendance-table .attendance-rate {
  min-width: 70px;
}
.attendance-table .attendance-day {
  min-width: 52px;
}
.attendance-table tfoot th {
  border-top: 2px solid rgba(var(--v-border-color), var(--v-border-opacity));
  font-weight: 600;
}
.attendance-rate-cell {
  width: 90px;
  text-align: right;
}
</style>

<template>
  <v-card flat>
    <v-card-text>
      <div class="d-flex align-center ga-2 mb-2">
        <v-btn icon="mdi-chevron-left" variant="text" size="small" aria-label="Vorheriges Jahr" @click="year--"></v-btn>
        <strong class="text-h6">{{ year }}</strong>
        <v-btn icon="mdi-chevron-right" variant="text" size="small" aria-label="Nächstes Jahr" :disabled="year >= currentYear" @click="year++"></v-btn>
        <v-spacer></v-spacer>
        <span v-if="data" class="text-body-2">
          Gesamt: <strong>{{ formatHours(data.hours) }} ÜL-Std.</strong> ({{ data.trainingCount }} Trainings)
        </span>
      </div>

      <v-alert v-if="errorMessage" type="error" variant="outlined">{{ errorMessage }}</v-alert>
      <v-progress-linear v-if="loading" indeterminate></v-progress-linear>

      <v-alert v-if="data && data.unevaluated.length" type="warning" variant="tonal" class="mb-3" density="compact">
        <div class="font-weight-bold">Noch nicht abgeschlossen ({{ data.unevaluated.length }}): diese Stunden fehlen noch in der Abrechnung.</div>
        <v-list density="compact" bg-color="transparent" class="pa-0">
          <v-list-item
              v-for="t in data.unevaluated"
              :key="t.id"
              :to="'/quickEvaluation/' + t.id"
              class="px-0"
          >
            <v-list-item-title>{{ dayjs(t.start).format('dd DD.MM. HH:mm') }}–{{ dayjs(t.end).format('HH:mm') }} · {{ t.groups.join(', ') }}</v-list-item-title>
            <template v-slot:append><v-icon size="small">mdi-chevron-right</v-icon></template>
          </v-list-item>
        </v-list>
      </v-alert>

      <template v-if="data">
        <div v-for="quarter in data.quarters" :key="quarter.quarter" class="mb-3">
          <div class="d-flex align-center py-1 quarter-head">
            <strong>{{ quarter.quarter }}. Quartal</strong>
            <v-spacer></v-spacer>
            <span class="text-body-2">{{ quarter.trainingCount }} Trainings · <strong>{{ formatHours(quarter.hours) }} ÜL-Std.</strong></span>
          </div>
          <v-expansion-panels variant="accordion" flat>
            <v-expansion-panel v-for="m in monthsOfQuarter(data.months, quarter.quarter).filter((x) => x.trainingCount)" :key="m.month">
              <v-expansion-panel-title>
                <span class="flex-grow-1">{{ monthName(m.month) }}</span>
                <span class="text-body-2 mr-2">{{ m.trainingCount }} × · {{ formatHours(m.hours) }} ÜL-Std.</span>
              </v-expansion-panel-title>
              <v-expansion-panel-text>
                <v-list density="compact" class="pa-0">
                  <v-list-item v-for="e in m.entries" :key="e.trainingId">
                    <v-list-item-title>{{ dayjs(e.start).format('dd DD.MM.') }} · {{ dayjs(e.start).format('HH:mm') }}–{{ dayjs(e.end).format('HH:mm') }}</v-list-item-title>
                    <v-list-item-subtitle>{{ e.groups.join(', ') }}</v-list-item-subtitle>
                    <template v-slot:append>
                      <span class="text-body-2">{{ formatMinutes(e.minutes) }} · {{ formatHours(e.hours) }}</span>
                    </template>
                  </v-list-item>
                </v-list>
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>
        </div>
        <div class="text-caption text-medium-emphasis">
          ÜL-Stunden zu 45 Minuten, nach den Zeiten aus der Abrechnung (dieselben Werte wie im Excel-Export).
        </div>
      </template>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import dayjs from '@/helpers/dayjs'
import httpClient from '@/http/api'
import { apiErrorMessage } from '@/helpers/apiError'
import { formatHours, formatMinutes, monthName, monthsOfQuarter, type HoursMonth, type HoursQuarter } from '@/helpers/hours'

interface HoursData {
  year: number
  trainingCount: number
  minutes: number
  hours: number
  months: HoursMonth[]
  quarters: HoursQuarter[]
  unevaluated: { id: number; start: string; end: string; groups: string[] }[]
}

const currentYear = dayjs().year()
const year = ref(currentYear)
const data = ref<HoursData | null>(null)
const loading = ref(false)
const errorMessage = ref('')

let requestId = 0
async function load() {
  const current = ++requestId
  loading.value = true
  errorMessage.value = ''
  try {
    const { data: result } = await httpClient.get(`/user/me/hours?year=${year.value}`)
    if (current === requestId) data.value = result
  } catch (error) {
    if (current === requestId) errorMessage.value = apiErrorMessage(error, 'Die Stunden konnten nicht geladen werden.')
  } finally {
    if (current === requestId) loading.value = false
  }
}

watch(year, load)
onMounted(load)
</script>

<style scoped>
.quarter-head {
  border-bottom: 2px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>

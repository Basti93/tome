<template>
  <v-container>
    <v-row>
      <v-col cols="12" md="6" v-if="!hideDate">
        <v-menu
            ref="dateMenuOpened"
            :close-on-content-click="false"
            v-model="dateMenuOpened"
        >
          <template v-slot:activator="{ props }">
            <v-text-field
                :modelValue="trainingDateFormatted"
                required
                label="Datum"
                prepend-icon="event"
                readonly
                v-bind="props"
            ></v-text-field>
          </template>
          <v-date-picker v-model="trainingDate" @update:modelValue="onDateChanged"></v-date-picker>
        </v-menu>
      </v-col>
      <v-col cols="6" md="3">
        <v-menu
            ref="startMenuOpened"
            v-model="startMenuOpened"
            :close-on-content-click="false">
          <template v-slot:activator="{ props }">
            <v-text-field
                v-model="startTime"
                label="Start"
                required
                prepend-icon="schedule"
                readonly
                v-bind="props"
            ></v-text-field>
          </template>
          <v-time-picker
              v-model="startTime"
              @click:minute="startMenuOpened = false"
              format="24hr">
          </v-time-picker>
        </v-menu>
      </v-col>
      <v-col cols="6" md="3">
        <v-menu
            ref="endMenuOpened"
            :close-on-content-click="false"
            v-model="endMenuOpened">
          <template v-slot:activator="{ props }">
            <v-text-field
                v-model="endTime"
                required
                label="Ende"
                prepend-icon="schedule"
                readonly
                v-bind="props"
            ></v-text-field>
          </template>
          <v-time-picker
              v-model="endTime"
              format="24hr"
          ></v-time-picker>
        </v-menu>
      </v-col>
      <v-col cols="12" md="6">
        <v-select
            :items="locations"
            item-value="id"
            item-title="name"
            v-model="selectedLocationId"
            clearable
            required
            label="Ort"
            prepend-icon="add_location"
        ></v-select>
      </v-col>
      <v-col cols="12" md="6">
        <v-checkbox
            v-model.lazy="editedAutomaticAttend"
            label="Benutzer automatisch anmelden"
            prepend-icon="active"
        ></v-checkbox>
      </v-col>
      <v-col cols="12">
        <v-select
            v-model="selectedTrainerIds"
            :items="trainers"
            item-value="id"
            :item-title="fullName"
            attach
            clearable
            chips
            closable-chips
            label="Trainer"
            prepend-icon="verified_user"
            multiple
        >
        </v-select>
      </v-col>
      <v-col cols="12">
        <v-select
            :items="groups"
            v-model="selectedGroupIds"
            item-value="id"
            :item-title="branchAndGroupName"
            label="Gruppen"
            prepend-icon="groups"
            multiple
            clearable
            chips
            closable-chips>
          <template v-slot:prepend-item>
            <v-list-item
                @click="toggleSelectAllGroups"
            >
              <template v-slot:prepend>
                <v-icon :color="selectedGroupIds.length > 0 ? 'indigo darken-4' : ''">
                  {{ allGroupsSelectedIcon }}
                </v-icon>
              </template>
              <v-list-item-title>
                Alle Selektieren
              </v-list-item-title>
            </v-list-item>
            <v-divider class="mt-2"></v-divider>
          </template>
        </v-select>
      </v-col>
      <v-col cols="12" class="ml-2" v-if="!hideContents">
        <v-label>Trainingsinhalte</v-label>
        <TrainingContent
            :contentIds="branchContentIds"
            :initContentIds="selectedContentIds"
            selectable
            v-on:change="contentIdsChanged"
        >
        </TrainingContent>
      </v-col>
      <v-col cols="12">
        <v-textarea
            label="Kommentar"
            v-model.lazy="editedComment" variant="filled"
        ></v-textarea>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import TrainingContent from './TrainingContent.vue'
import { formatDate } from '@/helpers/date-helpers'
import Group from '../models/Group'
import { useMasterDataStore } from '@/store/masterData'

const props = defineProps<{
  hideDate?: boolean
  hideContents?: boolean
  id?: number
  date?: string
  start?: string
  end?: string
  trainerIds?: any[]
  groupIds?: any[]
  contentIds?: any[]
  locationId?: number
  branchId?: number
  trainers?: any[]
  groups?: any[]
  comment?: string
  automaticAttend?: boolean
}>()

const emit = defineEmits<{
  change: [training: Record<string, any>]
}>()

const masterData = useMasterDataStore()

const trainingDate = ref<string>('2026-04-04')
const dataId = ref<number | null | undefined>(null)
const endTime = ref<string>('12:00')
const startTime = ref<string>('09:00')
const selectedLocationId = ref<number | null | undefined>(null)
const selectedGroupIds = ref<any[]>([])
const selectedContentIds = ref<any[]>([])
const selectedTrainerIds = ref<any[] | undefined>([])
const editedComment = ref<string | null | undefined>(null)
const dateMenuOpened = ref(false)
const startMenuOpened = ref(false)
const endMenuOpened = ref(false)
const editedAutomaticAttend = ref<boolean | undefined>(true)

const locations = computed(() => masterData.locations)
const trainingDateFormatted = computed(() => formatDate(trainingDate.value))
const branchContentIds = computed(() => masterData.getContentIdsByBranchId(props.branchId))
const allGroupsSelected = computed(() => selectedGroupIds.value.length === props.groups!.length)
const someGroupsSelected = computed(() => selectedGroupIds.value.length > 0 && !allGroupsSelected.value)
const allGroupsSelectedIcon = computed(() => {
  if (allGroupsSelected.value) return 'check_box'
  if (someGroupsSelected.value) return 'indeterminate_check_box'
  return 'check_box_outline_blank'
})

// Convert Date object to YYYY-MM-DD string format
function applyDate(dateValue: unknown) {
  if (dateValue instanceof Date) {
    const year = dateValue.getFullYear()
    const month = String(dateValue.getMonth() + 1).padStart(2, '0')
    const day = String(dateValue.getDate()).padStart(2, '0')
    trainingDate.value = `${year}-${month}-${day}`
  } else if (typeof dateValue === 'string') {
    trainingDate.value = dateValue
  }
}

function contentIdsChanged(contentIds: any[]): void {
  selectedContentIds.value = contentIds
}

function onDateChanged(dateValue: unknown) {
  applyDate(dateValue)
  // Close the date picker menu after selection
  dateMenuOpened.value = false
}

function fireChangeEvent() {
  emit('change', {
    id: dataId.value,
    date: trainingDate.value,
    start: startTime.value,
    end: endTime.value,
    locationId: selectedLocationId.value,
    trainerIds: selectedTrainerIds.value,
    groupIds: selectedGroupIds.value,
    contentIds: selectedContentIds.value,
    comment: editedComment.value,
    automaticAttend: editedAutomaticAttend.value
  })
}

function branchAndGroupName(item: Group) {
  return item.getWithBranchName()
}

const fullName = (item: { firstName: string; familyName: string }) => item.firstName + ' ' + item.familyName

function toggleSelectAllGroups() {
  nextTick(() => {
    if (allGroupsSelected.value) {
      selectedGroupIds.value = []
    } else {
      selectedGroupIds.value = props.groups!.map((g) => g.id).slice()
    }
  })
}

// props -> local edit state
watch(() => props.id, (newVal) => { dataId.value = newVal }, { immediate: true })
watch(() => props.date, (newVal) => applyDate(newVal), { immediate: true })
watch(() => props.start, (newVal) => { startTime.value = newVal as string }, { immediate: true })
watch(
  () => props.end,
  (newVal) => {
    if (newVal) {
      endTime.value = newVal
    }
  },
  { immediate: true }
)
watch(() => props.locationId, (newVal) => { selectedLocationId.value = newVal }, { immediate: true })
watch(
  () => props.groupIds,
  (newVal) => {
    if (newVal) {
      selectedGroupIds.value = newVal
    }
  },
  { immediate: true }
)
watch(() => props.trainerIds, (newVal) => { selectedTrainerIds.value = newVal }, { immediate: true })
watch(() => props.contentIds, (newVal) => { selectedContentIds.value = newVal as any[] }, { immediate: true })
watch(() => props.comment, (newVal) => { editedComment.value = newVal }, { immediate: true })
watch(() => props.automaticAttend, (newVal) => { editedAutomaticAttend.value = newVal }, { immediate: true })

// local edit state -> change event
for (const source of [
  trainingDate,
  startTime,
  endTime,
  selectedTrainerIds,
  selectedGroupIds,
  selectedContentIds,
  selectedLocationId,
  editedComment,
  editedAutomaticAttend
]) {
  watch(source, () => fireChangeEvent())
}
</script>

<style scoped>

</style>

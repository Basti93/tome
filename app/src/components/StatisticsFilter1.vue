<template>
  <v-container pa-0 ma-0>
    <v-row>
      <v-col cols="12" md="4">
        <v-select
            v-bind:items="branches"
            item-title="name"
            item-value="id"
            v-model="selectedBranchId"
            label="Sparte"
            prepend-icon="mdi-chart-bubble"
        ></v-select>
      </v-col>
      <v-col cols="12" md="4">
        <v-autocomplete
            v-bind:disabled="!selectedBranchId"
            v-bind:items="groupItems"
            v-model="selectedGroupIds"
            item-title="name"
            item-value="id"
            multiple
            clearable
            chips
            closable-chips
            @update:model-value="selectedGroupIdsChanged()"
            label="Gruppen"
            prepend-icon="mdi-account"
        ></v-autocomplete>
      </v-col>
      <v-col cols="12" md="4">
        <v-select
            :items="years"
            v-model="selectedYear"
            label="Jahr"
            @update:model-value="selectedYearChanged()"
            prepend-icon="mdi-calendar"
        ></v-select>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useMasterDataStore } from '@/store/masterData'
import dayjs from '@/helpers/dayjs'

const props = defineProps<{
  branchIds?: any[]
  year?: number
}>()

const emit = defineEmits<{
  groupsSelected: [groupIds: any[]]
  yearSelected: [year: number | null]
}>()

const masterData = useMasterDataStore()

const selectedBranchId = ref<number | null>(null)
const selectedGroupIds = ref<any[]>([])
const groupItems = ref<any[]>([])
const selectedYear = ref<number | null | undefined>(null)

const groups = computed(() => masterData.groups)
const branches = computed(() => masterData.branches)

// current year and the four years before it (dayjs is immutable, no mutation loop)
function lastFiveYears() {
  const currentYear = dayjs().year()
  return Array.from({ length: 5 }, (_, i) => currentYear - i)
}

const years = lastFiveYears()

function initSelect() {
  if (props.branchIds && props.branchIds.length > 0) {
    selectedBranchId.value = props.branchIds[0]
  }
}

function selectedYearChanged() {
  emit('yearSelected', selectedYear.value as number | null)
}

function selectedGroupIdsChanged() {
  emit('groupsSelected', selectedGroupIds.value)
}

watch(
  selectedBranchId,
  (newVal, oldVal) => {
    if (newVal && newVal != oldVal) {
      // reset group data
      selectedGroupIds.value = []
      groupItems.value = []
      // add all groups of branch to groupItems
      for (const group of groups.value) {
        if (selectedBranchId.value === group.branchId) {
          groupItems.value.push(group)
          selectedGroupIds.value.push(group.id)
        }
      }
      emit('groupsSelected', selectedGroupIds.value)
    }
  },
  { immediate: true }
)

selectedYear.value = props.year
initSelect()
</script>

<style scoped>

</style>

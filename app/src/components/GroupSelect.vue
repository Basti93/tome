<template>
    <v-card flat>
        <v-select
          v-bind:items="branches"
          item-title="name"
          item-value="id"
          v-model="selectedBranchId"
          clearable
          label="Sparte"
          prepend-icon="mdi-chart-bubble"
        ></v-select>
        <v-select
          v-bind:disabled="!selectedBranchId"
          v-bind:items="groupItems"
          v-model="selectedGroupId"
          item-title="name"
          item-value="id"
          clearable
          label="Gruppe"
          prepend-icon="mdi-account"
        ></v-select>
    </v-card>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useMasterDataStore } from '@/store/masterData'

const props = defineProps<{
  branchId?: number
  groupId?: number
}>()

const emit = defineEmits<{
  branchSelected: [branchId: number | null]
  groupSelected: [groupId: number | null]
}>()

const masterData = useMasterDataStore()

const selectedBranchId = ref<number | null>(null)
const selectedGroupId = ref<number | null>(null)
const groupItems = ref<any[]>([])

const groups = computed(() => masterData.groups)
const branches = computed(() => masterData.branches)

function fillGroupSelect() {
  groupItems.value = []
  for (const group of groups.value) {
    if (selectedBranchId.value === group.branchId) {
      groupItems.value.push(group)
    }
  }
}

watch(
  () => props.groupId,
  () => {
    selectedGroupId.value = props.groupId as number
  }
)
watch(
  () => props.branchId,
  () => {
    selectedBranchId.value = props.branchId as number
  }
)
watch(selectedBranchId, () => {
  if (selectedBranchId.value) {
    fillGroupSelect()
  } else {
    selectedGroupId.value = null
    groupItems.value = []
  }
  emit('branchSelected', selectedBranchId.value)
  emit('groupSelected', selectedGroupId.value)
})
watch(selectedGroupId, () => {
  emit('groupSelected', selectedGroupId.value)
})
</script>

<style scoped>

</style>

<template>
    <div>
        <v-autocomplete
                v-bind:items="groups"
                v-model="selectedGroupIds"
                :item-title="branchAndGroupName"
                item-value="id"
                multiple
                clearable
                chips
                closable-chips
                label="Gruppen"
                prepend-icon="mdi-account-group"
        >
        </v-autocomplete>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import Group from '../models/Group'
import { useMasterDataStore } from '@/store/masterData'

const props = defineProps<{
  groupIds?: number[]
}>()

const emit = defineEmits<{
  groupsChanged: [payload: { groupIds: number[] }]
}>()

const masterData = useMasterDataStore()

const selectedGroupIds = ref<number[]>([])
const groups = computed(() => masterData.groups)

function branchAndGroupName(item: Group) {
  return item.getWithBranchName()
}

watch(
  () => props.groupIds,
  (newVal) => {
    if (newVal) {
      selectedGroupIds.value = newVal
    }
  },
  { immediate: true }
)
watch(selectedGroupIds, () => {
  emit('groupsChanged', { groupIds: selectedGroupIds.value })
})
</script>

<style scoped>

</style>

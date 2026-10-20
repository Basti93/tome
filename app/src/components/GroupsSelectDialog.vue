<template>
  <v-dialog v-model="show" max-width="600px" :fullscreen="smAndDown" persistent>
    <v-card>
      <v-toolbar flat>
        <v-btn icon @click="show=false">
          <v-icon>mdi-close</v-icon>
        </v-btn>
        <v-toolbar-title>Filter ändern</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-toolbar-items>
          <v-btn color="primary" v-on:click="done" :disabled="!valid" variant="text"><v-icon start>mdi-check</v-icon>Auswählen</v-btn>
        </v-toolbar-items>
      </v-toolbar>
      <v-divider></v-divider>
      <v-card-text>
        <v-form
          v-model="valid">
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
          v-model="selectedGroupIds"
          item-title="name"
          item-value="id"
          multiple
          clearable
          chips
          closable-chips
          label="Gruppen"
          prepend-icon="mdi-account-group"
        ></v-select>
        </v-form>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useDisplay } from 'vuetify'
import { useMasterDataStore } from '@/store/masterData'

const props = defineProps<{
  visible?: boolean
  groupIds: any[]
}>()

const emit = defineEmits<{
  close: []
  done: [payload: { branchId: number | null; groupdIds: any[] | null }]
}>()

const { smAndDown } = useDisplay()
const masterData = useMasterDataStore()

const valid = ref(true)
const selectedBranchId = ref<number | null>(null)
const selectedGroupIds = ref<any[] | null>([])
const groupItems = ref<any[]>([])

const groups = computed(() => masterData.groups)
const branches = computed(() => masterData.branches)

const show = computed({
  get: () => props.visible,
  set: (value) => {
    if (!value) {
      emit('close')
    }
  }
})

function fillGroupSelect() {
  groupItems.value = []
  selectedGroupIds.value = []
  groups.value.forEach((item) => {
    if (selectedBranchId.value === item.branchId) {
      groupItems.value.push(item)
    }
  })
}

function initSelect() {
  selectedGroupIds.value = props.groupIds
  if (props.groupIds.length > 0) {
    selectedBranchId.value = masterData.getBranchByGroupId(props.groupIds[0]).id
  }
}

function done() {
  emit('done', { branchId: selectedBranchId.value, groupdIds: selectedGroupIds.value })
  show.value = false
}

watch(selectedBranchId, () => {
  if (selectedBranchId.value) {
    fillGroupSelect()
  } else {
    selectedGroupIds.value = null
    groupItems.value = []
  }
})

initSelect()
</script>

<style scoped>

</style>

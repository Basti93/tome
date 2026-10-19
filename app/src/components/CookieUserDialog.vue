<template>
  <v-dialog
          v-model="show"
          max-width="500px"
          persistent>
    <v-card>
      <v-toolbar flat>
        <v-btn icon @click="show=false">
          <v-icon>mdi-close</v-icon>
        </v-btn>
        <v-toolbar-title>Wer bist du?</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-toolbar-items>
          <v-btn color="primary" @click="done" variant="text"><v-icon start>mdi-check</v-icon>Auswählen</v-btn>
        </v-toolbar-items>
      </v-toolbar>
        <v-divider></v-divider>
        <v-card-text class="mt-2">
          <v-alert
                  type="info"
                  pa-1
                  ma-0
                  density="compact"
                  class="caption" variant="outlined"
          >
            Wähle deinen Namen aus um deine Trainings zu sehen.
          </v-alert>
          <v-select
            :items="branches"
            item-title="name"
            item-value="id"
            v-model="branchId"
            clearable
            label="Sparte"
          ></v-select>
          <v-select
            :disabled="!branchId"
            :items="branchGroups"
            item-title="name"
            item-value="id"
            v-model="groupId"
            clearable
            label="Gruppe"
          ></v-select>
          <v-autocomplete
            :items="filteredUsers"
            v-model="userId"
            :item-title="fullName"
            item-value="id"
            clearable

            label="Name"
          ></v-autocomplete>
        </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useMasterDataStore } from '@/store/masterData'
import { useCookieAuthStore } from '@/store/cookieAuth'

const props = defineProps<{
  visible?: boolean
  propUserId?: number | null
  propGroupId?: number | null
  propBranchId?: number | null
}>()

const emit = defineEmits<{
  close: []
}>()

const masterData = useMasterDataStore()

const userId = ref<number | null>(null)
const branchId = ref<number | null>(null)
const groupId = ref<number | null>(null)

const users = computed(() => masterData.simpleUsers)
const branches = computed(() => masterData.branches)

// groups of the selected branch (none until a branch is selected)
const branchGroups = computed(() => {
  if (!branchId.value) return []
  return masterData.groups.filter((g) => g.branchId === branchId.value)
})

// users narrowed down by the selected group, else by the selected branch
const filteredUsers = computed(() => {
  if (groupId.value) {
    return users.value.filter((u) => (u.groupIds || []).includes(groupId.value as number))
  }
  if (branchId.value) {
    const ids = branchGroups.value.map((g) => g.id)
    return users.value.filter((u) => (u.groupIds || []).some((id) => ids.includes(id)))
  }
  return users.value
})

const show = computed({
  get: () => props.visible,
  set: (value) => {
    if (!value) {
      emit('close')
    }
  }
})

function dropUnavailableUser() {
  if (userId.value && !filteredUsers.value.some((u) => u.id === userId.value)) {
    userId.value = null
  }
}

// preselect branch/group/user from the props (e.g. the previously chosen user) when (re)opening
function applyProps() {
  const user = props.propUserId ? users.value.find((u) => u.id === props.propUserId) : null
  const newGroupId = props.propGroupId || null
  let newBranchId = props.propBranchId || null
  if (newGroupId && !newBranchId) {
    const group = masterData.groups.find((g) => g.id === newGroupId)
    newBranchId = group ? group.branchId : null
  }
  branchId.value = newBranchId
  groupId.value = newGroupId
  userId.value = user ? user.id : null
}

const fullName = (item: { firstName: string; familyName: string }) => item.firstName + ' ' + item.familyName

function done() {
  if (userId.value) {
    useCookieAuthStore().selectCookieUser(JSON.stringify(users.value.find((u) => u.id === userId.value)))
    emit('close')
  }
}

// Vuetify 3/4 selects emit `update:modelValue` (never `change`), so react to the bound values
watch(branchId, () => {
  if (groupId.value && !branchGroups.value.some((g) => g.id === groupId.value)) {
    groupId.value = null
  }
  dropUnavailableUser()
})
watch(groupId, () => dropUnavailableUser())
watch(
  () => props.visible,
  (value) => {
    if (value) applyProps()
  }
)

applyProps()
</script>

<style scoped>
  .v-dialog__container {
    display: unset;
  }
</style>

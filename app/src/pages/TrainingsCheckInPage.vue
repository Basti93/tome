<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Aktuelle Trainings</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-chip variant="outlined">
              <v-icon start color="primary">mdi-account</v-icon>
              {{ filterDisplayValue }}
            </v-chip>
            <v-btn icon color="primary" aria-label="Filter ändern" @click="filterDialogVisible = true">
              <v-icon>mdi-filter-variant</v-icon>
            </v-btn>
            <template v-slot:extension v-if="cookieUser">
              <v-chip
                  class="ml-2"
                  closable
                  color="primary"
                  @click:close="removeCookieUser()">{{ cookieUser.getFullName() }}
              </v-chip>
            </template>
            <v-dialog
                v-model="filterDialogVisible"
                max-width="500px"
                :fullscreen="xs"
                persistent>
              <v-card>
                <v-toolbar flat dense>
                  <v-btn icon aria-label="Schließen" @click="filterDialogVisible = false">
                    <v-icon>mdi-close</v-icon>
                  </v-btn>
                  <v-toolbar-title>Filter ändern</v-toolbar-title>
                  <v-spacer></v-spacer>
                  <v-toolbar-items>
                    <v-btn color="primary" @click="filterDone" variant="text">
                      <v-icon>mdi-check</v-icon>
                    </v-btn>
                  </v-toolbar-items>
                </v-toolbar>
                <v-divider></v-divider>
                <v-card-text flat>
                  <GroupSelect
                      v-bind:groupId="currentUserGroupId"
                      v-on:groupSelected="groupChanged"
                      v-on:branchSelected="branchChanged"
                  >
                  </GroupSelect>
                </v-card-text>
              </v-card>
            </v-dialog>
          </v-toolbar>
          <v-divider></v-divider>
          <v-card-text class="pa-0 pa-md-4">
            <v-container>
              <v-row no-gutters>
                <v-col cols="12" md="3" class="pa-0">
                  <TrainingSelector
                      application="checkin"
                      :trainings="upcomingTrainings"
                      :selectedTrainingId="selectedTrainingId"
                      v-on:change="selectTraining"
                  ></TrainingSelector>
                </v-col>
                <v-col cols="12" md="9">
                  <div v-show="dataLoaded" class="tp-training-check-in" :style="{ '--selected-color': selectedTrainingColor } as any">
                    <v-slide-x-transition>
                      <TrainingCheckIn
                          v-if="selectedTraining && animationTrigger"
                          :currentUser="currentUser"
                          :isCookieUser="isCookieUser"
                          :training="selectedTraining"
                          :participants="selectedTraining.participants"
                          v-on:checkedIn="updateCheckedIn()"
                          v-on:checkedOut="updateCheckedOut()"
                          v-on:showCookieUserLogin="showCookieUserLogin()"
                          class="tp-training-check-in__card">
                      </TrainingCheckIn>
                      <v-alert
                          v-if="!initializing && !selectedTraining"
                          type="info"
                          pa-1
                          ma-0
                          class="caption" variant="outlined"
                      >
                        Momentan gibt es keine kommenden Trainings für deine Gruppe ☹️
                      </v-alert>
                    </v-slide-x-transition>
                  </div>
                </v-col>
              </v-row>
            </v-container>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    <CookieUserDialog
        :visible="cookieUserDialogVisible"
        v-on:close="cookieUserDialogVisible = false"
    ></CookieUserDialog>
  </v-container>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useDisplay } from 'vuetify'
import { useAuthStore } from '@/store/auth'
import { useMasterDataStore } from '@/store/masterData'
import { useCookieAuthStore } from '@/store/cookieAuth'
import { useSnackbarStore } from '@/store/snackbar'
import httpClient from '@/http/api'
import { parseApiDate } from '@/helpers/dayjs'
import TrainingCheckIn from '../components/TrainingCheckIn.vue'
import GroupSelect from '../components/GroupSelect.vue'
import CookieUserDialog from '../components/CookieUserDialog.vue'
import Training from '@/models/Training'
import TrainingParticipant from '@/models/TrainingParticipant'
import TrainingSelector from '../components/TrainingSelector.vue'

const { xs } = useDisplay()
const route = useRoute()
const authStore = useAuthStore()
const cookieAuthStore = useCookieAuthStore()
const masterData = useMasterDataStore()

const initalSelectedTrainingId = ref<any>(null)
const filterGroupId = ref<any>(null)
const filterBranchId = ref<any>(null)
const tempFilterGroupId = ref<any>(null)
const tempFilterBranchId = ref<any>(null)
const upcomingTrainings = ref<Training[]>([])
const dataLoaded = ref(false)
const filterDialogVisible = ref(false)
const cookieUserDialogVisible = ref(false)
const selectedTrainingId = ref<any>(null)
const animationTrigger = ref(true)
const initializing = ref(false)

const loggedInUser = computed(() => authStore.user)
const cookieUser = computed(() => cookieAuthStore.cookieUser)
const currentUser = computed<any>(() => {
  if (loggedInUser.value) {
    return loggedInUser.value
  } else if (cookieUser.value) {
    return cookieUser.value
  }
  return null
})
const isCookieUser = computed(() => cookieUser.value != null)

function getUpcomingTrainingById(id: any) {
  if (upcomingTrainings.value.length > 0) {
    return upcomingTrainings.value.filter((ut) => ut.id == id)[0]
  }
  return null
}

const selectedTraining = computed<any>(() => getUpcomingTrainingById(selectedTrainingId.value))

const selectedTrainingColor = computed(() => {
  if (selectedTraining.value) {
    const mainBranch = masterData.getBranchByGroupIds(selectedTraining.value.groupIds)
    if (mainBranch) {
      return mainBranch.colorHex
    }
  }
  return '#64b5f6'
})

const currentUserGroupId = computed(() => {
  if (currentUser.value && currentUser.value.groupIds && currentUser.value.groupIds.length > 0) {
    return currentUser.value.groupIds[0]
  }
  return null
})

const filterDisplayValue = computed(() => {
  if (filterGroupId.value) {
    const group = masterData.getGroupById(filterGroupId.value)
    if (group) {
      return group.name
    }
  } else if (filterBranchId.value) {
    const branch = masterData.getBranchById(filterBranchId.value)
    if (branch) {
      return branch.name
    }
  }
  return 'Alle Gruppen'
})

const currentUserId = computed(() => (currentUser.value ? currentUser.value.id : null))

function groupChanged(groupId: any) {
  tempFilterGroupId.value = groupId
}

function branchChanged(branchId: any) {
  tempFilterBranchId.value = branchId
}

function selectTraining(id: any) {
  animationTrigger.value = false
  selectedTrainingId.value = id
  setTimeout(() => {
    animationTrigger.value = true
  }, 100)
}

function addToUpcomingTrainings(trainingJson: any) {
  const participants: TrainingParticipant[] = []
  for (const partObj of trainingJson.participants) {
    participants.push(new TrainingParticipant(partObj.trainingId, partObj.userId, partObj.attend === 1 ? true : false, null))
  }
  if (upcomingTrainings.value.filter((t) => t.id === trainingJson.id).length === 0) {
    upcomingTrainings.value.push(
      new Training(
        trainingJson.id,
        parseApiDate(trainingJson.start),
        parseApiDate(trainingJson.end),
        trainingJson.locationId,
        trainingJson.groupIds,
        trainingJson.contentIds,
        trainingJson.trainerIds,
        participants,
        trainingJson.comment,
        false,
        false,
        trainingJson.automaticAttend == 1
      )
    )
  }
  upcomingTrainings.value.sort(function (a: Training, b: Training) {
    return a.start.valueOf() - b.start.valueOf()
  })
}

async function fetchSingleTraining(trainingId: any) {
  const { data } = await httpClient.get('/training/upcoming/' + trainingId)
  if (data.data) {
    addToUpcomingTrainings(data.data)
  } else {
    console.log('Training with id ' + trainingId + ' could not be found or is in the past')
  }
}

async function fetchData() {
  try {
    dataLoaded.value = false
    upcomingTrainings.value = []

    if (initalSelectedTrainingId.value) {
      await fetchSingleTraining(initalSelectedTrainingId.value)
    }

    //build fetch url
    let url = '/training/upcoming'
    if (filterGroupId.value) {
      url += '?groupIds=' + filterGroupId.value
    } else if (filterBranchId.value) {
      url += '?branchId=' + filterBranchId.value
    }
    //load data
    const { data } = await httpClient.get(url)
    if (data.data && data.data.length > 0) {
      for (const trObj of data.data) {
        addToUpcomingTrainings(trObj)
      }
    }
  } catch (error) {
    console.error(error)
  } finally {
    dataLoaded.value = true
  }
}

async function fetchDataForCurrentUser() {
  filterGroupId.value = currentUserGroupId.value

  initalSelectedTrainingId.value = route.params.id
  await fetchData()

  if (initalSelectedTrainingId.value) {
    selectTraining(upcomingTrainings.value.filter((t) => t.id == initalSelectedTrainingId.value)[0].id)
  } else if (upcomingTrainings.value.length > 0) {
    selectTraining(upcomingTrainings.value[0].id)
  }
}

async function filterDone() {
  filterBranchId.value = tempFilterBranchId.value
  filterGroupId.value = tempFilterGroupId.value
  await fetchData()
  if (upcomingTrainings.value.length > 0) {
    selectTraining(upcomingTrainings.value[0].id)
  }
  filterDialogVisible.value = false
}

function updateCheckedIn() {
  const participants = selectedTraining.value.participants
  const participant = participants.filter((p: TrainingParticipant) => p.userId === currentUserId.value)
  if (participant && participant.length > 0) {
    const index = participants.indexOf(participant[0])
    participant[0].attend = true
    participants[index].attend = true
  } else {
    participants.push(new TrainingParticipant(selectedTraining.value.id, currentUserId.value, true, null))
  }
  useSnackbarStore().show('Für das Training angemeldet', 'success')
}

function updateCheckedOut() {
  const participants = selectedTraining.value.participants
  const participant = participants.filter((p: TrainingParticipant) => p.userId === currentUserId.value)
  if (participant && participant.length > 0) {
    const index = participants.indexOf(participant[0])
    participants[index].attend = false
  } else {
    participants.push(new TrainingParticipant(selectedTraining.value.id, currentUserId.value, false, null))
  }
  useSnackbarStore().show('Vom Training abgemeldet', 'info')
}

function showCookieUserLogin() {
  cookieUserDialogVisible.value = true
}

function removeCookieUser() {
  cookieAuthStore.eraseCookieUser()
}

watch(currentUser, () => {
  filterGroupId.value = currentUserGroupId.value
  if (!initializing.value) {
    fetchDataForCurrentUser()
  }
})

initializing.value = true
if (!currentUser.value) {
  cookieUserDialogVisible.value = true
}
if (loggedInUser.value && loggedInUser.value.trainerBranchIds && loggedInUser.value.trainerBranchIds.length > 0) {
  filterBranchId.value = loggedInUser.value.trainerBranchIds[0]
}
fetchDataForCurrentUser()
initializing.value = false
</script>

<style scoped lang="scss">
.tp-training-check-in {
  --selected-color: #64b5f6;
  position: relative;

  .tp-training-check-in__card {
    position: relative;
    background: linear-gradient(
      90deg,
      color-mix(in srgb, var(--selected-color) 8%, white) 0%,
      transparent 25%
    );
    margin-left: 6px;

    :deep(.v-card__title) {
      justify-content: center !important;
      text-align: center !important;
    }

    &::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 6px;
      background-color: var(--selected-color);
      border-radius: 4px 0 0 4px;
    }
  }
}
</style>

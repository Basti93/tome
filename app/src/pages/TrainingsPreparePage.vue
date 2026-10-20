<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Trainingsvorbereitung</v-toolbar-title>
          </v-toolbar>
          <v-card-text v-show="dataLoaded">
            <v-container class="pa-0">
              <v-row no-gutters>
                <v-col cols="12" md="3" class="pa-0">
                  <TrainingSelector
                      :trainings="upcomingTrainings"
                      :selectedTrainingId="selectedTrainingId"
                      v-on:change="selectTraining"
                  ></TrainingSelector>
                </v-col>
                <v-col cols="12" md="9">
                  <v-slide-x-transition v-if="selectedTraining">
                    <div v-if="animationTrigger">
                      <v-card class="ma-1">
                        <v-card-title class="justify-center">
                          {{ selectedTraining.start.format('dddd [den] Do MMMM') }}
                        </v-card-title>
                        <v-card-subtitle class="text-center">{{ selectedTraining.start.fromNow() }}</v-card-subtitle>
                        <v-divider></v-divider>
                        <v-card-text class="text-center pa-0 pa-md-4">
                          <v-container>
                            <v-row>
                              <v-col>
                                Von
                                <v-chip
                                    @click="editTime()" variant="outlined"
                                >
                                  <v-icon
                                      size="small"
                                      color="primary"
                                      start>
                                    mdi-pencil
                                  </v-icon>
                                  {{ selectedTraining.start.format('HH:mm') }}
                                </v-chip>
                                Uhr bis
                                <v-chip
                                    @click="editTime()" variant="outlined"
                                >
                                  <v-icon
                                      size="small"
                                      color="primary"
                                      start>
                                    mdi-pencil
                                  </v-icon>
                                  {{ selectedTraining.end.format('HH:mm') }}
                                </v-chip>
                                Uhr
                              </v-col>
                            </v-row>
                            <v-row>
                              <v-col md="6" v-if="editingLocation">
                                <v-select
                                    :items="locations"
                                    item-title="name"
                                    item-value="id"
                                    v-model="editLocationId"
                                    clearable
                                    required
                                    label="Ort"
                                    prepend-icon="mdi-map-marker-plus"
                                ></v-select>
                                <v-btn @click="saveEditLocation()"
                                       color="primary"
                                       variant="text">
                                  <v-icon>mdi-check</v-icon>
                                </v-btn>
                                <v-btn @click="cancelEditLocation()"
                                       color="primary"
                                       variant="text">
                                  <v-icon>mdi-cancel</v-icon>
                                </v-btn>
                              </v-col>
                              <v-col v-else>
                                <v-chip
                                    @click="editLocation()" variant="outlined"
                                >
                                  <v-icon
                                      size="small"
                                      color="primary"
                                      start>
                                    mdi-pencil
                                  </v-icon>
                                  {{ getLocationNameById(selectedTraining.locationId) }}
                                </v-chip>
                              </v-col>
                            </v-row>
                            <v-row>
                              <v-col>
                                <v-btn
                                    :href="sharelink"
                                    target="_blank"
                                    color="primary">
                                  <v-icon start>mdi-share-variant</v-icon>
                                  Mit Whatsapp teilen
                                </v-btn>
                              </v-col>
                            </v-row>
                          </v-container>
                        </v-card-text>
                      </v-card>
                      <v-card class="ma-1">
                        <v-card-title>
                          <h6>Gruppen</h6>
                        </v-card-title>
                        <v-divider></v-divider>
                        <v-card-text>
                          <GroupChip
                              v-for="(item) in groups"
                              :key="item.id"
                              :group="item"></GroupChip>
                        </v-card-text>
                      </v-card>
                      <v-card class="ma-1">
                        <v-card-text class="pa-0 pa-md-4">
                          <v-container no-gutters>
                            <v-row v-if="editingComment">
                              <v-col
                                  cols="9"
                              >
                                <v-textarea
                                    label="Kommentar"
                                    rows="3"
                                    v-model="editComment" variant="filled"
                                ></v-textarea>
                              </v-col>
                              <v-col
                                  cols="3"
                              >
                                <v-btn
                                    class="ma-2"
                                    @click="saveEditComment()"
                                    color="primary"
                                >
                                  <v-icon>mdi-check</v-icon>
                                </v-btn>
                                <v-btn
                                    class="ma-2"
                                    @click="cancelEditComment()"
                                    color="error"
                                >
                                  <v-icon>mdi-cancel</v-icon>
                                </v-btn>
                              </v-col>
                            </v-row>
                            <v-row v-else>
                              <v-col
                                  cols="12"
                                  sm="12"
                                  md="10"
                              >
                                <v-textarea
                                    @click="startEditComment()"
                                    label="Kommentar"
                                    v-model="selectedTraining.comment"
                                    readonly
                                    rows="3"
                                    append-icon="mdi-pencil" variant="filled"
                                ></v-textarea>
                              </v-col>
                            </v-row>
                          </v-container>
                        </v-card-text>
                      </v-card>
                      <v-card class="ma-1" v-if="branchContentIds.length > 0">
                        <v-card-title>Trainingsinhalte</v-card-title>
                        <v-card-text class="pa-0 pa-md-4">
                          <v-container>
                            <v-row>
                              <v-col
                                  cols="12"
                              >
                                <TrainingContent
                                    :contentIds="branchContentIds"
                                    :initContentIds="selectedTraining.contentIds"
                                    selectable
                                    v-on:change="saveEditTrainingContent"
                                >
                                </TrainingContent>
                              </v-col>
                            </v-row>
                          </v-container>
                        </v-card-text>
                      </v-card>
                      <v-card class="ma-1">
                        <v-card-title>Teilnehmer</v-card-title>
                        <v-card-text class="pa-0 pa-md-4">
                          <v-list v-model:opened="openedGroups">
                          <v-list-group
                              value="participants"
                              prepend-icon="mdi-check"
                          >
                            <template v-slot:activator="{ props }">
                              <v-list-item v-bind="props">
                                <v-list-item-title>{{ participatingUsers.length }} Teilnehmer bis jetzt</v-list-item-title>
                              </v-list-item>
                            </template>
                            <v-list-item
                                v-for="(item) in participatingUsers"
                                :key="item.id"
                            >
                              <tome-list-item-profile-image
                                  :image-path="item.profileImageName">
                              </tome-list-item-profile-image>

                              <v-list-item-title>{{ fullName(item) }}</v-list-item-title>
                              <v-list-item-subtitle>
                                <span class="label">{{
                                    getGroupsByIds(item.groupIds).map(g => g.name).join(', ')
                                  }}</span>
                              </v-list-item-subtitle>
                            </v-list-item>
                          </v-list-group>
                          <v-list-group
                              value="canceled"
                              prepend-icon="mdi-cancel"
                          >
                            <template v-slot:activator="{ props }">
                              <v-list-item v-bind="props">
                                <v-list-item-title>{{ canceledUsers.length }} <span
                                    v-if="canceledUsers.length == 1">Absage</span><span
                                    v-else>Absagen</span></v-list-item-title>
                              </v-list-item>
                            </template>
                            <v-list-item
                                v-for="(item) in canceledUsers"
                                :key="item.id"
                                @click="openCancelReasonDialog(item.id)"
                            >
                              <tome-list-item-profile-image
                                  :image-path="item.profileImageName">
                              </tome-list-item-profile-image>

                              <v-list-item-title>{{ fullName(item) }}</v-list-item-title>
                              <v-list-item-subtitle>
                                <span class="label">{{
                                    getGroupsByIds(item.groupIds).map(g => g.name).join(', ')
                                  }}</span>
                              </v-list-item-subtitle>
                              <v-list-item-subtitle v-if="getCancelReason(item.id)" class="warning--text">Grund:
                                {{ getCancelReason(item.id) }}
                              </v-list-item-subtitle>
                            </v-list-item>
                          </v-list-group>
                          </v-list>
                        </v-card-text>
                      </v-card>
                    </div>
                  </v-slide-x-transition>
                  <v-alert
                      v-else
                      type="info"
                      class="text-small"
                      pa-0
                      ma-0
                      variant="outlined">
                    Keine Trainings für dich verfügbar
                  </v-alert>
                </v-col>
              </v-row>
            </v-container>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    <v-dialog
        v-model="showCancelReasonDialog"
        max-width="800px">
      <v-card>
        <v-toolbar flat>
          <v-btn icon @click="showCancelReasonDialog=false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
          <v-toolbar-title>Grund</v-toolbar-title>
        </v-toolbar>

        <v-card-text class="warning--text">{{ cancelReasonDialogText }}</v-card-text>
      </v-card>
    </v-dialog>
    <v-dialog
        v-model="timeDialogOpened"
        max-width="800px"
        scrollable
        :fullscreen="xs"
        persistent>
      <v-card>
        <v-card-title>
          <v-btn icon @click="timeDialogOpened=false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
          Trainingszeit ändern
          <v-spacer></v-spacer>
          <v-btn color="primary" @click="updateTrainingTime" variant="text">
            <v-icon start>mdi-check</v-icon>
            Speichern
          </v-btn>
        </v-card-title>
        <v-divider></v-divider>
        <v-card-text>
          <v-row
              justify="space-around"
              align="center"
          >
            <v-col style="width: 350px; flex: 0 1 auto;">
              <h2>Von</h2>
              <v-time-picker
                  flat
                  v-model="editStartTime"
                  :max="editEndTime"
                  format="24hr"
              ></v-time-picker>
            </v-col>
            <v-col style="width: 350px; flex: 0 1 auto;">
              <h2>Bis</h2>
              <v-time-picker
                  v-model="editEndTime"
                  :min="editStartTime"
                  format="24hr"
              ></v-time-picker>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useDisplay } from 'vuetify'
import { useAuthStore } from '@/store/auth'
import { useMasterDataStore } from '@/store/masterData'
import { useSnackbarStore } from '@/store/snackbar'
import httpClient from '@/http/api'
import { parseApiDate } from '@/helpers/dayjs'
import Training from '@/models/Training'
import TrainingParticipant from '@/models/TrainingParticipant'
import TrainingContent from '@/components/TrainingContent.vue'
import TomeListItemProfileImage from '@/components/ListItemProfileImage.vue'
import TrainingSelector from '../components/TrainingSelector.vue'
import GroupChip from '../components/GroupChip.vue'
import User, { userFromApi } from '../models/User'

const { xs } = useDisplay()
const authStore = useAuthStore()
const masterData = useMasterDataStore()

const upcomingTrainings = ref<Training[]>([])
const dataLoaded = ref(false)
const selectedTrainingId = ref<any>(null)
const animationTrigger = ref(true)
const users = ref<User[]>([])
const openedGroups = ref<string[]>([])
const showCancelReasonDialog = ref(false)
const cancelReasonDialogText = ref<string | null>(null)
const timeDialogOpened = ref(false)
const editStartTime = ref<string | null>(null)
const editEndTime = ref<string | null>(null)
const editingLocation = ref(false)
const editLocationId = ref<any>(null)
const editingComment = ref(false)
const editComment = ref<string | null>(null)

const loggedInUser = computed(() => authStore.user)
const locations = computed(() => masterData.locations)

function getUpcomingTrainingById(id: any) {
  return upcomingTrainings.value.filter((ut) => ut.id == id)[0]
}

const selectedTraining = computed<any>(() => getUpcomingTrainingById(selectedTrainingId.value))

const branchContentIds = computed((): any[] => {
  //check if multiple branches are in the play
  const groupBranchIds = masterData.getGroupsByIds(selectedTraining.value.groupIds).map((g) => g.branchId)
  if (groupBranchIds.every((v) => v === groupBranchIds[0])) {
    return masterData.getContentIdsByGroupIds(selectedTraining.value.groupIds)
  }
  return []
})

const groups = computed(() => masterData.getGroupsByIds(selectedTraining.value.groupIds))

const participatingUsers = computed(() => {
  if (selectedTraining.value.participants) {
    const filteredUserIds = selectedTraining.value.participants
      .filter((p: TrainingParticipant) => p.attend)
      .map((p: TrainingParticipant) => p.userId)
    if (filteredUserIds.length > 0) {
      return users.value.filter((u) => filteredUserIds.indexOf(u.id) >= 0)
    }
  }
  return []
})

const canceledUsers = computed(() => {
  if (selectedTraining.value.participants) {
    const filteredUserIds = selectedTraining.value.participants
      .filter((p: TrainingParticipant) => !p.attend)
      .map((p: TrainingParticipant) => p.userId)
    if (filteredUserIds.length > 0) {
      return users.value.filter((u) => filteredUserIds.indexOf(u.id) >= 0)
    }
  }
  return []
})

const sharelink = computed(() => {
  const training = selectedTraining.value
  const trainers = masterData.getSimpleTrainersByIds(training.trainerIds)
  let trainerText = ''
  for (let i = 0; i < trainers.length; i++) {
    trainerText += trainers[i].firstName + ' ' + trainers[i].familyName
    if (i < trainers.length - 1) {
      trainerText += ', '
    }
  }
  return (
    'https://wa.me/?text=' +
    encodeURIComponent(
      'Training am ' +
        training.start.format('dddd [den] DD.MM.YYYY') +
        '\r\n' +
        '\r\n' +
        'Uhrzeit: ' +
        training.start.format('HH:mm') +
        ' - ' +
        training.end.format('HH:mm') +
        ' Uhr' +
        '\r\n' +
        'Ort: ' +
        masterData.getLocationNameById(training.locationId) +
        '\r\n' +
        'Trainer: ' +
        trainerText +
        '\r\n' +
        '\r\n' +
        'Anmelden und weitere Informationen unter ' +
        window.location.origin +
        '/#/training/' +
        training.id
    )
  )
})

function getCancelReason(userId: number) {
  const cancelreason = selectedTraining.value.participants.filter((p: TrainingParticipant) => p.userId == userId)[0]
    .cancelreason
  if (cancelreason) {
    return cancelreason
  }
  return null
}

function openCancelReasonDialog(userId: number) {
  const cancelReason = getCancelReason(userId)
  if (cancelReason) {
    cancelReasonDialogText.value = cancelReason
    showCancelReasonDialog.value = true
  }
}

function selectTraining(id: any) {
  animationTrigger.value = false
  selectedTrainingId.value = id
  setTimeout(() => {
    animationTrigger.value = true
  }, 100)
}

async function fetchData() {
  try {
    dataLoaded.value = false
    upcomingTrainings.value = []
    //load data
    const res = await httpClient.get('/trainingprepare/' + loggedInUser.value!.id)
    if (res.data.data && res.data.data.length > 0) {
      //json result to objects
      for (const trObj of res.data.data) {
        const participants: TrainingParticipant[] = []
        for (const partObj of trObj.participants) {
          participants.push(
            new TrainingParticipant(partObj.trainingId, partObj.userId, partObj.attend === 1 ? true : false, partObj.cancelreason)
          )
        }
        upcomingTrainings.value.push(
          new Training(
            trObj.id,
            parseApiDate(trObj.start),
            parseApiDate(trObj.end),
            trObj.locationId,
            trObj.groupIds,
            trObj.contentIds,
            trObj.trainerIds,
            participants,
            trObj.comment,
            false,
            false,
            trObj.automaticAttend === 1 ? true : false
          )
        )
      }
      //select first training
      selectTraining(upcomingTrainings.value[0].id)
    }
    users.value = []
    const userRes = await httpClient.get('/user')
    for (const userObj of userRes.data) {
      users.value.push(
        userFromApi(userObj)
      )
    }
  } catch (error) {
    console.error(error)
  } finally {
    dataLoaded.value = true
  }
}

function editTime() {
  editStartTime.value = selectedTraining.value.start.format('HH:mm')
  editEndTime.value = selectedTraining.value.end.format('HH:mm')
  timeDialogOpened.value = true
}

function editLocation() {
  editingLocation.value = true
  editLocationId.value = selectedTraining.value.locationId
}

function startEditComment() {
  editingComment.value = true
  editComment.value = selectedTraining.value.comment
}

function cancelEditComment() {
  editComment.value = null
  editingComment.value = false
}

async function saveEditComment() {
  const postData = {
    comment: editComment.value
  }
  const { data } = await httpClient.post('/trainingprepare/' + selectedTraining.value.id + '/updatecomment', postData)
  if (data.status == 'ok') {
    selectedTraining.value.comment = editComment.value
    cancelEditComment()
    useSnackbarStore().show('Kommentar aktualisiert', 'success')
  }
}

async function saveEditTrainingContent(selectedContentIds: any[]) {
  const postData = {
    contentIds: selectedContentIds
  }
  const { data } = await httpClient.post('/trainingprepare/' + selectedTraining.value.id + '/updatecontent', postData)
  if (data.status == 'ok') {
    selectedTraining.value.contentIds = selectedContentIds
    useSnackbarStore().show('Trainingsinhalte aktualisiert', 'success')
  }
}

function cancelEditLocation() {
  editLocationId.value = null
  editingLocation.value = false
}

async function saveEditLocation() {
  const postData = {
    locationId: editLocationId.value
  }
  const { data } = await httpClient.post('/trainingprepare/' + selectedTraining.value.id + '/updatelocation', postData)
  if (data.status == 'ok') {
    selectedTraining.value.locationId = editLocationId.value
    cancelEditLocation()
    useSnackbarStore().show('Ort aktualisiert', 'success')
  }
}

async function updateTrainingTime() {
  timeDialogOpened.value = false
  const startDateTime = selectedTraining.value.start.clone().set({
    h: editStartTime.value!.split(':')[0],
    m: editStartTime.value!.split(':')[1]
  })
  const endDateTime = selectedTraining.value.end.clone().set({
    h: editEndTime.value!.split(':')[0],
    m: editEndTime.value!.split(':')[1]
  })
  const postData = {
    start: startDateTime.format(),
    end: endDateTime.format()
  }
  const { data } = await httpClient.post('/trainingprepare/' + selectedTraining.value.id + '/updatetrainingtime', postData)
  if (data.status == 'ok') {
    selectedTraining.value.start = startDateTime
    selectedTraining.value.end = endDateTime
    useSnackbarStore().show('Zeiten aktualisiert', 'success')
  }
}

const fullName = (item: { firstName: string; familyName: string }) => item.firstName + ' ' + item.familyName

function getLocationNameById(locationId: number) {
  return masterData.getLocationNameById(locationId)
}

function getGroupsByIds(groupIds: number[]) {
  return masterData.getGroupsByIds(groupIds)
}

fetchData()
</script>

<style scoped lang="scss">

</style>

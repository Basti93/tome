<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Abgehaltene Trainings</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-btn
                v-if="xs"
                color="primary"
                title="Übungsleiter-Abrechnung exportieren"
                aria-label="Übungsleiter-Abrechnung exportieren"
                size="small"
                v-on:click="showAccountingDialog = true" variant="outlined"
            >
              <v-icon>mdi-file-document-outline</v-icon>
            </v-btn>
            <v-btn
                v-else
                title="Übungsleiter-Abrechnung exportieren"
                color="primary"
                v-on:click="showAccountingDialog = true" variant="outlined"
            >
              <v-icon start>mdi-file-document-outline</v-icon>
              ÜL-Abrechnung
            </v-btn>
          </v-toolbar>
          <v-divider></v-divider>
          <v-card-text>
            <div v-show="dataLoaded" class="tp-training-prepare">
              <v-container class="pa-0">
                <v-row no-gutters>
                  <v-col cols="12" md="3" class="pa-0">
                    <TrainingSelector
                        application="evaluation"
                        :trainings="pastTrainings"
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
                            <v-card-subtitle class="text-center">{{
                                selectedTraining.start.fromNow()
                              }}
                            </v-card-subtitle>
                            <v-divider></v-divider>
                            <v-card-text class="text-center">
                              <v-alert type="success"
                                       class="text-small"
                                       density="compact"
                                       v-if="selectedTraining.evaluated" variant="outlined">
                                Training abgeschlossen
                              </v-alert>
                              <v-btn
                                  class="mt-2 mb-2 mr-2"
                                  elevation="1"
                                  v-show="!selectedTraining.evaluated"
                                  color="primary"
                                  data-test="quick-evaluation"
                                  :to="'/quickEvaluation/' + selectedTraining.id">
                                Schnell abschließen
                              </v-btn>
                              <v-dialog v-model="confirmEvaluationDialog" persistent max-width="290">
                                <template v-slot:activator="{ props }">
                                  <v-btn
                                      class="mt-2 mb-2"
                                      elevation="1"
                                      v-show="!selectedTraining.evaluated"
                                      @click="confirmEvaluationDialog = true"
                                      color="primary"
                                      v-bind="props">
                                    Abschließen
                                  </v-btn>
                                </template>
                                <v-card tile>
                                  <v-toolbar flat>
                                    <v-toolbar-title>Training abschließen?</v-toolbar-title>
                                  </v-toolbar>
                                  <v-divider></v-divider>
                                  <v-card-text>Nachdem das Training abgeschlossen ist, kann es nicht mehr verändert
                                    werden.
                                  </v-card-text>
                                  <v-card-actions>
                                    <v-spacer></v-spacer>
                                    <v-btn color="primary" @click="confirmEvaluationDialog = false" variant="text">Abbrechen
                                    </v-btn>
                                    <v-btn color="primary" test @click="evaluated()">Bestätigen</v-btn>
                                  </v-card-actions>
                                </v-card>
                              </v-dialog>
                            </v-card-text>
                          </v-card>
                          <v-card class="ma-1">
                            <v-card-text>
                              <TrainingNoteField v-model="selectedTraining.note"></TrainingNoteField>
                              <v-btn variant="text" color="primary" data-test="save-note" @click="saveNote">Notiz speichern</v-btn>
                            </v-card-text>
                          </v-card>
                          <v-card class="ma-1">
                            <v-card-title>Trainer</v-card-title>
                            <v-card-subtitle>Diese Zeiten werden für die ÜL-Abrechnung verwendet</v-card-subtitle>
                            <v-card-text>
                              <v-list-item
                                  v-for="(item, index) in selectedTraining.trainers"
                                  :key="index"
                              >
                                <template v-slot:prepend>
                                  <tome-list-item-profile-image
                                      :image-path="item.profileImageName">
                                  </tome-list-item-profile-image>
                                </template>

                                <v-list-item-title>{{ trainerFullName(item.userId) }}</v-list-item-title>
                                <v-list-item-subtitle>Von {{ item.accountingTimeStart.format('HH:mm') }} bis
                                  {{ item.accountingTimeEnd.format('HH:mm') }}
                                </v-list-item-subtitle>

                                <template v-slot:append v-if="!selectedTraining.evaluated">
                                  <v-btn color="primary" @click="editTime(item)" size="small" variant="outlined">
                                    <v-icon>mdi-pencil</v-icon>
                                  </v-btn>
                                </template>

                              </v-list-item>
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
                                    <v-list-item-title>{{ participatingUsers.length }} Teilnehmer</v-list-item-title>
                                  </v-list-item>
                                </template>
                                <v-list-item
                                    v-for="(item) in participatingUsers"
                                    :key="item.id"
                                >

                                  <template v-slot:prepend>
                                    <tome-list-item-profile-image
                                        :image-path="item.profileImageName">
                                    </tome-list-item-profile-image>
                                  </template>

                                  <v-list-item-title>{{ fullName(item) }}<BirthdayHint :user-id="item.id" :date="selectedTraining.start" /></v-list-item-title>
                                  <v-list-item-subtitle>
                                  <span class="label">{{
                                      getGroupsByIds(item.groupIds).map(g => g.name).join(', ')
                                    }}</span>
                                  </v-list-item-subtitle>

                                  <template v-slot:append v-if="!selectedTraining.evaluated">
                                    <v-tooltip text="Zu Abgesagt verschieben">
                                      <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-bind="props"
                                            icon
                                            variant="text"
                                            @click="removeParticipant(item.id)"
                                            size="default"
                                        >
                                          <v-icon size="small">mdi-thumb-down</v-icon>
                                        </v-btn>
                                      </template>
                                    </v-tooltip>
                                    <v-tooltip text="Zu Sonstige verschieben">
                                      <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-bind="props"
                                            icon
                                            variant="text"
                                            @click="removeParticipantToUndecided(item.id)"
                                            size="default"
                                        >
                                          <v-icon size="small">mdi-help-circle-outline</v-icon>
                                        </v-btn>
                                      </template>
                                    </v-tooltip>
                                  </template>

                                </v-list-item>
                              </v-list-group>
                              <v-list-group
                                  value="canceled"
                                  prepend-icon="mdi-cancel"
                              >
                                <template v-slot:activator="{ props }">
                                  <v-list-item v-bind="props">
                                    <v-list-item-title>{{ canceledUsers.length }} <span
                                        v-if="canceledUsers.length == 1">Absage</span><span v-else>Absagen</span></v-list-item-title>
                                  </v-list-item>
                                </template>
                                <v-list-item
                                    v-for="item in canceledUsers"
                                    :key="item.id"
                                >
                                  <template v-slot:prepend>
                                    <tome-list-item-profile-image
                                        :image-path="item.profileImageName">
                                    </tome-list-item-profile-image>
                                  </template>

                                  <v-list-item-title>{{ fullName(item) }}<BirthdayHint :user-id="item.id" :date="selectedTraining.start" /></v-list-item-title>
                                  <v-list-item-subtitle>
                                  <span class="label">{{
                                      getGroupsByIds(item.groupIds).map(g => g.name).join(', ')
                                    }}</span>
                                  </v-list-item-subtitle>
                                  <v-list-item-subtitle v-if="getCancelReason(item.id)" class="warning--text">Grund:
                                    {{ getCancelReason(item.id) }}
                                  </v-list-item-subtitle>
                                  <template v-slot:append v-if="!selectedTraining.evaluated">
                                    <v-tooltip v-if="getCancelReason(item.id)" text="Absagegrund anschauen">
                                      <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-bind="props"
                                            icon
                                            variant="text"
                                            @click="openCancelReasonDialog(item.id)"
                                            size="default">
                                          <v-icon size="small">mdi-magnify</v-icon>
                                        </v-btn>
                                      </template>
                                    </v-tooltip>
                                    <v-tooltip text="Zu Teilnehmern verschieben">
                                      <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-bind="props"
                                            icon
                                            variant="text"
                                            @click="addParticipant(item.id)"
                                            size="default"
                                        >
                                          <v-icon size="small">mdi-thumb-up</v-icon>
                                        </v-btn>
                                      </template>
                                    </v-tooltip>
                                    <v-tooltip text="Zu Sonstige verschieben">
                                      <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-bind="props"
                                            icon
                                            variant="text"
                                            @click="removeParticipantToUndecided(item.id)"
                                            size="default"
                                        >
                                          <v-icon size="small">mdi-help-circle-outline</v-icon>
                                        </v-btn>
                                      </template>
                                    </v-tooltip>
                                  </template>
                                </v-list-item>
                              </v-list-group>
                              <v-list-group
                                  value="undecided"
                                  prepend-icon="mdi-account-multiple"
                              >
                                <template v-slot:activator="{ props }">
                                  <v-list-item v-bind="props">
                                    <v-list-item-title>{{ undecidedUsers.length }} <span>Sonstige</span></v-list-item-title>
                                  </v-list-item>
                                </template>
                                <v-list-item
                                    v-for="(item) in undecidedUsers"
                                    :key="item.id"
                                >
                                  <template v-slot:prepend>
                                    <tome-list-item-profile-image
                                        :image-path="item.profileImageName">
                                    </tome-list-item-profile-image>
                                  </template>

                                  <v-list-item-title>{{ fullName(item) }}<BirthdayHint :user-id="item.id" :date="selectedTraining.start" /></v-list-item-title>
                                  <v-list-item-subtitle>
                                <span class="label">{{
                                    getGroupsByIds(item.groupIds).map(g => g.name).join(', ')
                                  }}</span>
                                  </v-list-item-subtitle>
                                  <template v-slot:append v-if="!selectedTraining.evaluated">
                                    <v-tooltip text="Zu Teilnehmern verschieben">
                                      <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-bind="props"
                                            icon
                                            variant="text"
                                            @click="addParticipant(item.id)"
                                            size="default"
                                        >
                                          <v-icon size="small">mdi-thumb-up</v-icon>
                                        </v-btn>
                                      </template>
                                    </v-tooltip>
                                    <v-tooltip text="Zu Abgesagt verschieben">
                                      <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-bind="props"
                                            icon
                                            variant="text"
                                            @click="addParticipantAsCanceled(item.id)"
                                            size="default"
                                        >
                                          <v-icon size="small">mdi-thumb-down</v-icon>
                                        </v-btn>
                                      </template>
                                    </v-tooltip>
                                  </template>
                                </v-list-item>
                              </v-list-group>
                              </v-list>
                            </v-card-text>
                          </v-card>
                        </div>
                    </v-slide-x-transition>
                    <div v-else>
                      <v-alert
                          type="info"
                          class="text-small"
                          pa-0
                          ma-0
                          variant="outlined">
                        Keine abgehaltenen Trainings für dich verfügbar
                      </v-alert>
                    </div>
                  </v-col>
                </v-row>
              </v-container>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    <v-dialog
        v-model="timeDialogOpened"
        max-width="800px"
        scrollable
        :fullscreen="xs"
        persistent>
      <v-card>
        <v-toolbar flat>
          <v-btn icon aria-label="Schließen" @click="timeDialogOpened=false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
          <v-toolbar-title>Abrechnungszeitraum ändern</v-toolbar-title>
          <v-btn color="primary" @click="updateAccountingTime" variant="text" class="mr-1">
            <v-icon start>mdi-check</v-icon>
            Speichern
          </v-btn>
        </v-toolbar>
        <v-divider></v-divider>
        <v-card-text>
          <v-row
              justify="space-around"
              align="center"
          >
            <v-col style="width: 350px; flex: 0 1 auto;" class="d-flex flex-column align-center">
              <h2>Von</h2>
              <v-time-picker
                  flat
                  v-model="editStartTime"
                  format="24hr"
              ></v-time-picker>
            </v-col>
            <v-col style="width: 350px; flex: 0 1 auto;" class="d-flex flex-column align-center">
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
    <v-dialog
        v-model="showCancelReasonDialog"
        max-width="800px">
      <v-card>
        <v-toolbar flat>
          <v-btn icon aria-label="Schließen" @click="showCancelReasonDialog=false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
          <v-toolbar-title>Grund</v-toolbar-title>
        </v-toolbar>

        <v-card-text class="warning--text">{{ cancelReasonDialogText }}</v-card-text>
      </v-card>
    </v-dialog>
    <TrainingAccountingExportDialog
        :visible="showAccountingDialog"
        v-on:close="showAccountingDialog = false"
    ></TrainingAccountingExportDialog>
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
import BirthdayHint from '@/components/BirthdayHint.vue'
import TrainingNoteField from '@/components/TrainingNoteField.vue'
import TrainingParticipant from '@/models/TrainingParticipant'
import TrainingTrainer from '@/models/TrainingTrainer'
import TrainingEvaluation from '@/models/TrainingEvaluation'
import TrainingAccountingExportDialog from '@/components/TrainingAccountingExportDialog.vue'
import TomeListItemProfileImage from '../components/ListItemProfileImage.vue'
import TrainingSelector from '../components/TrainingSelector.vue'
import User, { userFromApi } from '../models/User'

const { xs } = useDisplay()
const authStore = useAuthStore()
const masterData = useMasterDataStore()

const pastTrainings = ref<TrainingEvaluation[]>([])
const dataLoaded = ref(false)
const selectedTrainingId = ref<any>(null)
const selectedTrainerId = ref<any>(null)
const animationTrigger = ref(true)
const users = ref<User[]>([])
// groups of the participant lists that are expanded (v-list-group `value`s)
const openedGroups = ref(['participants', 'canceled', 'undecided'])
const confirmEvaluationDialog = ref(false)
const editStartTime = ref<string | null>(null)
const editEndTime = ref<string | null>(null)
const timeDialogOpened = ref(false)
const showCancelReasonDialog = ref(false)
const cancelReasonDialogText = ref<string | null>(null)
const showAccountingDialog = ref(false)

const loggedInUser = computed(() => authStore.user)

function getUpcomingTrainingById(id: any) {
  return pastTrainings.value.filter((ut) => ut.id == id)[0]
}

const selectedTraining = computed<any>(() => getUpcomingTrainingById(selectedTrainingId.value))

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

const undecidedUsers = computed(() => {
  const usersOfGroups = users.value.filter((u) => u.groupIds.some((r) => selectedTraining.value.groupIds.includes(r)))
  return usersOfGroups.filter((u) => !canceledUsers.value.concat(participatingUsers.value).includes(u))
})

const getGroupsByIds = (ids: number[]) => masterData.getGroupsByIds(ids)

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
  openedGroups.value = ['participants', 'canceled', 'undecided']
  setTimeout(() => {
    animationTrigger.value = true
  }, 100)
}

async function fetchData() {
  try {
    dataLoaded.value = false
    pastTrainings.value = []
    //load data
    const res = await httpClient.get('/trainingevaluation/' + loggedInUser.value!.id)
    if (res.data.data && res.data.data.length > 0) {
      //json result to objects
      for (const trObj of res.data.data) {
        const participants: TrainingParticipant[] = []
        for (const partObj of trObj.participants) {
          participants.push(
            new TrainingParticipant(partObj.trainingId, partObj.userId, partObj.attend === 1 ? true : false, partObj.cancelreason)
          )
        }
        const trainers: TrainingTrainer[] = []
        const trainingStartTime = parseApiDate(trObj.start)
        const trainingEndTime = parseApiDate(trObj.end)
        for (const partObj of trObj.trainers) {
          let timeStart
          if (partObj.accountingTimeStart != null) {
            timeStart = parseApiDate(partObj.accountingTimeStart)
          } else {
            timeStart = trainingStartTime
          }
          let timeEnd
          if (partObj.accountingTimeEnd != null) {
            timeEnd = parseApiDate(partObj.accountingTimeEnd)
          } else {
            timeEnd = trainingEndTime
          }
          trainers.push(new TrainingTrainer(partObj.trainingId, partObj.userId, timeStart, timeEnd))
        }
        pastTrainings.value.push(
          new TrainingEvaluation(
            trObj.id,
            trainingStartTime,
            trainingEndTime,
            trObj.locationId,
            trObj.groupIds,
            trObj.contentIds,
            trainers,
            participants,
            trObj.comment,
            trObj.prepared === 1 ? true : false,
            trObj.evaluated === 1 ? true : false,
            trObj.note ?? ''
          )
        )
      }
      //select first training
      selectTraining(pastTrainings.value[0].id)
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

async function removeParticipant(userId: number) {
  const { data } = await httpClient.post('/trainingevaluation/' + selectedTraining.value.id + '/removeparticipant/' + userId)
  if (data.status == 'ok') {
    selectedTraining.value.participants.filter((p: TrainingParticipant) => p.userId === userId)[0].attend = false
    useSnackbarStore().show('Benutzer entfernt', 'success')
  }
}

async function addParticipant(userId: number) {
  const { data } = await httpClient.post('/trainingevaluation/' + selectedTraining.value.id + '/addparticipant/' + userId)
  if (data.status == 'ok') {
    if (!selectedTraining.value.participants.map((p: TrainingParticipant) => p.userId).includes(userId)) {
      selectedTraining.value.participants.push(new TrainingParticipant(selectedTraining.value.id, userId, true, null))
    }
    selectedTraining.value.participants.filter((p: TrainingParticipant) => p.userId === userId)[0].attend = true
    useSnackbarStore().show('Benutzer hinzugefügt', 'success')
  }
}

async function addParticipantAsCanceled(userId: number) {
  const { data } = await httpClient.post('/trainingevaluation/' + selectedTraining.value.id + '/addparticipant/' + userId)
  if (data.status == 'ok') {
    if (!selectedTraining.value.participants.map((p: TrainingParticipant) => p.userId).includes(userId)) {
      selectedTraining.value.participants.push(new TrainingParticipant(selectedTraining.value.id, userId, false, null))
    } else {
      selectedTraining.value.participants.filter((p: TrainingParticipant) => p.userId === userId)[0].attend = false
    }
    useSnackbarStore().show('Benutzer zu Absagen hinzugefügt', 'success')
  }
}

async function removeParticipantToUndecided(userId: number) {
  const { data } = await httpClient.post('/trainingevaluation/' + selectedTraining.value.id + '/removeparticipant/' + userId)
  if (data.status == 'ok') {
    const index = selectedTraining.value.participants.findIndex((p: TrainingParticipant) => p.userId === userId)
    if (index >= 0) {
      selectedTraining.value.participants.splice(index, 1)
    }
    useSnackbarStore().show('Benutzer entfernt', 'success')
  }
}

async function saveNote() {
  const training = selectedTraining.value
  try {
    await httpClient.post('/trainingevaluation/' + training.id + '/note', { note: (training.note ?? '').trim() })
    training.note = (training.note ?? '').trim()
    useSnackbarStore().show('Notiz gespeichert', 'success')
  } catch {
    useSnackbarStore().show('Die Notiz konnte nicht gespeichert werden.', 'error')
  }
}

async function evaluated() {
  confirmEvaluationDialog.value = false
  // the note typed before completing is saved with it
  if ((selectedTraining.value.note ?? '').trim()) await saveNote()
  const { data } = await httpClient.post('/trainingevaluation/' + selectedTraining.value.id + '/evaluated')
  if (data.status == 'ok') {
    selectedTraining.value.evaluated = true
    useSnackbarStore().show('Training abgeschlossen', 'success')
  }
}

function editTime(item: TrainingTrainer) {
  selectedTrainerId.value = item.userId
  editStartTime.value = item.accountingTimeStart.format('HH:mm')
  editEndTime.value = item.accountingTimeEnd.format('HH:mm')
  timeDialogOpened.value = true
}

async function updateAccountingTime() {
  timeDialogOpened.value = false
  const selectedTrainer = selectedTraining.value.trainers.find((t: TrainingTrainer) => t.userId === selectedTrainerId.value)
  const startDateTime = selectedTrainer.accountingTimeStart.clone().set({
    h: editStartTime.value!.split(':')[0],
    m: editStartTime.value!.split(':')[1]
  })
  const endDateTime = selectedTrainer.accountingTimeEnd.clone().set({
    h: editEndTime.value!.split(':')[0],
    m: editEndTime.value!.split(':')[1]
  })
  const postData = {
    trainerId: selectedTrainerId.value,
    start: startDateTime.format(),
    end: endDateTime.format()
  }
  const { data } = await httpClient.post('/trainingevaluation/' + selectedTraining.value.id + '/updateaccountingtime', postData)
  if (data.status == 'ok') {
    selectedTrainer.accountingTimeStart = startDateTime
    selectedTrainer.accountingTimeEnd = endDateTime
    useSnackbarStore().show('Zeiten aktualisiert', 'success')
  }
}

const fullName = (item: { firstName: string; familyName: string }) => item.firstName + ' ' + item.familyName

function trainerFullName(id: number) {
  return fullName(masterData.getSimpleTrainerById(id))
}

fetchData()
</script>

<style scoped lang="scss">
.tp-training-prepare {

  &__navigation-card {
    flex-grow: 1;

    .v-card__title {
      display: block;
    }

    &--desktop {
      margin: 2rem;
    }

    &--mobile {
      margin: 0.5rem;
    }

    &--active {
      filter: brightness(80%);
    }

    &--evaluated {
      background-color: #60cc69 !important;
    }

  }

  &__card {
    margin-bottom: 2rem;
  }

}
</style>

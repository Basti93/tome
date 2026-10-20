<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar >
            <v-toolbar-title>Trainingsserien</v-toolbar-title>
            <v-select
                class="mr-2 series-branch-filter"
                :items="filterBranches"
                v-model="filterBranchId"
                @update:model-value="filterChanged()"
                item-title="name"
                item-value="id"
                flat
                hide-details
                density="compact"
                label="Sparte auswählen"
            >
            </v-select>
            <v-spacer></v-spacer>
            <template v-slot:extension>
              <v-btn
                  title="Neue Trainingsserie anlegen"
                  icon
                  class="ma-2"
                  elevation="1"
                  color="primary"
                  @click="openCreateDialog()">
                <v-icon>mdi-plus</v-icon>

              </v-btn>
            </template>
            <v-dialog
                v-model="showCreateDialog"
                fullscreen>
              <v-card>
                <v-toolbar flat>
                  <v-btn icon @click="showCreateDialog = false">
                    <v-icon>mdi-close</v-icon>
                  </v-btn>
                  <v-toolbar-title>{{editDialogTitle}}</v-toolbar-title>
                  <v-spacer></v-spacer>
                  <v-toolbar-items>
                    <v-btn color="primary" @click="save()" variant="text">
                      <v-icon start>mdi-check</v-icon>
                      Speichern
                    </v-btn>
                  </v-toolbar-items>
                </v-toolbar>
                <v-divider></v-divider>
                <v-card-text>
                  <v-tabs
                      v-model="activeTab"
                      icons-and-text
                  >

                    <v-tab>
                      Serie
                      <v-icon>mdi-calendar-month</v-icon>
                    </v-tab>

                    <v-tab>
                      Trainingsdaten
                      <v-icon>mdi-account-multiple</v-icon>
                    </v-tab>
                  </v-tabs>
                  <v-window v-model="activeTab">
                    <v-window-item>
                      <v-container>
                        <v-row>
                          <v-col cols="12">
                            <v-alert
                                type="info"
                                class="text-small"
                                pa-0
                                ma-0
                                variant="outlined">
                              Trainings von aktiven Trainingsserien werden immer eine Woche im Voraus erstellt.
                            </v-alert>
                          </v-col>
                          <v-col cols="12" md="9" offset-md="1">
                            <v-label>Wochentage</v-label>
                            <WeekdaysComponent
                                :weekdays="editedTrainingSeries.weekdays"
                                v-on:change="weekdaysChanged"
                            ></WeekdaysComponent>
                          </v-col>
                          <v-col cols="12" md="3" offset-md="1">
                            <v-text-field
                                v-model="editedTrainingSeries.deferUntil"
                                type="date"
                                clearable
                                label="Aussetzen bis"
                                prepend-icon="mdi-calendar"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                      </v-container>
                    </v-window-item>
                    <v-window-item>
                      <v-container>
                        <v-row>
                          <v-col>
                            <EditTrainingBase
                                hide-date
                                hide-contents
                                v-on:change="seriesChanged"
                                :id="editedTrainingSeries.id"
                                :start="editedTrainingSeries.startTime"
                                :end="editedTrainingSeries.endTime"
                                :trainerIds="editedTrainingSeries.trainerIds"
                                :groupIds="editedTrainingSeries.groupIds"
                                :groups="groups"
                                :trainers="trainers"
                                :locationId="editedTrainingSeries.locationId"
                                :comment="editedTrainingSeries.comment"
                                :branchId="filterBranchId"
                                :automatic-attend="editedTrainingSeries.automaticAttend"
                            ></EditTrainingBase>
                          </v-col>
                        </v-row>
                      </v-container>
                    </v-window-item>
                  </v-window>
                </v-card-text>
              </v-card>
            </v-dialog>
            <v-spacer></v-spacer>
          </v-toolbar>
          <v-divider></v-divider>
          <v-card-text class="pa-0 pa-md-4">
            <v-card>
              <v-card-text class="pa-0 pa-md-4">
                <v-container>
                  <v-row>
                    <v-col>
                      <v-data-table
                          :headers="headers"
                          :items="trainingSeriesList"
                          :loading="loading"
                          hide-default-footer>
                        <template v-slot:[`item.id`]="{ item }">
                          {{ item.id }}
                        </template>
                        <template v-slot:[`item.weekdays`]="{ item }">
                          {{ dayArrayToString(item.weekdays) }}
                        </template>
                        <template v-slot:[`item.startTime`]="{ item }">
                          {{ item.startTime }}
                        </template>
                        <template v-slot:[`item.endTime`]="{ item }">
                          {{ item.endTime }}
                        </template>
                        <template v-slot:[`item.trainerIds`]="{ item }">
                          <v-chip v-for="(trainer) in getSimpleTrainersByIds(item.trainerIds)"
                                  :key="trainer.id"
                                  size="small"
                                  class="ma-1" variant="outlined">
                            {{ trainer.firstName }}
                          </v-chip>
                        </template>
                        <template v-slot:[`item.groupIds`]="{ item }">
                          <v-chip v-for="(group) in getGroupsByIds(item.groupIds)"
                                  :key="group.id"
                                  size="small"
                                  class="ma-1" variant="outlined">
                            {{ group.name }}
                          </v-chip>
                        </template>
                        <template v-slot:[`item.deferUntil`]="{ item }">
                            <span v-if="checkDeferUntilIsActive(item)">
                                {{ parseApiDate(item.deferUntil).format('DD.MM.YYYY') }}
                            </span>
                          <span v-else>Aktiv</span>
                        </template>
                        <template v-slot:[`item.action`]="{ item }">
                            <v-icon
                                class="mr-2"
                                @click="editItem(item)"
                                color="success">mdi-pencil</v-icon>
                            <v-icon
                                @click="confirmAndDelete(item)"
                                color="error">mdi-trash-can</v-icon>
                        </template>
                      </v-data-table>
                    </v-col>
                  </v-row>
                </v-container>
              </v-card-text>
            </v-card>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    <ConfirmDialog
        :show="showConfirmDialog"
        action-text="Löschen"
        v-on:confirmed="deleteItem()"
        v-on:canceled="showConfirmDialog = false">
    </ConfirmDialog>
  </v-container>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useAuthStore } from '@/store/auth'
import { useMasterDataStore } from '@/store/masterData'
import { useSnackbarStore } from '@/store/snackbar'
import httpClient from '@/http/api'
import dayjs, { parseApiDate } from '@/helpers/dayjs'
import EditTrainingBase from '../components/EditTrainingBase.vue'
import TrainingSeries from '@/models/TrainingSeries'
import { dayArrayToString } from '../helpers/date-helpers'
import WeekdaysComponent from '../components/WeekdaysComponent.vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'

const authStore = useAuthStore()
const masterData = useMasterDataStore()

const activeTab = ref(0)
const showConfirmDialog = ref(false)
const itemToDelete = ref<any>(null)
const filterBranches = ref<any[]>([{ id: -1, name: 'Alle' }])
const filterBranchId = ref<number | null>(null)
const trainers = ref<any[]>([])
const trainingSeriesList = ref<TrainingSeries[]>([])
const showCreateDialog = ref(false)
const loading = ref(false)
const editDialogTitle = ref('')
const headers = [
  { title: 'Id', value: 'id', sortable: false },
  { title: 'Wochentage', value: 'weekdays', sortable: false },
  { title: 'Start', value: 'startTime', sortable: false },
  { title: 'Ende', value: 'endTime', sortable: false },
  { title: 'Trainer', value: 'trainerIds', sortable: false },
  { title: 'Gruppen', value: 'groupIds', sortable: false },
  { title: 'Ausgesetzt bis', value: 'deferUntil', sortable: false },
  { title: '', value: 'action', sortable: false }
]
const defaultTrainingSeries = new TrainingSeries(null, '09:00', '12:00', null, [], [], [], null, [], null, true)
const editedTrainingSeries = ref<any>(
  new TrainingSeries(null, '09:00', '12:00', null, [], [], [], null, [], null, true)
)

const groups = computed(() => masterData.groups)

const getGroupsByIds = (ids: number[]) => masterData.getGroupsByIds(ids)
const getSimpleTrainersByIds = (ids: number[]) => masterData.getSimpleTrainersByIds(ids)

function openCreateDialog() {
  editDialogTitle.value = 'Serie anlegen'
  editedTrainingSeries.value = { ...defaultTrainingSeries }
  showCreateDialog.value = true
}

function seriesChanged(item: any) {
  editedTrainingSeries.value.id = item.id
  editedTrainingSeries.value.startTime = item.start
  editedTrainingSeries.value.endTime = item.end
  editedTrainingSeries.value.locationId = item.locationId
  editedTrainingSeries.value.groupIds = item.groupIds
  editedTrainingSeries.value.trainerIds = item.trainerIds
  editedTrainingSeries.value.comment = item.comment
  editedTrainingSeries.value.automaticAttend = item.automaticAttend
}

async function fetchData() {
  try {
    loading.value = true
    trainingSeriesList.value = []
    let seriesUrl = '/trainingSeries'
    // Don't set filter with the general item 'Allgemein'
    if (filterBranchId.value! > 0) {
      seriesUrl += '?groupIds=' + masterData.getGroupsByBranchId(filterBranchId.value).map((g) => g.id)
    }
    const response = await httpClient.get(seriesUrl)
    for (const responseObject of response.data.data) {
      trainingSeriesList.value.push(TrainingSeries.from(responseObject))
    }

    const trainerRes = await httpClient.get('/user/trainer')
    trainers.value = trainerRes.data
  } finally {
    loading.value = false
  }
}

function editItem(item: any) {
  editDialogTitle.value = 'Serie bearbeiten'
  editedTrainingSeries.value = { ...item }
  const momDeferUntil = parseApiDate(item.deferUntil)
  if (momDeferUntil.isValid()) {
    editedTrainingSeries.value.deferUntil = momDeferUntil.format('YYYY-MM-DD')
  }
  showCreateDialog.value = true
}

function confirmAndDelete(item: any) {
  itemToDelete.value = item
  showConfirmDialog.value = true
}

async function deleteItem() {
  showConfirmDialog.value = false
  const response = await httpClient.delete('/trainingSeries/' + itemToDelete.value.id)
  if (response.data.status === 'ok') {
    useSnackbarStore().show('Serie erfolgreich gelöscht', 'success')
    trainingSeriesList.value.splice(trainingSeriesList.value.indexOf(itemToDelete.value), 1)
  } else {
    useSnackbarStore().show('Serie konnte nicht gelöscht werden', 'error')
  }
}

async function save() {
  const series = editedTrainingSeries.value
  const momDeferUntil = parseApiDate(series.deferUntil)
  const postData: Record<string, any> = {
    startTime: series.startTime,
    endTime: series.endTime,
    weekdays: series.weekdays,
    comment: series.comment,
    locationId: series.locationId,
    trainerIds: series.trainerIds,
    groupIds: series.groupIds,
    // the series dialog hides the content picker; send the stored ones, otherwise sync() would drop them
    contentIds: series.contentIds ?? [],
    deferUntil: momDeferUntil.isValid() ? momDeferUntil.format() : null,
    automaticAttend: series.automaticAttend
  }
  let url = '/trainingSeries'
  const isUpdate = !!series.id
  try {
    let res
    if (isUpdate) {
      url += '/' + series.id
      postData.id = series.id
      res = await httpClient.put(url, postData)
    } else {
      res = await httpClient.post(url, postData)
    }

    if (res.data.status == 'ok') {
      showCreateDialog.value = false
      fetchData()
      useSnackbarStore().show(isUpdate ? 'Serie erfolgreich gespeichert' : 'Serie erfolgreich erstellt', 'success')
    }
  } catch (error: any) {
    const errors = error?.response?.data?.errors
    const detail = errors ? ' (' + Object.keys(errors).join(', ') + ')' : ''
    useSnackbarStore().show('Serie konnte nicht gespeichert werden' + detail, 'error')
  }
}

function filterChanged() {
  fetchData()
}

function checkDeferUntilIsActive(item: TrainingSeries) {
  const momDeferUntil = dayjs(item.deferUntil)
  return momDeferUntil.isValid() && momDeferUntil >= dayjs().startOf('day')
}

function weekdaysChanged(weekdays: number[]) {
  editedTrainingSeries.value.weekdays = weekdays
}

const loggedInUser = authStore.user
if (loggedInUser && loggedInUser.trainerBranchIds && loggedInUser.trainerBranchIds.length > 0) {
  filterBranchId.value = masterData.getBranchById(loggedInUser.trainerBranchIds[0]).id
}
filterBranches.value = filterBranches.value.concat(masterData.branches)
fetchData()
</script>

<style scoped>
.series-branch-filter {
  max-width: 220px;
}
</style>

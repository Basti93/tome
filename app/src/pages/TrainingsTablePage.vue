<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar>
            <v-toolbar-title>Trainings</v-toolbar-title>
            <v-select
                class="mr-2 trainings-branch-filter"
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
            <template v-slot:extension>
              <v-btn
                  title="Neues Training anlegen"
                  icon
                  class="ma-2"
                  elevation="1"
                  color="primary"
                  @click="create()">
                <v-icon>mdi-plus</v-icon>
              </v-btn>
            </template>
          </v-toolbar>
          <v-divider></v-divider>
          <v-card-text class="pa-0 pa-md-4">
            <v-card>
              <v-card-text class="pa-0 pa-md-4">
                <v-container>
                  <v-row no-gutters>
                    <v-col>
                      <TrainingCalendar
                          v-if="activeView == 1">
                      </TrainingCalendar>
                      <v-data-table-server
                          v-if="activeView == 0"
                          :headers="headers"
                          :items="trainings"
                          :loading="loading"
                          :items-length="totalItems"
                          v-model:items-per-page="itemsPerPage"
                          v-model:page="page"
                          v-model:sort-by="sortBy"
                      >
                        <template v-slot:[`item.date`]="{ item }">
                          {{ parseApiDate(item.start).format('dd, DD.MM.YYYY') }}
                        </template>
                        <template v-slot:[`item.start`]="{ item }">
                          {{ parseApiDate(item.start).format('HH:mm') }}
                        </template>
                        <template v-slot:[`item.end`]="{ item }">
                          {{ parseApiDate(item.end).format('HH:mm') }}
                        </template>
                        <template v-slot:[`item.locationId`]="{ item }">
                          {{ getLocationNameById(item.locationId) }}
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
                        <template v-slot:[`item.action`]="{ item }">
                            <v-icon
                                class="mr-2"
                                v-if="loggedInUser.isAdmin || loggedInUser.isTrainer"
                                @click="editItem(item)"
                                color="success"
                            >mdi-pencil</v-icon>
                            <v-icon
                                color="error"
                                v-if="loggedInUser.isAdmin || loggedInUser.isTrainer"
                                @click="confirmAndDelete(item)">mdi-trash-can</v-icon>
                        </template>
                        <template v-slot:no-data>
                          <v-btn color="primary" :disabled="loading" @click="loadData()">
                            <v-icon start>mdi-refresh</v-icon>
                            Keine Daten gefunden
                          </v-btn>
                        </template>
                      </v-data-table-server>
                    </v-col>
                  </v-row>
                </v-container>
              </v-card-text>
            </v-card>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    <v-dialog
        v-model="editDialog"
        hide-overlay
        transition="dialog-bottom-transition"
        persistent
        scrollable
        fullscreen>
      <v-card tile>
        <v-toolbar flat>
          <v-btn icon @click="close">
            <v-icon>mdi-close</v-icon>
          </v-btn>
          <v-toolbar-title>{{ editDialogTitle }}</v-toolbar-title>
          <v-spacer></v-spacer>
          <v-toolbar-items>
            <v-btn color="primary" @click="save" variant="text">
              <v-icon start>mdi-check</v-icon>
              Speichern
            </v-btn>
          </v-toolbar-items>
        </v-toolbar>
        <v-divider></v-divider>
        <v-card-text>
          <EditTrainingBase
              hide-contents
              :date="editedItem.date"
              :start="editedItem.start"
              :end="editedItem.end"
              :locationId="editedItem.locationId"
              :trainerIds="editedItem.trainerIds"
              :groupIds="editedItem.groupIds"
              :contentIds="editedItem.contentIds"
              :comment="editedItem.comment"
              :trainers="trainers"
              :groups="groups"
              :automaticAttend="editedItem.automaticAttend"
              v-on:change="trainingBaseChanged"
          ></EditTrainingBase>
        </v-card-text>
      </v-card>
    </v-dialog>
    <ConfirmDialog
        :show="showConfirmDialog"
        action-text="Löschen"
        v-on:confirmed="deleteItem()"
        v-on:canceled="showConfirmDialog = false">
    </ConfirmDialog>
    <!--<v-bottom-navigation
             v-model="activeView"
             hide-on-scroll
             scroll-threshold="50"
             fixed
     >
       <v-btn>
         <span>Listenansicht</span>
         <v-icon>mdi-format-list-bulleted</v-icon>
       </v-btn>
       <v-btn>
         <span>Kalendaransicht</span>
         <v-icon>mdi-calendar-month</v-icon>
       </v-btn>
     </v-bottom-navigation>-->
  </v-container>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useAuthStore } from '@/store/auth'
import { useMasterDataStore } from '@/store/masterData'
import { useSnackbarStore } from '@/store/snackbar'
import httpClient from '@/http/api'
import { parseApiDate } from '@/helpers/dayjs'
import TrainingCalendar from '../components/TrainingCalendar.vue'
import EditTrainingBase from '../components/EditTrainingBase.vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import Training from '../models/Training'

const authStore = useAuthStore()
const masterData = useMasterDataStore()

const showConfirmDialog = ref(false)
const itemToDelete = ref<any>(null)
const filterBranchId = ref<number | null>(null)
const filterBranches = ref<any[]>([{ id: -1, name: 'Alle' }])
const editDialog = ref(false)
const editDialogTitle = ref('')
const loading = ref(false)
const total = ref<number | null>(null)
const totalItems = ref(0)
const page = ref(1)
const itemsPerPage = ref(10)
const sortBy = ref<any[]>([])
const headers = [
  { title: 'Datum', value: 'date', sortable: true },
  { title: 'Von', value: 'start', sortable: false },
  { title: 'Bis', value: 'end', sortable: false },
  { title: 'Ort', value: 'locationId', sortable: true },
  { title: 'Trainer', value: 'trainerIds', sortable: false },
  { title: 'Gruppen', value: 'groupIds', sortable: false },
  { title: '', value: 'action', sortable: false }
]
const trainings = ref<any[]>([])
const trainers = ref<any[]>([])
const editedId = ref<number | null>(null)
const editedItem = ref<any>({
  id: null,
  date: null,
  start: null,
  end: null,
  locationId: null,
  trainerIds: [],
  groupIds: [],
  contentIds: [],
  automaticAttend: true,
  comment: null
})
const defaultItem = {
  id: null,
  date: new Date().toISOString().substr(0, 10),
  start: '09:00',
  end: '12:00',
  locationId: null,
  trainerIds: [],
  groupIds: [],
  contentIds: [],
  automaticAttend: true,
  comment: null
}
const activeView = ref(0)

const loggedInUser = computed(() => authStore.user)
const groups = computed(() => masterData.groups)

function loadData() {
  loading.value = true
  trainings.value = []
  let url = '/training'
  // get by sort option
  if (sortBy.value && sortBy.value.length > 0) {
    const sortColumn = sortBy.value[0]
    const direction = sortColumn.order === 'desc' ? 'desc' : 'asc'
    url += '/sort?direction=' + direction + '&sortBy=' + sortColumn.key + '&page=' + page.value + '&per_page=' + itemsPerPage.value
  } else {
    url += '?page=' + page.value + '&per_page=' + itemsPerPage.value
  }
  // Don't set filter with the general item 'Allgemein'
  if (filterBranchId.value! > 0) {
    url += '&groupIds=' + masterData.getGroupsByBranchId(filterBranchId.value).map((g) => g.id)
  }
  const p1 = httpClient.get(url).then((res) => {
    for (const jsonObj of res.data.data) {
      trainings.value.push(
        new Training(
          jsonObj.id,
          jsonObj.start,
          jsonObj.end,
          jsonObj.locationId,
          jsonObj.groupIds,
          jsonObj.contentIds,
          jsonObj.trainerIds,
          jsonObj.participants,
          jsonObj.comment,
          jsonObj.prepared === 1 ? true : false,
          jsonObj.evaluated === 1 ? true : false,
          jsonObj.automaticAttend === 1 ? true : false
        )
      )
    }
    page.value = res.data.meta.currentPage
    totalItems.value = res.data.meta.total
    total.value = res.data.meta.total
  })

  const p2 = httpClient.get('/user/trainer').then((res) => {
    trainers.value = res.data
  })

  Promise.all([p1, p2]).then(() => {
    loading.value = false
  })
}

function filterChanged() {
  loadData()
}

function trainingBaseChanged(item: any) {
  Object.assign(editedItem.value, item)
}

function confirmAndDelete(item: any) {
  itemToDelete.value = item
  showConfirmDialog.value = true
}

async function deleteItem() {
  showConfirmDialog.value = false
  const { data } = await httpClient.patch('/training/' + itemToDelete.value.id + '/deleteinfuture')
  if (data.status == 'ok') {
    useSnackbarStore().show('Training erfolgreich gelöscht', 'success')
    loadData()
  } else {
    useSnackbarStore().show('Training konnte nicht gelöscht werden', 'error')
  }
}

function editItem(item: any) {
  editDialogTitle.value = 'Training bearbeiten'
  editedId.value = item.id
  Object.assign(editedItem.value, item)
  editedItem.value.date = parseApiDate(item.start).format('YYYY-MM-DD')
  editedItem.value.start = parseApiDate(item.start).format('HH:mm')
  editedItem.value.end = parseApiDate(item.end).format('HH:mm')
  editDialog.value = true
}

function create() {
  editedId.value = null
  editedItem.value = { ...defaultItem }
  editDialogTitle.value = 'Training erstellen'
  editDialog.value = true
}

function close() {
  editDialog.value = false
  setTimeout(() => {
    editedItem.value = { ...defaultItem }
  }, 300)
}

function save() {
  const postData = {
    id: null as number | null,
    start: parseApiDate(editedItem.value.date + 'T' + editedItem.value.start).format(),
    end: parseApiDate(editedItem.value.date + 'T' + editedItem.value.end).format(),
    locationId: editedItem.value.locationId,
    groupIds: editedItem.value.groupIds,
    trainerIds: editedItem.value.trainerIds,
    contentIds: editedItem.value.contentIds,
    comment: editedItem.value.comment,
    automaticAttend: editedItem.value.automaticAttend
  }
  if (editedId.value) {
    postData.id = editedId.value
    const id = editedId.value
    httpClient
      .put('/training/' + id, postData)
      .then((res) => {
        if (!res.data.error) {
          close()
          useSnackbarStore().show('Training gespeichert', 'success')
          httpClient.get('/training/' + id).then(({ data }) => {
            const index = trainings.value.findIndex((t) => t && t.id == id)
            trainings.value.splice(index, 1, data.data)
          })
        } else {
          console.error(res.data.error)
          useSnackbarStore().show('Training konnte nicht gespeichert werden', 'error')
        }
      })
      .catch((err) => {
        console.log(err)
        useSnackbarStore().show('Training konnte nicht gespeichert werden', 'error')
      })
  } else {
    httpClient
      .post('/training', postData)
      .then((res) => {
        if (!res.data.error) {
          close()
          useSnackbarStore().show('Training gespeichert', 'success')
          loadData()
        } else {
          console.error(res.data.error)
          useSnackbarStore().show('Training konnte nicht gespeichert werden', 'error')
        }
      })
      .catch((err) => {
        console.log(err)
        useSnackbarStore().show('Training konnte nicht gespeichert werden', 'error')
      })
  }
}

function getLocationNameById(locationId: number) {
  return masterData.getLocationNameById(locationId)
}

function getSimpleTrainersByIds(trainerIds: number[]) {
  return masterData.getSimpleTrainersByIds(trainerIds)
}

function getGroupsByIds(groupIds: number[]) {
  return masterData.getGroupsByIds(groupIds)
}

for (const source of [page, sortBy]) {
  watch(
    source,
    () => {
      if (!loading.value) {
        loadData()
      }
    },
    { deep: true }
  )
}

const user = authStore.user
if (user && user.trainerBranchIds && user.trainerBranchIds.length > 0) {
  filterBranchId.value = masterData.getBranchById(user.trainerBranchIds[0]).id
}
filterBranches.value = filterBranches.value.concat(masterData.branches)
loadData()
</script>

<style scoped>
.trainings-branch-filter {
  max-width: 220px;
}
</style>

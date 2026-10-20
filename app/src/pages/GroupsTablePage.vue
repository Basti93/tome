<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Gruppen</v-toolbar-title>
            <template v-slot:extension>
              <v-dialog
                  v-model="showDialog"
                  :fullscreen="xs"
                  persistent
                  max-width="1000px"
              >
                <template v-slot:activator="{ props }">
                  <v-btn
                      title="Neue Gruppe erstellen"
                      icon
                      class="ma-2"
                      elevation="1"
                      color="primary"
                      v-bind="props"
                      @click="createItem()">
                    <v-icon>mdi-plus</v-icon>
                  </v-btn>
                </template>

                <v-card>
                  <v-toolbar flat>
                    <v-btn icon v-on:click="closeDialog()">
                      <v-icon>mdi-close</v-icon>
                    </v-btn>
                    <v-toolbar-title>{{ titleDialog }}</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-toolbar-items>
                      <v-btn color="primary" v-on:click="save()" variant="text">
                        <v-icon start>mdi-check</v-icon>
                        Speichern
                      </v-btn>
                    </v-toolbar-items>
                  </v-toolbar>
                  <v-divider class="pb-2"></v-divider>
                  <v-card-text>
                    <v-form
                        ref="form"
                        v-model="validDialog">
                      <v-container>
                        <v-row>
                          <v-col cols="12">

                            <v-text-field
                                v-model="editedItem.name"
                                label="Name"
                                required
                                :rules="[v => !!v || 'Wird benötigt']"
                                prepend-icon="groups"
                            ></v-text-field>
                          </v-col>
                          <v-col cols="12">
                            <v-select
                                :items="branches"
                                required
                                label="Sparte"
                                :rules="[v => !!v || 'Wird benötigt']"
                                v-model="editedItem.branchId"
                                :item-title="buildFullBranchname"
                                prepend-icon="bubble_chart"
                                item-value="id"
                            >

                            </v-select>
                          </v-col>
                          <v-col cols="12" md="6">
                            <v-autocomplete
                                :items="filteredUsers"
                                :item-title="fullName"
                                item-value="id"
                                prepend-icon="group"
                                v-model="selectedUserIds"
                                multiple
                                clearable
                                label="Mitglied auswählen"
                            >
                              <template v-slot:selection="{ item, index }">
                                <v-chip v-if="index < 3">
                                  <span>{{ item.firstName }}</span>
                                </v-chip>
                                <span
                                    v-if="index === 3"
                                    class="grey--text caption"
                                >
                                  (+{{ selectedUserIds.length - 3 }} weitere)
                                </span>
                              </template>
                            </v-autocomplete>
                            <v-btn
                                color="primary"
                                elevation="1"
                                v-on:click="addSelectedUsersToGroup()">
                              <v-icon start>mdi-plus</v-icon>
                              Hinzufügen
                            </v-btn>
                          </v-col>
                          <v-col cols="12" md="6">
                            <v-list density="compact">
                              <v-list-item-title>{{ editedItem.userIds.length }} Mitglieder</v-list-item-title>
                              <v-list-item
                                  v-for="item in editedUsers"
                                  :key="item.id"
                              >
                                <template v-slot:prepend>
                                  <tome-list-item-profile-image
                                      :image-path="item.profileImageName"
                                  ></tome-list-item-profile-image>
                                </template>
                                {{ fullName(item) }}
                                <template v-slot:append>
                                  <v-btn
                                      icon
                                      size="x-small"
                                      v-on:click="removeUserFromGroup(item.id)">
                                    <v-icon>mdi-cancel</v-icon>
                                  </v-btn>
                                </template>
                              </v-list-item>
                            </v-list>
                          </v-col>
                        </v-row>
                      </v-container>
                    </v-form>
                  </v-card-text>
                </v-card>
              </v-dialog>
            </template>
          </v-toolbar>
          <v-divider></v-divider>
          <v-card-text class="pa-0 pa-md-4">
            <v-card>
              <v-card-text class="pa-0 pa-md-4">
                <v-container>
                  <v-row>
                    <v-col>
                      <v-data-table-server
                          :headers="headers"
                          :items="groups"
                          item-key="id"
                          :loading="loading"
                          :items-length="total"
                          :items-per-page-options="rowsPerPageItems"
                          v-model:itemsPerPage="itemsPerPage"
                          v-model:page="page"
                      >
                        <template v-slot:[`item.name`]="{ item }">
                          {{ item.name }}
                        </template>
                        <template v-slot:[`item.branchId`]="{ item }">
                          {{ item.branch.name }}
                        </template>
                        <template v-slot:[`item.userCount`]="{ item }">
                          {{ item.userIds.length }}
                        </template>
                        <template v-slot:[`item.action`]="{ item }">
                            <v-icon
                                class="mr-2"
                                v-on:click="editItem(item)"
                                color="success">mdi-pencil</v-icon>
                            <v-icon
                                v-on:click="confirmAndDelete(item)"
                                color="error">mdi-trash-can</v-icon>
                        </template>
                        <template v-slot:no-data>
                          <v-container>
                            <v-row>
                              <v-col>
                                <v-btn color="error" :disabled="loading" v-on:click="loadData()">
                                  <v-icon start>mdi-refresh</v-icon>
                                  Keine Daten gefunden
                                </v-btn>
                              </v-col>
                            </v-row>
                          </v-container>
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
import { useDisplay } from 'vuetify'
import Group from '../models/Group'
import Branch from '../models/Branch'
import User, { userFromApi } from '../models/User'
import TomeListItemProfileImage from '../components/ListItemProfileImage.vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import { useMasterDataStore } from '@/store/masterData'
import { useSnackbarStore } from '@/store/snackbar'
import httpClient from '@/http/api'
import { parseApiDate } from '@/helpers/dayjs'

const { xs } = useDisplay()
const masterData = useMasterDataStore()

const form = ref<any>(null)
const loading = ref(false)
const groups = ref<Group[]>([])
const users = ref<User[]>([])
const filteredUsers = ref<User[]>([])
const selectedUserIds = ref<any[]>([])
const total = ref<number | null>(null)
const rowsPerPageItems = [5, 10, 20, 50, 100]
const page = ref(1)
const itemsPerPage = ref(10)
const headers = [
  { title: 'Name', value: 'name', sortable: false },
  { title: 'Sparte', value: 'branchId', sortable: false },
  { title: 'Mitglieder', value: 'userCount', sortable: false },
  { title: '', value: 'action', sortable: false }
]
const defaultItem = { id: null, name: null, branchId: null, userIds: [] }
const editedItem = ref<any>({ id: null, name: null, branchId: null, userIds: [] })
const showConfirmDialog = ref(false)
const itemToDelete = ref<any>(null)
const showDialog = ref(false)
const titleDialog = ref<string | null>(null)
const validDialog = ref(false)

const branches = computed(() => masterData.branches)
const editedUsers = computed(() => users.value.filter((u) => editedItem.value.userIds.indexOf(u.id) >= 0))

function getBranchById(branchId: number) {
  return masterData.getBranchById(branchId)
}

async function loadGroups() {
  const { data } = await httpClient.get('/group')
  groups.value = []
  for (let i = 0; i < data.data.length; i++) {
    const groupObj = data.data[i]
    groups.value.push(
      new Group(groupObj.id, groupObj.name, groupObj.branchId, getBranchById(groupObj.branchId), groupObj.userIds)
    )
  }
  page.value = data.currentPage
  total.value = data.total
}

async function loadUsers() {
  users.value = []
  const userRes = await httpClient.get('/user')
  for (const userObj of userRes.data) {
    users.value.push(
      userFromApi(userObj)
    )
  }
}

async function loadData() {
  loading.value = true
  try {
    await Promise.all([loadGroups(), loadUsers()])
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

function updateMasterData() {
  masterData.setGroups(groups.value)
}

async function save() {
  const { valid } = await form.value.validate()
  if (!valid) return
  try {
    const postData = {
      name: editedItem.value.name,
      branchId: editedItem.value.branchId,
      userIds: editedItem.value.userIds
    }

    let response = null
    if (editedItem.value.id) {
      response = await httpClient.put('/group/' + editedItem.value.id, postData)
    } else {
      response = await httpClient.post('/group', postData)
    }
    if (response && response.data.error) {
      useSnackbarStore().show('Gruppe konnte nicht gespeichert werden', 'error')
    } else {
      useSnackbarStore().show('Gruppe gespeichert', 'success')
      showDialog.value = false
      await loadData()
      updateMasterData()
    }
  } catch (error) {
    console.error(error)
    useSnackbarStore().show('Speichern fehlgeschlagen', 'error')
  }
}

function createItem() {
  titleDialog.value = 'Gruppe erstellen'
  showDialog.value = true
  editedItem.value = { ...defaultItem }
  selectedUserIds.value = []
  filteredUsers.value = users.value.slice()
}

function editItem(group: Group) {
  titleDialog.value = 'Gruppe bearbeiten'
  showDialog.value = true
  editedItem.value = { ...group }
  selectedUserIds.value = []
  filteredUsers.value = users.value.filter((u) => !(editedItem.value.userIds.indexOf(u.id) >= 0))
}

function confirmAndDelete(group: Group) {
  itemToDelete.value = group
  showConfirmDialog.value = true
}

async function deleteItem() {
  showConfirmDialog.value = false
  const { data } = await httpClient.delete('/group/' + itemToDelete.value.id)
  await loadData()
  updateMasterData()

  if (data.status === 'ok') {
    useSnackbarStore().show('Gruppe ' + itemToDelete.value.name + ' erfolgreich gelöscht', 'success')
  } else {
    useSnackbarStore().show('Gruppe konnte nicht gelöscht werden', 'error')
  }
}

function addSelectedUsersToGroup() {
  editedItem.value.userIds = editedItem.value.userIds.concat(selectedUserIds.value)
  filteredUsers.value = users.value.filter((u) => !(editedItem.value.userIds.indexOf(u.id) >= 0))
  selectedUserIds.value = []
}

function removeUserFromGroup(userIdToRemove: number) {
  editedItem.value.userIds = editedItem.value.userIds.filter((userId: number) => userId !== userIdToRemove)
  filteredUsers.value.push(users.value.filter((u) => u.id == userIdToRemove)[0])
}

function closeDialog() {
  form.value.resetValidation()
  showDialog.value = false
}

function buildFullBranchname(item: Branch) {
  return item.getShortNameAndName()
}

const fullName = (item: { firstName: string; familyName: string }) => item.firstName + ' ' + item.familyName

loadData()
</script>

<style scoped>

</style>
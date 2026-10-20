<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Benutzer</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-btn
                title="Liste nach Sparte und Gruppe filtern"
                color="primary"
                v-on:click="showFilterDialog = true">
              <v-icon start>mdi-filter-variant</v-icon>
              Filtern
            </v-btn>

            <GroupsSelectDialog
                v-bind:visible="showFilterDialog"
                v-on:close="showFilterDialog = false"
                v-bind:groupIds="filterGroupIds"
                v-on:done="filterChanged">
            </GroupsSelectDialog>
            <template v-slot:extension>
              <v-container>
                <v-row no-gutters>
                  <v-col
                      class="text-right"
                      cols="6"
                      md="2"
                      v-for="(item) in filterGroups"
                      :key="item.id">
                    <v-chip
                        size="small"
                        class="pl-2 pr-2 ml-2 mr-2" variant="outlined">
                      <v-icon start color="primary">mdi-account</v-icon>
                      {{ item.getWithBranchName() }}
                    </v-chip>
                  </v-col>
                </v-row>
              </v-container>

              <v-btn
                  title="Vorläufigen Benutzer anlegen"
                  icon
                  class="ma-2"
                  elevation="1"
                  color="primary"
                  @click="editItem()">
                <v-icon>mdi-plus</v-icon>
              </v-btn>
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
                          mobile-breakpoint="sm"
                          :headers="headers"
                          :items="users"
                          item-key="id"
                          :loading="loading"
                          :items-length="total"
                          :items-per-page-options="rowsPerPageItems"
                          v-model:items-per-page="itemsPerPage"
                          v-model:page="page"
                          v-model:sort-by="sortBy"
                          :search="searchText"
                      >
                        <template v-slot:top>
                          <v-text-field
                              v-model="searchText"
                              label="Suchen"
                              class="mx-4"
                              clearable
                          ></v-text-field>
                        </template>
                        <template v-slot:[`item.firstName`]="{ item }">
                          {{ item.firstName }}
                        </template>
                        <template v-slot:[`item.familyName`]="{ item }">
                          {{ item.familyName }}
                        </template>
                        <template v-slot:[`item.birthdate`]="{ item }">
                          {{
                            item.birthdate ? dayjs().diff(item.birthdate, 'years') + ' (Jg. ' + item.birthdate.format('YY') + ')' : 'NA'
                          }}
                        </template>
                        <template v-slot:[`item.groups`]="{ item }">
                          {{ getGroupsByIds(item.groupIds).map(g => g.getWithBranchName()).join(', ') }}
                        </template>
                        <template v-slot:[`item.active`]="{ item }">
                          {{ item.active ? 'Ja' : 'Nein' }}
                        </template>
                        <template v-slot:[`item.registered`]="{ item }">
                          {{ item.registered ? 'Ja' : 'Nein' }}
                        </template>
                        <template v-slot:[`item.action`]="{ item }">
                          <v-icon
                              class="mr-2"
                              v-if="canEditUser(item)"
                              @click="editItem(item)"
                              color="success">mdi-pencil
                          </v-icon>
                          <v-icon
                              class="mr-2"
                              v-if="canInviteUser(item)"
                              :disabled="invitingUserId === item.id"
                              title="Einladung senden"
                              @click="sendInvitation(item)"
                              color="primary">mdi-email-fast-outline
                          </v-icon>
                          <v-icon
                              v-if="canDeleteUser(item)"
                              @click="confirmAndDelete(item)"
                              color="error">mdi-trash-can
                          </v-icon>
                        </template>
                        <template v-slot:no-data>
                          <v-container fluid>
                            <v-row class="justify-center">
                              <v-col cols="auto">
                                <v-btn color="error" :disabled="loading" @click="reset()">
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
    <EditUserDialog
        v-bind:visible="showDialog"
        v-on:saved="loadData()"
        v-on:close="showDialog = false"
        :edit-user-id="editedItem.id"
        :edit-active="editedItem.active"
        :edit-birthdate="editedItem.birthdate"
        :edit-family-name="editedItem.familyName"
        :edit-email="editedItem.email"
        :edit-group-ids="editedItem.groupIds"
        :edit-profile-image-name="editedItem.profileImageName"
        :edit-first-name="editedItem.firstName">
    </EditUserDialog>
    <ConfirmDialog
        :show="showConfirmDialog"
        action-text="Löschen"
        v-on:confirmed="deleteItem()"
        v-on:canceled="showConfirmDialog = false">
    </ConfirmDialog>
  </v-container>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useAuthStore } from '@/store/auth'
import { useMasterDataStore } from '@/store/masterData'
import { useSnackbarStore } from '@/store/snackbar'
import httpClient from '@/http/api'
import dayjs, { parseApiDate } from '@/helpers/dayjs'
import GroupsSelectDialog from '../components/GroupsSelectDialog.vue'
import User, { userFromApi } from '../models/User'
import EditUserDialog from '../components/EditUserDialog.vue'
import Group from '../models/Group'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import { inviteUser } from '@/services/userInvite'

const authStore = useAuthStore()
const masterData = useMasterDataStore()

const showDialog = ref(false)
const showFilterDialog = ref(false)
const showConfirmDialog = ref(false)
const itemToDelete = ref<any>(null)
const invitingUserId = ref<number | null>(null)
const filterBranchId = ref<number | null>(null)
const filterGroupIds = ref<any[]>([])
const loading = ref(false)
const total = ref(0)
const rowsPerPageItems = [5, 10, 20, 50, 100]
const page = ref(1)
const itemsPerPage = ref(10)
const sortBy = ref<any[]>([])
const searchText = ref('')
const headers = [
  { title: 'Vorname', value: 'firstName', sortable: true },
  { title: 'Nachname', value: 'familyName', sortable: true },
  { title: 'Alter', value: 'birthdate', sortable: true },
  { title: 'Gruppen', value: 'groups', sortable: false },
  { title: 'Aktiv', value: 'active', sortable: true },
  { title: 'Registriert', value: 'registered', sortable: false },
  { title: '', value: 'action', sortable: false }
]
const users = ref<User[]>([])
const editedItem = ref<any>({
  id: null,
  firstName: null,
  familyName: null,
  email: null,
  birthdate: null,
  profileImageName: null,
  groupIds: [],
  isTrainer: false,
  active: false
})
const defaultItem = {
  id: null,
  firstName: '',
  familyName: '',
  email: null,
  birthdate: null,
  profileImageName: null,
  groupIds: [],
  isTrainer: false,
  active: true
}

const loggedInUser = computed(() => authStore.user)

const filterGroups = computed((): Group[] => {
  if (filterGroupIds.value && filterGroupIds.value.length > 0) {
    return masterData.getGroupsByIds(filterGroupIds.value)
  } else if (filterBranchId.value) {
    return masterData.getGroupsByBranchId(filterBranchId.value)
  }
  return []
})

function dataLoaded(res: any) {
  users.value = []
  for (let i = 0; i < res.data.data.length; i++) {
    const userObj = res.data.data[i]
    users.value.push(
      userFromApi(userObj)
    )
  }
  page.value = res.data.currentPage
  total.value = res.data.total ?? 0
}

async function loadData() {
  loading.value = true
  let url = null
  // get by sort option
  if (sortBy.value[0]) {
    // Vuetify 3: sortBy is an array of { key, order }
    const direction = sortBy.value[0].order === 'desc' ? 'desc' : 'asc'
    url =
      '/user/sort?direction=' +
      direction +
      '&sortBy=' +
      sortBy.value[0].key +
      '&page=' +
      page.value +
      '&per_page=' +
      itemsPerPage.value
  } else {
    url = '/user?page=' + page.value + '&per_page=' + itemsPerPage.value
  }
  if (filterGroupIds.value && filterGroupIds.value.length > 0) {
    url += '&groupIds=' + filterGroupIds.value
  } else if (filterBranchId.value) {
    url += '&branchId=' + filterBranchId.value
  }
  if (searchText.value && searchText.value.trim().length > 0) {
    url += '&searchText=' + searchText.value
  }

  try {
    const request = await httpClient.get(url)
    dataLoaded(request)
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

function filterChanged({ branchId, groupdIds }: { branchId: number | null; groupdIds: any[] | null }) {
  filterGroupIds.value = groupdIds as any[]
  filterBranchId.value = branchId
  if (!filterGroupIds.value || filterGroupIds.value.length == 0) {
    filterGroupIds.value = masterData.getGroupsByBranchId(filterBranchId.value).map((g) => g.id)
  }
  loadData()
}

function editItem(item?: any) {
  if (item) {
    editedItem.value = { ...item }
  } else {
    editedItem.value = { ...defaultItem }
  }
  showDialog.value = true
}

function canDeleteUser(item: any) {
  return loggedInUser.value!.isAdmin && !item.isTrainer && !item.isAdmin
}

function canEditUser(item: any) {
  return (loggedInUser.value!.isAdmin || loggedInUser.value!.isTrainer) && !item.isTrainer && !item.isAdmin
}

// admins and trainers hold the `update-user` permission
function canInviteUser(item: any) {
  return !!item.email && !!loggedInUser.value && (loggedInUser.value.isAdmin || loggedInUser.value.isTrainer)
}

async function sendInvitation(item: any) {
  invitingUserId.value = item.id
  try {
    await inviteUser(item.id, item.firstName + ' ' + item.familyName)
  } finally {
    invitingUserId.value = null
  }
}

function confirmAndDelete(item: any) {
  itemToDelete.value = item
  showConfirmDialog.value = true
}

async function deleteItem() {
  showConfirmDialog.value = false
  const response = await httpClient.delete('/user/' + itemToDelete.value.id)
  if (response.data.status === 'ok') {
    useSnackbarStore().show(
      'Benutzer ' + itemToDelete.value.firstName + ' ' + itemToDelete.value.familyName + ' erfolgreich gelöscht',
      'success'
    )
    users.value.splice(users.value.indexOf(itemToDelete.value), 1)
  } else {
    useSnackbarStore().show('Benutzer konnte nicht gelöscht werden', 'error')
  }
}

function reset() {
  loadData()
}

function getGroupsByIds(groupIds: any[]) {
  return masterData.getGroupsByIds(groupIds)
}

for (const source of [searchText, page, itemsPerPage, sortBy]) {
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
  filterBranchId.value = user.trainerBranchIds[0]
  filterGroupIds.value = masterData.getGroupsByBranchId(filterBranchId.value).map((g) => g.id)
}
loadData()
</script>

<style scoped>

</style>

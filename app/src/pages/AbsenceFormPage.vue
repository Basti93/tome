<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Abwesenheit</v-toolbar-title>
          </v-toolbar>
          <v-divider></v-divider>
          <v-card-text flat class="pa-2 pa-md-4">
            <v-card>
              <v-card-subtitle>In deiner Abwesenheit wirst du nicht automatisch an Trainings angemeldet. Falls du deine
                Abwesenheit ändern oder vorzeitig beenden möchtest, melde dich bei deinem Trainer.
              </v-card-subtitle>
              <v-card-text class="pa-0 pa-md-4">
                <v-container>
                  <v-row no-gutters>
                    <v-col>
                      <v-form ref="form" v-model="valid">
                        <v-container>
                          <v-row>
                            <v-col
                                cols="12"
                                md="4"
                            >
                              <v-autocomplete
                                  :items="formattedUsers"
                                  v-model="userId"
                                  item-value="id"
                                  item-title="displayName"
                                  clearable
                                  required
                                  :rules="requiredRule"
                                  label="Sportler auswählen"
                              ></v-autocomplete>
                            </v-col>
                            <v-col
                                cols="12"
                                md="4"
                            >
                              <v-menu
                                  v-model="menuStart"
                                  :close-on-content-click="false"
                                  :nudge-right="40"
                                  transition="scale-transition"
                                  offset-y
                                  min-width="auto"
                              >
                                <template v-slot:activator="{ on, attrs }">
                                  <v-text-field
                                      v-model="absenceStartFormatted"
                                      label="Beginn der Abwesenheit"
                                      required
                                      readonly
                                      :rules="requiredRule"
                                      v-bind="attrs"
                                      v-on="on"
                                  ></v-text-field>
                                </template>
                                <v-date-picker
                                    v-model="absenceStart"
                                    :min="new Date().toISOString().substr(0, 10)"
                                    @input="menuStart = false"
                                ></v-date-picker>
                              </v-menu>
                            </v-col>
                            <v-col
                                cols="12"
                                md="4"
                            >
                              <v-menu
                                  v-model="menuEnd"
                                  :close-on-content-click="false"
                                  :nudge-right="40"
                                  transition="scale-transition"
                                  offset-y
                                  min-width="auto"
                              >
                                <template v-slot:activator="{ on, attrs }">
                                  <v-text-field
                                      v-model="absenceEndFormatted"
                                      label="Ende der Abwesenheit"
                                      readonly
                                      :rules="requiredRule"
                                      required
                                      v-bind="attrs"
                                      v-on="on"
                                  ></v-text-field>
                                </template>
                                <v-date-picker
                                    v-model="absenceEnd"
                                    :min="new Date().toISOString().substr(0, 10)"
                                    @input="menuEnd = false"
                                ></v-date-picker>
                              </v-menu>
                            </v-col>
                            <v-col
                                cols="12"
                                md="6"
                            >
                              <v-textarea
                                  v-model="absenceReason"
                                  label="Grund der Abwesenheit (Nur für Trainer sichtbar)"
                                  :rules="requiredRule"
                                  outlined
                                  required
                              ></v-textarea>
                            </v-col>
                            <v-col cols="12">
                              <v-btn
                                  :disabled="!valid"
                                  color="success"
                                  class="mr-4"
                                  v-on:click="sendAbsence"
                              >
                                Abschicken
                              </v-btn>
                            </v-col>
                          </v-row>
                        </v-container>
                      </v-form>
                    </v-col>
                  </v-row>
                  <v-row v-if="loggedInUser">
                    <v-col>
                      <v-divider class="mt-3 mb-3"></v-divider>
                      <h3>Benutzer mit eingetragener Abwesenheit (nur für Trainer sichtbar)</h3>
                      <h4>Abwesenheiten werden automatisch nach Ablauf gelöscht</h4>
                      <v-data-table
                          :headers="headers"
                          :items="absenceUsers"
                          item-key="id"
                          :loading="loadingUsers"
                          :server-items-length="total"
                          :footer-props="{
                                itemsPerPageOptions: rowsPerPageItems,
                            }"
                          v-model:itemsPerPage="itemsPerPage"
                          v-model:page="page"
                      >
                        <template v-slot:[`item.firstName`]="{ item }">
                          {{ item.firstName }}
                        </template>
                        <template v-slot:[`item.familyName`]="{ item }">
                          {{ item.familyName }}
                        </template>
                        <template v-slot:[`item.absenceStart`]="{ item }">
                          {{ item.absenceStart.format('DD.MM.YYYY') }}
                        </template>
                        <template v-slot:[`item.absenceEnd`]="{ item }">
                          {{ item.absenceEnd.format('DD.MM.YYYY') }}
                        </template>
                        <template v-slot:[`item.absenceReason`]="{ item }">
                          {{ item.absenceReason }}
                        </template>
                        <template v-slot:[`item.action`]="{ item }">
                          <v-icon v-on:click="confirmAndDelete(item.id)" color="error">mdi-trash-can</v-icon>
                        </template>
                        <template v-slot:no-data>
                          <v-container>
                            <v-row>
                              <v-col>
                                <v-btn color="error" :disabled="loadingUsers" v-on:click="loadAllAbsenceUsers()">
                                  <v-icon left>mdi-refresh</v-icon>
                                  Keine Daten gefunden
                                </v-btn>
                              </v-col>
                            </v-row>
                          </v-container>
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
        v-on:confirmed="deleteAbsence()"
        v-on:canceled="showConfirmDialog = false">
    </ConfirmDialog>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { formatDate } from "@/helpers/date-helpers"
import User from "../models/User"
import ConfirmDialog from "../components/ConfirmDialog.vue"
import { useAuthStore } from '@/store/auth'
import { useCookieAuthStore } from '@/store/cookieAuth'
import { useMasterDataStore } from '@/store/masterData'
import { useSnackbarStore } from '@/store/snackbar'
import axios from '@/axios'
import moment from 'moment'

const authStore = useAuthStore()
const cookieAuthStore = useCookieAuthStore()
const masterDataStore = useMasterDataStore()
const snackbarStore = useSnackbarStore()

const absenceUsers = ref([])
const total = ref(null)
const rowsPerPageItems = [5, 10, 20, 50]
const page = ref(1)
const itemsPerPage = ref(10)
const headers = [
  { text: 'Vorname', value: 'firstName', sortable: false },
  { text: 'Nachname', value: 'familyName', sortable: false },
  { text: 'Von', value: 'absenceStart', sortable: false },
  { text: 'Bis', value: 'absenceEnd', sortable: false },
  { text: 'Grund', value: 'absenceReason', sortable: false },
  { text: 'Löschen', value: 'action', sortable: false },
]
const loadingUsers = ref(false)
const absenceStart = ref(new Date().toISOString().substr(0, 10))
const absenceEnd = ref(null)
const absenceReason = ref(null)
const menuStart = ref(false)
const menuEnd = ref(false)
const valid = ref(true)
const userId = ref(null)
const users = ref([])
const requiredRule = [
  v => !!v || 'Bitte ausfüllen'
]
const showConfirmDialog = ref(false)
const userIdToDelete = ref(null)

const loggedInUser = computed(() => authStore.user)
const cookieUser = computed(() => cookieAuthStore.cookieUser)

const currentUser = computed(() => {
  if (loggedInUser.value) {
    return loggedInUser.value
  } else if (cookieUser.value) {
    return cookieUser.value
  }
  return null
})

const currentUserId = computed(() => {
  if (currentUser.value) {
    return currentUser.value.id
  }
  return null
})

const absenceStartFormatted = computed(() => {
  return formatDate(absenceStart.value)
})

const absenceEndFormatted = computed(() => {
  return formatDate(absenceEnd.value)
})

const formattedUsers = computed(() => {
  return users.value.map(user => ({
    ...user,
    displayName: `${user.firstName} ${user.familyName}`
  }))
})

function getAllSimpleUsersWithGroup() {
  return masterDataStore.getAllSimpleUsersWithGroup()
}

async function loadAllAbsenceUsers() {
  loadingUsers.value = true
  absenceUsers.value = []
  const { data } = await axios.get('user/allAbsence')
  if (data.data) {
    for (const userObj of data.data) {
      absenceUsers.value.push(new User(
        userObj.id,
        userObj.email,
        userObj.firstName,
        userObj.familyName,
        userObj.birthdate ? moment(userObj.birthdate, 'YYYY-MM-DDTHH:mm') : null,
        userObj.active === 1 ? true : false,
        userObj.groupIds,
        userObj.roleNames,
        userObj.trainerBranchIds,
        userObj.registered,
        userObj.profileImageName,
        userObj.absenceStart ? moment(userObj.absenceStart, 'YYYY-MM-DDTHH:mm') : null,
        userObj.absenceEnd ? moment(userObj.absenceEnd, 'YYYY-MM-DDTHH:mm') : null,
        userObj.absenceReason
      ))
    }
    page.value = data.currentPage
    total.value = data.total
  }
  loadingUsers.value = false
}

async function sendAbsence() {
  const postData = {
    absenceStart: moment(absenceStart.value, 'YYYY-MM-DD').format(),
    absenceEnd: moment(absenceEnd.value, 'YYYY-MM-DD').format(),
    absenceReason: absenceReason.value
  }
  const { data } = await axios.post('simpleuser/' + userId.value + '/storeAbsence', postData)
  if (data.status === 'ok') {
    snackbarStore.show("Abwesenheit erfolgreich eingetragen", "success")
    resetFormData()
    loadAllAbsenceUsers()
  } else if (data.status === 'absence_exists') {
    snackbarStore.show("Eine Abwesenheit ist für diesen Benutzer bereits eingetragen. Bitte kontaktiere einen Trainer um sie zu ändern.", "error")
  }
}

function confirmAndDelete(userIdVal: number) {
  showConfirmDialog.value = true
  userIdToDelete.value = userIdVal
}

async function deleteAbsence() {
  showConfirmDialog.value = false
  if (userIdToDelete.value) {
    const { data } = await axios.put('user/' + userIdToDelete.value + '/removeAbsence')
    if (data.status === 'ok') {
      snackbarStore.show("Abwesenheit erfolgreich gelöscht", "success")
      loadAllAbsenceUsers()
    }
  }
}

function resetFormData() {
  absenceReason.value = null
  absenceStart.value = new Date().toISOString().substr(0, 10)
  absenceEnd.value = null
  userId.value = null
}

function fullName(item) {
  return item.firstName + ' ' + item.familyName
}

onMounted(() => {
  users.value = getAllSimpleUsersWithGroup()
  if (currentUserId.value) {
    userId.value = currentUserId.value
  }
  loadAllAbsenceUsers()
})
</script>

<style scoped lang="scss">

</style>

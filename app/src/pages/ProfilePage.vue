<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat >
            <v-toolbar-title>Profileinstellungen</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-btn
                elevation="1"
                color="primary"
                v-bind:disabled="!valid || !hasUnsavedChanges"
                aria-label="Speichern"
                @click="save()"
                :class="{ 'pulse': hasUnsavedChanges }"
            >
              <v-icon :start="!xs">mdi-content-save</v-icon>
              <span v-if="!xs">Speichern</span>
              <v-badge
                  v-if="hasUnsavedChanges"
                  color="amber"
                  content="!"
                  inline
                  class="ml-2"
              ></v-badge>
            </v-btn>
          </v-toolbar>
          <v-card-text class="pa-0 pa-md-4">
            <v-row no-gutters>
              <v-col>
                <v-card class="ma-1">
                  <v-form
                      v-model="valid"
                  >

                    <v-card-text>
                      <v-tabs
                          v-model="activeTab"
                          class="profile-tabs"
                          grow
                          stacked
                      >

                        <v-tab>
                          <v-icon>mdi-account-circle</v-icon>
                          Profil
                        </v-tab>
                        <v-tab v-if="loggedInUser.isAdmin || loggedInUser.isTrainer">
                          <v-icon>mdi-shield-check</v-icon>
                          Trainer
                        </v-tab>
                        <v-tab>
                          <v-icon>mdi-bell</v-icon>
                          Benachrichtigungen
                        </v-tab>
                      </v-tabs>
                      <v-window v-model="activeTab">
                        <v-window-item>
                          <v-card flat>
                            <v-card-text>
                              <v-container>
                                <v-row>
                                  <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="editUser.firstName"
                                        label="Vorname"
                                        :rules="requiredRule"
                                        prepend-icon="account_circle"
                                        required>
                                    </v-text-field>
                                  </v-col>
                                  <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="editUser.familyName"
                                        label="Nachname"
                                        :rules="requiredRule"
                                        prepend-icon="account_circle"
                                        required>
                                    </v-text-field>
                                  </v-col>
                                  <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="editUser.email"
                                        label="E-Mail"
                                        :rules="emailRules"
                                        prepend-icon="email"
                                        required>
                                    </v-text-field>
                                  </v-col>
                                  <v-col cols="12" sm="6">
                                    <v-menu
                                        :close-on-content-click="false"
                                        v-model="birthdateMenu"
                                        :rules="requiredRule"
                                        required>
                                      <template v-slot:activator="{ props }">
                                        <v-text-field
                                            v-model="birthdateFormatted"
                                            required
                                            label="Geburtsdatum"
                                            prepend-icon="event"
                                            readonly
                                            v-bind="props"></v-text-field>
                                      </template>
                                      <v-date-picker
                                          v-model="editUser.birthdate"
                                          @update:modelValue="birthdateMenu = false"
                                          ref="birthdatePicker"
                                          :max="new Date().toISOString().substr(0, 10)"
                                          min="1950-01-01">
                                      </v-date-picker>
                                    </v-menu>
                                  </v-col>
                                  <v-col cols="12" sm="6">
                                    <UploadProfileImage
                                        v-on:imageChanged="imageChanged"
                                        v-on:imageRemoved="imageRemoved"
                                        :imagePath="editUser.profileImageName"
                                    ></UploadProfileImage>
                                  </v-col>
                                  <v-col cols="12" sm="6">
                                    <ChangePasswordDialog
                                        :visible="showPasswordDialog"
                                        v-on:close="showPasswordDialog = false"
                                    >
                                    </ChangePasswordDialog>
                                    <v-btn
                                        color="primary"
                                        elevation="1"
                                        @click="showPasswordDialog = true"
                                    >
                                      <v-icon start>mdi-shield-lock</v-icon>
                                      Passwort ändern
                                    </v-btn>
                                  </v-col>
                                  <v-col cols="12" v-if="loggedInUser.isTrainer">
                                    <v-card flat>
                                      <v-card-title>
                                        <span class="title">Erweiterte Einstellungen</span>
                                      </v-card-title>
                                      <v-card-text>
                                        <v-container>
                                          <v-row>
                                            <v-col md="10" offset-md="1">
                                              <v-alert
                                                  type="info"
                                                  variant="outlined"
                                              >
                                                Falls du nicht nur Trainer sondern auch noch aktiver Sportler bist,
                                                kannst
                                                du dich hier Gruppen zuweisen
                                              </v-alert>
                                              <GroupsSelect
                                                  v-bind:groupIds="editUser.groupIds"
                                                  v-on:groupsChanged="groupsChanged">
                                              </GroupsSelect>
                                            </v-col>
                                          </v-row>
                                        </v-container>
                                      </v-card-text>
                                    </v-card>
                                  </v-col>
                                </v-row>
                              </v-container>
                            </v-card-text>
                          </v-card>
                        </v-window-item>
                        <v-window-item>
                          <v-card flat>
                            <v-card-text>
                              <v-container>
                                <v-row>
                                  <v-col md="6" offset-md="3">
                                    <v-alert
                                        type="info"
                                        variant="outlined"
                                    >
                                      Wähle deine Hauptsparte um die App auf dich abzustimmen.
                                    </v-alert>
                                    <v-select
                                        class="pt-6"
                                        :items="branches"
                                        v-model="editUser.trainerBranchIds"
                                        :item-title="shortNameAndName"
                                        item-value="id"
                                        multiple
                                        chips
                                        closable-chips
                                        density="compact"
                                        label="Hauptsparte auswählen"
                                        prepend-icon="mdi-bubble-chart"
                                    >
                                    </v-select>
                                  </v-col>
                                </v-row>
                              </v-container>
                            </v-card-text>
                          </v-card>
                        </v-window-item>
                        <v-window-item>
                          <v-card flat>
                            <v-card-text>
                              <NotificationPreferencesForm />
                            </v-card-text>
                          </v-card>
                        </v-window-item>
                      </v-window>
                    </v-card-text>
                  </v-form>
                </v-card>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useDisplay } from 'vuetify'
import ChangePasswordDialog from '@/components/ChangePasswordDialog.vue'
import UploadProfileImage from '@/components/UploadProfileImage.vue'
import NotificationPreferencesForm from '@/components/NotificationPreferencesForm.vue'
import { formatDate } from '../helpers/date-helpers'
import GroupsSelect from '../components/GroupsSelect.vue'
import { useAuthStore } from '@/store/auth'
import { useMasterDataStore } from '@/store/masterData'
import { useSnackbarStore } from '@/store/snackbar'
import httpClient from '@/http/api'
import { parseApiDate } from '@/helpers/dayjs'
import User from '@/models/User'

const { xs } = useDisplay()
const authStore = useAuthStore()
const masterData = useMasterDataStore()

const activeTab = ref(0)
const valid = ref(true)
const birthdateMenu = ref(false)
const showPasswordDialog = ref(false)
const imageToUpload = ref<File | null>(null)
const editUser = ref<any>({
  id: null,
  firstName: null,
  familyName: null,
  email: null,
  birthdate: null,
  profileImageName: null,
  trainerBranchIds: [],
  groupIds: []
})
const requiredRule = [(v: any) => !!v || 'Pflichtfeld']
const emailRules = [(v: any) => !!v || 'Pflichtfeld', (v: any) => /.+@.+/.test(v) || 'E-mail muss gültig sein']

const loggedInUser = computed(() => authStore.user)
const branches = computed(() => masterData.branches)
const birthdateFormatted = computed(() => formatDate(editUser.value.birthdate))
const hasUnsavedChanges = computed(() => {
  const user = loggedInUser.value as any
  if (!user) return false
  return (
    editUser.value.firstName !== user.firstName ||
    editUser.value.familyName !== user.familyName ||
    editUser.value.email !== user.email ||
    editUser.value.birthdate !== user.birthdate ||
    imageToUpload.value !== null ||
    editUser.value.profileImageName !== user.profileImageName ||
    JSON.stringify(editUser.value.groupIds) !== JSON.stringify(user.groupIds) ||
    JSON.stringify(editUser.value.trainerBranchIds) !== JSON.stringify(user.trainerBranchIds)
  )
})

function assignCurrentUser() {
  editUser.value = { ...loggedInUser.value }
}

function groupsChanged({ groupIds }: { groupIds: any[] }) {
  editUser.value.groupIds = groupIds
}

function imageRemoved() {
  imageToUpload.value = null
  editUser.value.profileImageName = null
}

function imageChanged(file: File | null) {
  if (file && file.size) {
    imageToUpload.value = file
  } else {
    imageRemoved()
  }
}

async function uploadProfileImage() {
  const formData = new FormData()
  formData.append('profile_image', imageToUpload.value as File)
  const { data } = await httpClient.post('/user/me/uploadprofileimage', formData)
  if (data.status === 'ok') {
    return data.imageUrl
  }
  useSnackbarStore().show('Fehler beim Hochladen des Bildes.', 'error')
  throw 'Image upload error'
}

async function save() {
  try {
    const postData: Record<string, any> = {
      firstName: editUser.value.firstName,
      familyName: editUser.value.familyName,
      email: editUser.value.email,
      birthdate: editUser.value.birthdate ? parseApiDate(editUser.value.birthdate).format() : null,
      groupIds: editUser.value.groupIds,
      trainerBranchIds: editUser.value.trainerBranchIds
    }

    let imageName = null
    if (imageToUpload.value) {
      imageName = await uploadProfileImage()
    } else if (editUser.value.profileImageName) {
      imageName = editUser.value.profileImageName
    }
    if (imageName) {
      postData.profileImageName = imageName
    }

    const { data } = await httpClient.put('user/me', postData)
    if (data.status == 'ok') {
      useSnackbarStore().show('Erfolgreich gespeichert', 'success')
      const { data: userData } = await httpClient.get('auth/me')
      const user = User.from(JSON.stringify(userData))
      useAuthStore().updateUser(user)
      assignCurrentUser()
    } else {
      useSnackbarStore().show('Fehler beim Speichern.', 'error')
    }
  } catch (error) {
    useSnackbarStore().show('Fehler beim Speichern.', 'error')
  }
}

function shortNameAndName(branch: { name: string; shortName: string }) {
  return branch.name + ' (' + branch.shortName + ')'
}

assignCurrentUser()
</script>

<style scoped>
@media (max-width: 599.98px) {
  /* three tabs must fit side by side without scrolling */
  .profile-tabs :deep(.v-tab.v-btn) {
    min-width: 0;
    padding-inline: 4px;
    font-size: 0.75rem;
    letter-spacing: 0;
  }
}

.pulse {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
  }
  70% {
    box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(255, 193, 7, 0);
  }
}
</style>

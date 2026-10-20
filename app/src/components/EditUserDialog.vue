<template>
    <v-dialog v-model="show" max-width="1000px" :fullscreen="xs" persistent>
        <v-card>
            <v-toolbar flat>
                <v-btn icon @click="show=false">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
                <v-toolbar-title v-if="!editUserId">Vorläufigen Benutzer Anlegen</v-toolbar-title>
                <v-toolbar-title v-else>Benutzer Bearbeiten</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-toolbar-items>
                    <v-btn color="primary" @click="save" variant="text"><v-icon start>mdi-check</v-icon>Speichern</v-btn>
                </v-toolbar-items>
            </v-toolbar>
            <v-divider class="pb-2"></v-divider>
            <v-card-text>
                <v-alert
                        v-if="!editUserId"
                        type="info"
                        class="text-small"
                        pa-0
                        ma-0
                        dismissible variant="outlined">
                    Vorläufige Benutzer können an Trainings teilnehmen aber sich nicht einloggen. Mit einer E-Mail-Adresse kann der Benutzer anschließend per Einladung (Benutzerverwaltung, "Einladung senden") zum Login eingeladen werden.
                </v-alert>
              <v-form
                  ref="form"
                  v-model="valid">
                <v-container>
                    <v-row>
                        <v-col cols="12" sm="6">
                            <v-text-field
                                    v-model="firstName"
                                    label="Vorname"
                                    required
                                    :rules="[v => !!v || 'Wird benötigt']"
                                    prepend-icon="account_circle"
                            ></v-text-field>
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field
                                    v-model="familyName"
                                    label="Nachname"
                                    :rules="[v => !!v || 'Wird benötigt']"
                                    required
                                    prepend-icon="account_circle"
                            ></v-text-field>
                        </v-col>
                        <v-col cols="12">
                            <v-text-field
                                    v-model="email"
                                    label="E-Mail (optional, nötig für die Einladung)"
                                    type="email"
                                    :rules="[v => !v || /.+@.+\..+/.test(v) || 'Bitte eine gültige E-Mail-Adresse eingeben']"
                                    prepend-icon="email"
                            ></v-text-field>
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-menu
                                    :close-on-content-click="false"
                                    v-model="birthdateMenu">
                                <template v-slot:activator="{ props }">
                                    <v-text-field
                                            v-model="birthdateFormatted"
                                            label="Geburtsdatum"
                                            prepend-icon="event"
                                            readonly
                                            v-bind="props"
                                    ></v-text-field>
                                </template>
                                <v-date-picker
                                    ref="birthdatePicker"
                                    v-model="birthdate"
                                    @update:modelValue="birthdateMenu = false"
                                    :max="new Date().toISOString().substr(0, 10)"
                                    min="1950-01-01">
                                </v-date-picker>
                            </v-menu>
                        </v-col>
                        <v-col cols="12" md="6">
                            <GroupsSelect
                                    :groupIds="groupIds"
                                    v-on:groupsChanged="groupsChanged">
                            </GroupsSelect>
                        </v-col>
                        <v-col cols="12" md="6">
                            <UploadProfileImage
                                    v-on:imageChanged="imageChanged"
                                    v-on:imageRemoved="imageRemoved"
                                    :imagePath="profileImageName"
                            ></UploadProfileImage>
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-checkbox
                                    v-model="active"
                                    label="Aktiv"
                                    prepend-icon="active"
                            ></v-checkbox>
                        </v-col>
                    </v-row>
                </v-container>
              </v-form>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useDisplay } from 'vuetify'
import GroupsSelect from './GroupsSelect.vue'
import UploadProfileImage from './UploadProfileImage.vue'
import { formatDate } from '../helpers/date-helpers'
import { parseApiDate } from '@/helpers/dayjs'
import httpClient from '@/http/api'
import { apiErrorMessage } from '@/helpers/apiError'
import { useSnackbarStore } from '@/store/snackbar'

const props = defineProps<{
  visible?: boolean
  editUserId?: number | null
  editFirstName?: string | null
  editFamilyName?: string | null
  editEmail?: string | null
  editBirthdate?: any
  editProfileImageName?: string | null
  editGroupIds?: any[]
  editActive?: boolean
}>()

const emit = defineEmits<{
  close: []
  saved: []
}>()

const { xs } = useDisplay()

const form = ref<any>(null)
const birthdatePicker = ref<any>(null)

const valid = ref(false)
const firstName = ref<string | null | undefined>(null)
const familyName = ref<string | null | undefined>(null)
const email = ref<string | null>(null)
const birthdate = ref<string | null>(null)
const groupIds = ref<any[] | undefined>([])
const profileImageName = ref<string | null | undefined>(null)
const active = ref<boolean | undefined>(true)
const birthdateMenu = ref(false)
const imageToUpload = ref<File | null>(null)

function assignValues() {
  firstName.value = props.editFirstName
  familyName.value = props.editFamilyName
  email.value = props.editEmail || null
  birthdate.value = props.editBirthdate ? parseApiDate(props.editBirthdate).format('YYYY-MM-DD') : null
  groupIds.value = props.editGroupIds
  active.value = props.editActive
  profileImageName.value = props.editProfileImageName
  imageToUpload.value = null
}

const show = computed({
  get() {
    if (props.visible) {
      assignValues()
    }
    return props.visible
  },
  set(value) {
    if (!value) {
      form.value.resetValidation()
      emit('close')
    }
  }
})

const birthdateFormatted = computed(() => formatDate(birthdate.value))

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
  const { valid: isValid } = await form.value.validate()
  if (!isValid) return
  try {
    const postData: Record<string, any> = {
      firstName: firstName.value,
      familyName: familyName.value,
      email: email.value ? email.value.trim() : null,
      groupIds: groupIds.value,
      profileImageName: profileImageName.value,
      active: active.value
    }

    let imageName = null
    if (imageToUpload.value) {
      imageName = await uploadProfileImage()
    } else if (profileImageName.value) {
      imageName = profileImageName.value
    }
    if (imageName) {
      postData.profileImageName = imageName
    }

    let response = null
    if (birthdate.value) {
      postData.birthdate = parseApiDate(birthdate.value).format('YYYY-MM-DD')
    }
    if (props.editUserId) {
      response = await httpClient.put('/user/' + props.editUserId, postData)
    } else {
      response = await httpClient.post('/user/unregistered', postData)
    }
    if (response.data.status === 'ok') {
      useSnackbarStore().show('Benutzer gespeichert', 'success')
      emit('saved')
      show.value = false
    } else {
      useSnackbarStore().show('Fehler beim Speichern', 'error')
    }
  } catch (error) {
    console.error(error)
    // e.g. 422 "Diese E-Mail-Adresse wird bereits verwendet."
    useSnackbarStore().show(apiErrorMessage(error, 'Speichern fehlgeschlagen'), 'error')
  }
}

function imageRemoved() {
  imageToUpload.value = null
  profileImageName.value = null
}

function imageChanged(file: File | null) {
  if (file && file.size) {
    imageToUpload.value = file
  } else {
    imageRemoved()
  }
}

function groupsChanged({ groupIds: ids }: { groupIds: any[] }) {
  groupIds.value = ids
}

watch(birthdateMenu, (val) => {
  if (val) setTimeout(() => (birthdatePicker.value.activePicker = 'YEAR'))
})
</script>

<style scoped>

</style>

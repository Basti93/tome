<template>
  <div class="notification-preferences-form">
    <v-container>
        <v-row>
          <v-col cols="12">
            <v-card flat class="mb-4">
              <v-card-title class="text-body-large font-weight-bold">
                <v-icon start>mdi-bell</v-icon>
                Trainer-Benachrichtigungen
              </v-card-title>
              <v-card-text>
                <v-checkbox
                  v-model="preferences.trainer_upcoming"
                  label="Erinnerung vor Training"
                  hint="Benachrichtigung 4 Stunden vor einem Training"
                  persistent-hint
                ></v-checkbox>
                <v-checkbox
                  v-model="preferences.trainer_cancel_alert"
                  label="Absagen-Benachrichtigungen"
                  hint="Benachrichtigung wenn ein Sportler das Training absagt"
                  persistent-hint
                  class="mt-2"
                ></v-checkbox>
                <v-checkbox
                  v-model="preferences.trainer_evaluation_reminder"
                  label="Erinnerung an nicht abgeschlossene Trainings per Push-Benachrichtigung"
                  hint="Täglich um 20 Uhr, falls ein Training noch nicht abgeschlossen ist"
                  persistent-hint
                  class="mt-2"
                ></v-checkbox>
                <v-checkbox
                  v-model="preferences.trainer_evaluation_reminder_email"
                  label="Erinnerung an nicht abgeschlossene Trainings per E-Mail"
                  hint="Eine E-Mail pro Erinnerung mit allen offenen Trainings (standardmäßig aus)"
                  persistent-hint
                  class="mt-2"
                ></v-checkbox>
                <v-checkbox
                  v-model="preferences.trainer_birthday"
                  label="Geburtstage meiner Sportler (Push)"
                  hint="Morgens um 7 Uhr, falls jemand aus deinen Gruppen Geburtstag hat"
                  persistent-hint
                  class="mt-2"
                ></v-checkbox>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12">
            <v-card flat class="mb-4">
              <v-card-title class="text-body-large font-weight-bold">
                <v-icon start>mdi-bell</v-icon>
                Sportler-Benachrichtigungen
              </v-card-title>
              <v-card-text>
                <v-checkbox
                  v-model="preferences.participant_upcoming"
                  label="Training-Erinnerungen"
                  hint="Benachrichtigung 4 Stunden vor einem Training"
                  persistent-hint
                ></v-checkbox>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12">
            <v-card flat>
              <v-card-title class="text-body-large font-weight-bold">
                <v-icon start>mdi-bell</v-icon>
                Sonstige Benachrichtigungen
              </v-card-title>
              <v-card-text>
                <v-checkbox
                  v-model="preferences.absence_alert"
                  label="Abwesenheits-Benachrichtigungen"
                  hint="Benachrichtigung wenn ein Sportler Abwesenheit meldet"
                  persistent-hint
                ></v-checkbox>
                <v-checkbox
                  v-model="preferences.admin_messages"
                  label="Administrator-Nachrichten"
                  hint="Nachrichten von Administratoren"
                  persistent-hint
                  class="mt-2"
                ></v-checkbox>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
        <v-row class="mt-4">
          <v-col cols="12" class="text-right">
            <v-btn
              @click="reset"
              :disabled="!hasChanges"
              class="mr-2" variant="text"
            >
              Zurücksetzen
            </v-btn>
            <v-btn
              color="primary"
              elevation="1"
              @click="save"
              :disabled="!hasChanges || loading"
              :loading="loading"
            >
              <v-icon start>mdi-content-save</v-icon>
              Speichern
            </v-btn>
          </v-col>
        </v-row>
      </v-container>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/store/auth'
import httpClient from '@/http/api'
import { useSnackbarStore } from '@/store/snackbar'

interface NotificationPreferences {
  trainer_upcoming: boolean
  trainer_cancel_alert: boolean
  participant_upcoming: boolean
  absence_alert: boolean
  admin_messages: boolean
  trainer_evaluation_reminder: boolean
  trainer_evaluation_reminder_email: boolean
  trainer_birthday: boolean
}

const emit = defineEmits<{
  saved: [preferences: Record<string, boolean> | null]
}>()

// the API answers in camelCase (trainerUpcoming), the form and the request body use snake_case
function snakeCaseKeys(obj?: Record<string, boolean>): Record<string, boolean> | null {
  if (!obj) return null
  return Object.fromEntries(Object.entries(obj).map(([k, v]) => [k.replace(/[A-Z]/g, (c) => '_' + c.toLowerCase()), v]))
}

const defaults = (): NotificationPreferences => ({
  trainer_upcoming: false,
  trainer_cancel_alert: true,
  participant_upcoming: true,
  absence_alert: true,
  admin_messages: true,
  trainer_evaluation_reminder: true,
  trainer_evaluation_reminder_email: false,
  trainer_birthday: true,
})

const authStore = useAuthStore()

const preferences = ref<NotificationPreferences>(defaults())
const originalPreferences = ref<NotificationPreferences>(defaults())
const loading = ref(false)

const hasChanges = computed(() => JSON.stringify(preferences.value) !== JSON.stringify(originalPreferences.value))

async function loadPreferences() {
  try {
    const { data } = await httpClient.get('/user/me/notification-preferences')
    const loaded = snakeCaseKeys(data?.notificationPreferences ?? data?.notification_preferences)
    if (loaded) {
      // keys missing in older stored preferences count as enabled (the opt-in reminders as disabled)
      preferences.value = { ...preferences.value, ...loaded }
      originalPreferences.value = { ...preferences.value }
    }
  } catch (error) {
    console.error('Error loading notification preferences:', error)
    useSnackbarStore().show('Fehler beim Laden der Benachrichtigungseinstellungen', 'error')
  }
}

async function save() {
  loading.value = true
  try {
    const { data } = await httpClient.put('/user/me/notification-preferences', {
      notification_preferences: preferences.value
    })
    if (data && data.status === 'ok') {
      originalPreferences.value = { ...preferences.value }
      authStore.updateUser({
        ...authStore.user,
        notification_preferences: snakeCaseKeys(data.notificationPreferences ?? data.notification_preferences)
      } as any)
      useSnackbarStore().show('Benachrichtigungseinstellungen gespeichert', 'success')
      emit('saved', snakeCaseKeys(data.notificationPreferences ?? data.notification_preferences))
    }
  } catch (error) {
    console.error('Error saving notification preferences:', error)
    useSnackbarStore().show('Fehler beim Speichern der Benachrichtigungseinstellungen', 'error')
  } finally {
    loading.value = false
  }
}

function reset() {
  preferences.value = { ...originalPreferences.value }
}

onMounted(loadPreferences)
</script>

<style scoped>
.notification-preferences-form {
  padding: 16px;
}
</style>

<template>
  <div class="notification-preferences-form">
    <v-container>
        <v-row>
          <v-col cols="12">
            <v-card flat class="mb-4">
              <v-card-title class="text-subtitle-1 font-weight-bold">
                <v-icon left>mdi-bell</v-icon>
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
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12">
            <v-card flat class="mb-4">
              <v-card-title class="text-subtitle-1 font-weight-bold">
                <v-icon left>mdi-bell</v-icon>
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
              <v-card-title class="text-subtitle-1 font-weight-bold">
                <v-icon left>mdi-bell</v-icon>
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
              text
              @click="reset"
              :disabled="!hasChanges"
              class="mr-2"
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
              <v-icon left>mdi-content-save</v-icon>
              Speichern
            </v-btn>
          </v-col>
        </v-row>
      </v-container>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useAuthStore } from '@/store/auth'
import httpClient from '@/http/api'
import { useSnackbarStore } from '@/store/snackbar'

interface NotificationPreferences {
  trainer_upcoming: boolean
  trainer_cancel_alert: boolean
  participant_upcoming: boolean
  absence_alert: boolean
  admin_messages: boolean
}

export default defineComponent({
  name: 'NotificationPreferencesForm',
  data() {
    return {
      preferences: {
        trainer_upcoming: true,
        trainer_cancel_alert: true,
        participant_upcoming: true,
        absence_alert: true,
        admin_messages: true,
      } as NotificationPreferences,
      originalPreferences: {
        trainer_upcoming: true,
        trainer_cancel_alert: true,
        participant_upcoming: true,
        absence_alert: true,
        admin_messages: true,
      } as NotificationPreferences,
      loading: false,
    }
  },
  computed: {
    authStore() {
      return useAuthStore()
    },
    hasChanges(): boolean {
      return JSON.stringify(this.preferences) !== JSON.stringify(this.originalPreferences)
    },
  },
  async mounted() {
    await this.loadPreferences()
  },
  methods: {
    async loadPreferences() {
      try {
        const { data } = await httpClient.get('/user/me/notification-preferences')
        if (data && data.notification_preferences) {
          this.preferences = data.notification_preferences
          this.originalPreferences = { ...this.preferences }
        }
      } catch (error) {
        console.error('Error loading notification preferences:', error)
        useSnackbarStore().show('Fehler beim Laden der Benachrichtigungseinstellungen', 'error')
      }
    },
    async save() {
      this.loading = true
      try {
        const { data } = await httpClient.put('/user/me/notification-preferences', {
          notification_preferences: this.preferences,
        })
        if (data && data.status === 'ok') {
          this.originalPreferences = { ...this.preferences }
          this.authStore.updateUser({
            ...this.authStore.user,
            notification_preferences: data.notification_preferences,
          })
          useSnackbarStore().show('Benachrichtigungseinstellungen gespeichert', 'success')
          this.$emit('saved', data.notification_preferences)
        }
      } catch (error) {
        console.error('Error saving notification preferences:', error)
        useSnackbarStore().show('Fehler beim Speichern der Benachrichtigungseinstellungen', 'error')
      } finally {
        this.loading = false
      }
    },
    reset() {
      this.preferences = { ...this.originalPreferences }
    },
  },
})
</script>

<style scoped>
.notification-preferences-form {
  padding: 16px;
}
</style>

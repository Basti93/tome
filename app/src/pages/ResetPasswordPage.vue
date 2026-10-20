<template>
  <v-container>
    <v-row class="align-center justify-center">
      <v-col cols="12" md="6" sm="8">
        <v-card>
          <v-toolbar flat>
            <v-toolbar-title>Passwort zurücksetzen</v-toolbar-title>
          </v-toolbar>
          <v-divider></v-divider>

          <v-card-text>
            <!-- Success Message -->
            <template v-if="completed">
              <v-alert type="success" icon="mdi-check-circle" class="mb-4">
                <strong>Passwort zurückgesetzt</strong>
                <p class="mb-0">Dein Passwort wurde erfolgreich zurückgesetzt. Du kannst dich jetzt mit deinem neuen Passwort anmelden.</p>
              </v-alert>
              <v-btn color="primary" to="/login">Zur Anmeldung</v-btn>
            </template>

            <!-- Email Sent Confirmation -->
            <template v-else-if="emailSent">
              <v-alert type="success" icon="mdi-check-circle" class="mb-4">
                <strong>E-Mail gesendet</strong>
                <p class="mb-0">Wenn ein Konto mit {{ email }} existiert, haben wir einen Link zum Zurücksetzen des Passworts gesendet. Bitte prüfe deinen E-Mail-Posteingang.</p>
              </v-alert>
              <v-btn color="primary" @click="emailSent = false">Neuen Link anfordern</v-btn>
            </template>

            <!-- Reset Password Form (with token) -->
            <template v-else-if="token">
              <v-form v-model="passwordFormValid">
                <v-text-field
                    v-model="password"
                    :type="showPassword ? 'text' : 'password'"
                    :rules="passwordRules"
                    :append-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                    @click:append="showPassword = !showPassword"
                    label="Neues Passwort"
                    prepend-icon="mdi-shield-lock-outline"
                    :hint="getPasswordHint()"
                    persistent-hint
                    required
                ></v-text-field>

                <v-progress-linear
                    :value="passwordStrength"
                    :color="passwordStrengthColor"
                    height="8"
                    class="mb-2"
                ></v-progress-linear>

                <v-text-field
                    v-model="passwordConfirm"
                    :type="showPasswordConfirm ? 'text' : 'password'"
                    :rules="passwordConfirmRules"
                    :append-icon="showPasswordConfirm ? 'mdi-eye-off' : 'mdi-eye'"
                    @click:append="showPasswordConfirm = !showPasswordConfirm"
                    label="Passwort bestätigen"
                    prepend-icon="mdi-shield-lock-outline"
                    required
                ></v-text-field>

                <v-card-actions>
                  <v-spacer></v-spacer>
                  <v-btn to="/login" variant="text">Abbrechen</v-btn>
                  <v-btn
                      :disabled="!passwordFormValid || loading"
                      :loading="loading"
                      @click="resetPassword()"
                      color="primary"
                  >
                    Passwort speichern
                  </v-btn>
                </v-card-actions>
              </v-form>
            </template>

            <!-- Request Email Form -->
            <template v-else>
              <p class="mb-4">Gib deine E-Mail-Adresse ein, und wir senden dir einen Link zum Zurücksetzen deines Passworts.</p>
              <v-form v-model="emailFormValid">
                <v-text-field
                    v-model="email"
                    :rules="emailRules"
                    label="E-mail"
                    prepend-icon="mdi-email-outline"
                    required
                ></v-text-field>

                <v-card-actions>
                  <v-spacer></v-spacer>
                  <v-btn to="/login" variant="text">Abbrechen</v-btn>
                  <v-btn
                      :disabled="!emailFormValid || loading"
                      :loading="loading"
                      @click="requestResetEmail()"
                      color="primary"
                  >
                    Link senden
                  </v-btn>
                </v-card-actions>
              </v-form>
            </template>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useSnackbarStore } from '@/store/snackbar'
import httpClient from '@/http/api'
import { useRoute } from 'vue-router'
import { getPasswordHint, getPasswordValidationRules } from '@/constants/passwordPolicy'
import { apiErrorMessage } from '@/helpers/apiError'

const route = useRoute()

const email = ref('')
const token = ref('')
const password = ref('')
const passwordConfirm = ref('')
const loading = ref(false)
const completed = ref(false)
const emailSent = ref(false)
const showPassword = ref(false)
const showPasswordConfirm = ref(false)
const emailFormValid = ref(false)
const passwordFormValid = ref(false)
const emailRules = [
  (v: any) => !!v || 'E-mail wird benötigt',
  (v: any) => /.+@.+/.test(v) || 'E-mail muss gültig sein'
]
const passwordRules = getPasswordValidationRules()
const passwordConfirmRules = [
  (v: any) => !!v || 'Passwortbestätigung wird benötigt',
  (v: any) => v === password.value || 'Passwörter müssen übereinstimmen'
]

const passwordStrength = computed((): number => {
  if (!password.value) return 0
  let strength = 0
  if (password.value.length >= 8) strength += 25
  if (password.value.length >= 12) strength += 25
  if (/[A-Z]/.test(password.value)) strength += 25
  if (/[0-9!@#$%^&*]/.test(password.value)) strength += 25
  return strength
})

const passwordStrengthColor = computed((): string => {
  if (passwordStrength.value < 50) return 'error'
  if (passwordStrength.value < 75) return 'warning'
  return 'success'
})

function applyQuery() {
  const emailParam = route.query.email
  const tokenParam = route.query.token
  if (emailParam) email.value = emailParam as string
  if (tokenParam) {
    token.value = tokenParam as string
    emailSent.value = false
    completed.value = false
  }
}

applyQuery()

// a reset link opened in the same tab while this page is shown (same route, new query)
watch(() => route.query, applyQuery)

async function requestResetEmail() {
  try {
    loading.value = true
    await httpClient.post('/auth/recovery', { email: email.value })
    emailSent.value = true
    useSnackbarStore().show('Anfrage gesendet', 'success')
  } catch (error) {
    useSnackbarStore().show(apiErrorMessage(error, 'Fehler beim Versand der E-Mail'), 'error')
  } finally {
    loading.value = false
  }
}

async function resetPassword() {
  try {
    loading.value = true
    await httpClient.post('/auth/reset', {
      email: email.value,
      token: token.value,
      password: password.value,
      password_confirmation: passwordConfirm.value
    })
    completed.value = true
    useSnackbarStore().show('Passwort erfolgreich zurückgesetzt', 'success')
  } catch (error) {
    useSnackbarStore().show(apiErrorMessage(error, 'Fehler beim Zurücksetzen des Passworts'), 'error')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
</style>

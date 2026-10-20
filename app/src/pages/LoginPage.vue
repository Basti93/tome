<template>
  <v-container>
    <v-row class="align-center justify-center">
      <v-col cols="12" md="6" sm="8">
        <v-card>
          <v-toolbar flat>
            <v-toolbar-title>Anmelden</v-toolbar-title>
            <v-spacer></v-spacer>
          </v-toolbar>
          <v-divider></v-divider>

          <!-- Email Not Verified Alert -->
          <v-alert
              v-if="emailNotVerifiedError"
              type="warning"
              icon="mdi-email-alert"
              class="ma-4"
          >
            <v-row class="align-center">
              <v-col>
                <strong>E-Mail nicht verifiziert</strong>
                <p class="mb-0">Bitte bestätigen Sie Ihre E-Mail-Adresse, um sich anzumelden.</p>
                <p class="caption mb-0">{{ emailForResend }}</p>
              </v-col>
              <v-col cols="auto">
                <v-btn
                    size="small"
                    @click="resendVerificationEmail()"
                    :loading="resendLoading"
                    color="warning" variant="outlined"
                >
                  Erneut senden
                </v-btn>
              </v-col>
            </v-row>
          </v-alert>

          <v-form
              ref="form"
              v-model="valid"
          >
            <v-card-text>

              <v-text-field
                  v-model="email"
                  :rules="emailRules"
                  label="E-mail"
                  prepend-icon="email"
                  required
              ></v-text-field>

              <v-text-field
                  :type="showPassword ? 'text' : 'password'"
                  :rules="passwordRules"
                  v-model="password"
                  prepend-icon="security"
                  :append-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                  @click:append="showPassword = !showPassword"
                  label="Passwort"
                  @keypress.enter.prevent="login()"
                  required
              ></v-text-field>

              <div class="text-right mb-2">
                <router-link to="/reset-password" class="text-decoration-none">
                  <small>Passwort vergessen?</small>
                </router-link>
              </div>

            </v-card-text>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn
                  elevation="1"
                  :disabled="!valid || loading"
                  :loading="loading"
                  v-on:click="login()"
                  color="primary"
              >
                Anmelden
              </v-btn>
            </v-card-actions>
          </v-form>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { computed, onMounted, onUpdated, ref } from 'vue'
import { useAuthStore } from '@/store/auth'
import { useCookieAuthStore } from '@/store/cookieAuth'
import { useSnackbarStore } from '@/store/snackbar'
import httpClient from '@/http/api'
import { refreshMasterData } from '@/helpers/refreshMasterData'
import { useRouter, useRoute } from 'vue-router'
import User from '@/models/User'
import { analytics } from '@/services/analytics'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const valid = ref(true)
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)
const emailNotVerifiedError = ref(false)
const emailForResend = ref('')
const resendLoading = ref(false)
const passwordRules = [(v: string) => !!v || 'Wird benötigt']
const email = ref('')
const emailRules = [
  (v: string) => !!v || 'E-mail wird benötigt',
  (v: string) => /.+@.+/.test(v) || 'E-mail muss gültig sein'
]

const loggedInUser = computed(() => authStore.user)

function checkCurrentLogin() {
  if (loggedInUser.value) {
    router.replace((route.query.redirect as string) || '/')
  }
}

async function login() {
  try {
    loading.value = true
    const { data } = await httpClient.post('/auth/login', { email: email.value, password: password.value })
    if (data.status === 'error') {
      if (data.code === 'email_not_verified') {
        emailNotVerifiedError.value = true
        emailForResend.value = email.value
        useSnackbarStore().show('E-Mail-Adresse nicht verifiziert. Bitte verifizieren Sie Ihre E-Mail.', 'error')
      } else {
        useSnackbarStore().show(data.message, 'error')
      }
      useAuthStore().logout()
    } else if (!data.user) {
      useSnackbarStore().show('Falsches Passwort oder E-Mail!', 'error')
      useAuthStore().logout()
    } else {
      emailNotVerifiedError.value = false
      const user = User.from(JSON.stringify(data.user))
      useAuthStore().login(user)
      useCookieAuthStore().eraseCookieUser()
      // the cached master data still holds the abbreviated names of the anonymous visitor
      await refreshMasterData()
      useSnackbarStore().show('Erfolgreich angemeldet', 'success')
      // Track successful login
      analytics.trackEvent('Authentication', 'Login')
      router.replace((route.query.redirect as string) || '/')
    }
  } catch (err) {
    const error = err as any
    const status = error?.status ?? error?.response?.status
    const data = error?.data ?? error?.response?.data

    if (status === 403 && data?.code === 'email_not_verified') {
      emailNotVerifiedError.value = true
      emailForResend.value = email.value
      useSnackbarStore().show('E-Mail-Adresse nicht verifiziert. Bitte verifizieren Sie Ihre E-Mail.', 'error')
    } else if (status === 429) {
      useSnackbarStore().show('Zu viele Anmeldeversuche. Bitte warten Sie einen Moment.', 'error')
    } else if (data?.errors) {
      const first = Object.values(data.errors)[0]
      const message = Array.isArray(first) ? first[0] : first
      useSnackbarStore().show(message as string, 'error')
    } else if (data?.message) {
      useSnackbarStore().show(data.message, 'error')
    } else {
      useSnackbarStore().show('Anmeldung fehlgeschlagen', 'error')
    }
  } finally {
    loading.value = false
  }
}

async function resendVerificationEmail() {
  try {
    resendLoading.value = true
    await httpClient.post('/auth/email/send', {
      email: emailForResend.value
    })
    useSnackbarStore().show(
      'Verifizierungs-E-Mail erneut gesendet. Bitte überprüfen Sie Ihren E-Mail-Eingang.',
      'success'
    )
  } catch (err) {
    const error = err as any
    console.log(error)
    useSnackbarStore().show(error?.data?.message || 'Fehler beim Versand der E-Mail', 'error')
  } finally {
    resendLoading.value = false
  }
}

onMounted(() => {
  try {
    const el = document.querySelector('input')
    if (el) el.focus()
  } catch (e) {
    // focus failed
  }
})
onUpdated(checkCurrentLogin)

checkCurrentLogin()
</script>

<style scoped>

</style>

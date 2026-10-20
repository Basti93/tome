<template>
  <v-container>
    <v-row class="align-center justify-center">
      <v-col cols="12" md="8">
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>E-Mail-Adresse verifizieren</v-toolbar-title>
          </v-toolbar>
          <v-divider></v-divider>
          <v-card-text>
            <v-card>
              <v-card-text>
                <div v-if="loading">
                  <v-progress-circular indeterminate color="primary" size="50" class="mx-auto d-flex"></v-progress-circular>
                  <p class="text-center mt-4">Verifiziere deine E-Mail-Adresse...</p>
                </div>
                <div v-else>
                  <v-alert type="error" icon="mdi-alert-circle">
                    <h2>Verifizierung fehlgeschlagen</h2>
                    <p>{{ errorMessage }}</p>
                  </v-alert>
                  <v-btn color="primary" to="/login" class="mt-4">
                    Zurück zur Anmeldung
                  </v-btn>
                  <v-btn color="secondary" @click="resendDialog = true" class="mt-4 ml-2">
                    E-Mail erneut senden
                  </v-btn>
                </div>
              </v-card-text>
            </v-card>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    <v-dialog v-model="resendDialog" max-width="400">
      <v-card>
        <v-card-title>E-Mail erneut senden</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="resendEmail"
            label="E-Mail-Adresse"
            type="email"
            autofocus
            @keyup.enter="submitResend"
          ></v-text-field>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="resendDialog = false" variant="text">Abbrechen</v-btn>
          <v-btn color="primary" :loading="resendLoading" @click="submitResend">Senden</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import httpClient from '@/http/api'
import { apiErrorMessage } from '@/helpers/apiError'
import { useSnackbarStore } from '@/store/snackbar'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const errorMessage = ref('')
const resendLoading = ref(false)
const resendDialog = ref(false)
const resendEmail = ref('')

onMounted(async () => {
  try {
    const token = route.query.token
    if (!token) {
      errorMessage.value = 'Kein Verifizierungstoken gefunden.'
      loading.value = false
      return
    }

    const response = await httpClient.post('/auth/email/verify', {
      token: token
    })

    if (response.status === 200) {
      useSnackbarStore().show('E-Mail erfolgreich verifiziert!', 'success')
      router.push('/login')
    }
  } catch (err) {
    const error = err as any
    console.log(error)
    errorMessage.value = apiErrorMessage(error, 'Es gab einen Fehler bei der Verifizierung deiner E-Mail.')
  } finally {
    loading.value = false
  }
})

async function submitResend() {
  if (!resendEmail.value) return
  try {
    resendLoading.value = true
    await httpClient.post('/auth/email/send', {
      email: resendEmail.value
    })
    resendDialog.value = false
    resendEmail.value = ''
    useSnackbarStore().show('Verifizierungs-E-Mail erneut gesendet', 'success')
  } catch (err) {
    const error = err as any
    console.log(error)
    useSnackbarStore().show(apiErrorMessage(error, 'Fehler beim Versand der E-Mail'), 'error')
  } finally {
    resendLoading.value = false
  }
}
</script>

<style scoped>
</style>

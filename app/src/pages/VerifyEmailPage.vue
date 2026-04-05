<template>
  <v-container>
    <v-row align="center" justify="center">
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
                  <v-btn color="primary" to="/signup" class="mt-4">
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
          <v-btn text @click="resendDialog = false">Abbrechen</v-btn>
          <v-btn color="primary" :loading="resendLoading" @click="submitResend">Senden</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script>
import httpClient from '@/http/api'
import { useSnackbarStore } from '@/store/snackbar'

export default {
  name: "VerifyEmailPage",
  data: () => ({
    loading: true,
    errorMessage: '',
    resendLoading: false,
    resendDialog: false,
    resendEmail: '',
  }),
  mounted: async function() {
    try {
      const token = this.$route.query.token
      if (!token) {
        this.errorMessage = 'Kein Verifizierungstoken gefunden.'
        this.loading = false
        return
      }

      const response = await httpClient.post('/auth/email/verify', {
        token: token
      })

      if (response.status === 200) {
        useSnackbarStore().show('E-Mail erfolgreich verifiziert!', 'success')
        this.$router.push('/login')
      }
    } catch (error) {
      console.log(error)
      this.errorMessage = error?.data?.message || 'Es gab einen Fehler bei der Verifizierung deiner E-Mail.'
      this.verified = false
    } finally {
      this.loading = false
    }
  },
  methods: {
    async submitResend() {
      if (!this.resendEmail) return
      try {
        this.resendLoading = true
        await httpClient.post('/auth/email/send', {
          email: this.resendEmail
        })
        this.resendDialog = false
        this.resendEmail = ''
        useSnackbarStore().show('Verifizierungs-E-Mail erneut gesendet', 'success')
      } catch (error) {
        console.log(error)
        useSnackbarStore().show(error?.data?.message || 'Fehler beim Versand der E-Mail', 'error')
      } finally {
        this.resendLoading = false
      }
    },
  },
}
</script>

<style scoped>
</style>

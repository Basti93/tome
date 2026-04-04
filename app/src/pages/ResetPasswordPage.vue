<template>
  <v-container>
    <v-row align="center" justify="center">
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
                <p class="mb-0">Ihr Passwort wurde erfolgreich zurückgesetzt. Sie können sich jetzt mit Ihrem neuen Passwort anmelden.</p>
              </v-alert>
              <v-btn color="primary" to="/login">Zur Anmeldung</v-btn>
            </template>

            <!-- Email Sent Confirmation -->
            <template v-else-if="emailSent">
              <v-alert type="success" icon="mdi-check-circle" class="mb-4">
                <strong>E-Mail gesendet</strong>
                <p class="mb-0">Bitte überprüfen Sie Ihren E-Mail-Posteingang. Wir haben einen Passwort-Zurücksetzen-Link an {{ email }} gesendet.</p>
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
                    prepend-icon="security"
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
                    prepend-icon="security"
                    required
                ></v-text-field>

                <v-card-actions>
                  <v-spacer></v-spacer>
                  <v-btn to="/login" text>Abbrechen</v-btn>
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
              <p class="mb-4">Geben Sie Ihre E-Mail-Adresse ein, und wir senden Ihnen einen Link zum Zurücksetzen Ihres Passworts.</p>
              <v-form v-model="emailFormValid">
                <v-text-field
                    v-model="email"
                    :rules="emailRules"
                    label="E-mail"
                    prepend-icon="email"
                    required
                ></v-text-field>

                <v-card-actions>
                  <v-spacer></v-spacer>
                  <v-btn to="/login" text>Abbrechen</v-btn>
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

<script lang="ts">
import { useSnackbarStore } from '@/store/snackbar'
import httpClient from '@/http/api'
import { useRouter, useRoute } from 'vue-router'
import { PASSWORD_POLICY } from '@/constants/passwordPolicy'

export default {
  name: "ResetPasswordPage",
  setup() {
    const router = useRouter()
    const route = useRoute()
    return { router, route, PASSWORD_POLICY }
  },
  data() {
    return {
      email: '',
      token: '',
      password: '',
      passwordConfirm: '',
      loading: false,
      completed: false,
      emailSent: false,
      showPassword: false,
      showPasswordConfirm: false,
      emailFormValid: false,
      passwordFormValid: false,
      emailRules: [
        (v: any) => !!v || 'E-mail wird benötigt',
        (v: any) => /.+@.+/.test(v) || 'E-mail muss gültig sein'
      ],
      passwordRules: [
        (v: any) => !!v || 'Passwort wird benötigt',
        (v: any) => (v && v.length >= 8) || 'Passwort muss mindestens 8 Zeichen lang sein',
      ],
      passwordConfirmRules: [
        (v: any) => !!v || 'Passwortbestätigung wird benötigt',
        (v: any) => v === (this as any).password || 'Passwörter müssen übereinstimmen',
      ],
    }
  },
  computed: {
    passwordStrength(): number {
      if (!this.password) return 0
      let strength = 0
      if (this.password.length >= 8) strength += 25
      if (this.password.length >= 12) strength += 25
      if (/[A-Z]/.test(this.password)) strength += 25
      if (/[0-9!@#$%^&*]/.test(this.password)) strength += 25
      return strength
    },
    passwordStrengthColor(): string {
      if (this.passwordStrength < 50) return 'error'
      if (this.passwordStrength < 75) return 'warning'
      return 'success'
    },
  },
  created() {
    const emailParam = this.route.query.email
    const tokenParam = this.route.query.token

    if (emailParam) this.email = emailParam as string
    if (tokenParam) this.token = tokenParam as string
  },
  methods: {
    getPasswordHint(): string {
      return 'Mindestens 8 Zeichen, empfohlen: Großbuchstaben und Zahlen/Sonderzeichen'
    },
    async requestResetEmail() {
      try {
        this.loading = true
        await httpClient.post('/auth/recovery', { email: this.email })
        this.emailSent = true
        useSnackbarStore().show('Passwort-Zurücksetzen-Link gesendet', 'success')
      } catch (error) {
        const data = (error as any)?.data ?? (error as any)?.response?.data
        if (data?.message) {
          useSnackbarStore().show(data.message, 'error')
        } else if (data?.errors) {
          const first = Object.values(data.errors)[0]
          const message = Array.isArray(first) ? first[0] : first
          useSnackbarStore().show(message, 'error')
        } else {
          useSnackbarStore().show('Fehler beim Versand der E-Mail', 'error')
        }
      } finally {
        this.loading = false
      }
    },
    async resetPassword() {
      try {
        this.loading = true
        await httpClient.post('/auth/reset', {
          email: this.email,
          token: this.token,
          password: this.password,
          password_confirmation: this.passwordConfirm,
        })
        this.completed = true
        useSnackbarStore().show('Passwort erfolgreich zurückgesetzt', 'success')
      } catch (error) {
        const data = (error as any)?.data ?? (error as any)?.response?.data
        if (data?.message) {
          useSnackbarStore().show(data.message, 'error')
        } else if (data?.errors) {
          const first = Object.values(data.errors)[0]
          const message = Array.isArray(first) ? first[0] : first
          useSnackbarStore().show(message, 'error')
        } else {
          useSnackbarStore().show('Fehler beim Zurücksetzen des Passworts', 'error')
        }
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style scoped>
</style>

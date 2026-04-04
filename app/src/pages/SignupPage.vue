<template>
  <v-container>
    <v-row
        align="center"
        justify="center"
    >
      <v-col cols="12" md="8">
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Registrieren (nur für Trainer)</v-toolbar-title>
          </v-toolbar>
          <v-divider></v-divider>
          <v-card-text>
          <v-card>
            <v-card-text>
              <div v-if="completed">
                <h2>Erfolgreich registriert</h2>
                <p>Bitte verifizieren Sie Ihre E-Mail-Adresse. Wir haben einen Bestätigungslink an {{ email }} gesendet.</p>
                <p>Klicken Sie auf den Link in der E-Mail, um Ihr Konto zu aktivieren.</p>
                <v-btn
                    color="primary"
                    to="/"
                >
                  Zur Startseite
                </v-btn>

              </div>
              <v-form
                  ref="form"
                  v-model="valid"
                  v-else
              >
                <v-card-text>
                  <v-text-field
                      v-model="firstName"
                      :rules="nameRules"
                      label="Vorname"
                      prepend-icon="account_circle"
                      required
                  ></v-text-field>

                  <v-text-field
                      v-model="familyName"
                      :rules="nameRules"
                      prepend-icon="account_circle"
                      label="Nachname"
                      required
                  ></v-text-field>

                  <v-text-field
                      v-model="email"
                      :rules="emailRules"
                      label="E-mail"
                      prepend-icon="email"
                      required
                  ></v-text-field>

                  <v-menu
                      ref="birthdateMenu"
                      :close-on-content-click="false"
                      v-model="birthdateMenu">
                    <template v-slot:activator="{ props }">
                      <v-text-field
                          v-model="birthdateFormatted"
                          :rules="birthdateRules"
                          required
                          label="Geburtsdatum"
                          prepend-icon="event"
                          readonly
                          v-bind="props"
                      ></v-text-field>
                    </template>
                    <v-date-picker
                        v-model="birthdate"
                        @input="birthdateMenu = false"
                        ref="birthdatePicker"
                        :max="new Date().toISOString().substr(0, 10)"
                        min="1950-01-01">
                    </v-date-picker>
                  </v-menu>

                  <v-text-field
                      :type="showPassword ? 'text' : 'password'"
                      :rules="passwordRules"
                      v-model="password"
                      prepend-icon="security"
                      :append-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                      @click:append="showPassword = !showPassword"
                      label="Passwort"
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
                  <p class="caption mb-3" :style="{ color: passwordStrengthColor }">
                    Passwort-Sicherheit: {{ passwordStrengthLabel }}
                  </p>

                  <v-text-field
                      :type="showPasswordConfirm ? 'text' : 'password'"
                      :rules="[ confirmPassword ]"
                      v-model="passwordConfirm"
                      prepend-icon="security"
                      :append-icon="showPasswordConfirm ? 'mdi-eye-off' : 'mdi-eye'"
                      @click:append="showPasswordConfirm = !showPasswordConfirm"
                      label="Passwort bestätigen"
                      required
                  ></v-text-field>
                </v-card-text>
                <v-card-actions>
                  <v-spacer></v-spacer>
                  <v-btn
                      :disabled="!valid || loading"
                      :loading="loading"
                      color="primary"
                      right
                      @click="signup()"
                  >
                    Registrieren
                  </v-btn>
                </v-card-actions>
              </v-form>
            </v-card-text>
          </v-card>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import {formatDate} from "../helpers/date-helpers"
import { PASSWORD_POLICY, getPasswordHint, getPasswordValidationRules } from '@/constants/passwordPolicy'
import { useSnackbarStore } from '@/store/snackbar'
import axios from '@/axios'
import moment from 'moment'

export default {
  setup() {
    return { moment, PASSWORD_POLICY, getPasswordHint };
  },
  name: "SignupPage",
  data: () => ({
    errors: [],
    valid: true,
    completed: false,
    firstName: null,
    familyName: null,
    password: null,
    passwordConfirm: null,
    showPassword: false,
    showPasswordConfirm: false,
    birthdateMenu: false,
    birthdate: null,
    nameRules: [
      v => !!v || 'Wird benötigt'
    ],
    passwordRules: getPasswordValidationRules(),
    birthdateRules: [
      v => !!v || 'Wird benötigt',
    ],
    email: '',
    emailRules: [
      v => !!v || 'E-mail wird benötigt',
      v => /.+@.+/.test(v) || 'E-mail muss gültig sein'
    ],
    loading: false,
  }),
  mounted: function () {
    try {
      const el = document.querySelector('input');
      if (el) el.focus();
    } catch (e) {
      // focus failed
    }
  },
  computed: {
    birthdateFormatted() {
      return this.formatDate(this.birthdate)
    },
    passwordStrength() {
      if (!this.password) return 0
      let strength = 0
      const minLength = PASSWORD_POLICY.min_length
      
      if (this.password.length >= minLength) strength += 20
      if (this.password.length >= minLength + 4) strength += 10
      if (PASSWORD_POLICY.require_lowercase && /[a-z]/.test(this.password)) strength += 15
      if (PASSWORD_POLICY.require_uppercase && /[A-Z]/.test(this.password)) strength += 15
      if (PASSWORD_POLICY.require_digits && /[0-9]/.test(this.password)) strength += 15
      if (PASSWORD_POLICY.require_special) {
        const specialRegex = new RegExp(`[${PASSWORD_POLICY.special_chars.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')}]`)
        if (specialRegex.test(this.password)) strength += 15
      }
      // Extra points for very secure passwords
      const hasOtherSpecial = /[^A-Za-z0-9@$!%*?&]/.test(this.password)
      if (hasOtherSpecial) strength += 10
      
      return Math.min(strength, 100)
    },
    passwordStrengthColor() {
      if (this.passwordStrength < 30) return '#f44336' // red
      if (this.passwordStrength < 60) return '#ff9800' // orange
      if (this.passwordStrength < 80) return '#ffeb3b' // yellow
      return '#4caf50' // green
    },
    passwordStrengthLabel() {
      if (this.passwordStrength < 30) return 'Schwach'
      if (this.passwordStrength < 60) return 'Mittel'
      if (this.passwordStrength < 80) return 'Gut'
      return 'Stark'
    },
  },
  methods: {
    confirmPassword(password) {
      if (!password) {
        return "Bitte passwort bestätigen"
      }
      if (this.password === password) {
        return true;
      } else {
        return "Passwörter müssen identisch sein!"
      }
    },
    async signup() {
      try {
        this.loading = true
        await axios.post('/auth/signup', {
          firstName: this.firstName,
          familyName: this.familyName,
          email: this.email,
          birthdate: moment(this.birthdate, 'YYYY-MM-DDTHH:mm').format("YYYY-MM-DDTHH:mm:ss"),
          password: this.password,
          password_confirmation: this.passwordConfirm,
        });
        this.completed = true;
        useSnackbarStore().show("Erfolgreich registriert", "success")
      } catch (error) {
        console.log(error);
        if (error?.data?.errors) {
          // Handle validation errors from Laravel
          Object.entries(error.data.errors).forEach(([field, messages]) => {
            const message = Array.isArray(messages) ? messages[0] : messages
            useSnackbarStore().show(`${field}: ${message}`, "error")
          })
        } else if (error?.data?.message) {
          useSnackbarStore().show(error.data.message, "error")
        } else {
          useSnackbarStore().show("Registrierung fehlgeschlagen", "error")
        }
      } finally {
        this.loading = false
      }
    },
    formatDate,
  },
}
</script>

<style scoped>

</style>

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
                <p>Sobald du von einem Admin bestätigt wurdest erhältst du eine E-Mail und kannst dich anmelden.</p>
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
                      v-model="birthdateMenu"
                      :rules="birthdateRules"
                      required>
                    <template v-slot:activator="{ on }">
                      <v-text-field
                          v-model="birthdateFormatted"
                          required
                          label="Geburtsdatum"
                          prepend-icon="event"
                          readonly
                          v-on="on"
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
                      type="password"
                      :rules="passwordRules"
                      v-model="password"
                      prepend-icon="security"
                      label="Passwort"
                      required
                  ></v-text-field>

                  <v-text-field
                      type="password"
                      :rules="[ confirmPassword ]"
                      v-model="passwordConfirm"
                      prepend-icon="security"
                      label="Passwort bestätigen"
                      required
                  ></v-text-field>
                </v-card-text>
                <v-card-actions>
                  <v-spacer></v-spacer>
                  <v-btn
                      :disabled="!valid"
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

export default {
  name: "Signup",
  data: () => ({
    errors: [],
    valid: true,
    completed: false,
    firstName: null,
    familyName: null,
    password: null,
    passwordConfirm: null,
    birthdateMenu: false,
    birthdate: null,
    nameRules: [
      v => !!v || 'Wird benötigt'
    ],
    passwordRules: [
      v => !!v || 'Wird benötigt',
    ],
    birthdateRules: [
      v => !!v || 'Wird benötigt',
    ],
    email: '',
    emailRules: [
      v => !!v || 'E-mail wird benötigt',
      v => /.+@.+/.test(v) || 'E-mail muss gültig sein'
    ],
  }),
  mounted: function () {
    this.$refs.form.$children['0'].focus()
  },
  computed: {
    birthdateFormatted() {
      return this.formatDate(this.birthdate)
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
        await this.$http.post('/auth/signup', {
          firstName: this.firstName,
          familyName: this.familyName,
          email: this.email,
          birthdate: this.moment(this.birthdate, 'YYYY-MM-DDTHH:mm').format("YYYY-MM-DDTHH:mm:ss"),
          password: this.password,
        });
        this.completed = true;
        this.$emit("showSnackbar", "Erfolgreich registriert", "success");
      } catch (error) {
        console.log(error);
        if (error) {
          if (error.status_code === 422) {
            for (const validationError of error.errors) {
              console.info(validationError);
              this.$emit("showSnackbar", validationError, "error");
            }
          }
        }
      }
    },
    formatDate,
  },
  watch: {
    birthdateMenu(val) {
      val && setTimeout(() => (this.$refs.birthdatePicker.activePicker = 'YEAR'))
    },
  },
}
</script>

<style scoped>

</style>

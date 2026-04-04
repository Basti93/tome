<template>
  <v-container>
    <v-row align="center"
           justify="center">
      <v-col cols="12" md="6" sm="8">
        <v-card>
          <v-toolbar flat>
            <v-toolbar-title>Anmelden</v-toolbar-title>
            <v-spacer></v-spacer>
          </v-toolbar>
          <v-divider></v-divider>
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
                <router-link to="/forgot-password" class="text-decoration-none">
                  <small>Passwort vergessen?</small>
                </router-link>
              </div>

            </v-card-text>
            <v-card-actions>
              <v-spacer></v-spacer>
              <router-link to="/signup" class="text-decoration-none mr-2">
                <small>Registrieren</small>
              </router-link>
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

<script lang="ts">
import { useAuthStore } from '@/store/auth'
import { useCookieAuthStore } from '@/store/cookieAuth'
import { useSnackbarStore } from '@/store/snackbar'
import axios from '@/axios'
import { useRouter, useRoute } from 'vue-router'
import User from '@/models/User'
import { PASSWORD_POLICY } from '@/constants/passwordPolicy'

export default {
  name: "LoginPage",
  setup() {
    const router = useRouter()
    const route = useRoute()
    return { router, route, PASSWORD_POLICY }
  },
  data: () => ({
    valid: true,
    password: '',
    showPassword: false,
    loading: false,
    passwordRules: [
      v => !!v || 'Wird benötigt'
    ],
    email: '',
    emailRules: [
      v => !!v || 'E-mail wird benötigt',
      v => /.+@.+/.test(v) || 'E-mail muss gültig sein'
    ],
  }),
  computed: {
    loggedInUser() {
      return useAuthStore().user
    }
  },
  mounted: function () {
    try {
      const el = document.querySelector('input');
      if (el) el.focus();
    } catch (e) {
      // focus failed
    }
  },
  created() {
    this.checkCurrentLogin()
  },
  updated() {
    this.checkCurrentLogin()
  },
  methods: {
    checkCurrentLogin() {
      if (this.loggedInUser) {
        this.router.replace(this.route.query.redirect as string || '/')
      }
    },
    async login() {
      try {
        this.loading = true
        const {data} = await axios.post('/auth/login', {email: this.email, password: this.password})
        if (!data.token) {
          useSnackbarStore().show("Falsches Passwort oder E-Mail!", "error")
          useAuthStore().logout()
        } else {
          const user = User.from(JSON.stringify(data.user))
          useAuthStore().login(user, data.token)
          useCookieAuthStore().eraseCookieUser()
          useSnackbarStore().show("Erfolgreich angemeldet", "success")
          this.router.replace(this.route.query.redirect as string || '/')
        }
      } catch (error) {
        if (error?.data?.message) {
          useSnackbarStore().show(error.data.message, "error")
        } else {
          useSnackbarStore().show("Anmeldung fehlgeschlagen", "error")
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

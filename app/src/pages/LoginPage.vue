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
                  required
              ></v-text-field>

              <v-text-field
                  type="password"
                  :rules="passwordRules"
                  v-model="password"
                  label="Passwort"
                  @keypress.enter.prevent="login()"
                  required
              ></v-text-field>

            </v-card-text>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn
                  elevation="1"
                  :disabled="!valid"
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

export default {
  name: "LoginPage",
  setup() {
    const router = useRouter()
    const route = useRoute()
    return { router, route }
  },
  data: () => ({
    valid: true,
    password: '',
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
        const {data} = await axios.post('/auth/login', {email: this.email, password: this.password})
        if (!data.token) {
          useSnackbarStore().show("Falsches Passwort oder E-Mail!", "error")
          useAuthStore().logout()
        } else {
          useAuthStore().login({token: data.token, user: JSON.stringify(data.user)})
          useCookieAuthStore().eraseCookieUser()
          useSnackbarStore().show("Erfolgreich angemeldet", "success")
          this.router.replace(this.route.query.redirect as string || '/')
        }
      } catch (error) {
        useSnackbarStore().show("Anmeldung fehlgeschlagen", "error")
      }
    },
  },
}
</script>

<style scoped>

</style>

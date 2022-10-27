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
import Vue from "vue";
import {mapGetters} from 'vuex'

export default Vue.extend({
  name: "LoginPage",
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
    ...mapGetters({loggedInUser: 'loggedInUser'})
  },
  mounted: function () {
    this.$refs.form.$children['0'].focus()
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
        this.$router.replace(this.$route.query.redirect || '/')
      }
    },
    async login() {
      const {data} = await this.$http.post('/auth/login', {email: this.email, password: this.password});
      if (!data.token) {
        this.$emit("showSnackbar", "Falsches Passwort oder E-Mail!", "error");
        this.$store.dispatch('logout')
      } else {
        this.$store.dispatch('login', {token: data.token, user: JSON.stringify(data.user)})
        this.$store.dispatch('eraseCookieUser');
        this.$emit("showSnackbar", "Erfolgreich angemeldet", "success");
        this.$router.replace(this.$route.query.redirect || '/')
      }
    },
  },
})
</script>

<style scoped>

</style>

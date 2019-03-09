<template>
  <v-layout align-top justify-center>
    <v-flex xs12 sm8 md6>
      <v-card>
        <v-toolbar card prominent>
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
            :disabled="!valid"
            @click="login()"
            color="primary"
          >
            Anmelden
          </v-btn>
        </v-card-actions>
        </v-form>
      </v-card>
    </v-flex>
  </v-layout>
</template>

<script>
  import {mapGetters} from 'vuex'

  export default {
    name: "Login",
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
      login() {
        this.$http.post('/auth/login', {email: this.email, password: this.password})
          .then(request => this.checkLogIn(request))
          .catch(() => this.loginFailed())
      },
      checkLogIn(req) {
        if (!req.data.token) {
          this.loginFailed()
          return
        }
        localStorage.token = req.data.token
        localStorage.user = JSON.stringify(req.data.user)
        this.$store.dispatch('login')
        this.$emit("showSnackbar", "Erfolgreich angemeldet", "success");
        this.$router.replace(this.$route.query.redirect || '/')
      },
      loginFailed() {
        this.$store.dispatch('logout')
        delete localStorage.token
        delete localStorage.user
        this.$emit("showSnackbar", "Anmelden fehlgeschlagen! Bitte nochmal probieren", "error");
      }
    },
  }
</script>

<style scoped>

</style>

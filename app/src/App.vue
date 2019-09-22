<template>
  <div id="app">
    <v-app id="tome" dark>
      <Navigation/>
      <v-content
              :class="{'pt-0': $vuetify.breakpoint.smAndDown, 'pt-4': $vuetify.breakpoint.mdAndUp}">
        <v-container
                fluid
                fill-height
                :class="{'pa-0': $vuetify.breakpoint.smAndDown, 'pa-2': $vuetify.breakpoint.mdAndUp}">
          <SnackbarStore/>
          <v-snackbar
                  bottom
                  right
                  :text="text"
                  :color="color"
                  :value="snackbar"
          >
            {{text}}
          </v-snackbar>
          <v-snackbar
                  v-if="loggedInUser"
                  :value="pushPermissionSnackbarComputed"
                  bottom
                  right
                  :timeout="0"
          >
            Benachrichtigungen erlauben?
            <v-btn
                    color="primary"
                    flat
                    @click="requestPushPermission"
            >
              Erlauben
            </v-btn>
            <v-btn
                    color="primary"
                    flat
                    @click="pushPermissionSnackbar = false"
            >
              Nicht jetzt
            </v-btn>
          </v-snackbar>
          <v-slide-y-transition mode="out-in">
            <router-view v-on:showSnackbar="showSnackbar"/>
          </v-slide-y-transition>
        </v-container>
      </v-content>
      <v-footer app>
        <span><a style="text-decoration: none;" href="https://github.com/Basti93/tome">&copy; T.O.M.E. - 2019</a></span>
      </v-footer>
    </v-app>
  </div>
</template>

<script>
  import {mapGetters} from 'vuex'
  import Navigation from "@/components/Navigation.vue";
  import SnackbarStore from '@/components/SnackbarStore.vue'
  import firebase from './firebase-config'

  const { messaging } = firebase;

  export default {
    name: 'App',
    components: {Navigation, SnackbarStore},
    data() {
      return {
        snackbar: false,
        text: "",
        timeout: null,
        color: null,
        pushPermissionSnackbar: true,
      }
    },
    computed: {
      ...mapGetters({loggedInUser: 'loggedInUser'}),
      pushPermissionSnackbarComputed() {
        return this.pushPermissionSnackbar && Notification.permission !== 'granted' && Notification.permission !== 'denied';
      }
    },
    created() {
      const self = this;
      this.moment.locale('de')
      if (this.$isOffline) {
        this.$emit("showSnackbar", "Daten konnten nicht geladen werden! Stelle sicher dass du Internet hast.", "error");
      }

      if (self.loggedInUser) {
        this.getFirebaseToken();

        //refresh token
        messaging.onTokenRefresh(function() {
          messaging.getToken().then(function (token) {
            if (token) {
              self.sendTokenToServer(self.loggedInUser.id, token);
            } else {
              // Show permission request.
              self.pushPermissionSnackbar = true;
            }
          }).catch(function (err) {
            console.log('An error occurred while retrieving token. ', err);
          });
        });

        messaging.onMessage(function (payload) {
          console.log(payload);
        });
      }


    },
    methods: {
      showSnackbar(text, color = "info", timeout = 3000) {
        this.text = text
        this.timeout =
        this.color = color
        //snackbar fix
        //https://github.com/vuetifyjs/vuetify/issues/371
        this.snackbar = true
        setTimeout(() => {this.snackbar = false}, timeout)
      },
      requestPushPermission() {
        const self = this;
        Notification.requestPermission().then((permission) => {
          if (permission === 'granted') {
            console.log('Notification permission granted.');
            self.getFirebaseToken();
          } else {
            console.log('Unable to get permission to notify.');
          }
        });
      },
      getFirebaseToken() {
        let self = this;
        messaging.getToken().then(function (token) {
          if (token) {
            self.sendTokenToServer(self.loggedInUser.id, token);
            self.pushPermissionSnackbar = true;
          } else {
            // Show permission request.
            self.pushPermissionSnackbar = true;
          }
        });
      },
      sendTokenToServer(userId, token) {
        this.$http.post('/user/' + userId + '/notificationsubscribe', {token: token});
      },
    }

  }
</script>

<style>
  #app {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    text-align: center;
    margin-top: 60px;
  }
</style>

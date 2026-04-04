<template>
  <v-app>
    <Navigation/>
    <v-main>
      <v-container>
        <SnackbarStore/>
        <v-banner
            color="blue"
            sticky
            id="update-banner"
            style="display: none;"
        >
          <template v-slot:prepend>
            <v-avatar>
              <v-icon color="white">mdi-information</v-icon>
            </v-avatar>
          </template>
          Neue App Version verfügbar. Bitte aktualisieren :)
          <template v-slot:actions>
            <v-btn
              color="white"
              class="mr-2"
              @click="refreshPage()"
            >
              <v-icon start>mdi-refresh</v-icon>
              Aktualisieren
            </v-btn>
          </template>
        </v-banner>
        <v-snackbar
            v-if="currentUser"
            v-model="pushPermissionSnackbar"
            location="bottom right"
            :timeout="-1"
        >
          Benachrichtigungen erlauben?
          <template v-slot:actions>
            <v-btn
                color="primary"
                variant="text"
                @click="requestPushPermission"
            >
              Erlauben
            </v-btn>
            <v-btn
                color="primary"
                variant="text"
                @click="pushPermissionSnackbar = false"
            >
              Nicht jetzt
            </v-btn>
          </template>
        </v-snackbar>
        <router-view v-slot="{ Component }">
          <v-slide-y-transition mode="out-in">
            <component :is="Component" />
          </v-slide-y-transition>
        </router-view>
      </v-container>
    </v-main>
    <v-footer color="primary">
      <span><a style="text-decoration: none;"
               href="https://github.com/Basti93/tome">&copy; T.O.M.E. - {{ currentYear }}</a></span>
    </v-footer>
  </v-app>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import moment from 'moment'
import Navigation from '@/components/TomeNavigation.vue'
import SnackbarStore from '@/components/SnackbarStore.vue'
import { useSnackbarStore } from '@/store/snackbar'
import { useAuthStore } from '@/store/auth'
import { useCookieAuthStore } from '@/store/cookieAuth'

moment.locale('de')

const snackbarStore = useSnackbarStore()
const authStore = useAuthStore()
const cookieAuthStore = useCookieAuthStore()

const pushPermissionSnackbar = ref(true)
const currentYear = moment().year()

const currentUser = computed(() => {
  return authStore.user || cookieAuthStore.cookieUser || null
});

const pushPermissionSnackbarVisible = computed(() => {
  return isFirebaseSupported() && pushPermissionSnackbar.value &&
    Notification.permission !== 'granted' && Notification.permission !== 'denied'
});

function refreshPage() {
  window.location.reload()
}

function isFirebaseSupported() {
  return 'Notification' in window && 'serviceWorker' in navigator && 'PushManager' in window
}

function requestPushPermission() {
  Notification.requestPermission().then((permission) => {
    if (permission === 'granted') {
      console.log('Notification permission granted.')
      getFirebaseToken()
    } else {
      console.log('Unable to get permission to notify.')
    }
  })
}

function sendTokenToServer(userId: number, token: string) {
  import('@/axios').then(({ default: axios }) => {
    axios.post('/notifications/subscribe', { firebaseToken: token, userId })
  })
}

function getFirebaseToken() {
  import('@/firebase-config').then(({ default: messaging }) => {
    if (messaging) {
      import('firebase/messaging').then(({ getToken }) => {
        getToken(messaging).then((token: string) => {
          if (token && currentUser.value) {
            sendTokenToServer(currentUser.value.id, token)
            pushPermissionSnackbar.value = false
          } else {
            pushPermissionSnackbar.value = true
          }
        })
      })
    }
  })
}

onMounted(() => {
  if (currentUser.value && import.meta.env.PROD) {
    getFirebaseToken()
  }
});
</script>

<style>
#app {
  margin-top: 55px;
}
</style>

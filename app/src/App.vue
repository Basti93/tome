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
            v-if="pushPermissionSnackbarVisible"
            model-value
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
                @click="pushPermissionDismissed = true"
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
import { ref, computed, watch } from 'vue'
import dayjs from '@/helpers/dayjs'
import Navigation from '@/components/TomeNavigation.vue'
import SnackbarStore from '@/components/SnackbarStore.vue'
import { useSnackbarStore } from '@/store/snackbar'
import { useAuthStore } from '@/store/auth'
import { useCookieAuthStore } from '@/store/cookieAuth'
import { listenForForegroundMessages, registerPushToken, type PushStatus } from '@/services/push'

const snackbarStore = useSnackbarStore()
const authStore = useAuthStore()
const cookieAuthStore = useCookieAuthStore()

const currentYear = dayjs().year()

const currentUser = computed(() => {
  return authStore.user || cookieAuthStore.cookieUser || null
});

// Push notifications -------------------------------------------------------------------------
const pushStatus = ref<PushStatus | null>(null)
const pushPermissionDismissed = ref(false)
// the permission prompt is only offered while the browser has not decided yet
const pushPermissionSnackbarVisible = computed(() => pushStatus.value === 'needs-permission' && !pushPermissionDismissed.value)
let foregroundListening = false

async function startForegroundListener() {
  if (foregroundListening) return
  foregroundListening = await listenForForegroundMessages(({ title, body }) => {
    snackbarStore.show(body ? `${title}: ${body}` : title, 'info', 10000)
  })
}

/** Registers the FCM token for the user (no permission prompt) */
async function syncPushToken(requestPermission = false) {
  const user = currentUser.value
  if (!user) return
  pushStatus.value = await registerPushToken(user.id, requestPermission)
  if (pushStatus.value === 'subscribed') {
    pushPermissionDismissed.value = true
    startForegroundListener()
  } else if (pushStatus.value === 'denied' && requestPermission) {
    snackbarStore.show('Benachrichtigungen wurden blockiert. Du kannst sie in den Browser-Einstellungen erlauben.', 'warning')
  } else if (pushStatus.value === 'error' && requestPermission) {
    snackbarStore.show('Benachrichtigungen konnten nicht aktiviert werden.', 'error')
  }
}

// on app start and after login (user changes from null to a user)
watch(() => currentUser.value?.id, (id) => {
  if (id) syncPushToken()
}, { immediate: true })

function requestPushPermission() {
  return syncPushToken(true)
}

function refreshPage() {
  window.location.reload()
}
</script>

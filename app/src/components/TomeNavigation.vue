<template>
  <div>
    <v-navigation-drawer
        fixed
        v-model="drawer"
    >
      <v-container fluid text class="pt-6 text-center" center v-if="loggedInUser" v-show="xs">
        <ProfileImage :firstName="loggedInUser.firstName"
                      :familyName="loggedInUser.familyName"
                      :imagePath="loggedInUser.profileImageName"
                      size="50"
        ></ProfileImage>
        <div class="d-block">{{ loggedInUser.firstName }}&nbsp;{{ loggedInUser.familyName }}</div>
      </v-container>
      <v-divider></v-divider>
      <v-list v-model:opened="openedGroups" density="compact" class="pt-2">
        <template v-for="section in sections" :key="section.id">
          <!-- collapsible section (Verwaltung): collapsed by default, the state is remembered -->
          <v-list-group v-if="section.collapsible" :value="section.id">
            <template v-slot:activator="{ props }">
              <v-list-item v-bind="props" prepend-icon="mdi-cog-outline" :title="section.title"></v-list-item>
            </template>
            <v-list-item
                v-for="item in section.items"
                :key="item.to"
                :to="item.to"
                :prepend-icon="item.icon"
                :title="item.title"
                @click="drawer = false"
            ></v-list-item>
          </v-list-group>
          <template v-else>
            <v-list-subheader>{{ section.title }}</v-list-subheader>
            <v-list-item
                v-for="item in section.items"
                :key="item.to"
                :to="item.to"
                :prepend-icon="item.icon"
                :title="item.title"
                @click="drawer = false"
            ></v-list-item>
          </template>
        </template>
      </v-list>
      <!-- always visible, never inside a collapsed group -->
      <template v-slot:append>
        <v-divider></v-divider>
        <v-list density="compact">
          <v-list-item
              v-if="loggedInUser"
              to="/logout"
              prepend-icon="mdi-logout"
              title="Abmelden"
              @click="drawer = false"
          ></v-list-item>
          <v-list-item
              v-else
              to="/login"
              prepend-icon="mdi-account-circle"
              title="Anmelden"
              @click="drawer = false"
          ></v-list-item>
        </v-list>
      </template>
    </v-navigation-drawer>
    <v-app-bar
        color="primary"
        fixed>
      <v-app-bar-nav-icon @click.stop="drawer = !drawer"></v-app-bar-nav-icon>
      <v-toolbar-title class="tome-title">
        <router-link :to="hasRoles ? '/today' : '/'">{{ title }}</router-link>
      </v-toolbar-title>
      <v-spacer></v-spacer>
      <v-menu
          v-if="loggedInUser"
          location="bottom start"
          transition="scale-transition"
      >
        <template v-slot:activator="{ props }">
          <v-btn
              v-bind="props"
              v-show="mdAndUp" variant="text"
          >
            <span>{{ loggedInUser.firstName }}&nbsp;{{ loggedInUser.familyName }}</span>
            <ProfileImage :firstName="loggedInUser.firstName"
                          :familyName="loggedInUser.familyName"
                          :imagePath="loggedInUser.profileImageName"
                          size="32"
                          right
            ></ProfileImage>
          </v-btn>
        </template>
        <v-list>
          <v-list-item to="/profile">
            <template v-slot:prepend>
              <v-icon>mdi-cog</v-icon>
            </template>
            <v-list-item-title>Einstellungen</v-list-item-title>
          </v-list-item>
          <v-list-item to="/logout">
            <template v-slot:prepend>
              <v-icon>mdi-logout</v-icon>
            </template>
            <v-list-item-title>Abmelden</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>
    </v-app-bar>

    <!-- phones, trainers and admins: the most used pages one tap away -->
    <v-bottom-navigation v-if="showBottomNav" grow color="primary" class="tome-bottom-nav">
      <v-btn v-for="item in bottomNavItems" :key="item.to" :to="item.to" :prepend-icon="undefined">
        <v-icon>{{ item.icon }}</v-icon>
        <span>{{ item.title }}</span>
      </v-btn>
      <v-btn @click="drawer = !drawer">
        <v-icon>mdi-menu</v-icon>
        <span>Mehr</span>
      </v-btn>
    </v-bottom-navigation>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useDisplay } from 'vuetify'
import ProfileImage from '@/components/ProfileImage.vue'
import { useAuthStore } from '@/store/auth'
import { bottomNavItems } from '@/constants/navigation'
import { isTrainerOrAdmin, visibleSections } from '@/helpers/navigation'

const { xs, mdAndUp, smAndDown } = useDisplay()

const drawer = ref(false)
const title = import.meta.env.VITE_TITLE

const authStore = useAuthStore()
const loggedInUser = computed(() => authStore.user)
const hasRoles = computed(() => isTrainerOrAdmin(loggedInUser.value))
const sections = computed(() => visibleSections(loggedInUser.value))

// open state of the collapsible "Verwaltung" section, remembered per device (collapsed by default)
const OPENED_KEY = 'nav-opened-groups'
function readOpened(): string[] {
  try {
    const value = JSON.parse(localStorage.getItem(OPENED_KEY) ?? '[]')
    return Array.isArray(value) ? value : []
  } catch {
    return []
  }
}
const openedGroups = ref<string[]>(readOpened())
watch(openedGroups, (value) => {
  try {
    localStorage.setItem(OPENED_KEY, JSON.stringify(value))
  } catch {
    // storage unavailable: the section is simply collapsed again next time
  }
})

const showBottomNav = computed(() => smAndDown.value && hasRoles.value)
// the page needs room for the bar and the footer is hidden (see vuetify-compat.css)
watch(showBottomNav, (visible) => document.body.classList.toggle('has-bottom-nav', visible), { immediate: true })
</script>

<style scoped>
.tome-title a {
  text-decoration: none;
  color: #FFFFFF;
}
</style>

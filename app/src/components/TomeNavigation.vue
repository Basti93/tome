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
      <v-list dense class="pt-2">
        <v-list-item
            to="/"
            @click="drawer = false">
          <template v-slot:prepend>
            <v-icon>mdi-note-text</v-icon>
          </template>
          <v-list-item-title>Trainingsanmeldung</v-list-item-title>
        </v-list-item>
        <v-list-item
            v-show="hasRoles"
            to="/trainingsPrepare"
            @click="drawer = false">
          <template v-slot:prepend>
            <v-icon>mdi-calendar</v-icon>
          </template>
          <v-list-item-title>Vorbereiten</v-list-item-title>
        </v-list-item>
        <v-list-item
            v-show="hasRoles"
            to="/trainingsEvaluation"
            @click="drawer = false">
          <template v-slot:prepend>
            <v-icon>mdi-check-circle</v-icon>
          </template>
          <v-list-item-title>Nachbereiten</v-list-item-title>
        </v-list-item>
        <v-list-item
            v-show="hasRoles"
            to="/trainings"
            @click="drawer = false">
          <template v-slot:prepend>
            <v-icon>mdi-format-list-bulleted</v-icon>
          </template>
          <v-list-item-title>Trainings</v-list-item-title>
        </v-list-item>
        <v-list-item
            v-show="hasRoles"
            to="/trainingSeries"
            @click="drawer = false">
          <template v-slot:prepend>
            <v-icon>mdi-refresh</v-icon>
          </template>
          <v-list-item-title>Serien</v-list-item-title>
        </v-list-item>
        <v-list-item
            v-show="hasRoles"
            to="/users"
            @click="drawer = false">
          <template v-slot:prepend>
            <v-icon>mdi-account</v-icon>
          </template>
          <v-list-item-title>Benutzer</v-list-item-title>
        </v-list-item>
        <v-list-item
            v-show="hasRoles"
            to="/groups"
            @click="drawer = false">
          <template v-slot:prepend>
            <v-icon>mdi-account-multiple</v-icon>
          </template>
          <v-list-item-title>Gruppen</v-list-item-title>
        </v-list-item>
        <v-list-item
            v-show="hasRoles"
            to="/branches"
            @click="drawer = false">
          <template v-slot:prepend>
            <v-icon>mdi-chart-bubble</v-icon>
          </template>
          <v-list-item-title>Sparten</v-list-item-title>
        </v-list-item>
        <v-list-item
            v-show="hasRoles"
            to="/locations"
            @click="drawer = false">
          <template v-slot:prepend>
            <v-icon>mdi-map-marker</v-icon>
          </template>
          <v-list-item-title>Orte</v-list-item-title>
        </v-list-item>
        <v-list-item
            v-show="loggedInUser"
            to="/statistics"
            @click="drawer = false">
          <template v-slot:prepend>
            <v-icon>mdi-timeline</v-icon>
          </template>
          <v-list-item-title>Statistiken</v-list-item-title>
        </v-list-item>
        <v-list-item
            to="/calendar"
            @click="drawer = false">
          <template v-slot:prepend>
            <v-icon>mdi-calendar-month</v-icon>
          </template>
          <v-list-item-title>Kalender</v-list-item-title>
        </v-list-item>
        <!--
        <v-list-item
          v-show="loggedInUser"
          to="/feedback"
          @click="drawer = false">
          <v-list-item-action>
            <v-icon>mdi-comment-question-outline</v-icon>
          </v-list-item-action>

          <v-list-item-content>
            <v-list-item-title>Kummerkasten</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        -->
        <v-list-item
            to="/absenceForm"
            @click="drawer = false">
          <template v-slot:prepend>
            <v-icon>mdi-car</v-icon>
          </template>
          <v-list-item-title>Abwesenheit eintragen</v-list-item-title>
        </v-list-item>
        <v-list-item
            to="/groupsOverview"
            @click="drawer = false">
          <template v-slot:prepend>
            <v-icon>mdi-account-multiple</v-icon>
          </template>
          <v-list-item-title>Gruppenübersicht</v-list-item-title>
        </v-list-item>
        <v-list-item
            v-show="!loggedInUser"
            to="/login"
            @click="drawer = false">
          <template v-slot:prepend>
            <v-icon>mdi-account-circle</v-icon>
          </template>
          <v-list-item-title>Anmelden</v-list-item-title>
        </v-list-item>
        <v-list-item
            v-show="!loggedInUser"
            to="/signup"
            @click="drawer = false">
          <template v-slot:prepend>
            <v-icon>mdi-account-plus</v-icon>
          </template>
          <v-list-item-title>Registrieren</v-list-item-title>
        </v-list-item>
        <v-list-item
            v-show="loggedInUser"
            to="/profile"
            @click="drawer = false">
          <template v-slot:prepend>
            <v-icon>mdi-cog</v-icon>
          </template>
          <v-list-item-title>Profileinstellungen</v-list-item-title>
        </v-list-item>
        <v-list-item
            to="/info"
            @click="drawer = false">
          <template v-slot:prepend>
            <v-icon>mdi-information</v-icon>
          </template>
          <v-list-item-title>Über die App</v-list-item-title>
        </v-list-item>
        <v-list-item
            v-show="loggedInUser"
            to="/logout"
            @click="drawer = false">
          <template v-slot:prepend>
            <v-icon>mdi-logout</v-icon>
          </template>
          <v-list-item-title>Abmelden</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>
    <v-app-bar
        color="primary"
        fixed>
      <v-app-bar-nav-icon @click.stop="drawer = !drawer"></v-app-bar-nav-icon>
      <v-toolbar-title class="tome-title">
        <router-link to="/">{{ title }}</router-link>
      </v-toolbar-title>
      <v-spacer></v-spacer>
      <v-menu
          v-if="loggedInUser"
          location="bottom start"
          transition="scale-transition"
      >
        <template v-slot:activator="{ props }">
          <v-btn
              text
              v-bind="props"
              v-show="mdAndUp"
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
  </div>
</template>

<script>
import ProfileImage from "@/components/ProfileImage.vue";
import { useAuthStore } from '@/store/auth'
import { useMasterDataStore } from '@/store/masterData'
import { useDisplay } from 'vuetify'

export default {
  name: "TomeNavigation",
  components: {ProfileImage},
  setup() {
    const { xs, md } = useDisplay()
    return { xs, md }
  },
  data() {
    return {
      drawer: false,
      title: import.meta.env.VITE_TITLE,
    }
  },
  computed: {
    loggedInUser() {
      return useAuthStore().user
    },
    hasRoles: function () {
      return this.loggedInUser && (this.loggedInUser.isTrainer || this.loggedInUser.isAdmin);
    },
    currentUserGroupName: function () {
      let group = useMasterDataStore().getGroupById(this.loggedInUser.groupId);
      if (group) {
        return group.name
      }
      return undefined;
    },
  },
}
</script>

<style scoped>
.tome-title a {
  text-decoration: none;
  color: #FFFFFF;
}
</style>

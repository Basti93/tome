<template>
  <div>
    <v-navigation-drawer
        fixed
        v-model="drawer"
    >
      <v-container fluid text class="pt-6 text-center" center v-if="loggedInUser" v-show="$vuetify.breakpoint.xsOnly">
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
          <v-list-item-action>
            <v-icon>how_to_reg</v-icon>
          </v-list-item-action>

          <v-list-item-content>
            <v-list-item-title>Trainingsanmeldung</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        <v-list-item
            v-show="hasRoles"
            to="/trainingsPrepare"
            @click="drawer = false">
          <v-list-item-action>
            <v-icon>schedule</v-icon>
          </v-list-item-action>

          <v-list-item-content>
            <v-list-item-title>Vorbereiten</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        <v-list-item
            v-show="hasRoles"
            to="/trainingsEvaluation"
            @click="drawer = false">
          <v-list-item-action>
            <v-icon>fact_check</v-icon>
          </v-list-item-action>

          <v-list-item-content>
            <v-list-item-title>Nachbereiten</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        <v-list-item
            v-show="hasRoles"
            to="/trainings"
            @click="drawer = false">
          <v-list-item-action>
            <v-icon>list</v-icon>
          </v-list-item-action>

          <v-list-item-content>
            <v-list-item-title>Trainings</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        <v-list-item
            v-show="hasRoles"
            to="/trainingSeries"
            @click="drawer = false">
          <v-list-item-action>
            <v-icon>update</v-icon>
          </v-list-item-action>

          <v-list-item-content>
            <v-list-item-title>Serien</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        <v-list-item
            v-show="hasRoles"
            to="/users"
            @click="drawer = false">
          <v-list-item-action>
            <v-icon>group</v-icon>
          </v-list-item-action>

          <v-list-item-content>
            <v-list-item-title>Benutzer</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        <v-list-item
            v-show="hasRoles"
            to="/groups"
            @click="drawer = false">
          <v-list-item-action>
            <v-icon>groups</v-icon>
          </v-list-item-action>

          <v-list-item-content>
            <v-list-item-title>Gruppen</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        <v-list-item
            v-show="hasRoles"
            to="/branches"
            @click="drawer = false">
          <v-list-item-action>
            <v-icon>bubble_chart</v-icon>
          </v-list-item-action>

          <v-list-item-content>
            <v-list-item-title>Sparten</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        <v-list-item
            v-show="hasRoles"
            to="/locations"
            @click="drawer = false">
          <v-list-item-action>
            <v-icon>add_location</v-icon>
          </v-list-item-action>

          <v-list-item-content>
            <v-list-item-title>Orte</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        <v-list-item
            v-show="loggedInUser"
            to="/statistics"
            @click="drawer = false">
          <v-list-item-action>
            <v-icon>timeline</v-icon>
          </v-list-item-action>

          <v-list-item-content>
            <v-list-item-title>Statistiken</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        <v-list-item
            to="/calendar"
            @click="drawer = false">
          <v-list-item-action>
            <v-icon>event</v-icon>
          </v-list-item-action>

          <v-list-item-content>
            <v-list-item-title>Kalender</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        <!--
        <v-list-item
          v-show="loggedInUser"
          to="/feedback"
          @click="drawer = false">
          <v-list-item-action>
            <v-icon>feedback</v-icon>
          </v-list-item-action>

          <v-list-item-content>
            <v-list-item-title>Kummerkasten</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        -->
        <v-list-item
            to="/absenceForm"
            @click="drawer = false">
          <v-list-item-action>
            <v-icon>directions_car</v-icon>
          </v-list-item-action>

          <v-list-item-content>
            <v-list-item-title>Abwesenheit eintragen</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        <v-list-item
            to="/groupsOverview"
            @click="drawer = false">
          <v-list-item-action>
            <v-icon>groups</v-icon>
          </v-list-item-action>

          <v-list-item-content>
            <v-list-item-title>Gruppenübersicht</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        <v-list-item
            v-show="!loggedInUser"
            to="/login"
            @click="drawer = false">
          <v-list-item-action>
            <v-icon>account_circle</v-icon>
          </v-list-item-action>

          <v-list-item-content>
            <v-list-item-title>Anmelden</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        <v-list-item
            v-show="!loggedInUser"
            to="/signup"
            @click="drawer = false">
          <v-list-item-action>
            <v-icon>person_add</v-icon>
          </v-list-item-action>

          <v-list-item-content>
            <v-list-item-title>Registrieren</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        <v-list-item
            v-show="loggedInUser"
            to="/profile"
            @click="drawer = false">
          <v-list-item-action>
            <v-icon>settings</v-icon>
          </v-list-item-action>

          <v-list-item-content>
            <v-list-item-title>Profileinstellungen</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        <v-list-item
            to="/info"
            @click="drawer = false">
          <v-list-item-action>
            <v-icon>info</v-icon>
          </v-list-item-action>

          <v-list-item-content>
            <v-list-item-title>Über die App</v-list-item-title>
          </v-list-item-content>
        </v-list-item>
        <v-list-item
            v-show="loggedInUser"
            to="/logout"
            @click="drawer = false">
          <v-list-item-action>
            <v-icon>exit_to_app</v-icon>
          </v-list-item-action>

          <v-list-item-content>
            <v-list-item-title>Abmelden</v-list-item-title>
          </v-list-item-content>
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
          origin="center center"
          transition="scale-transition"
          bottom left>
        <template v-slot:activator="{ on }">
          <v-btn
              text
              v-on="on"
              v-show="$vuetify.breakpoint.mdAndUp"
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
            <v-list-item-action>
              <v-icon>settings</v-icon>
            </v-list-item-action>
            <v-list-item-content>
              <v-list-item-title>Einstellungen</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item to="/logout">
            <v-list-item-action>
              <v-icon>exit_to_app</v-icon>
            </v-list-item-action>
            <v-list-item-content>
              <v-list-item-title>Abmelden</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list>
      </v-menu>
    </v-app-bar>
  </div>
</template>

<script>
import {mapGetters} from 'vuex'
import ProfileImage from "@/components/ProfileImage.vue";

export default {
  name: "TomeNavigation",
  components: {ProfileImage},
  data() {
    return {
      drawer: false,
      title: process.env.VUE_APP_TITLE,
    }
  },
  computed: {
    ...mapGetters({loggedInUser: 'loggedInUser'}),
    ...mapGetters('masterData', {getGroupById: 'getGroupById'}),
    hasRoles: function () {
      return this.loggedInUser && (this.loggedInUser.isTrainer || this.loggedInUser.isAdmin);
    },
    currentUserGroupName: function () {
      let group = this.getGroupById(this.loggedInUser.groupId);
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

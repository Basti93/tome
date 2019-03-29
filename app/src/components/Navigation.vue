<template>
  <div>
  <v-navigation-drawer
    fixed
    v-model="drawer"
    app
    :clipped="$vuetify.breakpoint.lgAndUp"
  >
    <v-card flat class="pt-4" v-if="loggedInUser" v-show="$vuetify.breakpoint.xsOnly">
      <v-icon x-large center>account_circle</v-icon>
      <span>{{loggedInUser.firstName}}&nbsp;{{loggedInUser.familyName}}</span>
    </v-card>
    <v-divider></v-divider>
    <v-list dense class="pt-2">
      <v-list-tile
        to="/"
        @click="drawer = false">
        <v-list-tile-action>
          <v-icon>how_to_reg</v-icon>
        </v-list-tile-action>

        <v-list-tile-content>
          <v-list-tile-title>Trainingsanmeldung</v-list-tile-title>
        </v-list-tile-content>
      </v-list-tile>
      <v-list-tile
        v-show="hasRoles"
        to="/trainings"
        @click="drawer = false">
        <v-list-tile-action>
          <v-icon>event</v-icon>
        </v-list-tile-action>

        <v-list-tile-content>
          <v-list-tile-title>Trainings</v-list-tile-title>
        </v-list-tile-content>
      </v-list-tile>
      <v-list-tile
        v-show="hasRoles"
        to="/users"
        @click="drawer = false">
        <v-list-tile-action>
          <v-icon>person</v-icon>
        </v-list-tile-action>

        <v-list-tile-content>
          <v-list-tile-title>Benutzer</v-list-tile-title>
        </v-list-tile-content>
      </v-list-tile>
      <v-list-tile
        v-show="hasRoles"
        to="/approveUsers"
        @click="drawer = false">
        <v-list-tile-action>
          <v-badge right color="red" v-if="nonApprovedUserCount > 0">
            <v-icon>
              assignment_turned_in
            </v-icon>
            <span slot="badge">{{nonApprovedUserCount}}</span>
          </v-badge>
          <v-icon v-if="nonApprovedUserCount === 0">
            assignment_turned_in
          </v-icon>
        </v-list-tile-action>

        <v-list-tile-content>
          <v-list-tile-title>Benutzer Freischalten</v-list-tile-title>
        </v-list-tile-content>
      </v-list-tile>
      <v-list-tile
        v-show="loggedInUser"
        to="/statistics"
        @click="drawer = false">
        <v-list-tile-action>
          <v-icon>timeline</v-icon>
        </v-list-tile-action>

        <v-list-tile-content>
          <v-list-tile-title>Statistiken</v-list-tile-title>
        </v-list-tile-content>
      </v-list-tile>
      <!--
      <v-list-tile
        v-show="loggedInUser"
        to="/feedback"
        @click="drawer = false">
        <v-list-tile-action>
          <v-icon>feedback</v-icon>
        </v-list-tile-action>

        <v-list-tile-content>
          <v-list-tile-title>Kummerkasten</v-list-tile-title>
        </v-list-tile-content>
      </v-list-tile>
      -->
      <v-list-tile
        v-show="!loggedInUser"
        to="/login"
        @click="drawer = false">
        <v-list-tile-action>
          <v-icon>account_circle</v-icon>
        </v-list-tile-action>

        <v-list-tile-content>
          <v-list-tile-title>Anmelden</v-list-tile-title>
        </v-list-tile-content>
      </v-list-tile>
      <v-list-tile
        v-show="!loggedInUser"
        to="/signup"
        @click="drawer = false">
        <v-list-tile-action>
          <v-icon>person_add</v-icon>
        </v-list-tile-action>

        <v-list-tile-content>
          <v-list-tile-title>Registrieren</v-list-tile-title>
        </v-list-tile-content>
      </v-list-tile>
      <v-list-tile
        v-show="loggedInUser"
        to="/profile"
        @click="drawer = false">
        <v-list-tile-action>
          <v-icon>settings</v-icon>
        </v-list-tile-action>

        <v-list-tile-content>
          <v-list-tile-title>Profileinstellungen</v-list-tile-title>
        </v-list-tile-content>
      </v-list-tile>
      <v-list-tile
        v-show="loggedInUser"
        to="/logout"
        @click="drawer = false">
        <v-list-tile-action>
          <v-icon>exit_to_app</v-icon>
        </v-list-tile-action>

        <v-list-tile-content>
          <v-list-tile-title>Abmelden</v-list-tile-title>
        </v-list-tile-content>
      </v-list-tile>
    </v-list>


  </v-navigation-drawer>
  <v-toolbar
    :clipped-left="$vuetify.breakpoint.lgAndUp"
    app
    color="primary"
    fixed>
    <v-toolbar-side-icon @click.stop="drawer = !drawer"></v-toolbar-side-icon>
    <v-toolbar-title class="tome-title"><router-link to="/">{{title}}</router-link></v-toolbar-title>
    <v-spacer></v-spacer>
    <v-menu
      v-if="loggedInUser"
      origin="center center"
      transition="scale-transition"
      bottom left>
    <v-btn
      flat
      slot="activator"
      v-show="$vuetify.breakpoint.mdAndUp"
    >
      <span>{{loggedInUser.firstName}}&nbsp;{{loggedInUser.familyName}}</span>
      <v-icon right>account_circle</v-icon>
    </v-btn>
      <v-list>
        <v-list-tile to="/profile">
          <v-list-tile-action>
            <v-icon>settings</v-icon>
          </v-list-tile-action>
          <v-list-tile-content>
          <v-list-tile-title>Einstellungen</v-list-tile-title>
          </v-list-tile-content>
        </v-list-tile>
        <v-list-tile to="/logout">
          <v-list-tile-action>
            <v-icon>exit_to_app</v-icon>
          </v-list-tile-action>
          <v-list-tile-content>
          <v-list-tile-title>Abmelden</v-list-tile-title>
          </v-list-tile-content>
        </v-list-tile>
      </v-list>

    </v-menu>
  </v-toolbar>
    </div>
</template>

<script>
  import { mapGetters } from 'vuex'

  export default {
    name: "Navigation",
    data() {
      return {
        drawer: false,
        nonApprovedUserCount: 0,
        title: process.env.VUE_APP_TITLE,
      }
    },
    created() {
        let self = this;
        if (this.hasRoles) {
          this.$http.get('/user/nonapprovedcount').then(function (response) {
            self.nonApprovedUserCount = response.data.data;
          })
        }
    },
    computed: {
      ...mapGetters({ loggedInUser: 'loggedInUser' }),
      ...mapGetters('masterData', {getGroupById: 'getGroupById'}),
      hasRoles: function() {
        return this.loggedInUser && (this.loggedInUser.isTrainer || this.loggedInUser.isAdmin);
      },
      currentUserGroupName: function() {
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
.tome-title a{
  text-decoration: none;
  color: #FFFFFF;
}
</style>

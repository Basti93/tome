<template>
  <v-layout align-top>
    <v-flex xs12 md8 offset-md2 top>
      <v-card>
        <v-toolbar flat>
          <v-toolbar-title>Benutzer Freischalten</v-toolbar-title>
          <v-spacer></v-spacer>
        </v-toolbar>
        <v-divider></v-divider>
        <v-card-text>
          <v-stepper v-model="approveWizardStep" v-if="nonApprovedUsers.length > 0">
            <v-stepper-header>
              <v-stepper-step :complete="approveWizardStep > 1" step="1">Benutzer Auswählen</v-stepper-step>
              <v-divider></v-divider>
              <v-stepper-step :complete="approveWizardStep > 2" step="2">Gruppe Zuweisen</v-stepper-step>
              <v-divider></v-divider>
              <v-stepper-step :complete="approveWizardStep > 3" step="3">Benutzer Vereinen</v-stepper-step>
            </v-stepper-header>
            <v-stepper-items>
              <v-stepper-content step="1">
                <v-card height="350">
                  <v-container fluid fill-height row wrap>
                    <v-layout
                      justify-center
                      align-center
                    >
                      <v-list
                        two-line>
                        <v-radio-group v-model="selectedNonApprovedUserId" :mandatory="false">
                          <v-list-item
                            v-for="(item) in nonApprovedUsers"
                            :key="item.id"
                          >
                            <v-list-item-content>
                              <v-list-item-title>{{fullName(item)}}</v-list-item-title>
                              <v-list-item-subtitle>{{moment(item.createdAt, 'YYYY-MM-DDTHH:mm').fromNow() + " registriert"}}</v-list-item-subtitle>
                            </v-list-item-content>
                            <v-list-item-action>
                              <v-radio :value="item.id"></v-radio>
                            </v-list-item-action>
                          </v-list-item>
                        </v-radio-group>
                      </v-list>
                    </v-layout>
                  </v-container>
                </v-card>
                <v-btn
                  color="primary"
                  @click="approveWizardStep = 2"
                  :disabled="!selectedNonApprovedUserId"
                >
                  Weiter
                  <v-icon right>arrow_forward</v-icon>
                </v-btn>
              </v-stepper-content>
              <v-stepper-content step="2">
                <v-card height="350">
                  <v-container fluid fill-height>
                    <v-layout
                      justify-center
                      align-center
                    >
                      <GroupsSelect
                        v-bind:groupIds="selectedGroupIds"
                        v-on:groupsChanged="groupsChanged">
                      </GroupsSelect>
                    </v-layout>
                  </v-container>
                </v-card>
                <v-btn
                  color="primary"
                  @click="approveWizardStep = 1"
                >
                  <v-icon left>arrow_back</v-icon>
                  Zurück
                </v-btn>
                <v-btn
                  color="primary"
                  @click="approveWizardStep = 3"
                  :disabled="!selectedGroupIds"
                >
                  Weiter
                  <v-icon right>arrow_forward</v-icon>
                </v-btn>
              </v-stepper-content>
              <v-stepper-content step="3">
                <v-card height="350">
                  <v-container fluid fill-height row wrap>
                    <v-layout
                      justify-center
                      align-center
                    >
                      <v-container grid-list-md text-xs-center>
                        <v-layout row wrap>
                          <v-flex xs12>
                            <v-flex xs1 md12>
                              <v-alert
                                v-bind:value="$vuetify.breakpoint.lgAndUp && !nonRegisteredUserId"
                                type="info"
                                outlined
                                pa-1
                                ma-0
                                class="caption"
                                dismissible
                              >
                                Ein vorübergehender Benutzer ohne E-Mail und Passwort kann hier dem Benutzer zugeordnet werden. Damit werden alle Daten wie Trainingsteilnahmen dem Benutzer übertragen und der
                                vorübergehende
                                Benutzer gelöscht. Dieser Schritt ist optional.
                              </v-alert>
                            </v-flex>
                            <v-flex xs12>
                              <v-tooltip bottom v-show="$vuetify.breakpoint.mdAndDown && !nonRegisteredUserId">
                                <v-btn
                                  absolute
                                  right
                                  icon
                                  color="primary"
                                  slot="activator"
                                  style="top: 10px;"
                                >
                                  <v-icon>info</v-icon>
                                </v-btn>
                                Ein vorübergehender Benutzer ohne E-Mail und Passwort kann hier dem Benutzer zugeordnet werden. Damit werden alle Daten wie Trainingsteilnahmen dem Benutzer übertragen und der
                                vorübergehende
                                Benutzer gelöscht. Dieser Schritt ist optional.
                              </v-tooltip>
                              <v-alert
                                v-bind:value="nonRegisteredUserId"
                                type="warning"
                                class="text-small"
                                pa-0
                                ma-0
                                outlined
                              >
                                Daten von <b>{{nonRegisteredUserFullName}}</b> <i>(vorläufiger Benutzer)</i> werden an <b>{{nonApprovedUserFullName}}</b> <i>(Benutzer zum Freischalten)</i> übertragen.
                              </v-alert>
                            </v-flex>
                            <v-flex xs12 md4 offset-md4>
                              <v-autocomplete
                                v-bind:items="nonRegisteredUsers"
                                v-model="nonRegisteredUserId"
                                :item-text="fullName"
                                item-value="id"
                                clearable
                                label="Vorfläufige Benutzer">

                              </v-autocomplete>
                            </v-flex>
                          </v-flex>
                        </v-layout>
                      </v-container>
                    </v-layout>
                  </v-container>
                </v-card>
                <v-btn
                  color="primary"
                  @click="approveWizardStep = 2"
                >
                  <v-icon left>arrow_back</v-icon>
                  Zurück
                </v-btn>
                <v-btn
                  color="primary"
                  @click="approveUsers()"
                >
                  <v-icon left>check</v-icon>
                  Abschließen
                </v-btn>
                <v-dialog
                  v-model="confirmDialog"
                  width="500"
                >
                  <v-card>
                    <v-card-title
                      class="headline"
                      primary-title
                    >
                      Vereinigung Bestätigen
                    </v-card-title>
                    <v-spacer></v-spacer>
                    <v-card-text style="text-align: left;">
                      Alle Daten von Benutzer<br>
                      <br>
                      <b>{{nonRegisteredUserFullName}}</b> <i>(vorläufiger Benutzer)</i><br>
                      <br>
                      werden an<br>
                      <br>
                      <b>{{nonApprovedUserFullName}}</b> <i>(Benutzer zum Freischalten)</i><br>
                      <br>
                      übertragen!<br><br><br>
                      Benutzer <b>{{nonRegisteredUserFullName}}</b> <i>(vorläufiger Benutzer)</i> wird gelöscht.
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions>
                      <v-spacer></v-spacer>
                      <v-btn
                        color="primary"
                        @click="confirmMigrateUser()"
                      >
                        <v-icon left>check</v-icon>
                        Bestätigen
                      </v-btn>
                      <v-btn
                        color="primary"
                        @click="confirmDialog = false"
                      >
                        <v-icon left>cancel</v-icon>
                        Abbrechen
                      </v-btn>
                    </v-card-actions>
                  </v-card>
                </v-dialog>

              </v-stepper-content>
            </v-stepper-items>
          </v-stepper>
          <v-alert v-if="nonApprovedUsers"></v-alert>
          <v-alert
            v-bind:value="nonApprovedUsers.length === 0"
            type="info"
            outlined
          >
            Alle Benutzer freigeschaltet
          </v-alert>
        </v-card-text>
        <v-divider></v-divider>
      </v-card>
    </v-flex>
  </v-layout>
</template>

<script>
  import {mapGetters} from 'vuex'
  import GroupsSelect from "../components/GroupsSelect";

  export default {
    name: "ApproveUsers",
    components: {GroupsSelect},
    data: function () {
      return {
        nonApprovedUsers: [],
        selectedNonApprovedUserId: null,
        approveWizardStep: 0,
        selectedGroupIds: [],
        nonRegisteredUsers: [],
        nonRegisteredUserId: null,
        confirmDialog: false,
        confirmed: true,
        show: false,
      }
    },
    created() {
      this.fetchNonApprovedUsers()
      this.fetchNonRegisteredUsers()
    },
    computed: {
      ...mapGetters({loggedInUser: 'loggedInUser'}),
      nonRegisteredUserFullName: function () {
        if (this.nonRegisteredUserId) {
          return this.fullName(this.nonRegisteredUsers.filter(u => u.id === this.nonRegisteredUserId)[0]);
        }
        return null;
      },
      nonApprovedUserFullName: function () {
        if (this.selectedNonApprovedUserId) {
          return this.fullName(this.nonApprovedUsers.filter(u => u.id === this.selectedNonApprovedUserId)[0]);
        }
        return null;
      },
    },
    methods: {
      fetchNonApprovedUsers: function () {
        let self = this;
        this.$http.get('/user/nonapproved').then(function (response) {
          self.nonApprovedUsers = response.data.data;
        })
      },
      fetchNonRegisteredUsers: function () {
        let self = this;
        this.$http.get('/user/nonregistered').then(function (response) {
          self.nonRegisteredUsers = response.data.data;
        })
      },
      fullName: function (user) {
        return user.firstName + ' ' + user.familyName;

      },
      groupsChanged: function ({groupIds}) {
        this.selectedGroupIds = groupIds;
      },
      confirmMigrateUser: function () {
        this.confirmDialog = false;
        this.confirmed = true;
        this.approveUsers();
      },
      approveUsers: function () {
        if (this.nonRegisteredUserId && !this.confirmed) {
          this.confirmed = false;
          this.confirmDialog = true;
          return;
        }
        this.$http.post('user/' + this.selectedNonApprovedUserId + '/approve/', {groupIds: this.selectedGroupIds, migrateUserId: this.nonRegisteredUserId}).then(res => {
          if (res.data.status == 'ok') {
            this.selectedNonApprovedUserId = null;
            this.nonRegisteredUserId = null;
            this.selectedGroupIds = [];
            this.$emit('showSnackbar', 'Benutzer erfolgreich freigschaltet');
            this.fetchNonApprovedUsers()
            this.fetchNonRegisteredUsers()
            this.approveWizardStep = 1;
            this.confirmed = true;
          }
        });
      },
    },
    watch: {
      nonRegisteredUserId: function () {
        this.confirmed = false
      }
    },
  }
</script>

<style scoped>

</style>

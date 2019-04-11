<template>
  <v-layout align-top>
    <v-flex xs12 md10 offset-md1 top>
      <v-card>
        <v-toolbar card prominent>
          <v-toolbar-title>Profileinstellungen</v-toolbar-title>
          <v-spacer></v-spacer>
          <v-btn color="primary" v-bind:disabled="!valid"  @click="save()">
            <v-icon left>save</v-icon>
            Speichern
          </v-btn>
        </v-toolbar>
        <v-form
          v-model="valid"
        >

          <v-card-text>
            <v-tabs
              icons-and-text
            >
              <v-tabs-slider color="yellow"></v-tabs-slider>

              <v-tab href="#tab-1">
                Benutzereinstellungen
                <v-icon>account_circle</v-icon>
              </v-tab>

              <v-tab href="#tab-2" v-if="loggedInUser.isAdmin || loggedInUser.isTrainer">
                Trainereinstellungen
                <v-icon>verified_user</v-icon>
              </v-tab>
              <v-tab-item :value="'tab-1'">
                <v-card flat>
                  <v-card-text>
                    <v-container grid-list-md>
                      <v-layout wrap>
                        <v-flex xs12 sm6>
                          <v-text-field
                            v-model="editUser.firstName"
                            label="Vorname"
                            :rules="requiredRule"
                            prepend-icon="account_circle"
                            required>
                          </v-text-field>
                        </v-flex>
                        <v-flex xs12 sm6>
                          <v-text-field
                            v-model="editUser.familyName"
                            label="Nachname"
                            :rules="requiredRule"
                            prepend-icon="account_circle"
                            required>
                          </v-text-field>
                        </v-flex>
                        <v-flex xs12 md6>
                          <v-text-field
                            v-model="editUser.email"
                            label="E-Mail"
                            :rules="emailRules"
                            prepend-icon="email"
                            required>
                          </v-text-field>
                        </v-flex>
                        <v-flex xs12 md6>
                          <v-menu
                                  ref="birthdateMenu"
                                  :close-on-content-click="false"
                                  v-model="birthdateMenu"
                                  :rules="requiredRule"
                                  required
                                  lazy
                                  full-width>
                            <v-text-field
                                    slot="activator"
                                    v-model="birthdateFormatted"
                                    required
                                    label="Geburtsdatum"
                                    prepend-icon="event"
                                    readonly
                            ></v-text-field>
                            <v-date-picker v-model="editUser.birthdate" @input="birthdateMenu = false"></v-date-picker>
                          </v-menu>
                        </v-flex>
                        <v-flex xs12 v-if="loggedInUser.isTrainer">
                          <v-card flat>
                            <v-card-title>
                              <span class="title">Erweiterte Einstellungen</span>
                            </v-card-title>
                            <v-card-text>
                              <v-flex md10 offset-md1>
                              <v-alert
                                v-bind:value="true"
                                type="info"
                                outline
                              >
                                Falls du nicht nur Trainer sondern auch noch aktiver Sportler bist, kannst du dich hier Gruppen zuweisen
                              </v-alert>
                                <GroupsSelect
                                  v-bind:groupIds="editUser.groupIds"
                                  v-on:groupsChanged="groupsChanged">
                                </GroupsSelect>

                              </v-flex>
                            </v-card-text>
                          </v-card>
                        </v-flex>
                      </v-layout>
                    </v-container>
                  </v-card-text>
                </v-card>
              </v-tab-item>
              <v-tab-item :value="'tab-2'">
                <v-card flat>
                  <v-card-title>
                    <v-flex md10 offset-md1>
                      <v-alert
                        v-bind:value="true"
                        type="info"
                        outline
                      >
                        Hier kannst du deine Trainingsgruppen auswählen. <br />Sie dienen als Filter und werden für die Trainings- und Benutzerverwaltung benötigt.
                      </v-alert>
                    </v-flex>
                  </v-card-title>
                  <v-card-text>
                    <v-flex md6 offset-md3>
                  <GroupsSelect
                          v-bind:groupIds="editUser.trainerGroupIds"
                          v-on:groupsChanged="trainerGroupsChanged"
                  ></GroupsSelect>
                    </v-flex>
                  </v-card-text>
                </v-card>
              </v-tab-item>
            </v-tabs>
          </v-card-text>
        </v-form>
      </v-card>
    </v-flex>
  </v-layout>
</template>

<script>
  import {mapGetters} from 'vuex'
  import GroupsSelect from "@/components/GroupsSelect.vue";
  import {formatDate} from "../helpers/date-helpers"

  export default {
    name: "Profile",
    components: {GroupsSelect},
    data: function () {
      return {
        valid: true,
        birthdateMenu: false,
        editUser: {
          id: null,
          firstName: null,
          familyName: null,
          email: null,
          birthdate: null,
          trainerGroupIds: [],
          groupIds: [],
        },
        requiredRule: [
          v => !!v || 'Pflichtfeld'
        ],
        emailRules: [
          v => !!v || 'Pflichtfeld',
          v => /.+@.+/.test(v) || 'E-mail muss gültig sein'
        ],
      }
    },
    created() {
      this.assignCurrentUser();
    },
    computed: {
      ...mapGetters({loggedInUser: 'loggedInUser'}),
      birthdateFormatted() {
        return this.formatDate(this.editUser.birthdate)
      },
    },
    methods: {
      assignCurrentUser: function () {
        this.editUser = {...this.loggedInUser}
      },
      groupsChanged: function ({groupIds}) {
        this.editUser.groupIds = groupIds;
      },
      trainerGroupsChanged: function ({groupIds}) {
        this.editUser.trainerGroupIds = groupIds;
      },
      save: function () {
        let self = this;
        const postData = {
          firstName: self.editUser.firstName,
          familyName: self.editUser.familyName,
          birthdate: self.moment(self.editUser.birthdate).format("YYYY-MM-DD HH:mm:ss"),
          groupIds: self.editUser.groupIds,
          trainerGroupIds: self.editUser.trainerGroupIds
        };
        this.$http.put('user/me', postData).then(function (res) {
          if (res.data.status == 'ok') {
            self.$emit("showSnackbar", "Erfolgreich gespeichert", "success");
            self.$http.get('auth/me').then(function (res) {
              localStorage.user = JSON.stringify(res.data)
              self.$store.dispatch('login')
              self.assignCurrentUser();
            });
          } else {
            self.$emit("showSnackbar", "Fehler beim Speichern. Bitte probier es nochmal", "error");
          }
        });

      },
      formatDate,
    },
  }
</script>

<style scoped>

</style>

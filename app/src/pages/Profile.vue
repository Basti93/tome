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
                            :rules="nameRules"
                            required>
                          </v-text-field>
                        </v-flex>
                        <v-flex xs12 sm6>
                          <v-text-field
                            v-model="editUser.familyName"
                            label="Nachname"
                            :rules="nameRules"
                            required>
                          </v-text-field>
                        </v-flex>
                        <v-flex xs12>
                          <v-text-field
                            v-model="editUser.email"
                            label="E-Mail"
                            :rules="emailRules"
                            required>
                          </v-text-field>
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
                                Falls du nicht nur Trainer sondern auch noch aktiver Sportler bist, kannst du dich hier aktivieren und einer Gruppe zuweisen
                              </v-alert>
                                <v-checkbox
                                  v-model="editUser.active"
                                  label="Aktiv"
                                ></v-checkbox>
                                <GroupSelect
                                  v-bind:groupId="editUser.groupId"
                                  v-bind:branchId="currentUserBranchId"
                                  v-on:groupSelected="groupChanged">
                                </GroupSelect>

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

                    <v-select
                      v-bind:items="branches"
                      v-model="branchId"
                      item-value="id"
                      item-text="name"
                      label="Sparte"
                      prepend-icon="bubble_chart"
                      clearable>
                    </v-select>
                    <v-autocomplete
                      v-bind:disabled="!branchId"
                      v-bind:items="groups"
                      v-model="editUser.trainerGroupIds"
                      item-value="id"
                      item-text="name"
                      label="Deine Gruppen"
                      prepend-icon="groups"
                      multiple
                      clearable
                      chips
                      deletable-chips>
                    </v-autocomplete>
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
  import {mapGetters, mapState} from 'vuex'
  import GroupSelect from "../components/GroupSelect";

  export default {
    name: "Profile",
    components: {GroupSelect},
    data: function () {
      return {
        valid: true,
        groups: [],
        branchId: null,
        editUser: {
          id: null,
          firstName: '',
          familyName: '',
          email: '',
          trainerGroupIds: [],
          groupId: null,
          active: false,
        },
        nameRules: [
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
      ...mapGetters('masterData', {getGroupsByBranchId: 'getGroupsByBranchId', getBranchByGroupId: 'getBranchByGroupId',}),
      ...mapState('masterData', {
        branches: 'branches',
      }),
      currentUserBranchId() {
        if (this.loggedInUser && this.loggedInUser.groupId) {
          return this.getBranchByGroupId(this.loggedInUser.groupId).id
        }
        return null
      },
    },
    methods: {
      assignCurrentUser: function () {
        this.editUser = Object.assign({}, this.loggedInUser)
        //select the branch of the first user group that the trainer has assigned
        if (this.editUser.trainerGroupIds && this.editUser.trainerGroupIds.length > 0) {
          this.branchId = this.getBranchByGroupId(this.editUser.trainerGroupIds[0]).id;
        }
      },
      fillGroupSelect: function () {
        this.groups = this.getGroupsByBranchId(this.branchId);
        this.editUser.trainerGroupIds = this.editUser.trainerGroupIds.filter(gId => this.groups.find(g => g.id === gId))
      },
      groupChanged: function (groupId) {
        this.editUser.groupId = groupId;
      },
      save: function () {
        let self = this;
        this.$http.put('user/' + this.editUser.id, this.editUser).then(function (res) {
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
        })

      }
    },
    watch: {
      branchId: function () {
        this.fillGroupSelect();
      }
    }
  }
</script>

<style scoped>

</style>

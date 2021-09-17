<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Abwesenheit</v-toolbar-title>
          </v-toolbar>
          <v-divider></v-divider>
          <v-card-text flat class="pa-2 pa-md-4">
            <v-card>
              <v-card-subtitle>In deiner Abwesenheit wirst du nicht automatisch an Trainings angemeldet. Falls du deine
                Abwesenheit ändern oder vorzeitig beenden möchtest, melde dich bei deinem Trainer.
              </v-card-subtitle>
              <v-card-text class="pa-0 pa-md-4">
                <v-container>
                  <v-row no-gutters>
                    <v-col>
                      <v-form ref="form" v-model="valid">
                        <v-container>
                          <v-row>
                            <v-col
                                cols="12"
                                md="4"
                            >
                              <v-autocomplete
                                  :items="users"
                                  v-model="userId"
                                  item-value="id"
                                  :item-text="fullName"
                                  clearable
                                  required
                                  :rules="requiredRule"
                                  label="Sportler auswählen"
                              >

                                <template v-slot:selection="data">
                                  {{ data.item.getFullName() }}
                                </template>
                                <template v-slot:item="data">
                                  {{ data.item.getFullName() }}
                                </template>

                              </v-autocomplete>
                            </v-col>
                            <v-col
                                cols="12"
                                md="4"
                            >
                              <v-menu
                                  v-model="menuStart"
                                  :close-on-content-click="false"
                                  :nudge-right="40"
                                  transition="scale-transition"
                                  offset-y
                                  min-width="auto"
                              >
                                <template v-slot:activator="{ on, attrs }">
                                  <v-text-field
                                      v-model="absenceStartFormatted"
                                      label="Beginn der Abwesenheit"
                                      required
                                      readonly
                                      :rules="requiredRule"
                                      v-bind="attrs"
                                      v-on="on"
                                  ></v-text-field>
                                </template>
                                <v-date-picker
                                    v-model="absenceStart"
                                    :min="new Date().toISOString().substr(0, 10)"
                                    @input="menuStart = false"
                                ></v-date-picker>
                              </v-menu>
                            </v-col>
                            <v-col
                                cols="12"
                                md="4"
                            >
                              <v-menu
                                  v-model="menuEnd"
                                  :close-on-content-click="false"
                                  :nudge-right="40"
                                  transition="scale-transition"
                                  offset-y
                                  min-width="auto"
                              >
                                <template v-slot:activator="{ on, attrs }">
                                  <v-text-field
                                      v-model="absenceEndFormatted"
                                      label="Ende der Abwesenheit"
                                      readonly
                                      :rules="requiredRule"
                                      required
                                      v-bind="attrs"
                                      v-on="on"
                                  ></v-text-field>
                                </template>
                                <v-date-picker
                                    v-model="absenceEnd"
                                    :min="new Date().toISOString().substr(0, 10)"
                                    @input="menuEnd = false"
                                ></v-date-picker>
                              </v-menu>
                            </v-col>
                            <v-col
                                cols="12"
                                md="6"
                            >
                              <v-textarea
                                  v-model="absenceReason"
                                  label="Grund der Abwesenheit (Nur für Trainer sichtbar)"
                                  :rules="requiredRule"
                                  outlined
                                  required
                              ></v-textarea>
                            </v-col>
                            <v-col cols="12">
                              <v-btn
                                  :disabled="!valid"
                                  color="success"
                                  class="mr-4"
                                  v-on:click="sendAbsence"
                              >
                                Abschicken
                              </v-btn>
                            </v-col>
                          </v-row>
                        </v-container>
                      </v-form>
                    </v-col>
                  </v-row>
                  <v-row>
                    <v-col>
                      <v-divider class="mt-3 mb-3"></v-divider>
                      <h3>Benutzer mit eingetragener Abwesenheit (nur für Trainer sichtbar)</h3>
                      <h4>Abwesenheiten werden automatisch nach Ablauf gelöscht</h4>
                      <v-data-table
                          :headers="headers"
                          :items="absenceUsers"
                          item-key="id"
                          :loading="loadingUsers"
                          :server-items-length="total"
                          :footer-props="{
                                itemsPerPageOptions: rowsPerPageItems,
                            }"
                          :itemsPerPage.sync="itemsPerPage"
                          :page.sync="page"
                      >
                        <template v-slot:item.firstName="{ item }">
                          {{ item.firstName }}
                        </template>
                        <template v-slot:item.familyName="{ item }">
                          {{ item.familyName }}
                        </template>
                        <template v-slot:item.absenceStart="{ item }">
                          {{ item.absenceStart.format('DD.MM.YYYY') }}
                        </template>
                        <template v-slot:item.absenceEnd="{ item }">
                          {{ item.absenceEnd.format('DD.MM.YYYY') }}
                        </template>
                        <template v-slot:item.absenceReason="{ item }">
                          {{ item.absenceReason }}
                        </template>
                        <template v-slot:item.action="{ item }">
                          <v-icon v-on:click="confirmAndDelete(item.id)" color="error">delete</v-icon>
                        </template>
                        <template v-slot:no-data>
                          <v-container>
                            <v-row>
                              <v-col>
                                <v-btn color="error" :disabled="loadingUsers" v-on:click="loadAllAbsenceUsers()">
                                  <v-icon left>cached</v-icon>
                                  Keine Daten gefunden
                                </v-btn>
                              </v-col>
                            </v-row>
                          </v-container>
                        </template>
                      </v-data-table>
                    </v-col>
                  </v-row>
                </v-container>
              </v-card-text>
            </v-card>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    <ConfirmDialog
        :show="showConfirmDialog"
        action-text="Löschen"
        v-on:confirmed="deleteAbsence()"
        v-on:canceled="showConfirmDialog = false">
    </ConfirmDialog>
  </v-container>
</template>

<script lang="ts">

import Vue from "vue";
import {mapGetters} from "vuex";
import {formatDate} from "@/helpers/date-helpers"
import User from "../models/User";
import ConfirmDialog from "../components/ConfirmDialog.vue";

export default Vue.extend({
  name: "AbsenceForm",
  components: {ConfirmDialog},
  data: () => ({
    absenceUsers: [],
    total: null,
    rowsPerPageItems: [5, 10, 20, 50],
    page: 1,
    itemsPerPage: 10,
    headers: [
      {text: 'Vorname', value: 'firstName', sortable: false},
      {text: 'Nachname', value: 'familyName', sortable: false},
      {text: 'Von', value: 'absenceStart', sortable: false},
      {text: 'Bis', value: 'absenceEnd', sortable: false},
      {text: 'Grund', value: 'absenceReason', sortable: false},
      {text: 'Löschen', value: 'action', sortable: false},
    ],
    loadingUsers: false,
    absenceStart: new Date().toISOString().substr(0, 10),
    absenceEnd: null,
    absenceReason: null,
    menuStart: false,
    menuEnd: false,
    valid: true,
    userId: null,
    users: [],
    requiredRule: [
      v => !!v || 'Bitte ausfüllen'
    ],
    showConfirmDialog: false,
    userIdToDelete: null,
  }),
  created() {
    this.users = this.getAllSimpleUsersWithGroup();
    if (this.cookieUser) {
      this.userId = this.cookieUser.id;
    }
    this.loadAllAbsenceUsers();
  },
  computed: {
    ...mapGetters({cookieUser: 'cookieUser'}),
    ...mapGetters('masterData', {
      getAllSimpleUsersWithGroup: 'getAllSimpleUsersWithGroup',
    }),
    absenceStartFormatted(): String {
      return this.formatDate(this.absenceStart)
    },
    absenceEndFormatted(): String {
      return this.formatDate(this.absenceEnd)
    },
  },
  methods: {
    async loadAllAbsenceUsers() {
      this.loadingUsers = true;
      this.absenceUsers = [];
      const {data} = await this.$http.get('user/allAbsence');
      if (data.data) {
        for (const userObj of data.data) {
            this.absenceUsers.push(new User(
                userObj.id,
                userObj.email,
                userObj.firstName,
                userObj.familyName,
                userObj.birthdate ? this.moment(userObj.birthdate, 'YYYY-MM-DDTHH:mm') : null,
                userObj.active === 1 ? true : false,
                userObj.groupIds,
                userObj.roleNames,
                userObj.trainerBranchIds,
                userObj.registered,
                userObj.profileImageName,
                userObj.absenceStart ? this.moment(userObj.absenceStart, 'YYYY-MM-DDTHH:mm') : null,
                userObj.absenceEnd ? this.moment(userObj.absenceStart, 'YYYY-MM-DDTHH:mm') : null,
                userObj.absenceReason
            ))
        }
        this.page = data.currentPage;
        this.total = data.total;
      }
      this.loadingUsers = false;
    },
    async sendAbsence() {
      if (this.$refs.form.validate()) {
        let postData = {
          absenceStart: this.moment(this.absenceStart, 'YYYY-MM-DDTHH:mm').format(),
          absenceEnd: this.moment(this.absenceEnd, 'YYYY-MM-DDTHH:mm').format(),
          absenceReason: this.absenceReason
        }
        const {data} = await this.$http.post('simpleuser/' + this.userId + '/storeAbsence', postData);
        if (data.status === 'ok') {
          this.$emit("showSnackbar", "Abwesenheit erfolgreich eingetragen", "success");
          this.resetFormData();
          this.loadAllAbsenceUsers();
        } else if (data.status === 'absence_exists') {
          this.$emit("showSnackbar", "Eine Abwesenheit ist für diesen Benutzer bereits eingetragen. Bitte kontaktiere einen Trainer um sie zu ändern.", "error");
        }
      }
    },
    confirmAndDelete(userId: number) {
      this.showConfirmDialog = true;
      this.userIdToDelete = userId;
    },
    async deleteAbsence() {
      this.showConfirmDialog = false;
      if (this.userIdToDelete) {
        const {data} = await this.$http.put('user/' + this.userIdToDelete + '/removeAbsence');
        if (data.status === 'ok') {
          this.$emit("showSnackbar", "Abwesenheit erfolgreich gelöscht", "success");
          this.loadAllAbsenceUsers();
        }
      }
    },
    resetFormData() {
      this.absenceReason = null;
      this.absenceStart = new Date().toISOString().substr(0, 10);
      this.absenceEnd = null;
      this.userId = null;
      this.$refs.form.reset()
    },
    fullName: item => item.firstName + ' ' + item.familyName,
    formatDate
  },

})

</script>

<style scoped lang="scss">

</style>

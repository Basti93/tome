<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat >
            <v-toolbar-title>Profileinstellungen</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-btn
                elevation="1"
                color="primary"
                   v-bind:disabled="!valid"
                @click="save()"
            >
              <v-icon left>mdi-content-save</v-icon>
              Speichern
            </v-btn>
          </v-toolbar>
          <v-card-text class="pa-0 pa-md-4">
            <v-row no-gutters>
              <v-col>
                <v-card class="ma-1">
                  <v-form
                      v-model="valid"
                  >

                    <v-card-text>
                      <v-tabs
                          v-model="activeTab"
                          icons-and-text
                      >

                        <v-tab>
                          Benutzereinstellungen
                          <v-icon>mdi-account-circle</v-icon>
                        </v-tab>
                        <v-tab v-if="loggedInUser.isAdmin || loggedInUser.isTrainer">
                          Trainereinstellungen
                          <v-icon>mdi-shield-check</v-icon>
                        </v-tab>
                      </v-tabs>
                      <v-window v-model="activeTab">
                        <v-window-item>
                          <v-card flat>
                            <v-card-text>
                              <v-container>
                                <v-row>
                                  <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="editUser.firstName"
                                        label="Vorname"
                                        :rules="requiredRule"
                                        prepend-icon="account_circle"
                                        required>
                                    </v-text-field>
                                  </v-col>
                                  <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="editUser.familyName"
                                        label="Nachname"
                                        :rules="requiredRule"
                                        prepend-icon="account_circle"
                                        required>
                                    </v-text-field>
                                  </v-col>
                                  <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="editUser.email"
                                        label="E-Mail"
                                        :rules="emailRules"
                                        prepend-icon="email"
                                        required>
                                    </v-text-field>
                                  </v-col>
                                  <v-col cols="12" sm="6">
                                    <v-menu
                                        ref="birthdateMenu"
                                        :close-on-content-click="false"
                                        v-model="birthdateMenu"
                                        :rules="requiredRule"
                                        required>
                                      <template v-slot:activator="{ on }">
                                        <v-text-field
                                            slot="activator"
                                            v-model="birthdateFormatted"
                                            required
                                            label="Geburtsdatum"
                                            prepend-icon="event"
                                            readonly
                                            v-on="on"></v-text-field>
                                      </template>
                                      <v-date-picker
                                          v-model="editUser.birthdate"
                                          @input="birthdateMenu = false"
                                          ref="birthdatePicker"
                                          :max="new Date().toISOString().substr(0, 10)"
                                          min="1950-01-01">
                                      </v-date-picker>
                                    </v-menu>
                                  </v-col>
                                  <v-col cols="12" sm="6">
                                    <UploadProfileImage
                                        v-on:imageChanged="imageChanged"
                                        v-on:imageRemoved="imageRemoved"
                                        :imagePath="editUser.profileImageName"
                                    ></UploadProfileImage>
                                  </v-col>
                                  <v-col cols="12" sm="6">
                                    <ChangePasswordDialog
                                        :visible="showPasswordDialog"
                                        v-on:close="showPasswordDialog = false"
                                    >
                                    </ChangePasswordDialog>
                                    <v-btn
                                        color="primary"
                                        elevation="1"
                                        @click="showPasswordDialog = true"
                                    >
                                      <v-icon left>mdi-shield-lock</v-icon>
                                      Passwort ändern
                                    </v-btn>
                                  </v-col>
                                  <v-col cols="12" v-if="loggedInUser.isTrainer">
                                    <v-card flat>
                                      <v-card-title>
                                        <span class="title">Erweiterte Einstellungen</span>
                                      </v-card-title>
                                      <v-card-text>
                                        <v-container>
                                          <v-row>
                                            <v-col md="10" offset-md="1">
                                              <v-alert
                                                  type="info"
                                                  outlined
                                              >
                                                Falls du nicht nur Trainer sondern auch noch aktiver Sportler bist,
                                                kannst
                                                du dich hier Gruppen zuweisen
                                              </v-alert>
                                              <GroupsSelect
                                                  v-bind:groupIds="editUser.groupIds"
                                                  v-on:groupsChanged="groupsChanged">
                                              </GroupsSelect>
                                            </v-col>
                                          </v-row>
                                        </v-container>
                                      </v-card-text>
                                    </v-card>
                                  </v-col>
                                </v-row>
                              </v-container>
                            </v-card-text>
                          </v-card>
                        </v-window-item>
                        <v-window-item>
                          <v-card flat>
                            <v-card-text>
                              <v-container>
                                <v-row>
                                  <v-col md="6" offset-md="3">
                                    <v-alert
                                        type="info"
                                        outlined
                                    >
                                      Wähle deine Hauptsparte um die App auf dich abzustimmen.
                                    </v-alert>
                                    <v-select
                                        class="pt-6"
                                        :items="branches"
                                        v-model="editUser.trainerBranchIds"
                                        :item-title="shortNameAndName"
                                        item-value="id"
                                        multiple
                                        chips
                                        closable-chips
                                        dense
                                        label="Hauptsparte auswählen"
                                        prepend-icon="mdi-bubble-chart"
                                    >
                                    </v-select>
                                  </v-col>
                                </v-row>
                              </v-container>
                            </v-card-text>
                          </v-card>
                        </v-window-item>
                      </v-window>
                    </v-card-text>
                  </v-form>
                </v-card>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import ChangePasswordDialog from "@/components/ChangePasswordDialog.vue";
import UploadProfileImage from "@/components/UploadProfileImage.vue";
import {formatDate} from "../helpers/date-helpers"
import GroupsSelect from "../components/GroupsSelect.vue";
import { useAuthStore } from '@/store/auth'
import { useMasterDataStore } from '@/store/masterData'
import { useSnackbarStore } from '@/store/snackbar'
import axios from '@/axios'
import moment from 'moment'
import User from '@/models/User'

export default {
  name: "ProfilePage",
  components: {GroupsSelect, ChangePasswordDialog, UploadProfileImage},
  data: function () {
    return {
      activeTab: 0,
      valid: true,
      birthdateMenu: false,
      showPasswordDialog: false,
      imageToUpload: null,
      editUser: {
        id: null,
        firstName: null,
        familyName: null,
        email: null,
        birthdate: null,
        profileImageName: null,
        trainerBranchIds: [],
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
    loggedInUser() {
      return useAuthStore().user
    },
    branches() {
      return useMasterDataStore().branches
    },
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
    imageChanged(file) {
      if (file && file.size) {
        this.imageToUpload = file;
      } else {
        this.imageRemoved();
      }
    },
    imageRemoved() {
      this.imageToUpload = null;
      this.editUser.profileImageName = null;
    },
    async uploadProfileImage() {
      let formData = new FormData();
      formData.append('profile_image', this.imageToUpload);
      const {data} = await axios.post('/user/me/uploadprofileimage',
          formData,
          {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
          });
      if (data.status === 'ok') {
        console.log("Image uploaded")
        return data.imageUrl;
      }
      useSnackbarStore().show("Fehler beim Hochladen des Bildes.", "error")
      throw "Image upload error";
    },
    async save() {
      try {
        const postData = {
          firstName: this.editUser.firstName,
          familyName: this.editUser.familyName,
          email: this.editUser.email,
          birthdate: moment(this.editUser.birthdate, 'YYYY-MM-DDTHH:mm').format(),
          groupIds: this.editUser.groupIds,
          trainerBranchIds: this.editUser.trainerBranchIds,
        };

        let imageName = null;
        if (this.imageToUpload) {
          imageName = await this.uploadProfileImage();
        } else if (this.editUser.profileImageName) {
          imageName = this.editUser.profileImageName;
        }
        if (imageName) {
          postData.profileImageName = imageName;
        }

        let {data} = await axios.put('user/me', postData);
        if (data.status == 'ok') {
          useSnackbarStore().show("Erfolgreich gespeichert", "success")
          let {data: userData} = await axios.get('auth/me');
          const user = User.from(JSON.stringify(userData))
          useAuthStore().updateUser(user)
          this.assignCurrentUser();
        } else {
          useSnackbarStore().show("Fehler beim Speichern.", "error")
        }
      } catch (error) {
        useSnackbarStore().show("Fehler beim Speichern.", "error")
      }
    },
    formatDate,
    shortNameAndName(branch) {
      return branch.name + ' (' + branch.shortName + ')';
    }
  },
}
</script>

<style scoped>

</style>

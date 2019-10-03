<template>
  <v-layout align-top>
    <v-flex xs12 md10 offset-md1 top>
      <v-card>
        <v-toolbar flat>
        <v-toolbar-title>Profileinstellungen</v-toolbar-title>
        <v-spacer></v-spacer>
          <v-btn color="primary" v-bind:disabled="!valid"  @click="save()">
            <v-icon left>save</v-icon>
            Speichern
          </v-btn>
      </v-toolbar>
        <v-divider></v-divider>
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
                        </v-flex>
                        <v-flex xs12 md6>
                          <UploadProfileImage
                                  v-on:imageChanged="imageChanged"
                                  v-on:imageRemoved="imageRemoved"
                                  :imagePath="editUser.profileImageName"
                          ></UploadProfileImage>
                        </v-flex>
                        <v-flex xs12 md6>
                          <ChangePasswordDialog
                                  :visible="showPasswordDialog"
                                  v-on:close="showPasswordDialog = false"
                          >
                          </ChangePasswordDialog>
                          <v-btn
                                  color="primary"
                                  @click="showPasswordDialog = true"
                            >
                            <v-icon left>security</v-icon>
                            Passwort ändern
                          </v-btn>
                        </v-flex>
                        <v-flex xs12 v-if="loggedInUser.isTrainer">
                          <v-card flat>
                            <v-card-title>
                              <span class="title">Erweiterte Einstellungen</span>
                            </v-card-title>
                            <v-card-text>
                              <v-flex md10 offset-md1>
                                <v-alert
                                        type="info"
                                        outlined
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
                              type="info"
                              outlined
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
  import ChangePasswordDialog from "@/components/ChangePasswordDialog.vue";
  import UploadProfileImage from "@/components/UploadProfileImage.vue";
  import {formatDate} from "../helpers/date-helpers"

  export default {
    name: "Profile",
    components: {GroupsSelect, ChangePasswordDialog, UploadProfileImage},
    data: function () {
      return {
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
        //this.editUser.birthdate = this.logged
      },
      groupsChanged: function ({groupIds}) {
        this.editUser.groupIds = groupIds;
      },
      trainerGroupsChanged: function ({groupIds}) {
        this.editUser.trainerGroupIds = groupIds;
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
        const {data} = await this.$http.post('/user/me/uploadprofileimage',
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
        this.$emit("showSnackbar", "Fehler beim Hochladen des Bildes.", "error");
        throw "Image upload error";
      },
      async save() {
        try {
          const postData = {
            firstName: this.editUser.firstName,
            familyName: this.editUser.familyName,
            email: this.editUser.email,
            birthdate: this.moment(this.editUser.birthdate, 'YYYY-MM-DDTHH:mm').format(),
            groupIds: this.editUser.groupIds,
            trainerGroupIds: this.editUser.trainerGroupIds
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

          let {data} = await this.$http.put('user/me', postData);
          if (data.status == 'ok') {
            this.$emit("showSnackbar", "Erfolgreich gespeichert", "success");
            let {data} = await this.$http.get('auth/me');
            localStorage.user = JSON.stringify(data)
            this.$store.dispatch('login')
            this.assignCurrentUser();
          } else {
            this.$emit("showSnackbar", "Fehler beim Speichern.", "error");
          }
        } catch {
          this.$emit("showSnackbar", "Fehler beim Speichern.", "error");
        }
      },
      formatDate,
    },
    watch: {
      birthdateMenu (val) {
        val && setTimeout(() => (this.$refs.birthdatePicker.activePicker = 'YEAR'))
      },
    },
  }
</script>

<style scoped>

</style>

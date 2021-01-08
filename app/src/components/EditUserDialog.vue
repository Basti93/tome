<template>
    <v-dialog v-model="show" max-width="1000px" :fullscreen="$vuetify.breakpoint.xsOnly" persistent>
        <v-card>
            <v-toolbar flat>
                <v-btn icon @click="show=false">
                    <v-icon>close</v-icon>
                </v-btn>
                <v-toolbar-title v-if="!editUserId">Vorläufigen Benutzer Anlegen</v-toolbar-title>
                <v-toolbar-title v-else>Benutzer Bearbeiten</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-toolbar-items>
                    <v-btn text color="primary" @click="save"><v-icon left>check</v-icon>Speichern</v-btn>
                </v-toolbar-items>
            </v-toolbar>
            <v-divider class="pb-2"></v-divider>
            <v-card-text>
                <v-alert
                        v-if="!editUserId"
                        type="info"
                        class="text-small"
                        pa-0
                        ma-0
                        outlined
                        dismissible>
                    Vorläufige Benutzer können an Trainings teilnehmen aber sich nicht einloggen. Erst nachdem sie sich selbst registriert haben, können sie sich einloggen. Außerdem hat der Trainer die Möglichkeit den neu registrierten Benutzer einen vorläufigen Benutzer zuzuweißen und so die Daten des vorläufigen Benutzer auf den neu registrierten Benutzer zu übertragen.
                </v-alert>
              <v-form
                  ref="form"
                  v-model="valid">
                <v-container grid-list-md>
                    <v-layout wrap>
                        <v-flex xs12 sm6>
                            <v-text-field
                                    v-model="firstName"
                                    label="Vorname"
                                    required
                                    :rules="[v => !!v || 'Wird benötigt']"
                                    prepend-icon="account_circle"
                            ></v-text-field>
                        </v-flex>
                        <v-flex xs12 sm6>
                            <v-text-field
                                    v-model="familyName"
                                    label="Nachname"
                                    :rules="[v => !!v || 'Wird benötigt']"
                                    required
                                    prepend-icon="account_circle"
                            ></v-text-field>
                        </v-flex>
                        <v-flex xs12 md6>
                            <v-menu
                                    ref="birthdateMenu"
                                    :close-on-content-click="false"
                                    v-model="birthdateMenu">
                                <template v-slot:activator="{ on }">
                                    <v-text-field
                                            v-model="birthdateFormatted"
                                            label="Geburtsdatum"
                                            prepend-icon="event"
                                            readonly
                                            v-on="on"
                                    ></v-text-field>
                                </template>
                                <v-date-picker
                                    ref="birthdatePicker"
                                    v-model="birthdate"
                                    @input="birthdateMenu = false"
                                    :max="new Date().toISOString().substr(0, 10)"
                                    min="1950-01-01">
                                </v-date-picker>
                            </v-menu>
                        </v-flex>
                        <v-flex xs12 md6>
                            <GroupsSelect
                                    :groupIds="groupIds"
                                    v-on:groupsChanged="groupsChanged">
                            </GroupsSelect>
                        </v-flex>
                        <v-flex xs12 md6>
                            <UploadProfileImage
                                    v-on:imageChanged="imageChanged"
                                    v-on:imageRemoved="imageRemoved"
                                    :imagePath="profileImageName"
                            ></UploadProfileImage>
                        </v-flex>
                        <v-flex xs12 md6>
                            <v-checkbox
                                    v-model="active"
                                    label="Aktiv"
                                    prepend-icon="active"
                            ></v-checkbox>
                        </v-flex>
                    </v-layout>
                </v-container>
              </v-form>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script>
    import GroupsSelect from "./GroupsSelect";
    import UploadProfileImage from "./UploadProfileImage";
    import {formatDate, parseDate} from "../helpers/date-helpers"

    export default {
        name: "EditUserDialog",
        components: {GroupsSelect, UploadProfileImage},
        props: [
            'visible',
            'editUserId',
            'editFirstName',
            'editFamilyName',
            'editBirthdate',
            'editProfileImageName',
            'editGroupIds',
            'editActive',
        ],
        data: function () {
            return {
                valid: false,
                firstName: null,
                familyName: null,
                birthdate: null,
                groupIds: [],
                profileImageName: null,
                active: true,
                editGroups: [],
                birthdateMenu: false,
                imageToUpload: null,
            }
        },
        computed: {
            show: {
                get() {
                    if (this.visible) {
                      this.assignValues()
                    }
                    return this.visible;
                },
                set(value) {
                    if (!value) {
                        this.$refs.form.resetValidation();
                        this.$emit('close')
                    }
                }
            },
            birthdateFormatted() {
                return this.formatDate(this.birthdate)
            },
        },
        methods: {
            async save() {
                this.$refs.form.validate();
                try {
                    let postData = {
                        firstName: this.firstName,
                        familyName: this.familyName,
                        groupIds: this.groupIds,
                        profileImageName: this.profileImageName,
                        active: this.active,
                    }

                    let imageName = null;
                    if (this.imageToUpload) {
                        imageName = await this.uploadProfileImage();
                    } else if (this.profileImageName) {
                        imageName = this.profileImageName;
                    }
                    if (imageName) {
                        postData.profileImageName = imageName;
                    }

                    let response = null;
                    if (this.birthdate) {
                        postData.birthdate = this.moment(this.birthdate, 'YYYY-MM-DDTHH:mm').format("YYYY-MM-DD");
                    }
                    if (this.editUserId) {
                        response = await this.$http.put('/user/' + this.editUserId, postData);
                    } else {
                        response = await this.$http.post('/user/unregistered', postData);
                    }
                    if (response.data.status === 'ok') {
                      this.$emit("showSnackbar", "Benutzer gespeichert", "success")
                      this.$emit("saved")
                      this.show = false;
                    } else {
                      this.$emit("showSnackbar", "Fehler beim Speichern", "error")
                    }
                } catch (error) {
                    console.error(error);
                }
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
                this.profileImageName = null;
            },
            assignValues() {
              this.firstName = this.editFirstName;
              this.familyName = this.editFamilyName;
              this.birthdate = this.editBirthdate ? this.moment(this.editBirthdate, 'YYYY-MM-DDTHH:mm').format('Y-MM-DD') : null;
              this.groupIds = this.editGroupIds;
              this.active = this.editActive;
              this.profileImageName = this.editProfileImageName;
              this.imageToUpload = null;
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
            groupsChanged({groupIds}) {
                this.groupIds = groupIds;
            },
            formatDate,
            parseDate,
        },
        watch: {
            birthdateMenu(val) {
                val && setTimeout(() => (this.$refs.birthdatePicker.activePicker = 'YEAR'))
            },
        }
    }
</script>

<style scoped>

</style>

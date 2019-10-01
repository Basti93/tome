<template>
    <v-dialog v-model="show" max-width="1000px" :fullscreen="$vuetify.breakpoint.xsOnly" persistent>
        <v-card>
            <v-toolbar flat>
                <v-btn icon @click="show=false">
                    <v-icon>close</v-icon>
                </v-btn>
                <v-toolbar-title>Vorläufigen Benutzer Anlegen</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-toolbar-items>
                    <v-btn text color="primary" @click="createUser"><v-icon left>check</v-icon>Speichern</v-btn>
                </v-toolbar-items>
            </v-toolbar>
            <v-divider class="pb-2"></v-divider>
            <v-card-text>
                <v-alert
                        type="info"
                        class="text-small"
                        pa-0
                        ma-0
                        outlined>
                    Vorläufige Benutzer können an Trainings teilnehmen aber sich nicht einloggen. Erst nachdem sie sich selbst registriert haben, können sie sich einloggen. Außerdem hat der Trainer die Möglichkeit den neu registrierten Benutzer einen vorläufigen Benutzer zuzuweißen und so die Daten des vorläufigen Benutzer auf den neu registrierten Benutzer zu übertragen.
                </v-alert>
                <v-container grid-list-md>
                    <v-layout wrap>
                        <v-flex xs12 sm6>
                            <v-text-field
                                    v-model="firstName"
                                    label="Vorname"
                                    required
                                    prepend-icon="account_circle"
                            ></v-text-field>
                        </v-flex>
                        <v-flex xs12 sm6>
                            <v-text-field
                                    v-model="familyName"
                                    label="Nachname"
                                    required
                                    prepend-icon="account_circle"
                            ></v-text-field>
                        </v-flex>
                        <v-flex xs12 md6>
                            <v-menu
                                    ref="birthdateMenu"
                                    :close-on-content-click="false"
                                    v-model="birthdateMenu"
                                    lazy
                                    full-width>
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
                    </v-layout>
                </v-container>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script>
    import GroupsSelect from "./GroupsSelect";
    import {formatDate, parseDate} from "../helpers/date-helpers"

    export default {
        name: "CreateUnregistredUserDialog",
        components: {GroupsSelect},
        props: ['visible'],
        data: function () {
            return {
                firstName: null,
                familyName: null,
                birthdate: null,
                groupIds: [],
                editGroups: [],
                birthdateMenu: false,
            }
        },
        computed: {
            show: {
                get() {
                    return this.visible;
                },
                set(value) {
                    if (!value) {
                        this.firstName = null;
                        this.familyName = null;
                        this.birthdate = null;
                        this.groupIds = [];
                        this.editGroups = [];
                        this.birthdateMenu = false;
                        this.$emit('close')
                    }
                }
            },
            birthdateFormatted() {
                return this.formatDate(this.birthdate)
            },
        },
        methods: {
            async createUser() {
                const postData = {
                    firstName: this.firstName,
                    familyName: this.familyName,
                    groupIds: this.groupIds
                };
                if (this.birthdate) {
                    postData.birthdate = this.moment(this.birthdate, 'YYYY-MM-DDTHH:mm').format("YYYY-MM-DD");
                }
                try {
                    const {data} = await this.$http.post('/user/unregistered', postData);
                    if (data.error) {
                        this.$emit("showSnackbar", "Benutzer konnte nicht angelegt werden", "error")
                    } else {
                        this.$emit("showSnackbar", "Benutzer angelegt", "success")
                        this.$emit("userCreated")
                        this.show = false;
                    }
                } catch (error) {
                    console.error(error);
                }
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

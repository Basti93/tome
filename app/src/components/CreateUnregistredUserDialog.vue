<template>
    <v-dialog v-model="show" max-width="1000px" :fullscreen="$vuetify.breakpoint.xsOnly" persistent>
        <v-card>
            <v-card-title>
                <span class="title">Vorläufigen Benutzer Anlegen</span>
            </v-card-title>

            <v-card-text>
                <v-alert
                        v-bind:value="true"
                        type="info"
                        class="text-small"
                        pa-0
                        ma-0
                        outline>
                    Vorläufige Benutzer können sich für Trainings anmelden aber nicht einloggen. Erst nachdem sie sich selber dazu entschieden haben, sich zu registrieren können sie unter "Benutzer Freischalten" von
                    einem Trainer freigeschaltet werden. Außerdem hat der Trainer die Möglichkeit den neu registrierten Benutzer einem Vorläufigen Benutzer zuzuweißen und so die Daten des Vorläufigen Benutzer auf den
                    registrierten Benutzer zu übertragen.
                </v-alert>
                <v-container grid-list-md>
                    <v-layout wrap>
                        <v-flex xs12 sm6>
                            <v-text-field
                                    v-model="firstName"
                                    label="Vorname"
                            ></v-text-field>
                        </v-flex>
                        <v-flex xs12 sm6>
                            <v-text-field
                                    v-model="familyName"
                                    label="Nachname"
                            ></v-text-field>
                        </v-flex>
                        <v-flex xs12 md6>
                            <GroupsSelect
                                    v-on:groupsChanged="groupsChanged">
                            </GroupsSelect>
                        </v-flex>
                    </v-layout>
                </v-container>
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="primary" @click="closeDialog" right>
                    <v-icon>close</v-icon>
                    Abbrechen
                </v-btn>
                <v-btn color="primary" @click="createUser" right>
                    <v-icon>save</v-icon>
                    Speichern
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
    import GroupsSelect from "./GroupsSelect";

    export default {
        name: "CreateUnregistredUserDialog",
        components: {GroupsSelect},
        props: ['visible'],
        data: function () {
            return {
                firstName: null,
                familyName: null,
                groupIds: [],
                editGroups: [],
            }
        },
        computed: {
            show: {
                get() {
                    return this.visible;
                },
                set(value) {
                    if (!value) {
                        this.$emit('close')
                    }
                }
            }
        },
        methods: {
            createUser() {
                let self = this;
                const data = {firstName: this.firstName, familyName: this.familyName, groupIds: this.groupIds};
                this.$http.post('/user/unregistered', data)
                    .then(function (res) {
                        if (!res.data.error) {
                            self.$emit("showSnackbar", "Benutzer angelegt", "success")
                            self.$emit("userCreated")
                            self.closeDialog();
                        } else {
                            self.$emit("showSnackbar", "Benutzer konnte nicht angelegt werden", "error")
                        }
                    })
                    .catch(function (err) {
                        console.log(err);
                        self.$emit("showSnackbar", "Benutzer konnte nicht angelegt werden", "error")
                    })
            },
            closeDialog() {
                this.show = false;
            },
            groupsChanged({groupIds}) {
                this.groupIds = groupIds;
            }
        },
    }
</script>

<style scoped>

</style>

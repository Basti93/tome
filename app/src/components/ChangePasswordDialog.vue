<template>
    <v-dialog
            v-model="visible"
            max-width="600px"
            persistent>
        <v-card>
            <v-toolbar flat>
                <v-btn
                        icon
                        @click="show=false">
                    <v-icon>close</v-icon>
                </v-btn>
                <v-toolbar-title>Passwort ändern</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-toolbar-items>
                    <v-btn text color="primary" @click="save()" :disabled="!valid">
                        <v-icon right>done</v-icon>
                        Speichern
                    </v-btn>
                </v-toolbar-items>
            </v-toolbar>

            <v-card-text>
                <v-form
                        ref="form"
                        v-model="valid">
                    <v-flex xs12>
                        <v-text-field
                                type="password"
                                :rules="passwordRules"
                                v-model="password"
                                prepend-icon="security"
                                label="Passwort"
                                required
                        ></v-text-field>

                        <v-text-field
                                type="password"
                                :rules="[ confirmPassword ]"
                                v-model="passwordConfirm"
                                prepend-icon="security"
                                label="Passwort bestätigen"
                                required
                        ></v-text-field>
                    </v-flex>
                </v-form>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script lang="ts">
    import Vue from 'vue'
    import {mapGetters} from 'vuex'

    export default Vue.extend({
        name: "ChangePasswordDialog",
        props: {
            'visible': Boolean,
        },
        data: function () {
            return {
                valid: true,
                password: null,
                passwordConfirm: null,
                passwordRules: [
                    v => !!v || 'Wird benötigt',
                ],
            }
        },
        computed: {
            ...mapGetters({loggedInUser: 'loggedInUser'}),
            show: {
                get() {
                    return this.visible;
                },
                set(value) {
                    if (!value) {
                        this.reset();
                        this.$emit('close')
                    }
                }
            },
        },
        methods: {
            reset() {
                this.password = null;
                this.passwordConfirm = null;
            },
            confirmPassword(password) {
                if (!password) {
                    return "Bitte passwort bestätigen"
                }
                if (this.password === password) {
                    return true;
                } else {
                    return "Passwörter müssen identisch sein!"
                }
            },
            async save() {
                const response = await this.$http.post('/user/me/changepassword', {'password': this.password});
                if (response.data.status === 'ok') {
                    this.show = false;
                }
            }
        }
    });
</script>

<style scoped>

</style>

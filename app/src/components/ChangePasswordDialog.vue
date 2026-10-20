<template>
    <v-dialog
            v-model="show"
            max-width="600px"
            persistent>
        <v-card>
            <v-toolbar flat>
                <v-btn
                        icon
                        @click="show=false">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
                <v-toolbar-title>Passwort ändern</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-toolbar-items>
                    <v-btn color="primary" @click="save()" :disabled="!valid" variant="text">
                        <v-icon end>mdi-check</v-icon>
                        Speichern
                    </v-btn>
                </v-toolbar-items>
            </v-toolbar>

            <v-card-text>
                <v-form
                        ref="form"
                        v-model="valid">
                    <v-row>
                        <v-col cols="12">
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
                        </v-col>
                    </v-row>
                </v-form>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script lang="ts">
import httpClient from '@/http/api'
import { useSnackbarStore } from '@/store/snackbar'
import { apiErrorMessage } from '@/helpers/apiError'
import { getPasswordValidationRules } from '@/constants/passwordPolicy'

export default {
    name: "ChangePasswordDialog",
    emits: ['close'],
    props: {
        'visible': Boolean,
    },
    data: function () {
        return {
            valid: true,
            password: null,
            passwordConfirm: null,
            passwordRules: getPasswordValidationRules(),
        }
    },
    computed: {
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
            try {
                const response = await httpClient.post('/user/me/changepassword', {'password': this.password})
                if (response.data.status === 'ok') {
                    useSnackbarStore().show("Passwort geändert", "success")
                    this.show = false;
                } else {
                    useSnackbarStore().show("Fehler beim Ändern des Passworts", "error")
                }
            } catch (error) {
                useSnackbarStore().show(apiErrorMessage(error, "Fehler beim Ändern des Passworts"), "error")
            }
        }
    }
}
</script>

<style scoped>

</style>

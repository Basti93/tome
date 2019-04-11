<template>
    <v-layout align-top justify-center>
        <v-flex xs12 sm8>
            <v-card>
                <v-toolbar card prominent>
                    <v-toolbar-title>Registrieren</v-toolbar-title>
                    <v-spacer></v-spacer>
                </v-toolbar>
                <v-divider></v-divider>
                <div v-if="completed">
                    <h2>Erfolgreich registriert</h2>
                    <p>Sobald du von einem Trainer bestätigt wurdest erhältst du eine E-Mail und kannst dich anmelden.</p>
                    <v-btn
                            color="primary"
                            to="/"
                    >
                        Zur Startseite
                    </v-btn>

                </div>
                <v-form
                        ref="form"
                        v-model="valid"
                        v-else
                >
                    <v-card-text>
                        <v-text-field
                                v-model="firstName"
                                :rules="nameRules"
                                label="Vorname"
                                prepend-icon="account_circle"
                                required
                        ></v-text-field>

                        <v-text-field
                                v-model="familyName"
                                :rules="nameRules"
                                prepend-icon="account_circle"
                                label="Nachname"
                                required
                        ></v-text-field>

                        <v-text-field
                                v-model="email"
                                :rules="emailRules"
                                label="E-mail"
                                prepend-icon="email"
                                required
                        ></v-text-field>

                        <v-menu
                                ref="birthdateMenu"
                                :close-on-content-click="false"
                                v-model="birthdateMenu"
                                :rules="birthdateRules"
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
                            <v-date-picker v-model="birthdate" @input="birthdateMenu = false"></v-date-picker>
                        </v-menu>

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
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn
                                :disabled="!valid"
                                color="primary"
                                right
                                @click="signup()"
                        >
                            Registrieren
                        </v-btn>
                    </v-card-actions>
                </v-form>
            </v-card>
        </v-flex>
    </v-layout>
</template>

<script>
    import {formatDate} from "../helpers/date-helpers"

    export default {
        name: "Signup",
        data: () => ({
            errors: [],
            valid: true,
            completed: false,
            firstName: null,
            familyName: null,
            password: null,
            passwordConfirm: null,
            birthdateMenu: false,
            birthdate: null,
            nameRules: [
                v => !!v || 'Wird benötigt'
            ],
            passwordRules: [
                v => !!v || 'Wird benötigt',
            ],
            birthdateRules: [
                v => !!v || 'Wird benötigt',
            ],
            email: '',
            emailRules: [
                v => !!v || 'E-mail wird benötigt',
                v => /.+@.+/.test(v) || 'E-mail muss gültig sein'
            ],
        }),
        mounted: function () {
            this.$refs.form.$children['0'].focus()
        },
        computed: {
            birthdateFormatted() {
                return this.formatDate(this.birthdate)
            },
        },
        methods: {
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
            async signup() {
                try {
                    await this.$http.post('/auth/signup', {
                        firstName: this.firstName,
                        familyName: this.familyName,
                        email: this.email,
                        birthdate: this.moment(this.birthdate).format("YYYY-MM-DD HH:mm:ss"),
                        password: this.password,
                    });
                    this.completed = true;
                    this.$emit("showSnackbar", "Erfolgreich registriert", "success");
                } catch (error) {
                    console.log(error);
                    if (error) {
                        if (error.status_code === 422) {
                            for (const validationError of error.errors) {
                                console.info(validationError);
                                this.$emit("showSnackbar", validationError, "error");
                            }
                        }
                    }
                }
            },
            formatDate,
        },
    }
</script>

<style scoped>

</style>

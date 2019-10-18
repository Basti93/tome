<template>
    <v-layout align-top>
        <v-flex xs12 md10 offset-md1 top>
            <v-card>
                <v-toolbar flat>
                    <v-toolbar-title>Kalendar</v-toolbar-title>
                </v-toolbar>
                <v-divider></v-divider>
                <v-card-text flat style="padding-bottom: 100px;">
                    <TrainingCalendar
                        :privateMode="userLoggedIn">
                    </TrainingCalendar>
                </v-card-text>
            </v-card>
        </v-flex>
    </v-layout>
</template>

<script lang="ts">

    import Vue from "vue";
    import {mapGetters} from 'vuex'
    import TrainingCalendar from "../components/TrainingCalendar.vue";

    export default Vue.extend({
        name: "Calendar",
        components: {TrainingCalendar},

        computed: {
            ...mapGetters({loggedInUser: 'loggedInUser'}),
            userLoggedIn() {
                if (this.loggedInUser) {
                    return true;
                }
                return false;
            }
        }
    })

</script>

<style scoped lang="scss">

</style>

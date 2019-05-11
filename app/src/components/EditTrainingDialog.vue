<template>
    <v-dialog v-model="dialog" max-width="1000px" :fullscreen="$vuetify.breakpoint.xsOnly" persistent>
        <v-card>
            <v-card-title>
                <span class="title">Training Bearbeiten/Anlegen</span>
            </v-card-title>

            <v-card-text>
                <v-tabs
                        icons-and-text
                >
                    <v-tabs-slider color="yellow"></v-tabs-slider>
                    <v-tab href="#tab-1">
                        Allgemein
                        <v-icon>event</v-icon>
                    </v-tab>

                    <v-tab href="#tab-2">
                        Teilnehmer
                        <v-icon>groups</v-icon>
                    </v-tab>
                    <v-tab-item :value="'tab-1'">
                        <v-container grid-list-md>
                            <v-layout wrap>
                                <v-flex xs12 md6>
                                    <v-menu
                                            ref="dateMenuOpened"
                                            :close-on-content-click="false"
                                            v-model="dateMenuOpened"
                                            lazy
                                            full-width
                                    >
                                        <v-text-field
                                                slot="activator"
                                                v-model="trainingDateFormatted"
                                                required
                                                label="Datum"
                                                prepend-icon="event"
                                                readonly
                                        ></v-text-field>
                                        <v-date-picker v-model="trainingDate" @input="dateMenuOpened = false"></v-date-picker>
                                    </v-menu>
                                </v-flex>
                                <v-flex xs6 md3>
                                    <v-menu
                                            ref="startMenuOpened"
                                            :close-on-content-click="false"
                                            v-model="startMenuOpened"
                                            lazy
                                            full-width
                                    >
                                        <v-text-field
                                                slot="activator"
                                                v-model="startTime"
                                                label="Start"
                                                required
                                                prepend-icon="schedule"
                                                readonly
                                        ></v-text-field>
                                        <v-time-picker v-model="startTime" @input="startMenuOpened = false" format="24hr"></v-time-picker>
                                    </v-menu>
                                </v-flex>
                                <v-flex xs6 md3>
                                    <v-menu
                                            ref="endMenuOpened"
                                            :close-on-content-click="false"
                                            v-model="endMenuOpened"
                                            lazy
                                            full-width
                                    >
                                        <v-text-field
                                                slot="activator"
                                                v-model="endTime"
                                                required
                                                label="Ende"
                                                prepend-icon="schedule"
                                                readonly
                                        ></v-text-field>
                                        <v-time-picker v-model="endTime" @input="endMenuOpened = false" format="24hr"></v-time-picker>
                                    </v-menu>
                                </v-flex>
                                <v-flex xs12>
                                    <v-autocomplete
                                            :items="locations"
                                            item-text="name"
                                            item-value="id"
                                            v-model="editedItem.locationId"
                                            clearable
                                            required
                                            label="Ort"
                                            prepend-icon="add_location"
                                    ></v-autocomplete>
                                </v-flex>
                                <v-flex xs12>
                                    <v-autocomplete
                                            v-model="editedItem.trainerIds"
                                            :items="filterTrainers"
                                            item-value="id"
                                            :item-text="fullName"
                                            attach
                                            clearable
                                            chips
                                            deletable-chips
                                            label="Trainer"
                                            prepend-icon="verified_user"
                                            multiple
                                    >
                                    </v-autocomplete>
                                </v-flex>
                                <v-flex xs12>
                                    <v-autocomplete
                                            :items="filterGroups"
                                            v-model="editedItem.groupIds"
                                            item-value="id"
                                            item-text="name"
                                            label="Gruppen"
                                            prepend-icon="groups"
                                            multiple
                                            clearable
                                            chips
                                            deletable-chips>
                                    </v-autocomplete>
                                </v-flex>
                                <v-flex xs12 ml-2 style="text-align: left;">
                                    <v-label>Trainingsinhalte</v-label>
                                    <TrainingContent
                                            :contentIds="branchContentIds"
                                            :initContentIds="editedItem.contentIds"
                                            selectable
                                            v-on:contentSelected="editItemContentSelected($event)"
                                            v-on:contentUnselected="editItemContentUnselected($event)"
                                    >

                                    </TrainingContent>
                                </v-flex>
                                <v-flex xs12>
                                    <v-textarea
                                            box
                                            label="Kommentar"
                                            v-model="editedItem.comment"
                                    ></v-textarea>
                                </v-flex>
                            </v-layout>
                        </v-container>
                    </v-tab-item>
                    <v-tab-item :value="'tab-2'">
                        <v-container grid-list-md>
                            <v-layout wrap>
                                <v-flex xs12>
                                    <v-list>
                                        <template v-for="(item, index) in editDialogFilteredUsers">

                                            <v-list-tile>
                                                <v-list-tile-avatar>
                                                    <v-icon>account_circle</v-icon>
                                                </v-list-tile-avatar>
                                                <v-list-tile-content>
                                                    <v-list-tile-title>
                                                        {{ item.firstName }} {{ item.familyName }}
                                                    </v-list-tile-title>
                                                </v-list-tile-content>
                                                <v-list-tile-action>
                                                    <v-list-tile-action-text v-if="true">
                                                        Teilgenommen
                                                    </v-list-tile-action-text>
                                                    <v-list-tile-action-text v-else>
                                                        Abgesagt
                                                    </v-list-tile-action-text>
                                                    <v-icon v-if="true">
                                                        check
                                                    </v-icon>
                                                    <v-icon v-else>
                                                        cancel
                                                    </v-icon>
                                                </v-list-tile-action>
                                            </v-list-tile>
                                            <v-divider inset></v-divider>
                                        </template>
                                    </v-list>
                                    <v-autocomplete
                                            :disabled="!editDialogFilteredUsers"
                                            :items="editDialogFilteredUsers"
                                            v-model="editedItem.participantIds"
                                            item-value="id"
                                            :item-text="fullName"
                                            label="Teilnehmer"
                                            prepend-icon="how_to_reg"
                                            multiple
                                            clearable>
                                        <template
                                                slot="selection"
                                                slot-scope="{ item, index }"
                                        >
                                            <v-chip>
                                                <span>{{ item.firstName }}</span>
                                            </v-chip>
                                        </template>
                                    </v-autocomplete>
                                </v-flex>
                            </v-layout>
                        </v-container>
                    </v-tab-item>
                </v-tabs>
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="primary" @click="close">Abbrechen</v-btn>
                <v-btn color="primary" @click="save">Speichern</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script lang="ts">
    import Vue from "vue";
    import Training from "../models/Training";

    export default Vue.extend({
        name: "EditTrainingDialog",
        data: function () {
            return {
                training: null as Training,
                dateMenuOpened: false,
                startMenuOpened: false,
                endMenuOpened: false,
            }
        },
        computed: {
            trainingDateFormatted() {
                return this.formatDate(this.training.start)
            },
            trainingDate: {
                get: function () {
                    return this.training.start
                },
                set: function (newValue) {
                    this.training.start = newValue;
                }
            },
            startTime: {
                get: function () {
                    return this.training.start;
                },
                set: function (newValue) {
                    this.training.start = newValue;
                }
            },
            endTime: {
                get: function () {
                    return this.training.start;
                },
                set: function (newValue) {
                    this.training.start = newValue;
                }
            },
        },
        methods: {
            formatDate(date) {
                if (!date) return null

                const [year, month, day] = date.split('-')
                return `${day}.${month}.${year}`
            },
            parseDate(date) {
                if (!date) return null

                const [day, month, year] = date.split('.')
                return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`
            }
        }
    })
</script>

<style scoped>

</style>

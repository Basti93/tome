<template>
    <v-dialog v-model="dialog" max-width="1000px" :fullscreen="xsOnly" persistent>
        <v-card>
            <v-card-title>
                <span class="title">Training Bearbeiten/Anlegen</span>
            </v-card-title>

            <v-card-text>
                <v-tabs
                        v-model="activeTab"
                        icons-and-text
                >
                    <v-tab>
                        Allgemein
                        <v-icon>mdi-calendar-month</v-icon>
                    </v-tab>

                    <v-tab>
                        Teilnehmer
                        <v-icon>mdi-account-multiple</v-icon>
                    </v-tab>
                </v-tabs>
                <v-window v-model="activeTab">
                    <v-window-item>
                        <v-container grid-list-md>
                            <v-layout wrap>
                                <v-flex xs12 md6>
                                    <v-menu
                                            ref="dateMenuOpened"
                                            :close-on-content-click="false"
                                            v-model="dateMenuOpened"
                                    >
                                        <template v-slot:activator="{ props }">
                                            <v-text-field
                                                    v-model="trainingDateFormatted"
                                                    required
                                                    label="Datum"
                                                    prepend-icon="event"
                                                    readonly
                                                    v-bind="props"
                                            ></v-text-field>
                                        </template>
                                        <v-date-picker v-model="trainingDate" @input="dateMenuOpened = false"></v-date-picker>
                                    </v-menu>
                                </v-flex>
                                <v-flex xs6 md3>
                                    <v-menu
                                            ref="startMenuOpened"
                                            :close-on-content-click="false"
                                            v-model="startMenuOpened"
                                    >
                                        <template v-slot:activator="{ props }">
                                            <v-text-field
                                                    v-model="startTime"
                                                    label="Start"
                                                    required
                                                    prepend-icon="schedule"
                                                    readonly
                                                    v-bind="props"
                                            ></v-text-field>
                                        </template>
                                        <v-time-picker v-model="startTime" @input="startMenuOpened = false" format="24hr"></v-time-picker>
                                    </v-menu>
                                </v-flex>
                                <v-flex xs6 md3>
                                    <v-menu
                                            ref="endMenuOpened"
                                            :close-on-content-click="false"
                                            v-model="endMenuOpened"
                                    >
                                        <template v-slot:activator="{ props }">
                                            <v-text-field
                                                    v-model="endTime"
                                                    required
                                                    label="Ende"
                                                    prepend-icon="schedule"
                                                    readonly
                                                    v-bind="props"
                                            ></v-text-field>
                                        </template>
                                        <v-time-picker v-model="endTime" @input="endMenuOpened = false" format="24hr"></v-time-picker>
                                    </v-menu>
                                </v-flex>
                                <v-flex xs12>
                                    <v-autocomplete
                                            :items="locations"
                                            item-title="name"
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
                                            :item-title="fullName"
                                            attach
                                            clearable
                                            chips
                                            closable-chips
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
                                            item-title="name"
                                            label="Gruppen"
                                            prepend-icon="groups"
                                            multiple
                                            clearable
                                            chips
                                            closable-chips>
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
                                            filled
                                            label="Kommentar"
                                            v-model="editedItem.comment"
                                    ></v-textarea>
                                </v-flex>
                            </v-layout>
                        </v-container>
                    </v-window-item>
                    <v-window-item>
                        <v-container grid-list-md>
                            <v-layout wrap>
                                <v-flex xs12>
                                    <v-list>
                                        <template v-for="(item) in editDialogFilteredUsers">

                                            <v-list-item :key="item.id">
                                                <template v-slot:prepend>
                                                    <v-avatar>
                                                        <v-icon>mdi-account-circle</v-icon>
                                                    </v-avatar>
                                                </template>
                                                <v-list-item-title>
                                                    {{ item.firstName }} {{ item.familyName }}
                                                </v-list-item-title>
                                                <template v-slot:append>
                                                    <div class="d-flex align-center gap-2">
                                                        <span v-if="true">Teilgenommen</span>
                                                        <span v-else>Abgesagt</span>
                                                        <v-icon v-if="true" size="small">mdi-check</v-icon>
                                                        <v-icon v-else size="small">mdi-cancel</v-icon>
                                                    </div>
                                                </template>
                                            </v-list-item>
                                            <v-divider :key="item.id" inset></v-divider>
                                        </template>
                                    </v-list>
                                    <v-autocomplete
                                            :disabled="!editDialogFilteredUsers"
                                            :items="editDialogFilteredUsers"
                                            v-model="editedItem.participantIds"
                                            item-value="id"
                                            :item-title="fullName"
                                            label="Teilnehmer"
                                            prepend-icon="how_to_reg"
                                            multiple
                                            clearable>
                                        <template
                                                slot="selection"
                                                slot-scope="{ item }"
                                        >
                                            <v-chip>
                                                <span>{{ item.firstName }}</span>
                                            </v-chip>
                                        </template>
                                    </v-autocomplete>
                                </v-flex>
                            </v-layout>
                        </v-container>
                    </v-window-item>
                </v-window>
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="primary" @click="close">Abbrechen</v-btn>
                <v-btn color="primary" @click="save"><v-icon left>mdi-check</v-icon>Speichern</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script lang="ts">
import { useDisplay } from 'vuetify'
    ;
    import Training from "../models/Training";

    export default {
        name: "EditTrainingDialog",
        data: function () {
            return {
                activeTab: 0,
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
    });
</script>

<style scoped>

</style>

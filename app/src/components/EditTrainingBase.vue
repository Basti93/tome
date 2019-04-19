<template>
    <v-layout wrap>
        <v-flex xs12 md6>
            <WeekdaysComponent
                    v-if="series"
                    :weekdays="selectedWeekdays"
                    v-on:change="weekdaysChanged"
            ></WeekdaysComponent>
            <v-menu
                    v-else
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
                    v-model="selectedLocationId"
                    clearable
                    required
                    label="Ort"
                    prepend-icon="add_location"
            ></v-autocomplete>
        </v-flex>
        <v-flex xs12>
            <v-autocomplete
                    v-model="trainerIds"
                    :items="trainers"
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
                    :items="groups"
                    v-model="selectedGroupIds"
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
                    :initContentIds="selectedContentIds"
                    selectable
                    v-on:change="contentIdsChanged"
            >
            </TrainingContent>
        </v-flex>
        <v-flex xs12>
            <v-textarea
                    box
                    label="Kommentar"
                    v-model.lazy="editedComment"
            ></v-textarea>
        </v-flex>
    </v-layout>
</template>

<script lang="ts">
    import Vue from "vue";
    import TrainingContent from "./TrainingContent"
    import WeekdaysComponent from "./WeekdaysComponent"
    import {formatDate, parseDate} from "../helpers/date-helpers"
    import {mapGetters, mapState} from 'vuex'

    export default Vue.extend({
        name: "EditTrainingBase",
        components: {TrainingContent, WeekdaysComponent},
        props: {
            series: Boolean,
            weekdays: Array,
            id: Number,
            date: String,
            start: String,
            end: String,
            trainerIds: Array,
            groupIds: Array,
            contentIds: Array,
            locationId: Number,
            branchId: Number,
            trainers: Array,
            groups: Array,
            comment: String,
        },
        data: function () {
            return {
                trainingDate: new Date().toISOString().substr(0, 10) as Date,
                selectedWeekdays: [] as Array,
                dataId: null as Number,
                endTime: '12:00' as String,
                startTime: '09:00' as String,
                selectedLocationId: null as Number,
                selectedGroupIds: [] as Array,
                selectedContentIds: [] as Array,
                selectedTrainerIds: [] as Array,
                editedComment: null as String,
                dateMenuOpened: false,
                startMenuOpened: false,
                endMenuOpened: false,
            }
        },
        computed: {
            ...mapState('masterData', {
                locations: 'locations',
            }),
            ...mapGetters('masterData', {getContentIdsByBranchId: 'getContentIdsByBranchId'}),
            trainingDateFormatted(): String {
                return this.formatDate(this.trainingDate)
            },
            branchContentIds(): Array {
                return this.getContentIdsByBranchId(this.branchId);
            },
        },
        methods: {
            contentIdsChanged(contentIds: Array): void {
                this.selectedContentIds = contentIds;
            },
            fireChangeEvent() {
                this.$emit("change", {
                    id: this.dataId,
                    date: this.trainingDate,
                    weekdays: this.selectedWeekdays,
                    start: this.startTime,
                    end: this.endTime,
                    locationId: this.selectedLocationId,
                    trainerIds: this.selectedTrainerIds,
                    groupIds: this.selectedGroupIds,
                    contentIds: this.selectedContentIds,
                    comment: this.editedComment,
                })
            },
            weekdaysChanged(weekdays) {
                this.selectedWeekdays = weekdays;
                this.fireChangeEvent();
            },
            fullName: item => item.firstName + ' ' + item.familyName,
            formatDate,
            parseDate,
        },
        watch: {
            id: {
                immediate: true,
                handler(newVal) {
                    this.dataId = newVal;
                },
            },
            date: {
                immediate: true,
                handler(newVal) {
                    this.trainingDate = newVal;
                },
            },
            start: {
                immediate: true,
                handler(newVal) {
                    this.startTime = newVal;
                },
            },
            end: {
                immediate: true,
                handler(newVal) {
                    if (newVal) {
                        this.endTime = newVal;
                    }
                },
            },
            locationId: {
                immediate: true,
                handler(newVal) {
                    this.selectedLocationId = newVal;
                },
            },
            groupIds: {
                immediate: true,
                handler(newVal) {
                    if (newVal) {
                        this.selectedGroupIds = newVal;
                    }
                },
            },
            trainerIds: {
                immediate: true,
                handler(newVal) {
                    this.selectedTrainerIds = newVal;
                },
            },
            contentIds: {
                immediate: true,
                handler(newVal) {
                    this.selectedContentIds = newVal;
                },
            },
            comment: {
                immediate: true,
                handler(newVal) {
                    this.editedComment = newVal;
                },
            },
            weekdays: {
                immediate: true,
                handler(newVal) {
                    this.selectedWeekdays = newVal;
                },
            },
            trainingDate() {
                this.fireChangeEvent();
            },
            startTime() {
                this.fireChangeEvent();
            },
            endTime() {
                this.fireChangeEvent();
            },
            selectedTrainerIds() {
                this.fireChangeEvent();
            },
            selectedGroupIds() {
                this.fireChangeEvent();
            },
            selectedContentIds() {
                this.fireChangeEvent();
            },
            selectedLocationId() {
                this.fireChangeEvent();
            },
            editedComment() {
                this.fireChangeEvent();
            },
        },
    })
</script>

<style scoped>

</style>

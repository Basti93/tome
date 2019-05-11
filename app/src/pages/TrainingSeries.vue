<template>
    <v-layout align-top>
        <v-flex xs12 md10 offset-md1 top>
            <v-card>
                <v-toolbar card prominent>
                    <v-toolbar-title>Trainingsserien verwalten</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-btn title="Neues Training anlegen" color="primary" @click="openCreateDialog()">
                        <v-icon left>add_circle</v-icon>
                        Neue Serie
                    </v-btn>
                    <v-dialog
                            v-model="showCreateDialog"
                            fullscreen>
                        <v-card>
                            <v-toolbar card>
                                <v-btn icon @click="showCreateDialog = false">
                                    <v-icon>close</v-icon>
                                </v-btn>
                                <v-toolbar-title>Trainingsserie Bearbeiten/Anlegen</v-toolbar-title>
                                <v-spacer></v-spacer>
                                <v-toolbar-items>
                                    <v-btn flat color="primary" @click="save()">Speichern</v-btn>
                                </v-toolbar-items>
                            </v-toolbar>

                            <v-card-text>
                                <EditTrainingBase
                                        series
                                        v-on:change="seriesChanged"
                                        :id="editedTrainingSeries.id"
                                        :weekdays="editedTrainingSeries.weekdays"
                                        :start="editedTrainingSeries.startTime"
                                        :end="editedTrainingSeries.endTime"
                                        :trainerIds="editedTrainingSeries.trainerIds"
                                        :groupIds="editedTrainingSeries.groupIds"
                                        :groups="filterGroups"
                                        :trainers="trainers"
                                        :contentIds="editedTrainingSeries.contentIds"
                                        :locationId="editedTrainingSeries.locationId"
                                        :comment="editedTrainingSeries.comment"
                                        :branchId="filterBranchId"
                                        :active="editedTrainingSeries.active"
                                >
                                </EditTrainingBase>
                            </v-card-text>
                        </v-card>
                    </v-dialog>
                    <v-spacer></v-spacer>
                    <div v-if="$vuetify.breakpoint.lgAndUp">
                        <div v-if="filterGroupIds.length > 0">
                            <v-chip
                                    small
                                    v-for="(item, index) in filterGroups"
                                    :key="item.id">{{item.name}}
                            </v-chip>
                        </div>
                    </div>
                    <v-btn title="Liste nach Gruppen filtern" icon color="primary" @click="showFilterDialog = true">
                        <v-icon>filter_list</v-icon>
                    </v-btn>
                    <GroupsSelectDialog
                            :visible="showFilterDialog"
                            v-on:close="showFilterDialog = false"
                            :groupIds="filterGroupIds"
                            v-on:done="filterChanged">
                    </GroupsSelectDialog>
                </v-toolbar>
                <v-divider></v-divider>

                <v-card-text>
                    <v-alert
                            v-bind:value="true"
                            type="info"
                            class="text-small"
                            pa-0
                            ma-0
                            outline>
                        Traings von aktiven Trainingsserien werden immer eine Woche im Voraus erstellt.
                    </v-alert>
                    <v-data-table
                            :headers="headers"
                            :items="trainingSeriesList"
                            :loading="loading">
                        <v-progress-linear slot="loading" color="primary" indeterminate></v-progress-linear>
                        <template slot="items" slot-scope="props">
                            <tr @click="editItem(props.item)" style="cursor: pointer">
                                <td>{{ dayArrayToString(props.item.weekdays) }}</td>
                                <td>{{ removeMilleSec(props.item.startTime) }}</td>
                                <td>{{ removeMilleSec(props.item.endTime) }}</td>
                                <td>
                                    <v-chip v-for="(group) in getGroupsByIds(props.item.groupIds)"
                                            :key="group.id">
                                        {{ group.name }}
                                    </v-chip>
                                </td>
                                <td>{{ props.item.active ? 'Ja' : 'Nein' }}</td>
                            </tr>
                        </template>

                    </v-data-table>
                </v-card-text>

            </v-card>
        </v-flex>
    </v-layout>
</template>

<script lang="ts">
    import Vue from "vue";
    import EditTrainingBase from "../components/EditTrainingBase";
    import GroupsSelectDialog from "../components/GroupsSelectDialog";
    import TrainingSeries from "@/models/TrainingSeries";
    import {mapGetters} from 'vuex'
    import {dayArrayToString} from "../helpers/date-helpers"

    export default Vue.extend({
        name: "TrainingSeries",
        components: {EditTrainingBase, GroupsSelectDialog},
        data() {
            return {
                showFilterDialog: false,
                filterGroupIds: [],
                filterBranchId: null,
                trainers: [],
                trainingSeriesList: [] as TrainingSeries[],
                showCreateDialog: false,
                loading: false,
                headers: [
                    {text: 'Wochentage', value: 'weekdays', sortable: false},
                    {text: 'Start', value: 'startTime', sortable: false},
                    {text: 'Ende', value: 'endTime', sortable: false},
                    {text: 'Gruppen', value: 'groupIds', sortable: false},
                    {text: 'Aktiv', value: 'active', sortable: false},
                ],
                defaultTrainingSeries: new TrainingSeries(null, '09:00', '12:00', null, [], [], [], null, [], true),
                editedTrainingSeries: new TrainingSeries(null, '09:00', '12:00', null, [], [], [], null, [], true) as TrainingSeries,
            }
        },
        created() {
            if (this.trainerGroupIds && this.trainerGroupIds.length > 0) {
                this.filterBranchId = this.getBranchByGroupId(this.trainerGroupIds[0]).id;
                this.filterGroupIds = this.trainerGroupIds;
            }
            this.fetchData();
        },
        computed: {
            ...mapGetters({loggedInUser: 'loggedInUser'}),
            ...mapGetters('masterData', {getGroupsByIds: 'getGroupsByIds', getBranchByGroupId: 'getBranchByGroupId'}),
            filterGroups() {
                if (this.filterGroupIds.length > 0) {
                    return this.getGroupsByIds(this.filterGroupIds)
                }
                return [];
            },
            trainerGroupIds() {
                return this.loggedInUser.trainerGroupIds
            },
        },
        methods: {
            openCreateDialog() {
                this.editedTrainingSeries = {...this.defaultTrainingSeries};
                this.editedTrainingSeries.groupIds = this.filterGroupIds;
                this.showCreateDialog = true
            },
            seriesChanged(item) {
                this.editedTrainingSeries = new TrainingSeries(item.id, item.start, item.end, item.locationId, item.groupIds, item.contentIds, item.trainerIds, item.comment, item.weekdays, item.active);
            },
            async fetchData() {
                try {
                    this.loading = true;
                    const trRes = await this.$http.get('/trainingSeries');
                    this.trainingSeriesList = trRes.data.data;

                    let trainerUrl = '/user/trainer';
                    if (this.filterGroupIds && this.filterGroupIds.length > 0) {
                        trainerUrl += '?groupIds=' + this.filterGroupIds;
                    }
                    const trainerRes = await this.$http.get('/user/trainer');
                    this.trainers = trainerRes.data;
                } catch (error) {
                    console.error(error);
                } finally {
                    this.loading = false;
                }

            },
            editItem(item: TrainingSeries) {
                this.editedTrainingSeries = {...item};
                this.showCreateDialog = true;
            },
            async save() {
                let postData = {
                    startTime: this.editedTrainingSeries.startTime,
                    endTime: this.editedTrainingSeries.endTime,
                    weekdays: this.editedTrainingSeries.weekdays,
                    comment: this.editedTrainingSeries.comment,
                    locationId: this.editedTrainingSeries.locationId,
                    trainerIds: this.editedTrainingSeries.trainerIds,
                    contentIds: this.editedTrainingSeries.contentIds,
                    groupIds: this.editedTrainingSeries.groupIds,
                    active: this.editedTrainingSeries.active,

                };
                let url = '/trainingSeries';
                let res;
                if (this.editedTrainingSeries.id) {
                    url += '/' + this.editedTrainingSeries.id;
                    postData.id = this.editedTrainingSeries.id;
                    res = await this.$http.put(url, postData);
                } else {
                    res = await this.$http.post(url, postData);
                }

                if (res.data.status == 'ok') {
                    this.showCreateDialog = false;
                    this.fetchData();
                    this.$emit('showSnackbar', 'Serie erfolgreich erstellt');
                }

            },
            filterChanged({branchId: branchId, groupdIds: groupIds}) {
                this.filterGroupIds = groupIds;
                this.fetchData();
            },
            removeMilleSec(time :String) {
                return time ? time.substring(0, time.length - 3) : '';
            },
            dayArrayToString,
        }
    })
</script>

<style scoped>

</style>

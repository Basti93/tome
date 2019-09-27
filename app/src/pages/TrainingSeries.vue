<template>
    <v-layout align-top>
        <v-flex xs12 md10 offset-md1 top>
            <v-card>
                <v-toolbar flat>
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
                            <v-toolbar flat>
                                <v-btn icon @click="showCreateDialog = false">
                                    <v-icon>close</v-icon>
                                </v-btn>
                                <v-toolbar-title>Trainingsserie Bearbeiten/Anlegen</v-toolbar-title>
                                <v-spacer></v-spacer>
                                <v-toolbar-items>
                                    <v-btn text color="primary" @click="save()"><v-icon left>check</v-icon>Speichern</v-btn>
                                </v-toolbar-items>
                            </v-toolbar>
                            <v-divider></v-divider>
                            <v-card-text>
                                <v-container grid-list-md>
                                    <v-layout wrap>
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
                                    </v-layout>
                                </v-container>
                            </v-card-text>
                        </v-card>
                    </v-dialog>
                    <v-spacer></v-spacer>
                    <div v-if="$vuetify.breakpoint.lgAndUp">
                        <div v-if="filterGroupIds.length > 0">
                            <v-chip
                                small
                                v-for="(item, index) in filterGroups"
                                :key="item.id"
                                class="ma-1">
                                    <v-icon left color="primary">group</v-icon>
                                {{branchAndGroupName(item)}}
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
                            type="info"
                            class="text-small"
                            pa-0
                            ma-0
                            outlined>
                        Traings von aktiven Trainingsserien werden immer eine Woche im Voraus erstellt.
                    </v-alert>
                    <v-data-table
                            :headers="headers"
                            :items="trainingSeriesList"
                            :loading="loading"
                            hide-default-footer>
                        <template v-slot:item.id="{ item }">
                            {{ item.id }}
                        </template>
                        <template v-slot:item.weekdays="{ item }">
                            {{ dayArrayToString(item.weekdays) }}
                        </template>
                        <template v-slot:item.startTime="{ item }">
                            {{ item.startTime }}
                        </template>
                        <template v-slot:item.endTime="{ item }">
                            {{ item.endTime }}
                        </template>
                        <template v-slot:item.trainerIds="{ item }">
                            <v-chip v-for="(trainer) in getSimpleTrainersByIds(item.trainerIds)"
                                    :key="trainer.id"
                                    small
                                    class="ma-1">
                                {{ trainer.firstName }}</v-chip>
                        </template>
                        <template v-slot:item.active="{ item }">
                            {{ item.active ? 'Ja' : 'Nein' }}
                        </template>
                        <template v-slot:item.action="{ item }">
                            <v-btn
                                    outlined
                                    @click="editItem(item)"
                                    color="success">
                                <v-icon>edit</v-icon>
                            </v-btn>
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
                    {text: 'Id', value: 'id', sortable: false},
                    {text: 'Wochentage', value: 'weekdays', sortable: false},
                    {text: 'Start', value: 'startTime', sortable: false},
                    {text: 'Ende', value: 'endTime', sortable: false},
                    {text: 'Trainer', value: 'trainerIds', sortable: false},
                    {text: 'Aktiv', value: 'active', sortable: false},
                    {text: 'Actions', value: 'action', sortable: false },
                ],
                defaultTrainingSeries: new TrainingSeries(null, '09:00', '12:00', null, [], [], [], null, [], true),
                editedTrainingSeries: new TrainingSeries(null, '09:00', '12:00', null, [], [], [], null, [], true) as TrainingSeries,
            }
        },
        created() {
            if (this.trainerGroupIds && this.trainerGroupIds.length > 0) {
                this.filterBranchId = this.getBranchByGroupId(this.trainerGroupIds[0]).id;
                let firstBranchId = this.getBranchByGroupId(this.trainerGroupIds[0]).id;
                this.filterGroupIds = this.getGroupsByBranchId(firstBranchId).map(g => g.id);
            }
            this.fetchData();
        },
        computed: {
            ...mapGetters({loggedInUser: 'loggedInUser'}),
            ...mapGetters('masterData', {getGroupsByIds: 'getGroupsByIds', getBranchByGroupId: 'getBranchByGroupId', getGroupsByBranchId: 'getGroupsByBranchId', getBranchById: 'getBranchById', getSimpleTrainersByIds: 'getSimpleTrainersByIds'}),
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
                    let seriesUrl = '/trainingSeries';
                    if (this.filterGroupIds && this.filterGroupIds.length > 0) {
                        seriesUrl += '?groupIds=' + this.filterGroupIds;
                    }
                    const trRes = await this.$http.get(seriesUrl);
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
            editItem(item) {
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
                this.filterBranchId = branchId;
                if (!this.filterGroupIds || this.filterGroupIds.length == 0) {
                    this.filterGroupIds = this.getGroupsByBranchId(this.filterBranchId).map(g => g.id);
                }
                this.fetchData();
            },
            removeMilleSec(time :String) {
                return time ? time.substring(0, time.length - 3) : '';
            },
            branchAndGroupName(item) {
                return this.getBranchById(item.branchId).shortName + '/' + item.name;
            },
            dayArrayToString,
        }
    })
</script>

<style scoped>

</style>

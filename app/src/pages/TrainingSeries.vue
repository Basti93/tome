<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar extension-height="100">
            <v-toolbar-title>Trainingsserien</v-toolbar-title>
            <v-spacer></v-spacer>
            <template v-slot:extension>
              <v-container>
                <v-row no-gutters>
                  <v-col
                      class="text-right"
                      cols="6"
                      md="2"
                      v-for="(item) in filterGroups"
                      :key="item.id">
                    <v-chip
                        small
                        outlined
                        class="ma-1">
                      <v-icon left color="primary">group</v-icon>
                      {{ branchAndGroupName(item) }}
                    </v-chip>
                  </v-col>
                </v-row>
              </v-container>
              <v-btn
                  title="Neue Trainingsserie anlegen"
                  fab
                  absolute
                  bottom
                  left
                  elevation="2"
                  color="primary"
                  @click="openCreateDialog()">
                <v-icon>add</v-icon>

              </v-btn>
            </template>
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
                    <v-btn text color="primary" @click="save()">
                      <v-icon left>check</v-icon>
                      Speichern
                    </v-btn>
                  </v-toolbar-items>
                </v-toolbar>
                <v-divider></v-divider>
                <v-card-text>
                  <v-tabs
                      icons-and-text
                  >
                    <v-tabs-slider color="yellow"></v-tabs-slider>

                    <v-tab href="#tab-1">
                      Serie
                      <v-icon>event</v-icon>
                    </v-tab>

                    <v-tab href="#tab-2">
                      Trainingsdaten
                      <v-icon>groups</v-icon>
                    </v-tab>

                    <v-tab-item :value="'tab-1'">
                      <v-container grid-list-md>
                        <v-layout wrap>
                          <v-flex xs12>
                            <v-alert
                                type="info"
                                class="text-small"
                                pa-0
                                ma-0
                                outlined>
                              Traings von aktiven Trainingsserien werden immer eine Woche im Voraus erstellt.
                            </v-alert>
                          </v-flex>
                          <v-flex xs12 md9 offset-md-1>
                            <v-label>Wochentage</v-label>
                            <WeekdaysComponent
                                :weekdays="editedTrainingSeries.weekdays"
                                v-on:change="weekdaysChanged"
                            ></WeekdaysComponent>
                          </v-flex>
                          <v-flex xs12 md3 offset-md-1>
                            <v-menu
                                ref="deferUntilMenuOpened"
                                :close-on-content-click="false"
                                v-model="deferUntilMenuOpened"
                            >
                              <template v-slot:activator="{ on }">
                                <v-text-field
                                    slot="activator"
                                    v-model="deferUntilFormatted"
                                    clearable
                                    @click:clear="editedTrainingSeries.deferUntil = null"
                                    label="Aussetzen bis"
                                    prepend-icon="event"
                                    v-on="on"
                                ></v-text-field>
                              </template>
                              <v-date-picker v-model="editedTrainingSeries.deferUntil"
                                             @input="deferUntilMenuOpened = false"></v-date-picker>
                            </v-menu>
                          </v-flex>
                        </v-layout>
                      </v-container>
                    </v-tab-item>
                    <v-tab-item :value="'tab-2'">
                      <v-container grid-list-md>
                        <v-layout wrap>
                          <EditTrainingBase
                              showOnlyGeneralInfo
                              v-on:change="seriesChanged"
                              :id="editedTrainingSeries.id"
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
                          ></EditTrainingBase>
                        </v-layout>
                      </v-container>
                    </v-tab-item>
                  </v-tabs>
                </v-card-text>
              </v-card>
            </v-dialog>
            <v-spacer></v-spacer>
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

          <v-card-text class="mt-8 pa-0 pa-md-4">
            <v-card>
              <v-card-text class="pa-0 pa-md-4">
                <v-container>
                  <v-row>
                    <v-col>
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
                                  outlined
                                  class="ma-1">
                            {{ trainer.firstName }}
                          </v-chip>
                        </template>
                        <template v-slot:item.deferUntil="{ item }">
                            <span v-if="checkDeferUntilIsActive(item)">
                                {{ moment(item.deferUntil, 'YYYY-MM-DDTHH:mm').format('DD.MM.Y') }}
                            </span>
                          <span v-else>Aktiv</span>
                        </template>
                        <template v-slot:item.action="{ item }">
                          <v-btn
                              outlined
                              @click="editItem(item)"
                              color="success">
                            <v-icon>edit</v-icon>
                          </v-btn>
                          <v-btn
                              outlined
                              class="ml-5"
                              @click="deleteItem(item)"
                              color="error">
                            <v-icon>delete</v-icon>
                          </v-btn>
                        </template>
                      </v-data-table>
                    </v-col>
                  </v-row>
                </v-container>
              </v-card-text>
            </v-card>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script lang="ts">
import Vue from "vue";
import EditTrainingBase from "../components/EditTrainingBase";
import GroupsSelectDialog from "../components/GroupsSelectDialog";
import TrainingSeries from "@/models/TrainingSeries";
import {mapGetters} from 'vuex'
import {dayArrayToString, formatDate, parseDate} from "../helpers/date-helpers"
import WeekdaysComponent from "../components/WeekdaysComponent.vue";

export default Vue.extend({
  name: "TrainingSeries",
  components: {EditTrainingBase, GroupsSelectDialog, WeekdaysComponent},
  data() {
    return {
      showFilterDialog: false,
      filterGroupIds: [],
      filterBranchId: null,
      trainers: [],
      trainingSeriesList: [] as TrainingSeries[],
      showCreateDialog: false,
      loading: false,
      deferUntilMenuOpened: false,
      headers: [
        {text: 'Id', value: 'id', sortable: false},
        {text: 'Wochentage', value: 'weekdays', sortable: false},
        {text: 'Start', value: 'startTime', sortable: false},
        {text: 'Ende', value: 'endTime', sortable: false},
        {text: 'Trainer', value: 'trainerIds', sortable: false},
        {text: 'Ausgesetzt bis', value: 'deferUntil', sortable: false},
        {text: '', value: 'action', sortable: false},
      ],
      defaultTrainingSeries: new TrainingSeries(null, '09:00', '12:00', null, [], [], [], null, [], null),
      editedTrainingSeries: new TrainingSeries(null, '09:00', '12:00', null, [], [], [], null, [], null) as TrainingSeries,
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
    ...mapGetters('masterData', {
      getGroupsByIds: 'getGroupsByIds',
      getBranchByGroupId: 'getBranchByGroupId',
      getGroupsByBranchId: 'getGroupsByBranchId',
      getBranchById: 'getBranchById',
      getSimpleTrainersByIds: 'getSimpleTrainersByIds'
    }),
    filterGroups() {
      if (this.filterGroupIds.length > 0) {
        return this.getGroupsByIds(this.filterGroupIds)
      }
      return [];
    },
    trainerGroupIds() {
      return this.loggedInUser.trainerGroupIds
    },
    deferUntilFormatted(): String {
      return this.formatDate(this.editedTrainingSeries.deferUntil)
    },
  },
  methods: {
    openCreateDialog() {
      this.editedTrainingSeries = {...this.defaultTrainingSeries};
      this.editedTrainingSeries.groupIds = this.filterGroupIds;
      this.showCreateDialog = true
    },
    seriesChanged(item) {
      this.editedTrainingSeries.id = item.id;
      this.editedTrainingSeries.startTime = item.start;
      this.editedTrainingSeries.endTime = item.end;
      this.editedTrainingSeries.locationId = item.locationId;
      this.editedTrainingSeries.groupIds = item.groupIds;
      this.editedTrainingSeries.contentIds = item.contentIds;
      this.editedTrainingSeries.trainerIds = item.trainerIds;
      this.editedTrainingSeries.comment = item.comment;
      this.editedTrainingSeries.trainerIds = item.trainerIds;
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
      } finally {
        this.loading = false;
      }

    },
    editItem(item) {
      this.editedTrainingSeries = {...item};
      let momDeferUntil = this.moment(item.deferUntil, 'YYYY-MM-DDTHH:mm');
      if (momDeferUntil.isValid()) {
        this.editedTrainingSeries.deferUntil = momDeferUntil.format('Y-MM-DD')
      }
      this.showCreateDialog = true;
    },
    async deleteItem(item) {
      if (confirm('Löschen bestätigen')) {
        let response = await this.$http.delete('/trainingSeries/' + item.id);
        if (response.data.status === 'ok') {
          this.$emit("showSnackbar", "Serie erfolgreich gelöscht", "success")
          this.trainingSeriesList.splice(this.trainingSeriesList.indexOf(item), 1)
        } else {
          this.$emit("showSnackbar", "Serie konnte nicht gelöscht werden", "error")
        }
      }
    },
    async save() {
      const momDeferUntil = this.moment(this.editedTrainingSeries.deferUntil, 'YYYY-MM-DDTHH:mm');
      let postData = {
        startTime: this.editedTrainingSeries.startTime,
        endTime: this.editedTrainingSeries.endTime,
        weekdays: this.editedTrainingSeries.weekdays,
        comment: this.editedTrainingSeries.comment,
        locationId: this.editedTrainingSeries.locationId,
        trainerIds: this.editedTrainingSeries.trainerIds,
        contentIds: this.editedTrainingSeries.contentIds,
        groupIds: this.editedTrainingSeries.groupIds,
        deferUntil: momDeferUntil.isValid() ? momDeferUntil.format() : null,

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
    removeMilleSec(time: String) {
      return time ? time.substring(0, time.length - 3) : '';
    },
    branchAndGroupName(item) {
      return this.getBranchById(item.branchId).shortName + '/' + item.name;
    },
    checkDeferUntilIsActive(item: TrainingSeries) {
      const momDeferUntil = this.moment(item.deferUntil);
      return momDeferUntil.isValid() && momDeferUntil >= this.moment().startOf('day');
    },
    weekdaysChanged(weekdays) {
      this.editedTrainingSeries.weekdays = weekdays;
    },
    dayArrayToString,
    formatDate,
    parseDate,
  },
})
</script>

<style scoped>

</style>

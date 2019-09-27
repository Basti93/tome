<template>
  <v-layout align-top>
    <v-flex xs12 md10 offset-md1 top>
      <v-card>
        <v-toolbar flat>
          <v-toolbar-title>Trainingsverwaltung</v-toolbar-title>
          <v-spacer></v-spacer>
          <v-btn title="Neues Training anlegen" color="primary" @click="create">
            <v-icon left>add_circle</v-icon>
            Anlegen
          </v-btn>
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
          <v-btn title="Liste nach Sparte und Gruppe filtern" icon color="primary" @click="showFilterDialog = true">
            <v-icon>filter_list</v-icon>
          </v-btn>
          <GroupsSelectDialog
            v-bind:visible="showFilterDialog"
            v-on:close="showFilterDialog = false"
            v-bind:groupIds="filterGroupIds"
            v-on:done="filterChanged">
          </GroupsSelectDialog>
        </v-toolbar>
        <v-divider></v-divider>
        <v-card-text>
          <v-data-table
            :headers="headers"
            :items="trainings"
            :loading="loading"
            :sort-desc.sync="sortDesc"
            :server-items-length="totalItems"
            :items-per-page.sync="itemsPerPage"
            :page.sync="page"
            :sort-by.sync="sortBy"
          >
            <template v-slot:item.date="{ item }">
              {{ moment(item.start, 'YYYY-MM-DDTHH:mm').format('dd, DD.MM.Y') }}
            </template>
            <template v-slot:item.start="{ item }">
              {{ moment(item.start, 'YYYY-MM-DDTHH:mm').format('HH:mm') }}
            </template>
            <template v-slot:item.end="{ item }">
              {{ moment(item.end, 'YYYY-MM-DDTHH:mm').format('HH:mm') }}
            </template>
            <template v-slot:item.locationId="{ item }">
              {{ getLocationNameById(item.locationId) }}
            </template>
            <template v-slot:item.trainerIds="{ item }">
              <v-chip v-for="(trainer) in getSimpleTrainersByIds(item.trainerIds)"
                      :key="trainer.id"
                      small
                      class="ma-1">
                {{ trainer.firstName }}</v-chip>
            </template>
            <template v-slot:item.action="{ item }">
              <v-btn
                      outlined
                      v-if="loggedInUser.isAdmin || loggedInUser.isTrainer"
                      @click="editItem(item)"
                      small
                      color="success">
                <v-icon>edit</v-icon>
              </v-btn>
              <v-btn
                      outlined
                      class="ml-5"
                      color="error"
                      small
                      v-if="loggedInUser.isAdmin || loggedInUser.isTrainer"
                      @click="deleteItem(item)">
                <v-icon>delete</v-icon>
              </v-btn>

            </template>
            <template v-slot:no-data>
              <v-btn color="primary" :disabled="loading" @click="reset()">
                <v-icon left>cached</v-icon>
                Keine Daten gefunden
              </v-btn>
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>
      <v-dialog
              v-model="editDialog"
              hide-overlay
              transition="dialog-bottom-transition"
              persistent
              fullscreen>
        <v-card tile>
          <v-toolbar flat>
            <v-btn icon @click="close">
              <v-icon>close</v-icon>
            </v-btn>
            <v-toolbar-title>Training Bearbeiten/Anlegen</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-toolbar-items>
              <v-btn text color="primary" @click="save"><v-icon left>check</v-icon>Speichern</v-btn>
            </v-toolbar-items>
          </v-toolbar>
          <v-divider></v-divider>
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
                    <EditTrainingBase
                            :branchId="filterBranchId"
                            :date="editedItem.date"
                            :start="editedItem.start"
                            :end="editedItem.end"
                            :locationId="editedItem.locationId"
                            :trainerIds="editedItem.trainerIds"
                            :groupIds="editedItem.groupIds"
                            :contentIds="editedItem.contentIds"
                            :comment="editedItem.comment"
                            :trainers="trainers"
                            :groups="filterGroups"
                            v-on:change="trainingBaseChanged"
                    ></EditTrainingBase>
                  </v-layout>
                </v-container>
              </v-tab-item>
              <v-tab-item :value="'tab-2'">
                <v-container grid-list-md>
                  <v-layout wrap>
                    <v-flex xs12>
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
        </v-card>
      </v-dialog>
    </v-flex>
  </v-layout>
</template>

<script lang="ts">
  import Vue from 'vue'
  import {mapGetters, mapState} from 'vuex'
  import GroupsSelectDialog from "../components/GroupsSelectDialog.vue";
  import TrainingContent from "../components/TrainingContent";
  import EditTrainingBase from "../components/EditTrainingBase";
  import Training from "@/models/Training";
  import TrainingParticipant from "@/models/TrainingParticipant";
  import {formatDate, parseDate} from "../helpers/date-helpers"

  export default Vue.extend({
    name: "Trainings",
    components: {TrainingContent, GroupsSelectDialog, EditTrainingBase},
    data: function () {
      return {
        showFilterDialog: false,
        filterGroupIds: [],
        filterBranchId: null,
        editDialog: false,
        loading: false,
        total: null,
        rowsPerPageItems: [5, 10, 20, 50, 100],
        totalItems: 0,
        page: 1,
        itemsPerPage: 10,
        sortBy: null,
        sortDesc: null,
        initializing: true,
        editDialogDateMenu: false,
        editDialogStartMenu: false,
        editDialogEndMenu: false,
        editDialogFilteredUsers: [],
        headers: [
          {text: 'Datum', value: 'date', sortable: true},
          {text: 'Von', value: 'start', sortable: false},
          {text: 'Bis', value: 'end', sortable: false},
          {text: 'Ort', value: 'locationId', sortable: true},
          {text: 'Trainer', value: 'trainerIds', sortable: false},
          {text: 'Actions', value: 'action', sortable: false },
        ],
        trainings: [],
        trainers: [],
        users: [] as Array,
        editedId: null,
        editedItem: {
          id: null,
          date: null,
          start: null,
          end: null,
          locationId: null,
          trainerIds: [] as Array,
          groupIds: [] as Array,
          participantIds: [] as Array,
          contentIds: [] as Array,
          comment: null,
        },
        defaultItem: {
          id: null,
          date: new Date().toISOString().substr(0, 10),
          start: '09:00',
          end: '12:00',
          locationId: null,
          trainerIds: [] as Array,
          groupIds: [] as Array,
          participantIds: [] as Array,
          contentIds: [] as Array,
          comment: null,
        },
      }
    },
    created() {
      if (this.trainerGroupIds && this.trainerGroupIds.length > 0) {
        this.filterBranchId = this.getBranchByGroupId(this.trainerGroupIds[0]).id;
        let firstBranchId = this.getBranchByGroupId(this.trainerGroupIds[0]).id;
        this.filterGroupIds = this.getGroupsByBranchId(firstBranchId).map(g => g.id);
      }
      this.loadData();
    },
    computed: {
      ...mapGetters({loggedInUser: 'loggedInUser'}),
      ...mapGetters('masterData', {getBranchById: 'getBranchById', getGroupsByBranchId: 'getGroupsByBranchId', getBranchByGroupId: 'getBranchByGroupId', getLocationNameById: 'getLocationNameById', getGroupsByIds: 'getGroupsByIds', getContentIdsByBranchId: 'getContentIdsByBranchId', getBranchById: 'getBranchById', getSimpleTrainersByIds: 'getSimpleTrainersByIds'}),
      ...mapState('masterData', {
        locations: 'locations',
      }),
      editedItemDateFormatted() {
        return this.formatDate(this.editedItemDate)
      },
      editedItemDate: {
        get: function () {
          return this.editedItem.date
        },
        set: function (newValue) {
          Vue.set(this.editedItem, 'date', newValue)
        }
      },
      filterGroups() {
        if (this.filterGroupIds.length > 0) {
          return this.getGroupsByIds(this.filterGroupIds)
        }
        return [];
      },
      filterTrainers() {
        if (this.filterGroupIds.length > 0) {
          return this.trainers.filter(t => t.trainerGroups.filter(tg => tg.branchId === this.filterGroupIds))
        } else if (this.filterBranchId) {
          return this.trainers.filter(g => this.filterBranchId.includes(g.branchId))
        }
        return [];
      },
      trainerGroupIds() {
        return this.loggedInUser.trainerGroupIds
      },
    },
    watch: {
      page: {
        handler () {
          if (!this.loading) {
            this.loadData();
          }
        },
        deep: true,
      },
      itemPerPage: {
        handler () {
          if (!this.loading) {
            this.loadData();
          }
        },
        deep: true,
      },
      sortBy: {
        handler () {
          if (!this.loading) {
            this.loadData();
          }
        },
        deep: true,
      },
      sortDesc: {
        handler () {
          if (!this.loading) {
            this.loadData();
          }
        },
        deep: true,
      },
      editedItemDate() {
        this.editedItem.dateFormatted = this.formatDate(this.editedItem.date)
      },
      'editedItem.groupIds'() {
        //change filtered user list on selected groups
        this.editDialogFilteredUsers = this.users.filter(u => this.editedItem.groupIds.find(gId => u.groupIds.includes(gId)));
        if (this.editedItem.participantIds) {
          //clean list from users which are not in the selected groups
          this.editedItem.participantIds = this.editedItem.participantIds.filter(pId => this.editDialogFilteredUsers.find(u => u.id === pId))
        }
      },
    },
    methods: {
      filterChanged({branchId: branchId, groupdIds: groupIds}) {
        this.filterGroupIds = groupIds;
        this.filterBranchId = branchId;
        if (!this.filterGroupIds || this.filterGroupIds.length == 0) {
          this.filterGroupIds = this.getGroupsByBranchId(this.filterBranchId).map(g => g.id);
        }
        this.loadData();
      },
      loadData() {
        this.loading = true;
        let url = '/training';
        // get by sort option
        if (this.sortBy) {
          const direction = this.sortDesc ? 'desc' : 'asc';
          url += '/sort?direction=' + direction + '&sortBy=' + this.sortBy + '&page=' + this.page + '&per_page=' + this.itemsPerPage
        } else {
          url += '?page=' + this.page + '&per_page=' + this.itemsPerPage
        }
        if (this.filterGroupIds && this.filterGroupIds.length > 0) {
          url += '&groupIds=' + this.filterGroupIds;
        }
        let p1 = this.$http.get(url).then(function (res) {
          this.trainings = res.data.data;
          this.page = res.data.meta.currentPage;
          this.totalItems = res.data.meta.total;
          this.total = res.data.meta.total;
        }.bind(this));
        let userUrl = '/user';
        if (this.filterGroupIds && this.filterGroupIds.length > 0) {
          userUrl += '?groupIds=' + this.filterGroupIds;
        }
        let p2 = this.$http.get(userUrl).then(function (res) {
          this.users = res.data;
        }.bind(this));

        let trainerUrl = '/user/trainer';
        if (this.filterGroupIds && this.filterGroupIds.length > 0) {
          trainerUrl += '?groupIds=' + this.filterGroupIds;
        }
        let p3 = this.$http.get(trainerUrl).then(function (res) {
          this.trainers = res.data;
        }.bind(this));

        Promise.all([p1, p2, p3]).then(() => {
            this.loading = false
        }).finally(() => {
          this.initializing = false;
        });
      },
      trainingBaseChanged(item) {
        Object.assign(this.editedItem, item)
      },
      async deleteItem(item) {
        if (confirm('Löschen bestätigen')) {
          const {data} = await this.$http.delete('/training/' + item.id);
          if (data.status == 'ok') {
            this.$emit("showSnackbar", "Training erfolgreich gelöscht", "success")
            this.loadData();
          } else {
            this.$emit("showSnackbar", "Training konnte nicht gelöscht werden", "error")
          }
        }
      },
      editItem(item) {
        this.editedId = item.id;
        Object.assign(this.editedItem, item)
        this.editedItemDate = this.moment(item.start, 'YYYY-MM-DDTHH:mm').format('Y-MM-DD')
        this.editedItem.start = this.moment(item.start, 'YYYY-MM-DDTHH:mm').format('HH:mm')
        this.editedItem.end = this.moment(item.end, 'YYYY-MM-DDTHH:mm').format('HH:mm')
        this.editedItem.participantIds = []
        if (item.participants) {
          for (const participant of item.participants) {
            if (participant.attend === 1) {
              this.editedItem.participantIds.push(participant.userId);
            }
          }
        }
        this.editDialog = true
      },
      create() {
        this.editedItem = {...this.defaultItem}
        this.editDialog = true
      },
      fullName: item => item.firstName + ' ' + item.familyName,
      close() {
        this.editDialog = false
        setTimeout(() => {
          this.editedItem = {...this.defaultItem}
        }, 300)
      },
      save() {
        const self = this;
        const postData = {
          start: self.moment(self.editedItem.date + 'T' + self.editedItem.start, 'YYYY-MM-DDTHH:mm').format(),
          end: self.moment(self.editedItem.date + 'T' + self.editedItem.end, 'YYYY-MM-DDTHH:mm').format(),
          locationId: self.editedItem.locationId,
          groupIds: self.editedItem.groupIds,
          trainerIds: self.editedItem.trainerIds,
          participantIds: self.editedItem.participantIds,
          contentIds: self.editedItem.contentIds,
          comment: self.editedItem.comment,
        }
        if (self.editedId) {
          postData.id = self.editedId;
          self.$http.put('/training/' + self.editedId, postData)
            .then(function (res) {
              if (!res.data.error) {
                self.close()
                self.$emit("showSnackbar", "Training gespeichert", "success")
                self.$http.get('/training/' + self.editedId)
                        .then(function ({data}) {
                            const index = self.trainings.findIndex(t => (t && t.id == self.editedId));
                            self.trainings.splice(index, 1, data.data);
                        });

              } else {
                console.error(res.data.error);
                self.$emit("showSnackbar", "Training konnte nicht gespeichert werden", "error")
              }
            })
            .catch(function (err) {
              console.log(err);
              self.$emit("showSnackbar", "Training konnte nicht gespeichert werden", "error")
            })
        } else {

          self.$http.post('/training', postData)
            .then(function (res) {
              if (!res.data.error) {
                self.close()
                self.$emit("showSnackbar", "Training gespeichert", "success")
                self.loadData();
              } else {
                console.error(res.data.error);
                self.$emit("showSnackbar", "Training konnte nicht gespeichert werden", "error")
              }
            })
            .catch(function (err) {
              console.log(err);
              self.$emit("showSnackbar", "Training konnte nicht gespeichert werden", "error")
            })
        }
      },
      branchAndGroupName(item) {
        return this.getBranchById(item.branchId).shortName + '/' + item.name;
      },
      formatDate,
      parseDate,
    },
  })
</script>

<style scoped>

</style>

<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar>
            <v-container fluid>
              <v-row align="center">
                <v-col cols="6">
                  <v-toolbar-title>Trainingsverwaltung</v-toolbar-title>
                </v-col>
                <v-col cols="6">
                  <v-select
                      class="pt-6"
                      :items="filterBranches"
                      v-model="filterBranchId"
                      v-on:change="filterChanged()"
                      item-text="name"
                      item-value="id"
                      flat
                      dense
                      label="Sparte auswählen"
                      prepend-icon="bubble_chart"
                  >
                  </v-select>
                </v-col>
              </v-row>
            </v-container>
            <template v-slot:extension>
              <v-btn
                  title="Neues Training anlegen"
                  fab
                  absolute
                  bottom
                  left
                  elevation="2"
                  color="primary"
                  @click="create()">
                <v-icon>add</v-icon>
              </v-btn>
            </template>
          </v-toolbar>
          <v-divider></v-divider>
          <v-card-text class="mt-4 pa-0 pa-md-4">
            <v-card>
              <v-card-text class="pa-0 pa-md-4">
                <v-container>
                  <v-row no-gutters>
                    <v-col>
                      <TrainingCalendar
                          v-if="activeView == 1">
                      </TrainingCalendar>
                      <v-data-table
                          v-if="activeView == 0"
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
                                  outlined
                                  class="ma-1">
                            {{ trainer.firstName }}
                          </v-chip>
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
                    </v-col>
                  </v-row>
                </v-container>
              </v-card-text>
            </v-card>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    <v-dialog
        v-model="editDialog"
        hide-overlay
        transition="dialog-bottom-transition"
        persistent
        scrollable
        fullscreen>
      <v-card tile>
        <v-toolbar flat>
          <v-btn icon @click="close">
            <v-icon>close</v-icon>
          </v-btn>
          <v-toolbar-title>{{ editDialogTitle }}</v-toolbar-title>
          <v-spacer></v-spacer>
          <v-toolbar-items>
            <v-btn text color="primary" @click="save">
              <v-icon left>check</v-icon>
              Speichern
            </v-btn>
          </v-toolbar-items>
        </v-toolbar>
        <v-divider></v-divider>
        <v-card-text>
          <EditTrainingBase
              hide-contents
              :date="editedItem.date"
              :start="editedItem.start"
              :end="editedItem.end"
              :locationId="editedItem.locationId"
              :trainerIds="editedItem.trainerIds"
              :groupIds="editedItem.groupIds"
              :contentIds="editedItem.contentIds"
              :comment="editedItem.comment"
              :trainers="trainers"
              :groups="groups"
              v-on:change="trainingBaseChanged"
          ></EditTrainingBase>
        </v-card-text>
      </v-card>
    </v-dialog>
    <!--<v-bottom-navigation
             v-model="activeView"
             hide-on-scroll
             scroll-threshold="50"
             fixed
     >
       <v-btn>
         <span>Listenansicht</span>
         <v-icon>list</v-icon>
       </v-btn>
       <v-btn>
         <span>Kalendaransicht</span>
         <v-icon>event</v-icon>
       </v-btn>
     </v-bottom-navigation>-->
  </v-container>
</template>

<script lang="ts">
import Vue from 'vue'
import {mapGetters, mapState} from 'vuex'
import TrainingCalendar from "../components/TrainingCalendar.vue";
import EditTrainingBase from "../components/EditTrainingBase";
import {formatDate, parseDate} from "../helpers/date-helpers"

export default Vue.extend({
  name: "Trainings",
  components: {EditTrainingBase, TrainingCalendar},
  data: function () {
    return {
      showFilterDialog: false,
      filterBranchId: Number,
      filterBranches: [{id: -1, name: 'Alle'}],
      editDialog: false,
      editDialogTitle: String,
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
        {text: '', value: 'action', sortable: false},
      ],
      trainings: [],
      trainers: [],
      editedId: null,
      editedItem: {
        id: null,
        date: null,
        start: null,
        end: null,
        locationId: null,
        trainerIds: [] as Array<Number>,
        groupIds: [] as Array<Number>,
        contentIds: [] as Array<Number>,
        comment: null,
      },
      defaultItem: {
        id: null,
        date: new Date().toISOString().substr(0, 10),
        start: '09:00',
        end: '12:00',
        locationId: null,
        trainerIds: [] as Array<Number>,
        groupIds: [] as Array<Number>,
        contentIds: [] as Array<Number>,
        comment: null,
      },
      activeView: 0,
    }
  },
  created() {
    if (this.trainerBranchIds && this.trainerBranchIds.length > 0) {
      this.filterBranchId = this.getBranchByGroupId(this.trainerBranchIds[0]).id;
    }
    this.filterBranches = this.filterBranches.concat(this.branches)

    this.loadData();
  },
  computed: {
    ...mapGetters({loggedInUser: 'loggedInUser'}),
    ...mapGetters('masterData', {
      getGroupsByBranchId: 'getGroupsByBranchId',
      getBranchByGroupId: 'getBranchByGroupId',
      getLocationNameById: 'getLocationNameById',
      getGroupsByIds: 'getGroupsByIds',
      getContentIdsByBranchId: 'getContentIdsByBranchId',
      getBranchById: 'getBranchById',
      getSimpleTrainersByIds: 'getSimpleTrainersByIds'
    }),
    ...mapState('masterData', {
      locations: 'locations',
      branches: 'branches',
      groups: 'groups'
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
    trainerBranchIds() {
      return this.loggedInUser.trainerBranchIds
    },
  },
  watch: {
    page: {
      handler() {
        if (!this.loading) {
          this.loadData();
        }
      },
      deep: true,
    },
    itemPerPage: {
      handler() {
        if (!this.loading) {
          this.loadData();
        }
      },
      deep: true,
    },
    sortBy: {
      handler() {
        if (!this.loading) {
          this.loadData();
        }
      },
      deep: true,
    },
    sortDesc: {
      handler() {
        if (!this.loading) {
          this.loadData();
        }
      },
      deep: true,
    },
    editedItemDate() {
      this.editedItem.dateFormatted = this.formatDate(this.editedItem.date)
    },
  },
  methods: {
    filterChanged() {
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
      // Don't set filter with the general item 'Allgemein'
      if (this.filterBranchId > 0) {
        url += '&groupIds=' + this.getGroupsByBranchId(this.filterBranchId).map(g => g.id);
      }
      let p1 = this.$http.get(url).then(function (res) {
        this.trainings = res.data.data;
        this.page = res.data.meta.currentPage;
        this.totalItems = res.data.meta.total;
        this.total = res.data.meta.total;
      }.bind(this));

      let p2 = this.$http.get('/user/trainer').then(function (res) {
        this.trainers = res.data;
      }.bind(this));

      Promise.all([p1, p2]).then(() => {
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
      this.editDialogTitle = 'Training bearbeiten'
      this.editedId = item.id;
      Object.assign(this.editedItem, item)
      this.editedItemDate = this.moment(item.start, 'YYYY-MM-DDTHH:mm').format('Y-MM-DD')
      this.editedItem.start = this.moment(item.start, 'YYYY-MM-DDTHH:mm').format('HH:mm')
      this.editedItem.end = this.moment(item.end, 'YYYY-MM-DDTHH:mm').format('HH:mm')
      this.editDialog = true
    },
    create() {
      this.editedItem = {...this.defaultItem}
      this.editDialogTitle = 'Training erstellen'
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
        id: null,
        start: self.moment(self.editedItem.date + 'T' + self.editedItem.start, 'YYYY-MM-DDTHH:mm').format(),
        end: self.moment(self.editedItem.date + 'T' + self.editedItem.end, 'YYYY-MM-DDTHH:mm').format(),
        locationId: self.editedItem.locationId,
        groupIds: self.editedItem.groupIds,
        trainerIds: self.editedItem.trainerIds,
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
    formatDate,
    parseDate,
  },
})
</script>

<style scoped>

</style>

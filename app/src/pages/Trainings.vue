<template>
  <v-layout align-top>
    <v-flex xs12 md10 offset-md1 top>
      <v-card>
        <v-toolbar card prominent>
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
                :key="item.id">{{item.name}}
              </v-chip>
            </div>
            <div v-else-if="filterBranchId">
              <v-chip
                small>
                {{getBranchById(filterBranchId).name}}
              </v-chip>
            </div>
          </div>
          <v-btn title="Liste nach Sparte und Gruppe filtern" icon color="primary" @click="showFilterDialog = true">
            <v-icon>filter_list</v-icon>
          </v-btn>
          <GroupsSelectDialog
            v-bind:visible="showFilterDialog"
            v-on:close="showFilterDialog = false"
            v-bind:groupIds="trainerGroupIds"
            v-on:done="filterChanged">
          </GroupsSelectDialog>
        </v-toolbar>
        <v-divider></v-divider>
        <v-card-text>
          <v-data-table
            :headers="headers"
            :items="trainings"
            :loading="loading"
            :pagination.sync="pagination"
            :total-items="total"
            :rows-per-page-items="rowsPerPageItems"
            class="elevation-1"
          >
            <v-progress-linear slot="progress" color="primary" indeterminate></v-progress-linear>
            <template slot="items" slot-scope="props">
              <tr @click="editItem(props.item)" style="cursor: pointer">
              <td class="text-xs-left">{{ moment(props.item.start).format('DD.MM.Y') }}</td>
              <td class="text-xs-left">{{ moment(props.item.start).format('HH:mm') }}</td>
              <td class="text-xs-left">{{ moment(props.item.end).format('HH:mm') }}</td>
              <td class="text-xs-left">{{ getLocationNameById(props.item.locationId) }}</td>
              <td class="justify-center layout px-0">
                <v-icon
                  small
                  v-if="loggedInUser.isAdmin || loggedInUser.isTrainer"
                  @click="deleteItem(props.item)"
                >
                  delete
                </v-icon>
              </td>
              </tr>
            </template>
            <template slot="no-data">
              <v-container fluid>
                <v-layout row justify-center>
                  <v-btn color="error" :disabled="loading" @click="reset()">
                    <v-icon left>cached</v-icon>
                    Keine Daten gefunden
                  </v-btn>
                </v-layout>
              </v-container>
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>
      <v-dialog v-model="dialog" max-width="1000px" :fullscreen="$vuetify.breakpoint.xsOnly">
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
                              ref="editDialogDateMenu"
                              :close-on-content-click="false"
                              v-model="editDialogDateMenu"
                              lazy
                              full-width
                      >
                        <v-text-field
                                slot="activator"
                                v-model="editedItemDateFormatted"
                                required
                                label="Datum"
                                prepend-icon="event"
                                readonly
                        ></v-text-field>
                        <v-date-picker v-model="editedItemDate" @input="editDialogDateMenu = false"></v-date-picker>
                      </v-menu>
                    </v-flex>
                    <v-flex xs6 md3>
                      <v-menu
                              ref="editDialogStartMenu"
                              :close-on-content-click="false"
                              v-model="editDialogStartMenu"
                              lazy
                              full-width
                      >
                        <v-text-field
                                slot="activator"
                                v-model="editedItem.start"
                                label="Start"
                                required
                                prepend-icon="schedule"
                                readonly
                        ></v-text-field>
                        <v-time-picker v-model="editedItem.start" @input="editDialogStartMenu = false" format="24hr"></v-time-picker>
                      </v-menu>
                    </v-flex>
                    <v-flex xs6 md3>
                      <v-menu
                              ref="editDialogEndMenu"
                              :close-on-content-click="false"
                              v-model="editDialogEndMenu"
                              lazy
                              full-width
                      >
                        <v-text-field
                                slot="activator"
                                v-model="editedItem.end"
                                required
                                label="Ende"
                                prepend-icon="schedule"
                                readonly
                        ></v-text-field>
                        <v-time-picker v-model="editedItem.end" @input="editDialogEndMenu = false" format="24hr"></v-time-picker>
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
    </v-flex>
  </v-layout>
</template>

<script lang="ts">
  import Vue from 'vue'
  import {mapGetters, mapState} from 'vuex'
  import GroupsSelectDialog from "../components/GroupsSelectDialog.vue";
  import TrainingContent from "../components/TrainingContent";
  import Training from "@/models/Training";
  import TrainingParticipant from "@/models/TrainingParticipant";
  import {formatDate, parseDate} from "../helpers/date-helpers"

  export default Vue.extend({
    name: "Trainings",
    components: {TrainingContent, GroupsSelectDialog},
    data: function () {
      return {
        showFilterDialog: false,
        filterGroupIds: [],
        filterBranchId: null,
        dialog: false,
        loading: false,
        total: null,
        rowsPerPageItems: [5, 10, 20, 50, 100],
        pagination: {
          descending: true,
        },
        editDialogDateMenu: false,
        editDialogStartMenu: false,
        editDialogEndMenu: false,
        editDialogFilteredUsers: [],
        headers: [
          {text: 'Datum', value: 'date'},
          {text: 'Von', value: 'start'},
          {text: 'Bis', value: 'end'},
          {text: 'Ort', value: 'locationId'},
        ],
        trainings: [],
        trainers: [],
        users: [],
        editedId: null,
        editedItem: {
          id: null,
          date: null,
          dateFormatted: null,
          start: null,
          end: null,
          locationId: null,
          trainerIds: [],
          groupIds: [],
          participantIds: [],
          contentIds: [],
          comment: null,
        },
        defaultItem: {
          id: null,
          date: new Date().toISOString().substr(0, 10),
          dateFormatted: this.formatDate(new Date().toISOString().substr(0, 10)),
          start: '09:00',
          end: '12:00',
          locationId: null,
          trainerIds: [],
          groupIds: [],
          participantIds: [],
          contentIds: [],
          comment: null,
        },
      }
    },
    created() {
      this.filterGroupIds = this.trainerGroupIds;
      this.pagination.rowsPerPage = 10;
    },
    computed: {
      ...mapGetters({loggedInUser: 'loggedInUser'}),
      ...mapGetters('masterData', {getBranchById: 'getBranchById', getGroupsByBranchId: 'getGroupsByBranchId', getLocationNameById: 'getLocationNameById', getGroupsByIds: 'getGroupsByIds', getTrainerBranchIdByUser: 'getTrainerBranchIdByUser', getContentIdsByBranchId: 'getContentIdsByBranchId'}),
      ...mapState('masterData', {
        locations: 'locations',
      }),
      editedItemDateFormatted() {
        return this.formatDate(this.editedItemDate)
      },
      editedItemDate: {

        // getter
        get: function () {
          return this.editedItem.date
        },
        // setter
        set: function (newValue) {
          Vue.set(this.editedItem, 'date', newValue)
        }
      },
      filterGroups() {
        if (this.filterGroupIds.length > 0) {
          return this.getGroupsByIds(this.filterGroupIds)
        } else if (this.filterBranchId) {
          return this.getGroupsByBranchId(this.filterBranchId)
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
      branchContentIds() {
        return this.getContentIdsByBranchId(this.getTrainerBranchIdByUser(this.loggedInUser));
      }
    },
    watch: {
      pagination: {
        handler() {
          if (!this.loading) {
            this.loadData(true);
          }
        },
        deep: true
      },
      dialog(val) {
        val || this.close()
      },
      editedItemDate() {
        this.editedItem.dateFormatted = this.formatDate(this.editedItem.date)
      },
      'editedItem.groupIds'() {
        //change filtered user list on selected groups
        this.editDialogFilteredUsers = this.users.filter(u => this.editedItem.groupIds.find(gId => gId === u.groupId));
        //clean list from users which are not in the selected groups
        this.editedItem.participantIds = this.editedItem.participantIds.filter(pId => this.editDialogFilteredUsers.find(u => u.id === pId))
      },
    },
    methods: {
      filterChanged({branchId: branchId, groupdIds: groupIds}) {
        this.filterGroupIds = groupIds;
        this.filterBranchId = branchId;
        this.loadData(true);
      },
      loadData(loadCurrent) {
        this.loading = true;
        let url = '/training';
        // get by sort option
        if (this.pagination.sortBy) {
          const direction = this.pagination.descending ? 'desc' : 'asc';
          url += '/sort?direction=' + direction + '&sortBy=' + this.pagination.sortBy + '&page=' + this.pagination.page + '&per_page=' + this.pagination.rowsPerPage
        } else {
          url += '?page=' + this.pagination.page + '&per_page=' + this.pagination.rowsPerPage
        }
        if (this.filterGroupIds && this.filterGroupIds.length > 0) {
          url += '&groupIds=' + this.filterGroupIds;
        } else if (this.filterBranchId) {
          url += '&branchId=' + this.filterBranchId;
        }
        if (loadCurrent) {
          url += "&current";
        }
        let p1 = this.$http.get(url).then(function (res) {
          this.trainings = res.data.data;
          this.pagination.page = res.data.meta.currentPage;
          this.pagination.totalItems = res.data.meta.total;
          this.total = res.data.meta.total;
        }.bind(this));
        let userUrl = '/user';
        if (this.filterGroupIds && this.filterGroupIds.length > 0) {
          userUrl += '?groupIds=' + this.filterGroupIds;
        } else if (this.filterBranchId) {
          userUrl += '?branchId=' + this.filterBranchId;
        }
        let p2 = this.$http.get(userUrl).then(function (res) {
          this.users = res.data;
        }.bind(this));

        let trainerUrl = '/user/trainer';
        if (this.filterGroupIds && this.filterGroupIds.length > 0) {
          trainerUrl += '?groupIds=' + this.filterGroupIds;
        } else if (this.filterBranchId) {
          trainerUrl += '?branchId=' + this.filterBranchId;
        }
        let p3 = this.$http.get(trainerUrl).then(function (res) {
          this.trainers = res.data;
        }.bind(this));

        Promise.all([p1, p2, p3]).then(function () {
            this.loading = false
        }.bind(this));
      },
      editItem(item) {
        if (this.loggedInUser.isAdmin || this.loggedInUser.isTrainer) {
          this.editedId = item.id
          this.editedItem = {...item}
          this.editedItemDate = this.moment(item.start).format('Y-MM-DD')
          this.editedItem.start = this.moment(item.start).format('HH:mm')
          this.editedItem.end = this.moment(item.end).format('HH:mm')
          this.editedItem.participantIds = []
          for (const participant of item.participants) {
            if (participant.attend === 1) {
              this.editedItem.participantIds.push(participant.userId);
            }
          }
          this.dialog = true
        }
      },
      create() {
        this.editedItem = {...this.defaultItem}
        this.dialog = true
      },
      deleteItem(item) {
        if (confirm('Löschen bestätigen')) {
          this.$http.delete('/user/' + item.id)
            .then(this.userDeleted(item)).catch(function (err) {
            console.log(err);
            this.$emit("showSnackbar", "Benutzer konnte nicht gelöscht werden", "error")
          })
        }
      },
      userDeleted(item) {
        this.$emit("showSnackbar", "Benutzer " + item.firstName + " " + item.familyName + " erfolgreich gelöscht", "success")
        this.users.splice(this.users.indexOf(item), 1)
      },
      editedItemGroupIdChanged(groupId) {
        this.editedItem.groupId = groupId;
      },
      editItemContentSelected(contentId) {
        if (!this.editedItem.contentIds.includes(contentId)) {
          this.editedItem.contentIds.push(contentId);
        }
      },
      editItemContentUnselected(contentId) {
        var index = this.editedItem.contentIds.indexOf(contentId);
        if (index > -1) {
          this.editedItem.contentIds.splice(index, 1);
        }
      },
      fullName: item => item.firstName + ' ' + item.familyName,
      close() {
        this.dialog = false
        setTimeout(() => {
          this.editedItem = {...this.defaultItem}
        }, 300)
      },
      reset() {
        this.loadData();
      },
      save() {
        var self = this;
        var data = {
          start: self.moment(self.editedItem.date + ' ' + self.editedItem.start).format(),
          end: self.moment(self.editedItem.date + ' ' + self.editedItem.end).format(),
          locationId: self.editedItem.locationId,
          groupIds: self.editedItem.groupIds,
          trainerIds: self.editedItem.trainerIds,
          participantIds: self.editedItem.participantIds,
          contentIds: self.editedItem.contentIds,
          comment: self.editedItem.comment,
        }
        if (this.editedId) {
          data.id = this.editedId;
          self.$http.put('/training/' + self.editedId, data)
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
        } else {

          self.$http.post('/training', data)
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

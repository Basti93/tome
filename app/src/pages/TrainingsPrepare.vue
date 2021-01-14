<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Trainingsvorbereitung</v-toolbar-title>
          </v-toolbar>
          <v-card-text v-show="dataLoaded">
            <v-container class="pa-0">
              <v-row no-gutters>
                <v-col md="3" cols="0">
                  <TrainingSelector
                      :trainings="upcomingTrainings"
                      :selectedTrainingId="selectedTrainingId"
                      v-on:change="selectTraining"
                  ></TrainingSelector>
                </v-col>
                <v-col md="9">
                  <v-slide-x-transition v-if="selectedTraining">
                    <div v-if="animationTrigger">
                      <v-card class="ma-1">
                        <v-card-title class="justify-center">
                          {{ selectedTraining.start.format('dddd [den] Do MMMM') }}
                        </v-card-title>
                        <v-card-subtitle class="text-center">{{ selectedTraining.start.fromNow() }}</v-card-subtitle>
                        <v-divider></v-divider>
                        <v-card-text class="text-center pa-0 pa-md-4">
                          <v-container>
                            <v-row>
                              <v-col>
                                Von
                                <v-chip
                                    outlined
                                    @click="editTime()"
                                >
                                  <v-icon
                                      small
                                      color="primary"
                                      left>
                                    edit
                                  </v-icon>
                                  {{ selectedTraining.start.format('HH:mm') }}
                                </v-chip>
                                Uhr bis
                                <v-chip
                                    outlined
                                    @click="editTime()"
                                >
                                  <v-icon
                                      small
                                      color="primary"
                                      left>
                                    edit
                                  </v-icon>
                                  {{ selectedTraining.end.format('HH:mm') }}
                                </v-chip>
                                Uhr
                              </v-col>
                            </v-row>
                            <v-row>
                              <v-col md="6" v-if="editingLocation">
                                <v-select
                                    :items="locations"
                                    item-text="name"
                                    item-value="id"
                                    v-model="editLocationId"
                                    clearable
                                    required
                                    label="Ort"
                                    prepend-icon="add_location"
                                ></v-select>
                                <v-btn @click="saveEditLocation()"
                                       color="primary"
                                       text>
                                  <v-icon>check</v-icon>
                                </v-btn>
                                <v-btn @click="cancelEditLocation()"
                                       color="primary"
                                       text>
                                  <v-icon>cancel</v-icon>
                                </v-btn>
                              </v-col>
                              <v-col v-else>
                                <v-chip
                                    outlined
                                    @click="editLocation()"
                                >
                                  <v-icon
                                      small
                                      color="primary"
                                      left>
                                    edit
                                  </v-icon>
                                  {{ getLocationNameById(selectedTraining.locationId) }}
                                </v-chip>
                              </v-col>
                            </v-row>
                            <v-row>
                              <v-col>
                                <v-btn
                                    :href="sharelink"
                                    target="_blank"
                                    color="primary">
                                  <v-icon left>share</v-icon>
                                  Mit Whatsapp teilen
                                </v-btn>
                              </v-col>
                            </v-row>
                          </v-container>
                        </v-card-text>
                      </v-card>
                      <v-card class="ma-1">
                        <v-card-title>
                          <h6>Gruppen</h6>
                        </v-card-title>
                        <v-divider></v-divider>
                        <v-card-text>
                          <GroupChip
                              v-for="(item) in groups"
                              :key="item.id"
                              :group="item"></GroupChip>
                        </v-card-text>
                      </v-card>
                      <v-card class="ma-1">
                        <v-card-text class="pa-0 pa-md-4">
                          <v-container no-gutters>
                            <v-row v-if="editingComment">
                              <v-col
                                  cols="9"
                              >
                                <v-textarea
                                    filled
                                    label="Kommentar"
                                    rows="3"
                                    v-model="editComment"
                                ></v-textarea>
                              </v-col>
                              <v-col
                                  cols="3"
                              >
                                <v-btn
                                    class="ma-2"
                                    @click="saveEditComment()"
                                    color="primary"
                                >
                                  <v-icon>check</v-icon>
                                </v-btn>
                                <v-btn
                                    class="ma-2"
                                    @click="cancelEditComment()"
                                    color="error"
                                >
                                  <v-icon>cancel</v-icon>
                                </v-btn>
                              </v-col>
                            </v-row>
                            <v-row v-else>
                              <v-col
                                  cols="12"
                                  sm="12"
                                  md="10"
                              >
                                <v-textarea
                                    filled
                                    @click="startEditComment()"
                                    label="Kommentar"
                                    v-model="selectedTraining.comment"
                                    readonly
                                    rows="3"
                                    append-icon="edit"
                                ></v-textarea>
                              </v-col>
                            </v-row>
                          </v-container>
                        </v-card-text>
                      </v-card>
                      <v-card class="ma-1" v-if="branchContentIds.length > 0">
                        <v-card-title>Trainingsinhalte</v-card-title>
                        <v-card-text class="pa-0 pa-md-4">
                          <v-container>
                            <v-row>
                              <v-col
                                  cols="12"
                              >
                                <TrainingContent
                                    :contentIds="branchContentIds"
                                    :initContentIds="selectedTraining.contentIds"
                                    selectable
                                    v-on:change="saveEditTrainingContent"
                                >
                                </TrainingContent>
                              </v-col>
                            </v-row>
                          </v-container>
                        </v-card-text>
                      </v-card>
                      <v-card class="ma-1">
                        <v-card-title>Teilnehmer</v-card-title>
                        <v-card-text class="pa-0 pa-md-4">
                          <v-list-group
                              v-model="participantsListGroupActive"
                              prepend-icon="check"
                              group="participants"
                              no-action
                          >
                            <template slot="activator">
                              <v-list-item>
                                <v-list-item-content>
                                  <v-list-item-title>{{ participatingUsers.length }} Teilnehmer bis jetzt
                                  </v-list-item-title>
                                </v-list-item-content>
                              </v-list-item>
                            </template>
                            <v-list-item
                                v-for="(item) in participatingUsers"
                                :key="item.id"
                            >
                              <tome-list-item-profile-image
                                  :image-path="item.profileImageName">
                              </tome-list-item-profile-image>

                              <v-list-item-content>
                                <v-list-item-title>{{ fullName(item) }}</v-list-item-title>
                                <v-list-item-subtitle>
                                  <span class="label">{{
                                      getGroupsByIds(item.groupIds).map(g => g.name).join(', ')
                                    }}</span>
                                </v-list-item-subtitle>
                              </v-list-item-content>
                            </v-list-item>
                          </v-list-group>
                          <v-list-group
                              v-model="canceledUserListGroupActive"
                              prepend-icon="cancel"
                              group="canceledusers"
                              no-action
                          >
                            <template slot="activator">
                              <v-list-item>
                                <v-list-item-content>
                                  <v-list-item-title>{{ canceledUsers.length }} <span
                                      v-if="canceledUsers.length == 1">Absage</span><span
                                      v-else>Absagen</span></v-list-item-title>
                                </v-list-item-content>
                              </v-list-item>
                            </template>
                            <v-list-item
                                v-for="(item) in canceledUsers"
                                :key="item.id"
                            >
                              <tome-list-item-profile-image
                                  :image-path="item.profileImageName">
                              </tome-list-item-profile-image>

                              <v-list-item-content @click="openCancelReasonDialog(item.id)">
                                <v-list-item-title>{{ fullName(item) }}</v-list-item-title>
                                <v-list-item-subtitle>
                                  <span class="label">{{
                                      getGroupsByIds(item.groupIds).map(g => g.name).join(', ')
                                    }}</span>
                                </v-list-item-subtitle>
                                <v-list-item-subtitle v-if="getCancelReason(item.id)" class="warning--text">Grund:
                                  {{ getCancelReason(item.id) }}
                                </v-list-item-subtitle>
                              </v-list-item-content>
                            </v-list-item>
                          </v-list-group>
                        </v-card-text>
                      </v-card>
                    </div>
                  </v-slide-x-transition>
                  <v-alert
                      v-else
                      type="info"
                      class="text-small"
                      pa-0
                      ma-0
                      outlined>
                    Keine Trainings für dich verfügbar
                  </v-alert>
                </v-col>
              </v-row>
            </v-container>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    <v-dialog
        v-model="showCancelReasonDialog"
        max-width="800px">
      <v-card>
        <v-toolbar flat>
          <v-btn icon @click="showCancelReasonDialog=false">
            <v-icon>close</v-icon>
          </v-btn>
          <v-toolbar-title>Grund</v-toolbar-title>
        </v-toolbar>

        <v-card-text class="warning--text">{{ cancelReasonDialogText }}</v-card-text>
      </v-card>
    </v-dialog>
    <v-dialog
        v-model="timeDialogOpened"
        max-width="800px"
        scrollable
        :fullscreen="$vuetify.breakpoint.xsOnly"
        persistent>
      <v-card>
        <v-card-title>
          <v-btn icon @click="timeDialogOpened=false">
            <v-icon>close</v-icon>
          </v-btn>
          Trainingszeit ändern
          <v-spacer></v-spacer>
          <v-btn text color="primary" @click="updateTrainingTime">
            <v-icon left>check</v-icon>
            Speichern
          </v-btn>
        </v-card-title>
        <v-divider></v-divider>
        <v-card-text>
          <v-row
              justify="space-around"
              align="center"
          >
            <v-col style="width: 350px; flex: 0 1 auto;">
              <h2>Von</h2>
              <v-time-picker
                  flat
                  v-model="editStartTime"
                  :max="editEndTime"
                  format="24hr"
              ></v-time-picker>
            </v-col>
            <v-col style="width: 350px; flex: 0 1 auto;">
              <h2>Bis</h2>
              <v-time-picker
                  v-model="editEndTime"
                  :min="editStartTime"
                  format="24hr"
              ></v-time-picker>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script lang="ts">

import Vue from "vue";
import {mapGetters, mapState} from 'vuex'
import Training from "@/models/Training";
import TrainingParticipant from "@/models/TrainingParticipant";
import TrainingContent from "@/components/TrainingContent"
import ListItemProfileImage from "@/components/ListItemProfileImage"
import TrainingSelector from "../components/TrainingSelector.vue";
import GroupChip from "../components/GroupChip.vue";

export default Vue.extend({
  name: "TrainingsPrepare",
  components: {
    GroupChip,
    TrainingSelector,
    TrainingContent,
    "tome-list-item-profile-image": ListItemProfileImage
  },
  data: function () {
    return {
      upcomingTrainings: [] as Training[],
      dataLoaded: false,
      selectedTrainingId: null,
      animationTrigger: true,
      users: [],
      selectedLocationId: null,
      trainingDataGroupActive: true,
      participantsListGroupActive: false,
      canceledUserListGroupActive: false,
      showCancelReasonDialog: false,
      cancelReasonDialogText: null,
      timeDialogOpened: false,
      editStartTime: null,
      editEndTime: null,
      editingLocation: false,
      editLocationId: null,
      editingComment: false,
      editingTrainingContent: false,
      editComment: null,
      editTrainingContentIds: [],
      branchId: null,
    }
  },
  computed: {
    ...mapGetters({loggedInUser: 'loggedInUser'}),
    ...mapGetters('masterData', {
      getBranchByGroupId: 'getBranchByGroupId',
      getGroupById: 'getGroupById',
      getBranchById: 'getBranchById',
      getLocationNameById: 'getLocationNameById',
      getContentIdsByBranchId: 'getContentIdsByBranchId',
      getGroupsByIds: 'getGroupsByIds',
      getContentIdsByGroupIds: 'getContentIdsByGroupIds',
      getSimpleTrainersByIds: 'getSimpleTrainersByIds',
    }),
    ...mapState('masterData', {
      locations: 'locations',
    }),
    branchContentIds(): Array<Number> {
      //check if multiple branches are in the play
      let groupBranchIds = this.getGroupsByIds(this.selectedTraining.groupIds).map(g => g.branchId);
      if (groupBranchIds.every(v => v === groupBranchIds[0])) {
        return this.getContentIdsByGroupIds(this.selectedTraining.groupIds);
      }
      return [];
    },
    selectedTraining() {
      return this.getUpcomingTrainingById(this.selectedTrainingId);
    },
    groups() {
      return this.getGroupsByIds(this.selectedTraining.groupIds);
    },
    participatingUsers() {
      if (this.selectedTraining.participants) {
        const filteredUserIds = this.selectedTraining.participants.filter(p => p.attend).map(p => p.userId);
        if (filteredUserIds.length > 0) {
          return this.users.filter(u => filteredUserIds.indexOf(u.id) >= 0);
        }
      }
      return [];
    },
    canceledUsers() {
      if (this.selectedTraining.participants) {
        const filteredUserIds = this.selectedTraining.participants.filter(p => !p.attend).map(p => p.userId);
        if (filteredUserIds.length > 0) {
          return this.users.filter(u => filteredUserIds.indexOf(u.id) >= 0);
        }
      }
      return [];
    },
    currentUserGroupId() {
      if (this.currentUser && this.currentUser.groupIds && this.currentUser.groupIds.length > 0) {
        return this.currentUser.groupIds[0]
      }
      return null
    },
    sharelink() {
      let trainers = this.getSimpleTrainersByIds(this.selectedTraining.trainerIds);
      let trainerText = "";
      for (let i = 0; i < trainers.length; i++) {
        trainerText += trainers[i].firstName + " " + trainers[i].familyName
        if (i < trainers.length - 1) {
          trainerText += ", "
        }
      }
      const link = "https://wa.me/?text=" + encodeURIComponent(
          "Training am " + this.selectedTraining.start.format('dddd [den] DD.MM.YYYY')
          + "\r\n"
          + "\r\n"
          + "Uhrzeit: " + this.selectedTraining.start.format('HH:mm') + " - " + this.selectedTraining.end.format('HH:mm') + " Uhr"
          + "\r\n"
          + "Ort: " + this.getLocationNameById(this.selectedTraining.locationId)
          + "\r\n"
          + "Trainer: " + trainerText
          + "\r\n"
          + "\r\n"
          + "Anmelden und weitere Informationen unter " + window.location.origin + "/#/training/" + this.selectedTraining.id)
      return link;
    },
  },
  created() {
    this.fetchData();
  },
  methods: {
    getCancelReason(userId: Number) {
      const cancelreason = this.selectedTraining.participants.filter(p => p.userId == userId)[0].cancelreason;
      if (cancelreason) {
        return cancelreason
      }
      return null;
    },
    openCancelReasonDialog(userId: Number) {
      const cancelReason = this.getCancelReason(userId);
      if (cancelReason) {
        this.cancelReasonDialogText = cancelReason;
        this.showCancelReasonDialog = true;
      }
    },
    async fetchData() {
      try {
        this.dataLoaded = false;
        this.upcomingTrainings = [];
        //load data
        const res = await this.$http.get('/trainingprepare/' + this.loggedInUser.id);
        if (res.data.data && res.data.data.length > 0) {
          //json result to objects
          for (let trObj of res.data.data) {
            let participants = [] as TrainingParticipant[];
            for (let partObj of trObj.participants) {
              participants.push(new TrainingParticipant(partObj.trainingId, partObj.userId, partObj.attend === 1 ? true : false, partObj.cancelreason));
            }
            this.upcomingTrainings.push(new Training(trObj.id, this.moment(trObj.start, 'YYYY-MM-DDTHH:mm'), this.moment(trObj.end, 'YYYY-MM-DDTHH:mm'), trObj.locationId, trObj.groupIds, trObj.contentIds, trObj.trainerIds, participants, trObj.comment, false, false));
          }
          //select first training
          this.selectTraining(this.upcomingTrainings[0].id);
        }
        const res2 = await this.$http.get('/user');
        this.users = res2.data;
      } catch (error) {
        console.error(error);
      } finally {
        this.dataLoaded = true;
      }
    },
    selectTraining(id) {
      this.animationTrigger = false;
      this.selectedTrainingId = id;
      if (this.selectedTraining.groupIds && this.selectedTraining.groupIds.length > 0) {
        this.branchId = this.getBranchByGroupId(this.selectedTraining.groupIds[0]).id;
      }
      setTimeout(() => {
        this.animationTrigger = true;
      }, 100);
    },
    getUpcomingTrainingById(id) {
      return this.upcomingTrainings.filter(ut => ut.id == id)[0];
    },
    editTime() {
      this.editStartTime = this.selectedTraining.start.format('HH:mm')
      this.editEndTime = this.selectedTraining.end.format('HH:mm')
      this.timeDialogOpened = true;
    },
    editLocation() {
      this.editingLocation = true;
      this.editLocationId = this.selectedTraining.locationId;
    },
    startEditComment() {
      this.editingComment = true;
      this.editComment = this.selectedTraining.comment;
    },
    async saveEditComment() {
      const postData = {
        'comment': this.editComment,
      };
      const {data} = await this.$http.post('/trainingprepare/' + this.selectedTraining.id + '/updatecomment', postData)
      if (data.status == 'ok') {
        this.selectedTraining.comment = this.editComment;
        this.cancelEditComment();
        this.$emit("showSnackbar", "Kommentar aktualisiert", "success");
      }
    },
    cancelEditComment() {
      this.editComment = null;
      this.editingComment = false;
    },
    async saveEditTrainingContent(selectedContentIds: Array<Number>) {
      console.log('send ', selectedContentIds)
      const postData = {
        'contentIds': selectedContentIds,
      };
      const {data} = await this.$http.post('/trainingprepare/' + this.selectedTraining.id + '/updatecontent', postData)
      if (data.status == 'ok') {
        this.selectedTraining.contentIds = selectedContentIds;
        this.$emit("showSnackbar", "Trainingsinhalte aktualisiert", "success");
      }
    },
    async saveEditLocation() {
      const postData = {
        'locationId': this.editLocationId,
      };
      const {data} = await this.$http.post('/trainingprepare/' + this.selectedTraining.id + '/updatelocation', postData)
      if (data.status == 'ok') {
        this.selectedTraining.locationId = this.editLocationId;
        this.cancelEditLocation();
        this.$emit("showSnackbar", "Ort aktualisiert", "success");
      }
    },
    cancelEditLocation() {
      this.editLocationId = null;
      this.editingLocation = false;
    },
    async updateTrainingTime() {
      this.timeDialogOpened = false;
      const startDateTime = this.selectedTraining.start.clone().set({
        h: this.editStartTime.split(":")[0],
        m: this.editStartTime.split(":")[1]
      });
      const endDateTime = this.selectedTraining.end.clone().set({
        h: this.editEndTime.split(":")[0],
        m: this.editEndTime.split(":")[1]
      });
      const postData = {
        'start': startDateTime.format(),
        'end': endDateTime.format(),
      };
      const {data} = await this.$http.post('/trainingprepare/' + this.selectedTraining.id + '/updatetrainingtime', postData)
      if (data.status == 'ok') {
        this.selectedTraining.start = startDateTime;
        this.selectedTraining.end = endDateTime;
        this.$emit("showSnackbar", "Zeiten aktualisiert", "success");
      }
    },
    fullName: item => item.firstName + ' ' + item.familyName,
  },
})

</script>

<style scoped lang="scss">

</style>

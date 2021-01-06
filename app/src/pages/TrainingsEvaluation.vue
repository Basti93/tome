<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Abgehaltene Trainings</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-btn
                v-if="$vuetify.breakpoint.mobile"
                color="primary"
                outlined
                title="Übungsleiter-Abrechnung exportieren"
                small
                v-on:click="showAccountingDialog = true"
            >
              <v-icon>request_quote</v-icon>
            </v-btn>
            <v-btn
                v-else
                outlined
                title="Übungsleiter-Abrechnung exportieren"
                color="primary"
                v-on:click="showAccountingDialog = true"
            >
              <v-icon left>request_quote</v-icon>
              ÜL-Abrechnung
            </v-btn>
          </v-toolbar>
          <v-divider></v-divider>
          <v-card-text class="pa-0 pa-md-4">
            <div v-show="dataLoaded" class="tp-training-prepare">
              <v-container>
                <v-row no-gutters>
                  <v-col md="3" cols="0">
                    <TrainingSelector
                        application="evaluation"
                        :trainings="pastTrainings"
                        :selectedTrainingId="selectedTrainingId"
                        v-on:change="selectTraining"
                    ></TrainingSelector>
                  </v-col>
                  <v-col md="9">
                    <v-slide-x-transition v-if="selectedTraining">
                      <div v-if="animationTrigger">
                          <v-card class="ma-1">
                            <v-card-title class="justify-center">Trainingsdaten
                              {{ selectedTraining.start.format('dddd [den] Do MMMM') }}
                            </v-card-title>
                            <v-card-subtitle class="text-center">{{
                                selectedTraining.start.fromNow()
                              }}
                            </v-card-subtitle>
                            <v-divider></v-divider>
                            <v-card-text class="text-center pa-0 pa-md-4">
                              <v-alert type="success" class="text-small"
                                       dense
                                       outlined
                                       v-if="selectedTraining.evaluated">
                                Training abgeschlossen
                              </v-alert>
                              <v-dialog v-model="confirmEvaluationDialog" persistent max-width="290">
                                <template v-slot:activator="{ on }">
                                  <v-btn
                                      class="mt-2 mb-2"
                                      elevation="1"
                                      v-show="!selectedTraining.evaluated"
                                      @click="confirmEvaluationDialog = true"
                                      color="primary">
                                    Abschließen
                                  </v-btn>
                                </template>
                                <v-card tile>
                                  <v-toolbar flat>
                                    <v-toolbar-title>Training abschließen?</v-toolbar-title>
                                  </v-toolbar>
                                  <v-divider></v-divider>
                                  <v-card-text>Nachdem das Training abgeschlossen ist, kann es nicht mehr verändert
                                    werden.
                                  </v-card-text>
                                  <v-card-actions>
                                    <v-spacer></v-spacer>
                                    <v-btn color="primary" text @click="confirmEvaluationDialog = false">Abbrechen
                                    </v-btn>
                                    <v-btn color="primary" test @click="evaluated()">Bestätigen</v-btn>
                                  </v-card-actions>
                                </v-card>
                              </v-dialog>
                            </v-card-text>
                          </v-card>
                          <v-card class="ma-1">
                            <v-card-title>Trainer</v-card-title>
                            <v-card-subtitle>Diese Zeiten werden für die ÜL-Abrechnung verwendet</v-card-subtitle>
                            <v-card-text>
                              <v-list-item
                                  v-for="(item, index) in selectedTraining.trainers"
                                  :key="index"
                              >
                                <tome-list-item-profile-image
                                    :image-path="item.profileImageName">
                                </tome-list-item-profile-image>

                                <v-list-item-content>
                                  <v-list-item-title>{{ trainerFullName(item.userId) }}</v-list-item-title>
                                  <v-list-item-subtitle>Von {{ item.accountingTimeStart.format('HH:mm') }} bis
                                    {{ item.accountingTimeEnd.format('HH:mm') }}
                                  </v-list-item-subtitle>
                                </v-list-item-content>

                                <v-list-item-action v-if="!selectedTraining.evaluated">
                                  <v-btn color="primary" @click="editTime(item)" outlined>
                                    <v-icon>edit</v-icon>
                                  </v-btn>
                                </v-list-item-action>

                              </v-list-item>
                            </v-card-text>
                          </v-card>
                          <v-card class="ma-1">
                            <v-card-title>Teilnehmer</v-card-title>
                            <v-card-text class="pa-0 pa-md-4">


                              <v-list-group
                                  v-model="participantsListGroupActive"
                                  prepend-icon="check"
                                  group="participants"
                                  key="1"
                                  no-action
                              >
                                <template v-slot:activator>
                                  <v-list-item-content>
                                    <v-list-item-title>{{ participatingUsers.length }} Teilnehmer</v-list-item-title>
                                  </v-list-item-content>
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

                                  <v-list-item-action v-if="!selectedTraining.evaluated">
                                    <v-btn
                                        title="Benutzer zu Absagen hinzufügen"
                                        color="primary"
                                        @click="removeParticipant(item.id)"
                                        outlined
                                    >
                                      <v-icon>remove</v-icon>
                                    </v-btn>
                                  </v-list-item-action>

                                </v-list-item>
                              </v-list-group>
                              <v-list-group
                                  v-model="canceledUserListGroupActive"
                                  prepend-icon="cancel"
                                  group="canceledusers"
                                  key="2"
                                  no-action
                              >
                                <template v-slot:activator>
                                  <v-list-item-content>
                                    <v-list-item-title>{{ canceledUsers.length }} <span
                                        v-if="canceledUsers.length == 1">Absage</span><span v-else>Absagen</span>
                                    </v-list-item-title>
                                  </v-list-item-content>
                                </template>
                                <v-list-item
                                    v-for="(item) in canceledUsers"
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
                                    <v-list-item-subtitle v-if="getCancelReason(item.id)" class="warning--text">Grund:
                                      {{ getCancelReason(item.id) }}
                                    </v-list-item-subtitle>
                                  </v-list-item-content>
                                  <v-list-item-action v-if="!selectedTraining.evaluated">
                                    <v-btn v-if="getCancelReason(item.id)"
                                           title="Absage anschauen"
                                           color="primary"
                                           @click="openCancelReasonDialog(item.id)"
                                           outlined>
                                      <v-icon>search</v-icon>
                                    </v-btn>
                                  </v-list-item-action>
                                  <v-list-item-action v-if="!selectedTraining.evaluated">
                                    <v-btn
                                        color="primary"
                                        title="Benutzer zu Teilnehmern hinzufügen"
                                        @click="addParticipant(item.id)"
                                        outlined
                                    >
                                      <v-icon>add</v-icon>
                                    </v-btn>
                                  </v-list-item-action>
                                </v-list-item>
                              </v-list-group>
                            </v-card-text>
                          </v-card>
                        </div>
                    </v-slide-x-transition>
                    <div v-else>
                      <v-alert
                          type="info"
                          class="text-small"
                          pa-0
                          ma-0
                          outlined>
                        Keine abgehaltenen Trainings für dich verfügbar
                      </v-alert>
                    </div>
                  </v-col>
                </v-row>
              </v-container>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
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
          Abrechnungszeitraum ändern
          <v-spacer></v-spacer>
          <v-btn text color="primary" @click="updateAccountingTime">
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
    <TrainingAccountingExportDialog
        :visible="showAccountingDialog"
        v-on:close="showAccountingDialog = false"
    ></TrainingAccountingExportDialog>
  </v-container>
</template>

<script lang="ts">
import Vue from "vue";
import {mapGetters, mapState} from 'vuex'
import TrainingParticipant from "@/models/TrainingParticipant";
import TrainingTrainer from "@/models/TrainingTrainer";
import TrainingEvaluation from "@/models/TrainingEvaluation";
import TrainingAccountingExportDialog from "@/components/TrainingAccountingExportDialog";
import ListItemProfileImage from "../components/ListItemProfileImage"
import TrainingSelector from "../components/TrainingSelector.vue";

export default Vue.extend({
  name: "TrainingsEvaluation",
  components: {
    TrainingSelector,
    TrainingAccountingExportDialog,
    "tome-list-item-profile-image": ListItemProfileImage
  },
  data: function () {
    return {
      pastTrainings: [] as TrainingEvaluation[],
      dataLoaded: false,
      selectedTrainingId: null,
      selectedTrainerId: null,
      animationTrigger: true,
      users: [],
      selectedLocationId: null,
      trainersListGroupActive: true,
      participantsListGroupActive: true,
      canceledUserListGroupActive: false,
      confirmEvaluationDialog: false,
      editStartTime: null as String,
      editEndTime: null as String,
      timeDialogOpened: false,
      showCancelReasonDialog: false,
      cancelReasonDialogText: null,
      showAccountingDialog: false,
    }
  },
  computed: {
    ...mapGetters({loggedInUser: 'loggedInUser'}),
    ...mapGetters('masterData', {
      getSimpleTrainerById: 'getSimpleTrainerById',
      getGroupsByIds: 'getGroupsByIds',
    }),
    ...mapState('masterData', {
      locations: 'locations',
    }),
    selectedTraining() {
      return this.getUpcomingTrainingById(this.selectedTrainingId);
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
        this.pastTrainings = [];
        //load data
        const res = await this.$http.get('/trainingevaluation/' + this.loggedInUser.id);
        if (res.data.data && res.data.data.length > 0) {
          //json result to objects
          for (let trObj of res.data.data) {
            let participants = [] as TrainingParticipant[];
            for (let partObj of trObj.participants) {
              participants.push(new TrainingParticipant(partObj.trainingId, partObj.userId, partObj.attend === 1 ? true : false, partObj.cancelreason));
            }
            let trainers = [] as TrainingTrainer[];
            const trainingStartTime = this.moment(trObj.start, 'YYYY-MM-DDTHH:mm');
            const trainingEndTime = this.moment(trObj.end, 'YYYY-MM-DDTHH:mm');
            for (let partObj of trObj.trainers) {
              let timeStart;
              if (partObj.accountingTimeStart != null) {
                timeStart = this.moment(partObj.accountingTimeStart, 'YYYY-MM-DDTHH:mm');
              } else {
                timeStart = trainingStartTime
              }
              let timeEnd;
              if (partObj.accountingTimeEnd != null) {
                timeEnd = this.moment(partObj.accountingTimeEnd, 'YYYY-MM-DDTHH:mm');
              } else {
                timeEnd = trainingEndTime
              }
              trainers.push(new TrainingTrainer(partObj.trainingId, partObj.userId, timeStart, timeEnd));
            }
            this.pastTrainings.push(new TrainingEvaluation(trObj.id, trainingStartTime, trainingEndTime, trObj.locationId, trObj.groupIds, trObj.contentIds, trainers, participants, trObj.comment, trObj.prepared === 1 ? true : false, trObj.evaluated === 1 ? true : false));
          }
          //select first training
          this.selectTraining(this.pastTrainings[0].id);
        }
        const allGroupIds = this.pastTrainings
            .map(t => t.groupIds)
            .reduce(function (pre, cur) {
              return pre.concat(cur);
            });
        const res2 = await this.$http.get('/user?groupIds=' + allGroupIds);
        this.users = res2.data;
      } catch (error) {
        console.error(error);
      } finally {
        this.dataLoaded = true;
      }
    },
    async removeParticipant(userId) {
      const {data} = await this.$http.post('/trainingevaluation/' + this.selectedTraining.id + '/removeparticipant/' + userId)
      if (data.status == 'ok') {
        this.selectedTraining.participants.filter(p => p.userId === userId)[0].attend = false
        this.$emit("showSnackbar", "Benutzer entfernt", "success");
      }
    },
    async addParticipant(userId) {
      const {data} = await this.$http.post('/trainingevaluation/' + this.selectedTraining.id + '/addparticipant/' + userId)
      if (data.status == 'ok') {
        this.selectedTraining.participants.filter(p => p.userId === userId)[0].attend = true
        this.$emit("showSnackbar", "Benutzer hinzugefügt", "success");
      }
    },
    async evaluated() {
      this.confirmEvaluationDialog = false;
      const {data} = await this.$http.post('/trainingevaluation/' + this.selectedTraining.id + '/evaluated')
      if (data.status == 'ok') {
        this.selectedTraining.evaluated = true
        this.$emit("showSnackbar", "Training abgeschlossen", "success");
      }
    },
    editTime(item: TrainingTrainer) {
      this.selectedTrainerId = item.userId;
      this.editStartTime = item.accountingTimeStart.format('HH:mm')
      this.editEndTime = item.accountingTimeEnd.format('HH:mm')
      this.timeDialogOpened = true;
    },
    async updateAccountingTime() {
      this.timeDialogOpened = false;
      const selectedTrainer = this.selectedTraining.trainers.find(t => t.userId === this.selectedTrainerId)
      const startDateTime = selectedTrainer.accountingTimeStart.clone().set({
        h: this.editStartTime.split(":")[0],
        m: this.editStartTime.split(":")[1]
      });
      const endDateTime = selectedTrainer.accountingTimeEnd.clone().set({
        h: this.editEndTime.split(":")[0],
        m: this.editEndTime.split(":")[1]
      });
      const postData = {
        'trainerId': this.selectedTrainerId,
        'start': startDateTime.format(),
        'end': endDateTime.format(),
      };
      const {data} = await this.$http.post('/trainingevaluation/' + this.selectedTraining.id + '/updateaccountingtime', postData)
      if (data.status == 'ok') {
        selectedTrainer.accountingTimeStart = startDateTime;
        selectedTrainer.accountingTimeEnd = endDateTime;
        this.$emit("showSnackbar", "Zeiten aktualisiert", "success");
      }
    },
    selectTraining(id) {
      this.animationTrigger = false;
      this.selectedTrainingId = id;
      setTimeout(() => {
        this.animationTrigger = true;
      }, 100);
    },
    getUpcomingTrainingById(id) {
      return this.pastTrainings.filter(ut => ut.id == id)[0];
    },
    fullName: item => item.firstName + ' ' + item.familyName,
    trainerFullName(id) {
      return this.fullName(this.getSimpleTrainerById(id))
    },
  },
})

</script>

<style scoped lang="scss">
.tp-training-prepare {

  &__navigation-card {
    flex-grow: 1;

    .v-card__title {
      display: block;
    }

    &--desktop {
      margin: 2rem;
    }

    &--mobile {
      margin: 0.5rem;
    }

    &--active {
      filter: brightness(80%);
    }

    &--evaluated {
      background-color: #60cc69 !important;
    }

  }

  &__card {
    margin-bottom: 2rem;
  }

}
</style>

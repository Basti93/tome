<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Aktuelle Trainings</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-chip outlined>
              <v-icon left color="primary">group</v-icon>
              {{ filterDisplayValue }}
            </v-chip>
            <v-btn icon color="primary" @click="filterDialogVisible = true">
              <v-icon>filter_list</v-icon>
            </v-btn>
            <template v-slot:extension v-if="cookieUser">
              <v-chip
                  class="ml-2"
                  close
                  color="primary"
                  @click:close="removeCookieUser()"
                  v-model="cookieUser">{{ cookieUser.getFullName() }}
              </v-chip>
            </template>
            <v-dialog
                v-model="filterDialogVisible"
                max-width="500px"
                :fullscreen="$vuetify.breakpoint.xsOnly"
                persistent>
              <v-card>
                <v-toolbar flat dense>
                  <v-btn icon @click="filterDialogVisible = false">
                    <v-icon>close</v-icon>
                  </v-btn>
                  <v-toolbar-title>Filter ändern</v-toolbar-title>
                  <v-spacer></v-spacer>
                  <v-toolbar-items>
                    <v-btn text color="primary" @click="filterDone">
                      <v-icon>done</v-icon>
                    </v-btn>
                  </v-toolbar-items>
                </v-toolbar>
                <v-divider></v-divider>
                <v-card-text flat>
                  <GroupSelect
                      v-bind:groupId="currentUserGroupId"
                      v-on:groupSelected="groupChanged"
                      v-on:branchSelected="branchChanged"
                  >
                  </GroupSelect>
                </v-card-text>
              </v-card>
            </v-dialog>
          </v-toolbar>
          <v-divider></v-divider>
          <v-card-text class="pa-0 pa-md-4">
            <v-container>
              <v-row no-gutters>
                <v-col cols="0" md="3">
                  <TrainingSelector
                      application="checkin"
                      :trainings="upcomingTrainings"
                      :selectedTrainingId="selectedTrainingId"
                      v-on:change="selectTraining"
                  ></TrainingSelector>
                </v-col>
                <v-col md="9">
                  <div v-show="dataLoaded" class="tp-training-check-in">
                    <v-slide-x-transition>
                      <TrainingCheckIn
                          v-if="selectedTraining && animationTrigger"
                          :currentUser="currentUser"
                          :isCookieUser="isCookieUser"
                          :training="selectedTraining"
                          :participants="selectedTraining.participants"
                          v-on:checkedIn="updateCheckedIn()"
                          v-on:checkedOut="updateCheckedOut()"
                          v-on:showCookieUserLogin="showCookieUserLogin()"
                          class="tp-training-check-in__card">
                      </TrainingCheckIn>
                      <v-alert
                          v-if="!initializing && !selectedTraining"
                          type="info"
                          outlined
                          pa-1
                          ma-0
                          class="caption"
                      >
                        Momentan gibt es keine kommenden Trainings für deine Gruppe ☹️
                      </v-alert>
                    </v-slide-x-transition>
                  </div>
                </v-col>
              </v-row>
            </v-container>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    <CookieUserDialog
        :visible="cookieUserDialogVisible"
        v-on:close="cookieUserDialogVisible = false"
    ></CookieUserDialog>
  </v-container>
</template>

<script lang="ts">

import Vue from "vue";
import {mapGetters} from 'vuex'
import TrainingCheckIn from "../components/TrainingCheckIn.vue";
import GroupSelect from "../components/GroupSelect.vue";
import CookieUserDialog from "../components/CookieUserDialog.vue";
import Training from "@/models/Training";
import TrainingParticipant from "@/models/TrainingParticipant";
import TrainingSelector from "../components/TrainingSelector.vue";

export default Vue.extend({
  name: "TrainingsCheckInPage",
  components: {TrainingSelector, CookieUserDialog, TrainingCheckIn, GroupSelect},
  data: function () {
    return {
      initalSelectedTrainingId: null,
      filterGroupId: null,
      filterBranchId: null,
      tempFilterGroupId: null,
      tempFilterBranchId: null,
      upcomingTrainings: [] as Training[],
      dataLoaded: false,
      filterDialogVisible: false,
      cookieUserDialogVisible: false,
      selectedTrainingId: null,
      animationTrigger: true,
      initializing: false,
    }
  },
  computed: {
    ...mapGetters({loggedInUser: 'loggedInUser', cookieUser: 'cookieUser'}),
    ...mapGetters('masterData', {
      getBranchByGroupId: 'getBranchByGroupId',
      getGroupById: 'getGroupById',
      getBranchById: 'getBranchById',
    }),
    currentUser() {
      if (this.loggedInUser) {
        return this.loggedInUser;
      } else if (this.cookieUser) {
        return this.cookieUser;
      }
      return null;
    },
    isCookieUser() {
      return this.cookieUser != null;
    },
    selectedTraining() {
      return this.getUpcomingTrainingById(this.selectedTrainingId);
    },
    currentUserGroupId() {
      if (this.currentUser && this.currentUser.groupIds && this.currentUser.groupIds.length > 0) {
        return this.currentUser.groupIds[0]
      }
      return null
    },
    filterDisplayValue() {
      if (this.filterGroupId) {
        let group = this.getGroupById(this.filterGroupId)
        if (group) {
          return group.name;
        }
      } else if (this.filterBranchId) {
        let branch = this.getBranchById(this.filterBranchId)
        if (branch) {
          return branch.name;
        }
      }
      return "Alle Gruppen"
    },
    currentUserId() {
      if (this.currentUser) {
        return this.currentUser.id;
      }
      return null;
    },

  },
  async created() {
    this.initializing = true;
    if (!this.currentUser) {
      this.cookieUserDialogVisible = true;
    }
    if (this.loggedInUser && this.loggedInUser.trainerBranchIds && this.loggedInUser.trainerBranchIds.length > 0) {
      this.filterBranchId = this.loggedInUser.trainerBranchIds[0];
    }
    this.fetchDataForCurrentUser();
    this.initializing = false;
  },
  methods: {
    groupChanged(groupId) {
      this.tempFilterGroupId = groupId;
    },
    branchChanged(branchId) {
      this.tempFilterBranchId = branchId;
    },
    async filterDone() {
      this.filterBranchId = this.tempFilterBranchId;
      this.filterGroupId = this.tempFilterGroupId;
      await this.fetchData();
      if (this.upcomingTrainings.length > 0) {
        this.selectTraining(this.upcomingTrainings[0].id);
      }
      this.filterDialogVisible = false;
    },
    findBranch(groupIds) {
      if (groupIds && groupIds.length > 0) {
        return this.getBranchByGroupId(groupIds[0]);
      }
      return null;
    },
    async fetchSingleTraining(trainingId: Number) {
      const {data} = await this.$http.get('/training/upcoming/' + trainingId);
      if (data.data) {
        this.addToUpcomingTrainings(data.data);
      } else {
        console.log('Training with id ' + trainingId + ' could not be found or is in the past')
      }
    },
    async fetchDataForCurrentUser() {
      this.filterGroupId = this.currentUserGroupId;

      this.initalSelectedTrainingId = this.$route.params.id;
      await this.fetchData();

      if (this.initalSelectedTrainingId) {
        this.selectTraining(this.upcomingTrainings.filter(t => t.id == this.initalSelectedTrainingId)[0].id);
      } else if (this.upcomingTrainings.length > 0) {
        this.selectTraining(this.upcomingTrainings[0].id);
      }
    },
    async fetchData() {
      try {
        this.dataLoaded = false;
        this.upcomingTrainings = [];

        if (this.initalSelectedTrainingId) {
          await this.fetchSingleTraining(this.initalSelectedTrainingId);
        }

        //build fetch url
        let url = '/training/upcoming';
        if (this.filterGroupId) {
          url += '?groupIds=' + this.filterGroupId;
        } else if (this.filterBranchId) {
          url += '?branchId=' + this.filterBranchId;
        }
        //load data
        const {data} = await this.$http.get(url);
        if (data.data && data.data.length > 0) {
          for (let trObj of data.data) {
            this.addToUpcomingTrainings(trObj);
          }
        }
      } catch (error) {
        console.error(error);
      } finally {
        this.dataLoaded = true;
      }
    },
    addToUpcomingTrainings(trainingJson) {
      let participants = [] as TrainingParticipant[];
      for (let partObj of trainingJson.participants) {
        participants.push(new TrainingParticipant(partObj.trainingId, partObj.userId, partObj.attend === 1 ? true : false, null));
      }
      if (this.upcomingTrainings.filter(t => t.id === trainingJson.id).length === 0) {
        this.upcomingTrainings.push(new Training(trainingJson.id, this.moment(trainingJson.start, 'YYYY-MM-DDTHH:mm'), this.moment(trainingJson.end, 'YYYY-MM-DDTHH:mm'), trainingJson.locationId, trainingJson.groupIds, trainingJson.contentIds, trainingJson.trainerIds, participants, trainingJson.comment, false, false, trainingJson.automaticAttend == 1));
      }
      this.upcomingTrainings.sort(function (a: Training, b: Training) {
        return a.start - b.start;
      });
    },
    updateCheckedIn() {
      let participant = this.selectedTraining.participants.filter(p => p.userId === this.currentUserId);
      if (participant && participant.length > 0) {
        const index = this.selectedTraining.participants.indexOf(participant[0]);
        participant[0].attend = true;
        this.$set(this.selectedTraining.participants[index], 'attend', true);
      } else {
        this.selectedTraining.participants.push(new TrainingParticipant(this.selectedTraining.id, this.currentUserId, true, null));
      }
      this.$emit("showSnackbar", "Für das Training angemeldet", "success");
    },
    updateCheckedOut() {
      let participant = this.selectedTraining.participants.filter(p => p.userId === this.currentUserId);
      if (participant && participant.length > 0) {
        const index = this.selectedTraining.participants.indexOf(participant[0]);
        this.$set(this.selectedTraining.participants[index], 'attend', false);
      } else {
        this.selectedTraining.participants.push(new TrainingParticipant(this.selectedTraining.id, this.currentUserId, false, null));
      }
      this.$emit("showSnackbar", "Vom Training abgemeldet", "info");
    },
    showCookieUserLogin() {
      this.cookieUserDialogVisible = true;
    },
    selectTraining(id) {
      this.animationTrigger = false;
      this.selectedTrainingId = id;
      setTimeout(() => {
        this.animationTrigger = true;
      }, 100);
    },
    getUpcomingTrainingById(id) {
      if (this.upcomingTrainings.length > 0) {
        return this.upcomingTrainings.filter(ut => ut.id == id)[0];
      }
      return null;
    },
    removeCookieUser() {
      this.$store.dispatch('eraseCookieUser')
    },
    attendingStatus(trainingId) {
      const training = this.getUpcomingTrainingById(trainingId);
      if (training) {
        const tp = training.participants.filter(p => (p.userId === this.currentUserId));
        if (tp && tp.length > 0) {
          return tp[0].attend;
        }
      }
      return null;
    },
    attending(trainingId) {
      return this.attendingStatus(trainingId) ? true : false;
    },
    canceled(trainingId) {
      const status = this.attendingStatus(trainingId);
      return (status !== null && !status) ? true : false;
    },
    timelinecolor(trainingId: Number) {
      if (this.attending(trainingId)) {
        return 'primary'
      } else if (this.canceled(trainingId)) {
        return 'red lighten-2'
      }
      return 'blue lighten-2'
    }
  },
  watch: {
    currentUser() {
      this.filterGroupId = this.currentUserGroupId;
      if (!this.initializing) {
        this.fetchDataForCurrentUser();
      }
    }
  }
})

</script>

<style scoped lang="scss">

</style>

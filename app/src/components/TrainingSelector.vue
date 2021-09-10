<template>
  <v-container class="fill-height tp-training-selector pa-0">
    <h3 class="hidden-sm-and-down">Training auswählen</h3>
    <v-timeline align-top dense class="hidden-sm-and-down fill-height">
      <v-slide-x-reverse-transition
          group
          hide-on-leave
      >
        <v-timeline-item
            v-for="(item) in trainings"
            :key="item.id"
            v-on:click.native="selectTraining(item)"
            right
            dense
            :color="timelineColor(item)"
            class="tp-training-selector__item"
            :class="{'tp-training-selector__item--active' : item === selectedTraining}"
        >
          <h2 class="subtitle-1">{{ item.start.format('dddd').slice(0, 2) + " " + item.start.format('DD') + '.' + item.start.format('MMM')}}</h2>
          <div>{{ getBranchShortNameByGroupIds(item.groupIds)}}</div>
          <div>{{ item.start.format('HH:mm') + ' - ' + item.end.format('HH:mm') }}</div>
          <div>{{ getLocationById(item.locationId).name }}</div>
          <template v-slot:icon>
            <v-avatar>
              <v-icon v-if="checkStatusDone(item)" small>{{isCheckIn ? 'thumb_up' : 'check' }}</v-icon>
              <v-icon v-else-if="isCheckIn && canceled(item)" small>{{isCheckIn ? 'thumb_down' : 'cancel' }}</v-icon>
              <v-icon v-else small>new_releases</v-icon>
            </v-avatar>
          </template>
        </v-timeline-item>
      </v-slide-x-reverse-transition>
    </v-timeline>
    <v-overlay
        opacity=".8"
        :value="showOverlay"
        v-on:click.native="showOverlay = false"
    >
      <v-btn v-on:click="showOverlay = false" icon fixed top right>
        <v-icon>close</v-icon>
      </v-btn>
      <h2>Training auswählen</h2>
      <v-timeline align-top dense class="fill-height tp-training-selector__overlay-timeline">
      <v-timeline-item
          v-for="(item) in trainings"
          :key="item.id"
          v-on:click.native="selectTraining(item)"
          right
          dense
          :color="timelineColor(item)"
          class="tp-training-selector__item"
          :class="{'tp-training-selector__item--active' : item === selectedTraining}"
      >
        <h2 class="subtitle-1">{{ item.start.format('dddd').slice(0, 2) + " " + item.start.format('DD') + '.' + item.start.format('MMM')}}</h2>
        <div>{{ getBranchShortNameByGroupIds(item.groupIds)}}</div>
        <div>{{ item.start.format('HH:mm') + ' - ' + item.end.format('HH:mm') }}</div>
        <div>{{ getLocationById(item.locationId).name }}</div>
        <template v-slot:icon>
          <v-avatar>
            <v-icon v-if="checkStatusDone(item)" small>{{isCheckIn ? 'thumb_up' : 'check' }}</v-icon>
            <v-icon v-else-if="isCheckIn &&canceled(item)" small>{{isCheckIn ? 'thumb_down' : 'cancel' }}</v-icon>
            <v-icon v-else small>new_releases</v-icon>
          </v-avatar>
        </template>
      </v-timeline-item>
      </v-timeline>
    </v-overlay>
    <v-btn
        color="primary"
        title="Training auswählen"
        class="hidden-md-and-up"
        v-on:click="showOverlay = true"
        fab
        elevation="6"
        dark
        fixed
        bottom
        right
    >
      <v-icon>list</v-icon>
    </v-btn>
  </v-container>
</template>

<script lang="ts">

import Vue from 'vue'
import Training from "../models/Training";
import {mapGetters} from "vuex";

export default Vue.extend({
  name: "TrainingSelector",
  props: {
    trainings: Array,
    application: String,
    selectedTrainingId: Number,
  },
  data: function () {
    return {
      selectedTraining: Training,
      showOverlay: false,
    }
  },
  created() {
    this.selectedTraining = this.getTrainingById(this.selectedTrainingId);
  },
  computed: {
    ...mapGetters({loggedInUser: 'loggedInUser', cookieUser: 'cookieUser'}),
    ...mapGetters('masterData', {
      getBranchByGroupIds: 'getBranchByGroupIds',
      getBranchShortNameByGroupIds: 'getBranchShortNameByGroupIds',
      getLocationById: 'getLocationById'
    }),
    currentUser() {
      if (this.loggedInUser) {
        return this.loggedInUser;
      } else if (this.cookieUser) {
        return this.cookieUser;
      }
      return null;
    },
    isEvaluation(): Boolean {
      return this.application === 'evaluation'
    },
    isCheckIn(): Boolean {
      return this.application === 'checkin'
    },
    isNoStatus(): Boolean {
      return !this.isEvaluation() && !this.isCheckIn()
    },
  },
  methods: {
    selectTraining(training: Training): void {
      this.$emit('change', training.id)
      this.showOverlay = false
    },
    timelineColor(training: Training): String {
      if (this.checkStatusDone(training)) {
        return 'primary'
      } else if (this.isCheckIn && this.canceled(training)) {
        return 'red lighten-2'
      }
      const mainBranch = this.getBranchByGroupIds(training.groupIds);
      if (mainBranch) {
        return mainBranch.colorHex
      }
      return 'blue lighten-2'
    },
    checkStatusDone(training: Training): Boolean {
      return (this.isCheckIn && this.attending(training)) || (this.isEvaluation && this.evaluated(training))
    },
    attending(training: Training): Boolean {
      return this.attendingStatus(training) ? true : false;
    },
    evaluated(training: Training): Boolean {
      return training.evaluated;
    },
    canceled(training: Training): Boolean {
      const status = this.attendingStatus(training);
      return (status !== null && !status);
    },
    attendingStatus(training: Training): Boolean {
      if (this.currentUser) {
        const tp = training.participants.filter(p => (p.userId === this.currentUser.id));
        if (tp && tp.length > 0) {
          return tp[0].attend;
        }
      }
      return null;
    },
    getTrainingById(id): Training {
      return this.trainings.filter(ut => ut.id == id)[0];
    },
  },
  watch: {
    selectedTrainingId(): Number {
      this.selectedTraining = this.getTrainingById(this.selectedTrainingId);
    }
  }
});
</script>

<style lang="scss">

.tp-training-selector {
  text-align: left;

  .v-overlay__content {
    height: 100%;
    padding-top: 100px;
    padding-bottom: 100px;
  }

  &__item {
    cursor: pointer;

    &--active {
      filter: brightness(110%);
    }
  }

  &__overlay-timeline {
    overflow: scroll;
    height: 100%;
  }


}

</style>

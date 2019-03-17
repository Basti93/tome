<template>
  <div>
    <v-card class="tp-upcoming-training white--text" :class="'ma-3'">
      <v-card-title>
       <h3>{{ moment(start).format('dddd [den] Do MMMM') }}&nbsp;({{moment(start).fromNow()}})</h3>
      </v-card-title>
      <v-divider></v-divider>
      <v-card-text class="tp-upcoming-training__text">
        <v-container grid-list-md>
          <v-layout wrap>
            <v-flex xs6>
              <p class="font-weight-bold">Von {{moment(start).format('HH:mm')}} Uhr</p>
              <p class="font-weight-bold">Bis {{moment(end).format('HH:mm')}} Uhr</p>
            </v-flex>
            <v-flex xs6 v-show="allowedToCheckIn()">
              <v-btn
                color="primary"
                @click="currentUser ? participate() : showCookieUserLogin()"
                v-show="!attending">
                Anmelden
              </v-btn>
              <v-btn
                color="error"
                @click="cancelParticipation()"
                v-show="attending">
                Abmelden
              </v-btn>
            </v-flex>
            <v-flex xs12>
              <p class="font-weight-bold">{{location}}</p>
            </v-flex>
          </v-layout>
        </v-container>
      </v-card-text>
    </v-card>
    <v-card :class="'ma-3'" v-show="comment">
      <v-card-title>
        <h3>Zusätzliche Informationen</h3>
      </v-card-title>
      <v-divider></v-divider>
      <v-card-text class="tp-upcoming-training__text">
        <v-alert
          v-bind:value="comment"
          type="info"
          class="text-small"
          pa-0
          ma-0
          outline
        >
          {{comment}}
        </v-alert>
      </v-card-text>
    </v-card>
    <v-card :class="'ma-3'" v-show="contentIds.length">
      <v-card-title>
        <h3>Trainingsinhalte</h3>
      </v-card-title>
      <v-divider></v-divider>
      <v-card-text class="tp-upcoming-training__text">
        <TrainingContent
          :contentIds="contentIds"
          :initContentIds="contentIds"
        >
        </TrainingContent>
      </v-card-text>
    </v-card>
    <v-card :class="'ma-3'">
      <v-card-title>
        <h3>Gruppen</h3>
      </v-card-title>
      <v-divider></v-divider>
      <v-card-text class="tp-upcoming-training__text">
        <v-chip v-for="(item) in groups"
                :key="item.id">{{item.name}}
        </v-chip>
      </v-card-text>
    </v-card>
  </div>
</template>

<script>
  import TrainingContent from "./TrainingContent";
  import User from "@/models/User";
  import {mapGetters} from 'vuex'

  export default {
    name: "TrainingCheckIn",
    components: {TrainingContent},
    //from parent
    props: {
      training: Object,
      currentUser: User,
      isCookieUser: Boolean,
      participantIds: Array,
    },
    data: function () {
      return {
        id: null,
        date: null,
        start: null,
        end: null,
        location: null,
        groups: [],
        contentIds: [],
        comment: null,
      }
    },
    created() {
      this.id = this.training.id;
      this.date = this.moment(this.training.date).toDate();
      this.start = this.moment(this.training.start).toDate();
      this.end = this.moment(this.training.end).toDate();
      this.location = this.getLocationNameById(this.training.locationId);
      this.groups = this.getGroupsByIds(this.training.groupIds);
      this.contentIds = this.training.contentIds;
      this.comment = this.training.comment;
    },
    computed: {
      attending: function () {
        if (this.currentUser && this.participantIds) {
          return this.participantIds.filter(pId => pId === this.currentUser.id).length > 0;
        }
        return false;
      },
      ...mapGetters('masterData', {
        getLocationNameById: 'getLocationNameById',
        getGroupsByIds: 'getGroupsByIds'
      }),
    },
    watch: {
      training: function () {
        this.id = this.training.id;
        this.date = this.moment(this.training.date).toDate();
        this.start = this.moment(this.training.start).toDate();
        this.end = this.moment(this.training.end).toDate();
        this.location = this.getLocationNameById(this.training.locationId);
        this.groups = this.getGroupsByIds(this.training.groupIds);
        this.contentIds = this.training.contentIds;
        this.comment = this.training.comment;

      },
    },
    methods: {
      allowedToCheckIn() {
        if (this.currentUser) {
          return this.groups.map(g => g.id).filter(gId => gId === this.currentUser.groupId).length > 0;
        }
        return true;
      },
      participate() {
        var self = this;
        if (this.isCookieUser) {
          this.$http.post('training/' + this.id + '/checkinunregistered/' + this.currentUser.id).then(res => {
            if (res.status === 200) {
              self.$emit('checkedIn', self.id)
            }
          })
        } else {
          this.$http.post('training/' + this.id + '/checkin/' + this.currentUser.id).then(res => {
            if (res.status === 200) {
              self.$emit('checkedIn', self.id);
            }
          })
        }
      },
      showCookieUserLogin() {
        this.$emit('showCookieUserLogin');
      },
      cancelParticipation() {
        var self = this;
        if (this.isCookieUser) {
          this.$http.post('training/' + this.id + '/checkoutunregistered/' + this.currentUser.id).then(res => {
            if (res.status === 200) {
              self.$emit('checkedOut', self.id)
            }
          })
        } else {
          this.$http.post('training/' + this.id + '/checkout/' + this.currentUser.id).then(res => {
            if (res.status === 200) {
              self.$emit('checkedOut', self.id)
            }
          })
        }
      },
    },
  }
</script>

<style lang="scss" scoped>
  .tp-upcoming-training {
    .v-input {
      flex-grow: 0;
    }

    &__text {
      text-align: left;
    }
  }
</style>

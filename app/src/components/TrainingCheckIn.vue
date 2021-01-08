<template>
  <div>
    <v-card class="tp-upcoming-training ma-1">
      <v-card-title class="justify-center">{{ start.format('dddd, Do MMMM') }}</v-card-title>
      <v-card-subtitle class="text-center">{{ start.fromNow() }}</v-card-subtitle>
      <v-divider></v-divider>
      <v-card-text class="text-center">
        <v-container>
          <v-row>
            <v-col>
              <span class="label text-small">Von</span>
              <v-chip small outlined class="ma-1">
                <v-icon left color="primary">query_builder</v-icon>
                {{ start.format('HH:mm') }}
              </v-chip>
              <span class="label text-small">bis</span>
              <v-chip small outlined class="ml-1">
                <v-icon left color="primary">query_builder</v-icon>
                {{ end.format('HH:mm') }}
              </v-chip>
            </v-col>
          </v-row>
          <v-row>
            <v-col>
              <v-chip outlined small class="ma-1">
                <v-icon left color="primary">room</v-icon>
                {{ location }}
              </v-chip>
            </v-col>
          </v-row>
          <v-row v-if="allowedToCheckIn()">
            <v-col>
              <v-btn
                  style="min-width: 150px"
                  elevation="1"
                  v-if="!attending || notYet"
                  color="primary"
                  class="ma-2"
                  @click="currentUser ? participate() : showCookieUserLogin()">
                Teilnehmen
                <v-icon right>thumb_up</v-icon>
              </v-btn>
              <v-btn
                  style="min-width: 150px"
                  outlined
                  v-if="attending || notYet"
                  color="red lighten-2"
                  class="ma-2"
                  @click="currentUser ? cancelParticipation() : showCookieUserLogin()">
                <v-icon left>thumb_down</v-icon>
                Absagen
              </v-btn>
              <v-alert
                  v-if="notYet"
                  type="info"
                  outlined
                  pa-1
                  ma-0
                  class="caption"
                  dense>
                Du wirst 24 Stunden vor dem Training automatisch angemeldet.
              </v-alert>
            </v-col>
          </v-row>
          <v-row v-else>
            <v-col>
              <v-alert
                  type="warning"
                  outlined
                  pa-1
                  ma-0
                  class="caption"
                  dense
              >
                Teilnehmen nicht möglich. Du bist keiner dieser Gruppen zugeordnet. Frage einen Trainer bei Problemen.
              </v-alert>
            </v-col>
          </v-row>
          <v-row>
            <v-col>
              <h4>{{ participantCount }} Teilnehmer bis jetzt</h4>
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>
    </v-card>
    <v-card v-show="comment" class="ma-1">
      <v-card-text>
        <v-alert
            class="tp-upcoming-training__text ma-1"
            text
            style="white-space: pre-line;word-break: break-all;"
            type="info"
            dense
            outlined
        >
          <div v-html="urlify(comment)"></div>
        </v-alert>
      </v-card-text>
    </v-card>
    <v-card class="ma-1" v-show="contentIds.length">
      <v-card-title>
        <h6>Trainingsinhalte</h6>
      </v-card-title>
      <v-divider></v-divider>
      <v-card-text class="tp-upcoming-training__text">
        <v-container grid-list-md>
          <TrainingContent
              :contentIds="contentIds"
              :initContentIds="contentIds"
          >
          </TrainingContent>
        </v-container>
      </v-card-text>
    </v-card>
    <v-card class="ma-1">
      <v-card-title>
        <h6>Trainer</h6>
      </v-card-title>
      <v-divider></v-divider>
      <v-card-text class="tp-upcoming-training__text">
        <ProfileImage
            v-for="(item) in trainers"
            :key="item.id"
            class="ma-1"
            :firstName="item.firstName"
            :familyName="item.familyName"
            :imagePath="item.profileImageName"
            left
            clickable
        ></ProfileImage>
      </v-card-text>
    </v-card>
    <v-card class="ma-1">
      <v-card-title>
        <h6>Gruppen</h6>
      </v-card-title>
      <v-divider></v-divider>
      <v-card-text class="tp-upcoming-training__text">
        <GroupChip v-for="(item) in groups"
                   :key="item.id"
                   :group="item"></GroupChip>
      </v-card-text>
    </v-card>
    <v-dialog v-model="showCancelDialog" max-width="500px">
      <v-card>
        <v-toolbar flat>
          <v-btn icon @click="showCancelDialog=false">
            <v-icon>close</v-icon>
          </v-btn>
          <v-toolbar-title>Kurzfristige Absage</v-toolbar-title>
          <v-spacer></v-spacer>
          <v-toolbar-items>
            <v-btn text
                   color="primary"
                   @click="cancelParticipation(cancelReason)"
                   :disabled="!cancelDialogValid">
              <v-icon left>check</v-icon>
              Absage abschicken
            </v-btn>
          </v-toolbar-items>
        </v-toolbar>
        <v-divider></v-divider>
        <v-card-text>
          <v-form v-model="cancelDialogValid">
            <v-container grid-list-md>
              <v-layout wrap>
                <v-flex xs12>
                  <v-alert
                      type="warning"
                      outlined
                      pa-1
                      ma-0
                      class="caption"
                  >
                    Das Training findet innerhalb der nächsten 24 Stunden statt. Bitte gib einen Grund für deine Absage
                    an.
                  </v-alert>
                </v-flex>
                <v-flex xs12>
                  <v-textarea
                      filled
                      label="Gib hier einen Grund an"
                      required
                      :rules="cancelReasonRules"
                      v-model="cancelReason">

                  </v-textarea>
                </v-flex>
              </v-layout>
            </v-container>
          </v-form>
        </v-card-text>
      </v-card>
    </v-dialog>
  </div>
</template>

<script lang="ts">
import TrainingContent from "./TrainingContent";
import ProfileImage from "@/components/ProfileImage";
import User from "@/models/User";
import Training from "@/models/Training";
import {mapGetters} from 'vuex'
import GroupChip from "./GroupChip.vue";

export default {
  name: "TrainingCheckIn",
  components: {GroupChip, TrainingContent, ProfileImage},
  props: {
    training: Training,
    currentUser: User,
    isCookieUser: Boolean,
    participants: Array,
  },
  data: function () {
    return {
      id: null,
      start: null,
      end: null,
      location: null,
      groups: [],
      trainers: [],
      contentIds: [],
      comment: null,
      showCancelDialog: false,
      cancelReason: null,
      cancelDialogValid: true,
      cancelReasonRules: [
        v => !!v || 'Pflichtfeld'
      ],
    }
  },
  created() {
    this.id = this.training.id;
    this.start = this.training.start;
    this.end = this.training.end;
    this.location = this.getLocationNameById(this.training.locationId);
    this.groups = this.getGroupsByIds(this.training.groupIds);
    this.trainers = this.getSimpleTrainersByIds(this.training.trainerIds);
    this.contentIds = this.training.contentIds;
    this.comment = this.training.comment;
  },
  computed: {
    attending: function () {
      if (this.currentUser && this.participants) {
        return this.participants.filter(p => (p.userId === this.currentUser.id && p.attend)).length > 0;
      }
      return false;
    },
    notYet: function () {
      if (this.currentUser && this.participants) {
        const found = this.participants.filter(p => (p.userId === this.currentUser.id));
        return (!found || found.length === 0 || found[0].attend === null);
      }
      return false;
    },
    participantCount: function () {
      return this.training.participants ? this.training.participants.filter(p => p.attend).length : null;
    },
    ...mapGetters('masterData', {
      getLocationNameById: 'getLocationNameById',
      getGroupsByIds: 'getGroupsByIds',
      getBranchById: 'getBranchById',
      getSimpleTrainersByIds: 'getSimpleTrainersByIds',
    }),
  },
  watch: {
    training: function () {
      this.id = this.training.id;
      this.start = this.training.start;
      this.end = this.training.end;
      this.location = this.getLocationNameById(this.training.locationId);
      this.groups = this.getGroupsByIds(this.training.groupIds);
      this.trainers = this.getSimpleTrainersByIds(this.training.trainerIds);
      this.contentIds = this.training.contentIds;
      this.comment = this.training.comment;

    },
  },
  methods: {
    allowedToCheckIn() {
      if (this.currentUser) {
        if (this.currentUser.groupIds) {
          return this.groups.map(g => g.id).filter(gId => this.currentUser.groupIds.includes(gId)).length > 0;
        }
      }
      return true;
    },
    showCookieUserLogin() {
      this.$emit('showCookieUserLogin');
    },
    async participate() {
      try {
        let url = 'training/' + this.id;
        if (this.isCookieUser) {
          url += '/checkinunregistered/' + this.currentUser.id;
        } else {
          url += '/checkin/' + this.currentUser.id;
        }
        //send post
        const {data} = await this.$http.post(url);
        if (data.status === 'ok') {
          this.$emit('checkedIn')
        }
      } catch (error) {
        console.error(error);
      }
    },
    async cancelParticipation(reason) {
      try {
        let url = 'training/' + this.id;
        if (this.isCookieUser) {
          url += '/checkoutunregistered/';
        } else {
          url += '/checkout/';
        }
        url += this.currentUser.id;
        let postData = {};
        if (reason) {
          postData = {
            reason: reason,
          }
        }
        //send post
        const {data} = await this.$http.post(url, postData);
        if (data.status === 'ok') {
          this.cancelReason = null;
          this.showCancelDialog = false;
          this.$emit('checkedOut')
        } else if (data.status === 'cancel_needs_reason') {
          this.showCancelDialog = true;
        }
      } catch (error) {
        console.error(error);
      }
    },
    fullName: item => item.firstName + ' ' + item.familyName,
    urlify(text) {
      if (text) {
        var urlRegex = /(https?:\/\/[^\s]+)/g;
        return text.replace(urlRegex, function (url) {
          return '<a target="_blank" href="' + url + '">' + url + '</a>';
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

<template>
  <v-layout align-top>
    <v-flex xs12 md10 offset-md1 top>
      <v-card>
        <v-toolbar card prominent>
          <v-toolbar-title v-show="$vuetify.breakpoint.mdAndUp">Trainingsanmeldung</v-toolbar-title>
          <v-spacer></v-spacer>
          <v-btn color="primary" v-show="!currentUser" @click="cookieUserDialogVisible = true">
            <v-icon>person</v-icon>
            Wer bist du?
          </v-btn>
          <div v-if="cookieUser" class="text-small tp-training-check-in__cookie-user mt-3">
            <div>
              <h5>Ausgewählter Sportler</h5>
              <p>{{cookieUser.getFullName()}}</p>
            </div>
            <v-btn
              icon
            @click="removeCookieUser()">
              <v-icon color="red">cancel</v-icon>
            </v-btn>
          </div>
          <CookieUserDialog
            :visible="cookieUserDialogVisible"
            v-on:close="cookieUserDialogVisible = false"
          ></CookieUserDialog>
          <v-spacer></v-spacer>
          <v-chip v-show="$vuetify.breakpoint.mdAndUp">{{filterDisplayValue}}</v-chip>
          <v-btn icon color="primary" @click="filterDialogVisible = true">
            <v-icon>filter_list</v-icon>
          </v-btn>
          <v-dialog v-model="filterDialogVisible" max-width="500px" :fullscreen="$vuetify.breakpoint.xsOnly">
            <v-card>
              <v-card-title>
                <span class="title">Filter ändern</span>
              </v-card-title>
              <v-card-text>
                <GroupSelect
                  v-bind:groupId="currentUserGroupId"
                  v-bind:branchId="currentUserBranchId"
                  v-on:groupSelected="groupChanged"
                  v-on:branchSelected="branchChanged"
                >
                </GroupSelect>
              </v-card-text>
              <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="primary" @click="filterDone()">
                  <v-icon>done</v-icon>
                </v-btn>
                <v-btn color="primary" @click="filterDialogVisible = false">
                  <v-icon>close</v-icon>
                </v-btn>
              </v-card-actions>
            </v-card>
          </v-dialog>

        </v-toolbar>
        <v-divider></v-divider>
        <v-card-text flat v-show="dataLoaded">
          <div class="tp-training-check-in__navigation-carousel">
            <h3>Aktuelle Trainings</h3>
            <v-btn icon
                   v-show="$vuetify.breakpoint.mdAndUp"
                   class="tp-training-check-in__navigation-carousel-prev"
                   @click="$refs.navigationCarousel.prev()">
              <v-icon>navigate_before</v-icon>
            </v-btn>
            <siema
              ref="navigationCarousel"
              :ready="false"
              :options="navigationCarouselOptions">
              <v-card
                v-for="(item, index) in upcomingTrainings"
                :key="item.id"
                hover
                @click="trainingSelected(index, item)"
                class="tp-training-check-in__navigation-carousel-card"
                :class="{'tp-training-check-in__navigation-carousel-card--attending': attending(item.id), 'tp-training-check-in__navigation-carousel-card--active': item.id === selectedTrainingId, 'tp-training-check-in__navigation-carousel-card--mobile': $vuetify.breakpoint.smAndDown, 'tp-training-check-in__navigation-carousel-card--desktop': $vuetify.breakpoint.mdAndUp}"
              >
                <v-card-title>
                  <h2 class="subheading">{{ moment(item.start).format('dddd').slice(0, 2) }}</h2>
                  <p class="title pt-1">{{moment(item.start).format('DD')}}</p>
                  <v-icon v-show="attending(item.id)" small>check</v-icon>
                  <v-icon v-show="!attending(item.id)" small>priority_high</v-icon>
                </v-card-title>
              </v-card>
            </siema>
            <v-btn icon
                   v-show="$vuetify.breakpoint.mdAndUp"
                   class="tp-training-check-in__navigation-carousel-next"
                   @click="$refs.navigationCarousel.next()">
              <v-icon>navigate_next</v-icon>
            </v-btn>
          </div>
          <v-divider></v-divider>
          <siema
            ref="trainingCarousel"
            :ready="false"
            :options="trainingCarouselOptions">
            <UpcomingTraining
              v-for="(item, index) in upcomingTrainings"
              :key="item.id"
              :id="item.id"
              :currentUser="currentUser"
              :date="moment(item.start).toDate()"
              :start="moment(item.start).toDate()"
              :end="moment(item.end).toDate()"
              :location="getLocationNameById(item.locationId)"
              :groups="getGroupsByIds(item.groupIds)"
              :participantIds="item.participantIds"
              :comment="item.comment"
              :contentIds="item.contentIds"
              v-on:checkedIn="updateCheckedIn($event)"
              v-on:checkedOut="updateCheckedOut($event)"
              class="tp-training-check-in__card">

            </UpcomingTraining>
          </siema>

        </v-card-text>
      </v-card>
    </v-flex>
  </v-layout>
</template>

<script>


  import {mapGetters} from 'vuex'
  import UpcomingTraining from "@/components/UpcomingTraining";
  import GroupSelect from "@/components/GroupSelect";
  import CookieUserDialog from "@/components/CookieUserDialog";
  import User from "@/models/User";

  export default {
    name: "TrainingCheckIn",
    components: {CookieUserDialog, UpcomingTraining, GroupSelect},
    data: function () {
      return {
        filterGroupId: null,
        filterBranchId: null,
        tempFilterGroupId: null,
        tempFilterBranchId: null,
        upcomingTrainings: Array,
        dataLoaded: false,
        filterDialogVisible: false,
        cookieUserDialogVisible: false,
        selectedTrainingId: null,
        cookieUser: null,
        navigationCarouselOptions: {
          duration: 200,
          easing: 'ease-out',
          perPage: 5,
          startIndex: 0,
          draggable: true,
          multipleDrag: true,
          threshold: 20,
          loop: true,
          rtl: false,
          onInit: () => {
          },
          onChange: () => {
          },
        },
        trainingCarouselOptions: {
          duration: 200,
          easing: 'ease-in',
          perPage: 1,
          startIndex: 0,
          draggable: false,
          multipleDrag: false,
          threshold: 20,
          loop: false,
          rtl: false,
          onInit: () => {
          },
          onChange: () => {
          },
        },
      }
    },
    computed: {
      ...mapGetters({loggedInUser: 'loggedInUser'}),
      ...mapGetters('masterData', {getBranchByGroupId: 'getBranchByGroupId', getGroupById: 'getGroupById', getBranchById: 'getBranchById', getLocationNameById: 'getLocationNameById', getGroupsByIds: 'getGroupsByIds'}),
      currentUser: function () {
        if (this.loggedInUser) {
          return this.loggedInUser;
        } else if (this.cookieUser) {
          return this.cookieUser;
        }
        return null;
      },
      currentUserGroupId() {
        if (this.currentUser && this.currentUser.groupId) {
          return this.currentUser.groupId
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
      currentUserBranchId() {
        if (this.currentUser && this.currentUser.groupId) {
          return this.getBranchByGroupId(this.currentUser.groupId).id
        }
        return null
      },
      currentUserId() {
        if (this.currentUser) {
          return this.currentUser.id;
        }
        return null;
      },
    },
    created() {
      if (!this.loggedInUser) {
        this.cookieUser = User.from(this.getCookie('cookieUser'));
      }
      this.filterGroupId = this.currentUserGroupId;
      this.filterBranchId = this.currentUserBranchId;
      this.fetchData();
    },
    methods: {
      groupChanged(groupId) {
        this.tempFilterGroupId = groupId;
      },
      branchChanged(branchId) {
        this.tempFilterBranchId = branchId;
      },
      filterDone() {
        this.filterBranchId = this.tempFilterBranchId;
        this.filterGroupId = this.tempFilterGroupId;
        this.fetchData();
        this.filterDialogVisible = false;
      },
      fetchData: function () {
        var self = this;
        self.dataLoaded = false;

        let url = '/training/upcoming';
        if (this.filterGroupId) {
          url += '?groupIds=' + this.filterGroupId;
        }

        this.$http.get(url).then(response => {
          self.upcomingTrainings = response.data.data;
          self.$refs.navigationCarousel.init()
          self.$refs.trainingCarousel.init()
          self.selectedTrainingId = self.upcomingTrainings[0].id;
          self.dataLoaded = true;
        })
      },
      updateCheckedIn: function (id) {
        let training = this.getTrainingById(id);
        this.$set(training, 'attending', true)
        training.participantIds.push(this.currentUserId);
        this.$emit("showSnackbar", "Im Training angemeldet", "success");
      },
      updateCheckedOut: function (id) {
        let training = this.getTrainingById(id);
        this.$set(training, 'attending', false)
        training.participantIds = training.participantIds.filter(id => id !== this.currentUserId)
        this.$emit("showSnackbar", "Vom Training abgemeldet", "info");
      },
      getTrainingById: function (id) {
        return this.upcomingTrainings.filter(ut => ut.id == id)[0];
      },
      trainingSelected: function (index, item) {
        this.$refs.trainingCarousel.goTo(index);
        this.selectedTrainingId = item.id;
      },
      attending: function (trainingId) {
        return this.getTrainingById(trainingId).participantIds.filter(id => id === this.currentUserId).length > 0;
      },
      removeCookieUser: function () {
        this.eraseCookie('cookieUser');
        this.cookieUser = null;
      },
      setCookie(name, value, days) {
        var expires = "";
        if (days) {
          var date = new Date();
          date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
          expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
      },
      getCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
          var c = ca[i];
          while (c.charAt(0) == ' ') c = c.substring(1, c.length);
          if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
      },
      eraseCookie(name) {
        document.cookie = name + '=; Max-Age=-99999999;';
      }
    }
  }
</script>

<style scoped lang="scss">
  .tp-training-check-in {
    &__cookie-user {
      display: flex;
    }

    &__navigation-carousel-card {
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
      &--attending {
        background-color: #60cc69;
      }
    }

    &__card {
      margin-bottom: 2rem;
    }

    &__navigation-carousel {
      position: relative;

      &-next {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translate(50%, -50%);
        z-index: 1;
      }

      &-prev {
        position: absolute;
        left: 0;
        top: 50%;
        transform: translate(-50%, -50%);
        z-index: 1;
      }

    }
  }
</style>

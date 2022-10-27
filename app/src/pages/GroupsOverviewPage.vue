<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Gruppenübersicht</v-toolbar-title>
          </v-toolbar>
          <v-divider></v-divider>
          <v-card-text flat class="pa-2 pa-md-4">
            <v-card>
              <v-card-text class="pa-0 pa-md-4">
                <v-container>
                  <v-row no-gutters>
                    <v-col>
                      <v-data-iterator
                          :headers="headers"
                          :items="groups"
                          item-key="id"
                      >

                        <template v-slot:default="props">
                          <v-row>
                            <v-col
                                v-for="group in props.items"
                                :key="group.id"
                                cols="12"
                                sm="6"
                                md="4"
                                lg="3">
                              <v-card :color="group.branch.colorHex">
                                <v-card-title class="subheading font-weight-bold">
                                  {{ group.name }}
                                </v-card-title>
                                <v-card-subtitle>
                                  {{ group.branch.name }}
                                </v-card-subtitle>

                                <v-divider></v-divider>
                                <v-card-text>
                                  <v-list subheader :color="group.branch.colorHex" dense>
                                    <v-subheader>Sportler</v-subheader>

                                    <v-list-item
                                        v-for="user in getSimpleUsersByIds(group.userIds).sort((a,b) => a.familyName.localeCompare(b.familyName))"
                                        :key="user.id">
                                      <v-list-item-content>
                                        {{ user.getFullNameFamilyFirst() }}
                                      </v-list-item-content>
                                    </v-list-item>
                                  </v-list>
                                  <v-list subheader :color="group.branch.colorHex" dense>
                                    <v-subheader>Trainingszeiten</v-subheader>

                                    <v-list-item
                                        v-for="training in getTrainingSeriesByGroupId(group.id).sort((a,b) => (a.weekdays.length > 0 && b.weekdays.length > 0) ? a.weekdays[0] - b.weekdays[0] : -1)"
                                        :key="training.id">
                                      <v-list-item-content>
                                        <span class="caption">{{ dayArrayToString(training.weekdays) }} - {{ training.startTime }} - {{ training.endTime }}</span>
                                        <span class="caption">{{getLocationNameById(training.locationId)}}</span>
                                      </v-list-item-content>
                                    </v-list-item>
                                  </v-list>
                                </v-card-text>
                              </v-card>
                            </v-col>
                          </v-row>
                        </template>
                      </v-data-iterator>
                    </v-col>
                  </v-row>
                </v-container>
              </v-card-text>
            </v-card>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script lang="ts">

import Vue from "vue";
import {mapGetters} from 'vuex'
import Group from "../models/Group";
import User from "../models/User";
import {dayArrayToString, formatDate, parseDate} from "../helpers/date-helpers"
import TrainingSeries from "../models/TrainingSeries";

export default Vue.extend({
  name: "GroupsOverviewPage",
  components: {},
  data() {
    return {
      loading: false,
      trainingSeries: this.$store.state.masterData.trainingSeries as TrainingSeries[],
      users: this.$store.state.masterData.simpleUsers as User[],
      groups: this.$store.state.masterData.groups as Group[],
      headers: [
        {text: 'Name', value: 'name', sortable: false},
        {text: 'Sparte', value: 'branchId', sortable: false},
        {text: 'Mitglieder', value: 'userCount', sortable: false},
      ],
    }
  },
  created() {
  },
  computed: {
    ...mapGetters({loggedInUser: 'loggedInUser'}),
    ...mapGetters('masterData', {
      getSimpleUsersByIds: 'getSimpleUsersByIds',
      getSimpleTrainersByGroupId: 'getSimpleTrainersByGroupId',
      getTrainingSeriesByGroupId: 'getTrainingSeriesByGroupId',
      getLocationNameById: 'getLocationNameById',
    }),
  },
  methods: {
    dayArrayToString,
    formatDate,
    parseDate,
  }
})

</script>

<style scoped lang="scss">

</style>

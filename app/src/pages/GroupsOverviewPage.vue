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
                                    <v-list-subheader>Sportler</v-list-subheader>

                                    <v-list-item
                                        v-for="user in getSimpleUsersByIds(group.userIds).sort((a,b) => a.familyName.localeCompare(b.familyName))"
                                        :key="user.id">
                                      {{ user.getFullNameFamilyFirst() }}
                                    </v-list-item>
                                  </v-list>
                                  <v-list subheader :color="group.branch.colorHex" dense>
                                    <v-list-subheader>Trainingszeiten</v-list-subheader>

                                    <v-list-item
                                        v-for="training in getTrainingSeriesByGroupId(group.id).sort((a,b) => (a.weekdays.length > 0 && b.weekdays.length > 0) ? a.weekdays[0] - b.weekdays[0] : -1)"
                                        :key="training.id">
                                      <span class="caption">{{ dayArrayToString(training.weekdays) }} - {{ training.startTime }} - {{ training.endTime }}</span>
                                      <br/>
                                      <span class="caption">{{getLocationNameById(training.locationId)}}</span>
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
import Group from "../models/Group";
import User from "../models/User";
import {dayArrayToString, formatDate, parseDate} from "../helpers/date-helpers"
import TrainingSeries from "../models/TrainingSeries";
import { useAuthStore } from '@/store/auth'
import { useMasterDataStore } from '@/store/masterData'

export default {
  name: "GroupsOverviewPage",
  components: {},
  data() {
    const masterData = useMasterDataStore()
    return {
      loading: false,
      trainingSeries: masterData.trainingSeries as TrainingSeries[],
      users: masterData.simpleUsers as User[],
      groups: masterData.groups as Group[],
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
    loggedInUser() {
      return useAuthStore().user
    },
  },
  methods: {
    getSimpleUsersByIds(userIds) {
      return useMasterDataStore().getSimpleUsersByIds(userIds)
    },
    getSimpleTrainersByGroupId(groupId) {
      return useMasterDataStore().getSimpleTrainersByGroupId(groupId)
    },
    getTrainingSeriesByGroupId(groupId) {
      return useMasterDataStore().getTrainingSeriesByGroupId(groupId);
    },
    getLocationNameById(locationId) {
      return useMasterDataStore().getLocationNameById(locationId);
    },
    dayArrayToString,
    formatDate,
    parseDate,
  },
});

</script>

<style scoped lang="scss">

</style>

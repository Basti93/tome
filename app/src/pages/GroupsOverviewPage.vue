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
                          :items-per-page="-1"
                          item-key="id"
                      >

                        <template v-slot:default="props">
                          <v-row>
                            <v-col
                                v-for="{ raw: group } in props.items"
                                :key="group.id"
                                cols="12"
                                sm="6"
                                md="4"
                                lg="3">
                              <v-card :color="getBranchColorByGroupId(group.id)">
                                <v-card-title class="subheading font-weight-bold">
                                  {{ group.name }}
                                </v-card-title>
                                <v-card-subtitle>
                                  {{ getBranchNameByGroupId(group.id) }}
                                </v-card-subtitle>

                                <v-divider></v-divider>
                                <v-card-text>
                                  <v-list subheader :color="getBranchColorByGroupId(group.id)" density="compact">
                                    <v-list-subheader>Sportler</v-list-subheader>

                                    <v-list-item
                                        v-for="user in getSimpleUsersByGroupId(group.id)"
                                        :key="user.id">
                                      {{ user.getFullNameFamilyFirst() }}
                                    </v-list-item>
                                  </v-list>
                                  <v-list subheader :color="getBranchColorByGroupId(group.id)" density="compact">
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

<script setup lang="ts">
import { computed, ref } from 'vue'
import { dayArrayToString } from '../helpers/date-helpers'
import { useMasterDataStore } from '@/store/masterData'

const masterData = useMasterDataStore()

const loading = ref(false)
const headers = [
  { title: 'Name', value: 'name', sortable: false },
  { title: 'Sparte', value: 'branchId', sortable: false },
  { title: 'Mitglieder', value: 'userCount', sortable: false }
]

const groups = computed(() => masterData.groups)
const trainingSeries = computed(() => masterData.trainingSeries)

function getSimpleUsersByGroupId(groupId: number) {
  return masterData.getSimpleUsersByGroupId(groupId)
}

function getSimpleTrainersByGroupId(groupId: number) {
  return masterData.getSimpleTrainersByGroupId(groupId)
}

function getTrainingSeriesByGroupId(groupId: number) {
  return masterData.getTrainingSeriesByGroupId(groupId)
}

function getLocationNameById(locationId: number) {
  return masterData.getLocationNameById(locationId)
}

function getBranchColorByGroupId(groupId: number) {
  const group = masterData.groups.find((g) => g.id === groupId)
  if (group) {
    const branch = masterData.branches.find((b) => b.id === group.branchId)
    return branch ? branch.colorHex : '#000000'
  }
  return '#000000'
}

function getBranchNameByGroupId(groupId: number) {
  const group = masterData.groups.find((g) => g.id === groupId)
  if (group) {
    const branch = masterData.branches.find((b) => b.id === group.branchId)
    return branch ? branch.name : 'Unknown'
  }
  return 'Unknown'
}
</script>

<style scoped lang="scss">

</style>

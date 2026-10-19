<template>
  <v-container pa-0 ma-0>
    <v-row>
      <v-col cols="12" md="4">
        <v-select
            v-bind:items="branches"
            item-title="name"
            item-value="id"
            v-model="selectedBranchId"
            label="Sparte"
            prepend-icon="mdi-chart-bubble"
        ></v-select>
      </v-col>
      <v-col cols="12" md="4">
        <v-autocomplete
            v-bind:disabled="!selectedBranchId"
            v-bind:items="groupItems"
            v-model="selectedGroupIds"
            item-title="name"
            item-value="id"
            multiple
            clearable
            chips
            closable-chips
            @update:model-value="selectedGroupIdsChanged()"
            label="Gruppen"
            prepend-icon="mdi-account"
        ></v-autocomplete>
      </v-col>
      <v-col cols="12" md="4">
        <v-select
            :items="years"
            v-model="selectedYear"
            label="Jahr"
            @update:model-value="selectedYearChanged()"
            prepend-icon="mdi-calendar"
        ></v-select>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import { useMasterDataStore } from '@/store/masterData'
import dayjs from '@/helpers/dayjs'

export default {
  name: "StatisticsFilter1",
  props: {
    'branchIds': Array,
    'year': Number,
  },
  created() {
    this.initializing = true;
    this.years = this.lastFiveYears();
    this.selectedYear = this.year;
    this.initialGroupIds = this.groupIds;
    this.initSelect()
    this.initializing = false;
  },
  data() {
    return {
      selectedBranchId: null,
      selectedGroupIds: [],
      initialGroupIds: [],
      groupItems: [],
      selectedYear: null,
      years: [],
      initializing: false,
    }
  },
  computed: {
    groups() {
      return useMasterDataStore().groups
    },
    branches() {
      return useMasterDataStore().branches
    },
  },
  methods: {
    initSelect: function () {
      if (this.branchIds && this.branchIds.length > 0) {
        this.selectedBranchId = this.branchIds[0];
      }
    },
    selectedYearChanged: function () {
      this.$emit('yearSelected', this.selectedYear)
    },
    selectedGroupIdsChanged() {
      this.$emit('groupsSelected', this.selectedGroupIds)
    },
    lastFiveYears() {
      // current year and the four years before it (dayjs is immutable, no mutation loop)
      const currentYear = dayjs().year()
      return Array.from({ length: 5 }, (_, i) => currentYear - i)
    }
  },
  watch: {
    selectedBranchId: {
      immediate: true,
      handler(newVal, oldVal) {
        if (newVal && newVal != oldVal) {
          //reset group data
          this.selectedGroupIds = [];
          this.groupItems = [];
          //add all groups of branch to groupItems
          for (const group of this.groups) {
            if (this.selectedBranchId === group.branchId) {
              this.groupItems.push(group);
              this.selectedGroupIds.push(group.id);
            }
          }
          this.$emit('groupsSelected', this.selectedGroupIds)
        }
      },

    },
  }
}
</script>

<style scoped>

</style>

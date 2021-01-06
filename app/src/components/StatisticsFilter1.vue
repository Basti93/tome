<template>
  <v-container pa-0 ma-0>
    <v-row>
      <v-col cols="12" md="4">
        <v-select
            v-bind:items="branches"
            item-text="name"
            item-value="id"
            v-model="selectedBranchId"
            label="Sparte"
            prepend-icon="bubble_chart"
        ></v-select>
      </v-col>
      <v-col cols="12" md="4">
        <v-autocomplete
            v-bind:disabled="!selectedBranchId"
            v-bind:items="groupItems"
            v-model="selectedGroupIds"
            item-text="name"
            item-value="id"
            multiple
            clearable
            chips
            deletable-chips
            @change="selectedGroupIdsChanged()"
            label="Gruppen"
            prepend-icon="group"
        ></v-autocomplete>
      </v-col>
      <v-col cols="12" md="4">
        <v-select
            :items="years"
            v-model="selectedYear"
            label="Jahr"
            @change="selectedYearChanged()"
            prepend-icon="date_range"
        ></v-select>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import {mapGetters, mapState} from 'vuex'

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
    ...mapGetters('masterData', {getBranchByGroupId: 'getBranchByGroupId'}),
    ...mapState('masterData', {
      groups: 'groups',
      branches: 'branches',
    }),
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
      const years = []
      const dateEnd = this.moment().subtract(4, 'y')
      const dateStart = this.moment()
      while (dateEnd.diff(dateStart, 'years') <= 0) {
        years.push(dateStart.year())
        dateStart.subtract(1, 'year')
      }
      return years
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
          for (let group of this.groups) {
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

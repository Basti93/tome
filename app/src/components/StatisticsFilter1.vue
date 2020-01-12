<template>
  <v-container pa-0 ma-0>
    <v-layout row wrap>
      <v-flex xs12 md4>
        <v-select
          v-bind:items="branches"
          item-text="name"
          item-value="id"
          v-model="selectedBranchId"
          label="Sparte"
          prepend-icon="bubble_chart"
        ></v-select>
      </v-flex>
      <v-flex xs12 md4>
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
      </v-flex>
      <v-flex xs12 md4>
        <v-select
          :items="years"
          v-model="selectedYear"
          label="Jahr"
          @change="selectedYearChanged()"
          prepend-icon="date_range"
        ></v-select>
      </v-flex>
    </v-layout></v-container>
</template>

<script>
  import {mapGetters, mapState} from 'vuex'

  export default {

    name: "StatisticsFilter1",
    props: {
      'groupIds': Array,
      'year': Number,
    },
    created() {
      this.initializing = true;
      this.years = [2020, 2019, 2018, 2017]
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
        if (this.groupIds.length > 0) {
          this.selectedBranchId = this.getBranchByGroupId(this.groupIds[0]).id;
        }
      },
      selectedYearChanged: function () {
        this.$emit('yearSelected', this.selectedYear)
      },
      selectedGroupIdsChanged() {
        this.$emit('groupsSelected', this.selectedGroupIds)
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
                //pre-select groups from user settings
                if (this.initialGroupIds.includes(group.id)) {
                  this.selectedGroupIds.push(group.id);
                }
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

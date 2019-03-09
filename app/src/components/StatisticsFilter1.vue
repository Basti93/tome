<template>
  <v-container pa-0 ma-0>
    <v-layout row wrap>
      <v-flex xs12 md4>
        <v-select
          v-bind:items="branches"
          item-text="name"
          item-value="id"
          v-model="selectedBranchId"
          clearable
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
          label="Gruppen"
          prepend-icon="group"
        ></v-autocomplete>
      </v-flex>
      <v-flex xs12 md4>
        <v-select
          :items="years"
          v-model="selectedYear"
          label="Jahr"
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
    },
    created() {
      this.years = [2019, 2018, 2017]
      this.selectedYear = this.moment().year();
      this.initSelect()
    },
    data() {
      return {
        selectedBranchId: null,
        selectedGroupIds: [],
        groupItems: [],
        selectedYear: null,
        years: [],
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
      fillGroupSelect: function () {
        this.groupItems = [];
        this.groups.forEach(function (item) {
          if (this.selectedBranchId === item.branchId) {
            this.groupItems.push(item);
          }
        }.bind(this));
      },
      initSelect: function () {
        this.selectedGroupIds = this.groupIds;
        if (this.groupIds.length > 0) {
          this.selectedBranchId = this.getBranchByGroupId(this.groupIds[0]).id;
        }
      },
    },
    watch: {
      selectedBranchId: function () {
        if (this.selectedBranchId) {
          this.fillGroupSelect();
        } else {
          this.selectedGroupIds = null;
          this.groupItems = []
        }
        this.$emit('branchSelected', this.selectedBranchId)
      },
      branchId: function () {
        this.initSelect();
      },
      selectedGroupIds: function () {
        this.$emit('groupsSelected', this.selectedGroupIds)
      },
      selectedYear: function () {
        this.$emit('yearSelected', this.selectedYear)
      },
    }
  }
</script>

<style scoped>

</style>

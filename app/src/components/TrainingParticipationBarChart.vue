<template>
  <v-card flat>
    <v-card-title>
      <StatisticsFilter1
          v-bind:branchIds="filterBranchIds"
          v-bind:year="filterYear"
          v-on:groupsSelected="filterGroupIdsChanged"
          v-on:yearSelected="filterYearChanged">
      </StatisticsFilter1>
    </v-card-title>
    <v-divider></v-divider>
    <v-card-text>
      <apexchart ref="trainingParticipationBarChart" height="600" type="bar" :options="options"
                 :series="series"></apexchart>
    </v-card-text>
  </v-card>
</template>

<script>
import {mapGetters} from 'vuex'
import StatisticsFilter1 from "./StatisticsFilter1";

export default {
  name: "TrainingParticipationBarChart",
  components: {StatisticsFilter1},
  data: function () {
    return {
      filterBranchIds: [],
      filterYear: null,
      options: {
        chart: {
          id: 'training-participation',
          height: 400,
        },
        colors: ['#60cb69'],
        plotOptions: {
          bar: {
            horizontal: true,
            distributed: true,
          }
        },
        legend: {
          show: false,
        },
        dataLabels: {
          style: {
            colors: ['#000']
          },
        },
        xaxis: {
          type: 'category',
          categories: [],
        },
      },
      series: [{
        name: 'Teilnahmen',
        data: []
      }],
    }
  },
  created() {
    if (this.loggedInUser.isTrainer) {
      this.filterBranchIds = this.loggedInUser.trainerBranchIds;
    } else {
      if (this.loggedInUser.groupIds && this.loggedInUser.groupIds.length > 0) {
        this.filterBranchIds.push(this.getBranchByGroupId(this.loggedInUser.groupIds[0].id));
      }
    }
    this.filterYear = this.moment().year();
  },
  computed: {
    ...mapGetters('masterData', {
      getGroupColorById: 'getGroupColorById',
      getGroupIdsByBranchId: 'getGroupIdsByBranchId',
      getBranchByGroupId: 'getBranchByGroupId',
    }),
    ...mapGetters({loggedInUser: 'loggedInUser'}),
  },
  methods: {
    async fetchData() {
      this.series = [];
      if (this.filterGroupIds.length > 0 && this.filterYear) {
        let url = '/training/participationcount?groupIds=' + this.filterGroupIds + '&year=' + this.filterYear;
        let {data} = await this.$http.get(url);
        let names = [];
        let count = [];
        for (let i = 0; i < data.length; i++) {
          names.push(data[i].firstName + " " + data[i].familyName)
          count.push(data[i].total)
        }
        this.options = {
          xaxis: {
            categories: names,
          },
        };
        this.series = [{
          data: count
        }];

      }
    },
    filterGroupIdsChanged: function (groupIds) {
      this.filterGroupIds = groupIds;
      this.fetchData();
    },
    filterYearChanged: function (year) {
      this.filterYear = year;
      this.fetchData();
    },
  },
}
</script>

<style scoped>

</style>

<template>
  <v-card>
    <v-card-title>
      <StatisticsFilter1
        v-bind:groupIds="filterGroupIds"
        v-bind:year="filterYear"
        v-on:branchSelected="filterBranchIdChanged"
        v-on:groupsSelected="filterGroupIdsChanged"
        v-on:yearSelected="filterYearChanged">
      </StatisticsFilter1>
    </v-card-title>
    <v-card-text>
      <apexchart ref="trainerTimeLineChart" type="line" :options="options" :series="series" />
    </v-card-text>
  </v-card>
</template>

<script>
  import {mapGetters} from 'vuex'
  import StatisticsFilter1 from "./StatisticsFilter1";

  export default {
    name: "TrainerTimeline",
    components: {StatisticsFilter1},
    props: ['trainerId'],
    data: function () {
      return {
        filterGroupIds: [],
        filterYear: null,
        options: {
          chart: {
            id: 'trainerTimeLineChart',
            foreColor: '#fff',
            zoom: {
              enabled: false
            }
          },
          dataLabels: {
            enabled: true
          },
          stroke: {
            curve: 'straight'
          },
          tooltip: {
            theme: 'dark',
            x: {
              show: false
            },
          },
          yaxis: {
            labels: {
              show: false
            }
          },
          xaxis: {
            categories: ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'],
          },
        },
        series: [{
          name: "Abgehaltene Trainings",
          data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
        }],
      }
    },
    created() {
      if (this.loggedInUser.isTrainer || this.loggedInUser.isTrainer) {
        this.filterGroupIds = this.loggedInUser.trainerGroupIds;
      } else {
        this.filterGroupIds.push(this.loggedInUser.groupId);
      }
      this.filterYear = this.moment().year();
      this.fetchData();
    },
    computed: {
      ...mapGetters('masterData', {getGroupColorById: 'getGroupColorById', getGroupIdsByBranchId: 'getGroupIdsByBranchId'}),
      ...mapGetters({loggedInUser: 'loggedInUser'}),
    },
    methods: {
      fetchData() {
        var self = this;
        let url = '/training/' + this.loggedInUser.id + '/trainingscount/' + this.filterYear;
        let append = false;
        if (this.filterGroupIds && this.filterGroupIds.length > 0) {
          url += "?groupIds=" + this.filterGroupIds;
          append = true;
        }
        this.$http.get(url).then(function (response) {
          let count = [];
          for (let i = 0; i < 12; i++) {
            count.push(0);
          }
          for (let i = 0; i < response.data.length; i++) {
            let monthNumber = parseInt(response.data[i].month);
            count.splice((monthNumber - 1), 1, response.data[i].total);
          }

          self.series = [{
            data: count
          }];

        })
      },
      filterBranchIdChanged: function (branchId) {
        this.filterGroupIds = this.getGroupIdsByBranchId(branchId);
        this.fetchData();
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

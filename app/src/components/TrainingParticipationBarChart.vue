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
    <v-divider></v-divider>
    <v-card-text>
      <apexchart ref="trainingParticipationBarChart" height="600" type="bar" :options="options" :series="series"></apexchart>
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
        filterGroupIds: [],
        filterYear: null,
        options: {
          chart: {
            id: 'training-participation',
            foreColor: '#fff',
            height: 400
          },
          plotOptions: {
            bar: {
              horizontal: true,
              distributed: true,
              dataLabels: {
                position: 'bottom'
              },
            }
          },
          tooltip: {
            theme: 'dark',
            x: {
              show: false
            },
          },
          dataLabels: {
            enabled: true,
            textAnchor: 'start',
            style: {
              colors: ['#fff']
            },
            formatter: function (val, opt) {
              return opt.w.globals.labels[opt.dataPointIndex] + ":  " + val
            },
            offsetX: 0,
            dropShadow: {
              enabled: true
            }
          },
          legend: {
            show: true,
          },
          yaxis: {
            labels: {
              show: false
            }
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
        let url = '/training/participationcount';
        let append = false;
        if (this.filterGroupIds && this.filterGroupIds.length > 0) {
          url += "?groupIds=" + this.filterGroupIds;
          append = true;
        }
        if (this.filterYear) {
          url += (append ? "&" : "?") + "year=" + this.filterYear;
        }
        this.$http.get(url).then(function (response) {
          let names = [];
          let count = [];
          let colors = [];
          for (let i = 0; i < response.data.length; i++) {
            names.push(response.data[i].firstName + " " + response.data[i].familyName)
            colors.push(self.getGroupColorById(response.data[i].groupId))
            count.push(response.data[i].total)
          }

          self.options = {
            xaxis: {
              categories: names,
            },
            colors: colors,
          };

          self.series = [{
            data: count
          }];

        })
      },
      filterBranchIdChanged: function (branchId) {
        this.filterGroupIds = this.getGroupIdsByBranchId(branchId);
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

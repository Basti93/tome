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
      <apexchart ref="trainerTimeLineChart" type="bar" :options="options" :series="series"/>
    </v-card-text>
  </v-card>
</template>

<script>
import {mapGetters} from 'vuex'
import StatisticsFilter1 from "./StatisticsFilter1";
import {getRandomColor} from "../helpers/color-helper"

export default {
  name: "TrainerTimeline",
  components: {StatisticsFilter1},
  props: ['trainerId'],
  data: function () {
    return {
      filterBranchIds: [],
      filterYear: null,
      options: {
        chart: {
          id: 'trainerTimeLineChart',
          stacked: true,
        },
        plotOptions: {
          bar: {
            horizontal: true
          }
        },
        dataLabels: {
          enabled: true
        },
        grid: {
          yaxis: {
            lines: {
              show: true
            }
          },
        },
        legend: {
          showForNullSeries: false,
          showForZeroSeries: false,
        },
        tooltip: {
          y: {
            formatter: function (val) {
              return val + " Trainingsstunden"
            }
          }
        },
        fill: {
          opacity: 1
        },
        xaxis: {
          categories: ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'],
        },
      },
      series: [],
    }
  },
  created() {
    if (this.loggedInUser.isTrainer) {
      this.filterBranchIds = this.loggedInUser.trainerBranchIds;
    }
    this.filterYear = this.moment().year();
  },
  computed: {
    ...mapGetters('masterData', {
      getGroupColorById: 'getGroupColorById',
      getGroupIdsByBranchId: 'getGroupIdsByBranchId'
    }),
    ...mapGetters({loggedInUser: 'loggedInUser'}),
  },
  methods: {
    async fetchData() {
      this.series = [];
      let url = '/trainingevaluation/accountingtimestatistics?year=' + this.filterYear + "&groupIds=" + this.filterGroupIds;
      let {data} = await this.$http.get(url);
      for (let z = 0; z < data.length; z++) {
        let count = [];
        for (let i = 0; i < 12; i++) {
          count.push(0);
        }
        for (let i = 0; i < data[z].data.length; i++) {
          let monthNumber = parseInt(data[z].data[i].month);
          count.splice((monthNumber - 1), 1, data[z].data[i].accountingHours);
        }
        this.series.push({
          name: data[z].trainer.firstName + ' ' + data[z].trainer.familyName,
          data: count
        });
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

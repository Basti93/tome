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
    <v-card-text class="pa-0">
      <apexchart ref="trainingParticipationBarChart" height="600" type="bar" :options="options"
                 :series="series"></apexchart>
    </v-card-text>
  </v-card>
</template>

<script>
import StatisticsFilter1 from "./StatisticsFilter1";
import { useAuthStore } from '@/store/auth';
import { useMasterDataStore } from '@/store/masterData';
import axios from '@/axios';
import moment from 'moment';

export default {
  name: "TrainingParticipationBarChart",
  components: {StatisticsFilter1},
  data: function () {
    const authStore = useAuthStore();
    const masterData = useMasterDataStore();
    const user = authStore.user;
    let filterBranchIds = [];
    if (user?.isTrainer) {
      filterBranchIds = user.trainerBranchIds;
    } else {
      if (user?.groupIds && user.groupIds.length > 0) {
        const branch = masterData.getBranchByGroupId(user.groupIds[0]);
        if (branch) filterBranchIds.push(branch.id);
      }
    }
    return {
      filterBranchIds: filterBranchIds,
      filterYear: moment().year(),
      filterGroupIds: [],
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
    };
  },
  created() {
    this.fetchData();
  },
  computed: {
    loggedInUser() {
      return useAuthStore().user;
    },
  },
  methods: {
    getGroupColorById(groupId) {
      return useMasterDataStore().getGroupColorById(groupId);
    },
    getGroupIdsByBranchId(branchId) {
      return useMasterDataStore().getGroupIdsByBranchId(branchId);
    },
    getBranchByGroupId(groupId) {
      return useMasterDataStore().getBranchByGroupId(groupId);
    },
    async fetchData() {
      this.series = [];
      if (this.filterGroupIds.length > 0 && this.filterYear) {
        try {
          let url = '/training/participationcount?groupIds=' + this.filterGroupIds + '&year=' + this.filterYear;
          let {data} = await axios.get(url);
          let names = [];
          let count = [];
          for (let i = 0; i < data.length; i++) {
            names.push(data[i].firstName + " " + data[i].familyName);
            count.push(data[i].total);
          }
          this.options = {
            xaxis: {
              categories: names,
            },
          };
          this.series = [{
            data: count
          }];
        } catch (error) {
          console.error('Error fetching participation data:', error);
        }
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

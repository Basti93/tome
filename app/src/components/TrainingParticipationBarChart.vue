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

<script setup lang="ts">
import { ref } from 'vue'
import StatisticsFilter1 from './StatisticsFilter1.vue'
import { useAuthStore } from '@/store/auth'
import { useMasterDataStore } from '@/store/masterData'
import httpClient from '@/http/api'
import dayjs from '@/helpers/dayjs'

const user = useAuthStore().user
const masterData = useMasterDataStore()

let initialBranchIds: any[] = []
if (user?.isTrainer) {
  initialBranchIds = user.trainerBranchIds
} else if (user?.groupIds && user.groupIds.length > 0) {
  const branch = masterData.getBranchByGroupId(user.groupIds[0])
  if (branch) initialBranchIds.push(branch.id)
}

const filterBranchIds = ref<any[]>(initialBranchIds)
const filterYear = ref(dayjs().year())
const filterGroupIds = ref<any[]>([])
const options = ref<any>({
  chart: {
    id: 'training-participation',
    height: 400
  },
  colors: ['#60cb69'],
  plotOptions: {
    bar: {
      horizontal: true,
      distributed: true
    }
  },
  legend: {
    show: false
  },
  dataLabels: {
    style: {
      colors: ['#000']
    }
  },
  xaxis: {
    type: 'category',
    categories: []
  }
})
const series = ref<any[]>([
  {
    name: 'Teilnahmen',
    data: []
  }
])

async function fetchData() {
  series.value = []
  if (filterGroupIds.value.length > 0 && filterYear.value) {
    try {
      const url = '/training/participationcount?groupIds=' + filterGroupIds.value + '&year=' + filterYear.value
      const { data } = await httpClient.get(url)
      const names: string[] = []
      const count: number[] = []
      for (let i = 0; i < data.length; i++) {
        names.push(data[i].firstName + ' ' + data[i].familyName)
        count.push(data[i].total)
      }
      options.value = {
        xaxis: {
          categories: names
        }
      }
      series.value = [
        {
          data: count
        }
      ]
    } catch (error) {
      console.error('Error fetching participation data:', error)
    }
  }
}

function filterGroupIdsChanged(groupIds: any[]) {
  filterGroupIds.value = groupIds
  fetchData()
}

function filterYearChanged(year: number) {
  filterYear.value = year
  fetchData()
}

fetchData()
</script>

<style scoped>

</style>

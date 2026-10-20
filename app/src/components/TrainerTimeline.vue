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
      <apexchart ref="trainerTimeLineChart" type="bar" height="600" :options="themedOptions" :series="series"/>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useTheme } from 'vuetify'
import StatisticsFilter1 from './StatisticsFilter1.vue'
import { useAuthStore } from '@/store/auth'
import httpClient from '@/http/api'
import dayjs from '@/helpers/dayjs'

const authStore = useAuthStore()

const filterBranchIds = ref<any[]>(authStore.user?.isTrainer ? authStore.user.trainerBranchIds : [])
const filterYear = ref(dayjs().year())
const filterGroupIds = ref<any[]>([])
const options = ref({
  chart: {
    id: 'trainerTimeLineChart',
    stacked: true
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
    }
  },
  legend: {
    showForNullSeries: false,
    showForZeroSeries: false
  },
  tooltip: {
    y: {
      formatter: function (val: number) {
        return val + ' Trainingsstunden'
      }
    }
  },
  fill: {
    opacity: 1
  },
  xaxis: {
    categories: ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez']
  }
})
const series = ref<{ name: string; data: number[] }[]>([])

async function fetchData() {
  series.value = []
  const url = '/trainingevaluation/accountingtimestatistics?year=' + filterYear.value + '&groupIds=' + filterGroupIds.value
  const { data } = await httpClient.get(url)
  for (let z = 0; z < data.length; z++) {
    const count: number[] = []
    for (let i = 0; i < 12; i++) {
      count.push(0)
    }
    for (let i = 0; i < data[z].data.length; i++) {
      const monthNumber = parseInt(data[z].data[i].month)
      count.splice(monthNumber - 1, 1, data[z].data[i].accountingHours)
    }
    series.value.push({
      name: data[z].trainer.firstName + ' ' + data[z].trainer.familyName,
      data: count
    })
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

// ApexCharts has its own colors: follow the Vuetify theme (dark mode)
const vuetifyTheme = useTheme()
const themedOptions = computed(() => {
  const dark = vuetifyTheme.current.value.dark
  return {
    ...options.value,
    chart: { ...options.value.chart, foreColor: dark ? '#e0e0e0' : '#373d3f' },
    theme: { mode: dark ? 'dark' : 'light' },
    grid: { borderColor: dark ? '#444' : '#e0e0e0' },
    tooltip: { theme: dark ? 'dark' : 'light' },
  }
})
</script>

<style scoped>

</style>

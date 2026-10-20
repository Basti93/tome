<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Meine Stunden</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-btn variant="text" color="primary" prepend-icon="mdi-file-excel-outline" @click="showExport = true">
              Abrechnung (Excel)
            </v-btn>
          </v-toolbar>
          <v-card-text class="pa-0 pa-md-4">
            <v-card class="ma-1">
              <MyHours @year-changed="year = $event" />
            </v-card>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    <TrainingAccountingExportDialog
        :visible="showExport"
        :from="`${year}-01-01`"
        :to="year === currentYear ? today : `${year}-12-31`"
        @close="showExport = false"
    />
  </v-container>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import dayjs from '@/helpers/dayjs'
import MyHours from '@/components/MyHours.vue'
import TrainingAccountingExportDialog from '@/components/TrainingAccountingExportDialog.vue'

const currentYear = dayjs().year()
const today = dayjs().format('YYYY-MM-DD')
const year = ref(currentYear)
const showExport = ref(false)
</script>

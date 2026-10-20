<template>
  <v-dialog
      :model-value="visible"
      max-width="400px"
      :fullscreen="xs"
      persistent>
    <v-card>
      <v-toolbar flat>
        <v-btn
            icon
            aria-label="Schließen"
            @click="close">
          <v-icon>mdi-close</v-icon>
        </v-btn>
        <v-toolbar-title>Übungsleiter-Abrechnung</v-toolbar-title>
      </v-toolbar>
      <v-divider></v-divider>
      <v-card-text>
        <v-form v-model="valid">
          <v-container>
            <v-row>
              <v-col>
                <v-text-field
                    v-model="dateFrom"
                    type="date"
                    label="Von"
                    prepend-icon="mdi-calendar"
                    :rules="[required]"
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col>
                <v-text-field
                    v-model="dateTo"
                    type="date"
                    label="Bis"
                    prepend-icon="mdi-calendar"
                    :rules="[required]"
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col>
                <v-btn
                    color="primary"
                    :disabled="!valid || !loggedInUser"
                    :loading="creatingExcelReport"
                    @click="downloadExcelReport()">
                  <v-icon start>mdi-download</v-icon>
                  Excel herunterladen
                </v-btn>
              </v-col>
            </v-row>
          </v-container>
        </v-form>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useDisplay } from 'vuetify'
import { useAuthStore } from '@/store/auth'
import { useSnackbarStore } from '@/store/snackbar'
import httpClient from '@/http/api'
import dayjs from '@/helpers/dayjs'
import { filenameFromContentDisposition, saveBlob } from '@/helpers/download'

const { xs } = useDisplay()
// from/to (YYYY-MM-DD) prefill the period every time the dialog opens (default: the last 6 months)
const props = defineProps<{ visible: boolean; from?: string; to?: string }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const authStore = useAuthStore()
const snackbar = useSnackbarStore()
const loggedInUser = computed(() => authStore.user)

const valid = ref(true)
const dateFrom = ref(dayjs().subtract(6, 'month').format('YYYY-MM-DD'))
const dateTo = ref(dayjs().format('YYYY-MM-DD'))
const creatingExcelReport = ref(false)

watch(() => props.visible, (open) => {
  if (!open) return
  dateFrom.value = props.from ?? dayjs().subtract(6, 'month').format('YYYY-MM-DD')
  dateTo.value = props.to ?? dayjs().format('YYYY-MM-DD')
})

const required = (value: string) => !!value || 'Pflichtfeld'

function close() {
  emit('close')
}

/** The endpoint answers with the XLSX file itself (Content-Disposition: attachment). */
async function downloadExcelReport() {
  if (!loggedInUser.value) return
  creatingExcelReport.value = true
  try {
    const response = await httpClient.post<Blob>(
      '/trainingevaluation/exportaccountingtimes',
      {
        userId: loggedInUser.value.id,
        from: dayjs(dateFrom.value, 'YYYY-MM-DD').format(),
        to: dayjs(dateTo.value, 'YYYY-MM-DD').format(),
      },
      { responseType: 'blob' },
    )
    const filename = filenameFromContentDisposition(response.headers?.get('content-disposition'), 'abrechnung.xlsx')
    saveBlob(response.data, filename)
  } catch (error) {
    console.error(error)
    snackbar.show('Die Abrechnung konnte nicht erstellt werden', 'error')
  } finally {
    creatingExcelReport.value = false
  }
}
</script>

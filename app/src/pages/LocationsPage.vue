<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Trainingsorte</v-toolbar-title>
            <template v-slot:extension>
              <v-dialog
                  v-model="showDialog"
                  :fullscreen="xs"
                  persistent
                  max-width="1000px"
              >
                <template v-slot:activator="{ props }">
                  <v-btn
                      title="Neuen Trainingsort erstellen"
                      icon
                      class="ma-2"
                      elevation="1"
                      color="primary"
                      v-bind="props"
                      @click="createItem()">
                    <v-icon>mdi-plus</v-icon>
                  </v-btn>
                </template>

                <v-card>
                  <v-toolbar flat>
                    <v-btn icon v-on:click="closeDialog()">
                      <v-icon>mdi-close</v-icon>
                    </v-btn>
                    <v-toolbar-title>{{ titleDialog }}</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-toolbar-items>
                      <v-btn color="primary" v-on:click="save()" variant="text">
                        <v-icon start>mdi-check</v-icon>
                        Speichern
                      </v-btn>
                    </v-toolbar-items>
                  </v-toolbar>
                  <v-divider class="pb-2"></v-divider>
                  <v-card-text>
                    <v-form
                        ref="form"
                        v-model="validDialog">
                      <v-container>
                        <v-row>
                          <v-col cols="12">

                            <v-text-field
                                v-model="editedItem.name"
                                label="Name"
                                required
                                :rules="[v => !!v || 'Wird benötigt']"
                                prepend-icon="add_location"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                      </v-container>
                    </v-form>
                  </v-card-text>
                </v-card>
              </v-dialog>
            </template>
          </v-toolbar>
          <v-divider></v-divider>
          <v-card-text class="pa-0 pa-md-4">
            <v-card>
              <v-card-text class="pa-0 pa-md-4">
                <v-container>
                  <v-row>
                    <v-col>
                      <v-data-table-server
                          :headers="headers"
                          :items="locations"
                          item-key="id"
                          :loading="loading"
                          :items-length="total"
                          :items-per-page-options="rowsPerPageItems"
                          v-model:items-per-page="itemsPerPage"
                          v-model:page="page"
                      >
                        <template v-slot:[`item.name`]="{ item }">
                          {{ item.name }}
                          <span class="caption" v-if="item.name == 'Online'">&nbsp;(Für Trainings mit diesem Ort werden automatisch Zoom Trainings erstellt)</span>
                        </template>
                        <template v-slot:[`item.action`]="{ item }">
                            <v-icon :disabled="item.name == 'Online'"
                                    v-on:click="editItem(item)"
                                    color="success">mdi-pencil</v-icon>
                        </template>
                        <template v-slot:no-data>
                          <v-container>
                            <v-row>
                              <v-col>
                                <v-btn color="error" :disabled="loading" v-on:click="loadData()">
                                  <v-icon start>mdi-refresh</v-icon>
                                  Keine Daten gefunden
                                </v-btn>
                              </v-col>
                            </v-row>
                          </v-container>
                        </template>
                      </v-data-table-server>
                    </v-col>
                  </v-row>
                </v-container>
              </v-card-text>
            </v-card>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    <v-row>
      <v-col>
      <v-alert
          type="info"
          pa-1
          ma-0
          class="caption" variant="outlined"
      >
        Um Trainingsorte zu löschen bitte den Admin fragen
      </v-alert>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useDisplay } from 'vuetify'
import httpClient from '@/http/api'
import { useSnackbarStore } from '@/store/snackbar'
import { useMasterDataStore } from '@/store/masterData'

const { xs } = useDisplay()

const form = ref<any>(null)
const loading = ref(false)
const locations = ref<any[]>([])
const total = ref<number | null>(null)
const rowsPerPageItems = [5, 10, 20, 50, 100]
const page = ref(1)
const itemsPerPage = ref(10)
const headers = [
  { title: 'Name', value: 'name', sortable: false },
  { title: '', value: 'action', sortable: false }
]
const defaultItem = { id: null, name: null }
const editedItem = ref<any>({ id: null, name: null })
const showDialog = ref(false)
const titleDialog = ref<string | null>(null)
const validDialog = ref(false)

async function loadLocations() {
  const { data } = await httpClient.get('/location')
  locations.value = data.data
  page.value = data.currentPage
  total.value = data.total
}

async function loadData() {
  loading.value = true
  try {
    await loadLocations()
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

function updateMasterData() {
  useMasterDataStore().setLocations(locations.value)
}

async function save() {
  const { valid } = await form.value.validate()
  if (!valid) return
  try {
    const postData = {
      name: editedItem.value.name
    }

    let response = null
    if (editedItem.value.id) {
      response = await httpClient.put('/location/' + editedItem.value.id, postData)
    } else {
      response = await httpClient.post('/location', postData)
    }
    if (response && response.data.error) {
      useSnackbarStore().show('Ort konnte nicht gespeichert werden', 'error')
    } else {
      useSnackbarStore().show('Ort gespeichert', 'success')
      showDialog.value = false
      await loadData()
      updateMasterData()
    }
  } catch (error) {
    console.error(error)
    useSnackbarStore().show('Speichern fehlgeschlagen', 'error')
  }
}

function createItem() {
  titleDialog.value = 'Ort erstellen'
  showDialog.value = true
  editedItem.value = { ...defaultItem }
}

function editItem(location: any) {
  titleDialog.value = 'Ort bearbeiten'
  showDialog.value = true
  editedItem.value = { ...location }
}

function closeDialog() {
  form.value.resetValidation()
  showDialog.value = false
}

loadData()
</script>

<style scoped>

</style>
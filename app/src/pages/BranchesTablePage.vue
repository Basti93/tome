<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Sparten</v-toolbar-title>
            <template v-slot:extension>
              <v-dialog
                  v-model="showDialog"
                  :fullscreen="xs"
                  persistent
                  max-width="1000px"
              >
                <template v-slot:activator="{ props }">
                  <v-btn
                      title="Neue Sparte erstellen"
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
                                prepend-icon="bubble_chart"
                            ></v-text-field>
                            <v-text-field
                                v-model="editedItem.shortName"
                                label="Kurzname"
                                required
                                :rules="[v => !!v || 'Wird benötigt']"
                                prepend-icon="bubble_chart"
                            ></v-text-field>
                            <v-color-picker
                                v-model="editedItem.color"
                                hide-inputs
                              >
                            </v-color-picker>
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
                          :items="branches"
                          item-key="id"
                          :loading="loading"
                          :items-length="total"
                          :items-per-page-options="rowsPerPageItems"
                          v-model:items-per-page="itemsPerPage"
                          v-model:page="page"
                      >
                        <template v-slot:[`item.name`]="{ item }">
                          {{ item.name }}
                        </template>
                        <template v-slot:[`item.shortName`]="{ item }">
                          {{ item.shortName }}
                        </template>
                        <template v-slot:[`item.colorHex`]="{ item }">
                          <v-chip v-if="item.colorHex" :color="item.colorHex"></v-chip>
                        </template>
                        <template v-slot:[`item.action`]="{ item }">
                            <v-icon v-on:click="editItem(item)"
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
        Um Sparten zu löschen bitte den Admin fragen
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
const branches = ref<any[]>([])
const total = ref<number | null>(null)
const rowsPerPageItems = [5, 10, 20, 50, 100]
const page = ref(1)
const itemsPerPage = ref(10)
const headers = [
  { title: 'Name', value: 'name', sortable: false },
  { title: 'Kurzname', value: 'shortName', sortable: false },
  { title: 'Farbe', value: 'colorHex', sortable: false },
  { title: '', value: 'action', sortable: false }
]
const editedItem = ref<any>({
  id: null,
  name: null,
  shortName: null,
  color: null
})
const showDialog = ref(false)
const titleDialog = ref<string | null>(null)
const validDialog = ref(false)

async function loadBranches() {
  const { data } = await httpClient.get('/branch')
  branches.value = data.data
  page.value = data.currentPage
  total.value = data.total
}

async function loadData() {
  loading.value = true
  try {
    await loadBranches()
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

function updateMasterData() {
  useMasterDataStore().setBranches(branches.value)
}

async function save() {
  const { valid } = await form.value.validate()
  if (!valid) return
  try {
    const postData = {
      name: editedItem.value.name,
      shortName: editedItem.value.shortName,
      colorHex: editedItem.value.color
    }

    let response = null
    if (editedItem.value.id) {
      response = await httpClient.put('/branch/' + editedItem.value.id, postData)
    } else {
      response = await httpClient.post('/branch', postData)
    }
    if (response && response.data.error) {
      useSnackbarStore().show('Sparte konnte nicht gespeichert werden', 'error')
    } else {
      useSnackbarStore().show('Sparte gespeichert', 'success')
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
  titleDialog.value = 'Sparte erstellen'
  editedItem.value.id = null
  editedItem.value.name = null
  editedItem.value.shortName = null
  editedItem.value.color = '#000000'
  showDialog.value = true
}

function editItem(branch: any) {
  titleDialog.value = 'Sparte bearbeiten'
  editedItem.value.id = branch.id
  editedItem.value.name = branch.name
  editedItem.value.shortName = branch.shortName
  editedItem.value.color = branch.colorHex
  showDialog.value = true
}

function closeDialog() {
  form.value.resetValidation()
  showDialog.value = false
}

loadData()
</script>

<style scoped>

</style>
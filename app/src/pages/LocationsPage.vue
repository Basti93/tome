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
                        <template v-slot:[`item.action`]="{ item }">
                            <v-icon
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

<script lang="ts">
import { useDisplay } from 'vuetify';
import httpClient from '@/http/api';
import { useSnackbarStore } from '@/store/snackbar';
import { useMasterDataStore } from '@/store/masterData';

export default {
  setup() {
    const { xs, md } = useDisplay()
    return { xs, md }
  },
  name: "LocationsPage",
  data: function () {
    return {
      loading: false,
      locations: [],
      total: null,
      rowsPerPageItems: [5, 10, 20, 50, 100],
      page: 1,
      itemsPerPage: 10,
      headers: [
        { title: 'Name', value: 'name', sortable: false},
        { title: '', value: 'action', sortable: false},
      ],
      editedItem: {
        id: null,
        name: null,
      },
      defaultItem: {
        id: null,
        name: null,
      },
      showDialog: false,
      titleDialog: null,
      validDialog: false,
    }
  },
  created() {
    this.loadData();
  },
  methods: {
    async loadData() {
      this.loading = true
      try {
        await this.loadLocations();
      } catch (error) {
        console.error(error)
      } finally {
        this.loading = false
      }
    },
    async loadLocations() {
      const {data} = await httpClient.get('/location')
      this.locations = data.data;
      this.page = data.currentPage;
      this.total = data.total;
    },
    async save() {
      const { valid } = await this.$refs.form.validate();
      if (!valid) return;
      try {
        const postData = {
          name: this.editedItem.name,
        }

        let response = null;
        if (this.editedItem.id) {
          response = await httpClient.put('/location/' + this.editedItem.id, postData);
        } else {
          response = await httpClient.post('/location', postData);
        }
        if (response && response.data.error) {
          useSnackbarStore().show("Ort konnte nicht gespeichert werden", "error")
        } else {
          useSnackbarStore().show("Ort gespeichert", "success")
          this.showDialog = false;
          await this.loadData();
          this.updateMasterData();
        }
      } catch (error) {
        console.error(error);
        useSnackbarStore().show('Speichern fehlgeschlagen', 'error')
      }
    },
    createItem() {
      this.titleDialog = "Ort erstellen"
      this.showDialog = true;
      this.editedItem = {...this.defaultItem}
    },
    editItem(location) {
      this.titleDialog = "Ort bearbeiten"
      this.showDialog = true;
      this.editedItem = {...location}
    },
    closeDialog() {
      this.$refs.form.resetValidation();
      this.showDialog = false;
    },
    updateMasterData() {
      const masterDataStore = useMasterDataStore();
      masterDataStore.setLocations(this.locations);
    },
  }
}
</script>

<style scoped>

</style>
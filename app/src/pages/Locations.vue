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
                  :fullscreen="$vuetify.breakpoint.xsOnly"
                  persistent
                  max-width="1000px"
              >
                <template v-slot:activator="{ on, attrs }">
                  <v-btn
                      title="Neuen Trainingsort erstellen"
                      fab
                      absolute
                      bottom
                      left
                      elevation="2"
                      color="primary"
                      v-on="on"
                      v-bind="attrs"
                      v-on:click="createItem()">
                    <v-icon>add</v-icon>
                  </v-btn>
                </template>

                <v-card>
                  <v-toolbar flat>
                    <v-btn icon v-on:click="closeDialog()">
                      <v-icon>close</v-icon>
                    </v-btn>
                    <v-toolbar-title>{{ titleDialog }}</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-toolbar-items>
                      <v-btn text color="primary" v-on:click="save()">
                        <v-icon left>check</v-icon>
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
          <v-card-text class="mt-8 pa-0 pa-md-4">
            <v-card>
              <v-card-text class="pa-0 pa-md-4">
                <v-container>
                  <v-row>
                    <v-col>
                      <v-data-table
                          :headers="headers"
                          :items="locations"
                          item-key="id"
                          :loading="loading"
                          :server-items-length="total"
                          :footer-props="{
                                itemsPerPageOptions: rowsPerPageItems,
                            }"
                          :itemsPerPage.sync="itemsPerPage"
                          :page.sync="page"
                      >
                        <template v-slot:item.name="{ item }">
                          {{ item.name }}
                          <span class="caption" v-if="item.name == 'Online'">&nbsp;(Für Trainings mit diesem Ort werden automatisch Zoom Trainings erstellt)</span>
                        </template>
                        <template v-slot:item.action="{ item }">
                          <v-btn
                              outlined
                              :disabled="item.name == 'Online'"
                              v-on:click="editItem(item)"
                              color="success">
                            <v-icon>edit</v-icon>
                          </v-btn>
                        </template>
                        <template v-slot:no-data>
                          <v-container>
                            <v-row>
                              <v-col>
                                <v-btn color="error" :disabled="loading" v-on:click="loadData()">
                                  <v-icon left>cached</v-icon>
                                  Keine Daten gefunden
                                </v-btn>
                              </v-col>
                            </v-row>
                          </v-container>
                        </template>
                      </v-data-table>
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
          outlined
          pa-1
          ma-0
          class="caption"
      >
        Um Trainingsorte zu löschen bitte den Admin fragen
      </v-alert>
      </v-col>
    </v-row>
  </v-container>
</template>

<script lang="ts">

export default {
  name: "Locations",
  data: function () {
    return {
      loading: false,
      locations: [],
      total: null,
      rowsPerPageItems: [5, 10, 20, 50, 100],
      page: 1,
      itemsPerPage: 10,
      headers: [
        {text: 'Name', value: 'name', sortable: false},
        {text: '', value: 'action', sortable: false},
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
      const {data} = await this.$http.get('/location')
      this.locations = data.data;
      this.page = data.currentPage;
      this.total = data.total;
    },
    async save() {
      this.$refs.form.validate();
      try {
        let postData = {
          name: this.editedItem.name,
        }

        let response = null;
        if (this.editedItem.id) {
          response = await this.$http.put('/location/' + this.editedItem.id, postData);
        } else {
          response = await this.$http.post('/location', postData);
        }
        if (response && response.data.error) {
          this.$emit("showSnackbar", "Ort konnte nicht gespeichert werden", "error")
        } else {
          this.$emit("showSnackbar", "Ort gespeichert", "success")
          this.showDialog = false;
          await this.loadData();
          this.updateMasterData();
        }
      } catch (error) {
        console.error(error);
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
      this.$store.commit('masterData/setLocations', this.locations);
    },
  }
}
</script>

<style scoped>

</style>
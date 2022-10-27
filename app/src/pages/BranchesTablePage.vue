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
                  :fullscreen="$vuetify.breakpoint.xsOnly"
                  persistent
                  max-width="1000px"
              >
                <template v-slot:activator="{ on, attrs }">
                  <v-btn
                      title="Neue Sparte erstellen"
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
          <v-card-text class="mt-8 pa-0 pa-md-4">
            <v-card>
              <v-card-text class="pa-0 pa-md-4">
                <v-container>
                  <v-row>
                    <v-col>
                      <v-data-table
                          :headers="headers"
                          :items="branches"
                          item-key="id"
                          :loading="loading"
                          :server-items-length="total"
                          :footer-props="{
                                itemsPerPageOptions: rowsPerPageItems,
                            }"
                          :itemsPerPage.sync="itemsPerPage"
                          :page.sync="page"
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
                                    color="success">edit</v-icon>
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
        Um Sparten zu löschen bitte den Admin fragen
      </v-alert>
      </v-col>
    </v-row>
  </v-container>
</template>

<script lang="ts">

export default {
  name: "BranchesTablePage",
  data: function () {
    return {
      loading: false,
      branches: [],
      total: null,
      rowsPerPageItems: [5, 10, 20, 50, 100],
      page: 1,
      itemsPerPage: 10,
      headers: [
        {text: 'Name', value: 'name', sortable: false},
        {text: 'Kurzname', value: 'shortName', sortable: false},
        {text: 'Farbe', value: 'colorHex', sortable: false},
        {text: '', value: 'action', sortable: false},
      ],
      editedItem: {
        id: null,
        name: null,
        shortName: null,
        color: null,
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
        await this.loadBranches();
      } catch (error) {
        console.error(error)
      } finally {
        this.loading = false
      }
    },
    async loadBranches() {
      const {data} = await this.$http.get('/branch')
      this.branches = data.data;
      this.page = data.currentPage;
      this.total = data.total;
    },
    async save() {
      this.$refs.form.validate();
      try {
        let postData = {
          name: this.editedItem.name,
          shortName: this.editedItem.shortName,
          colorHex: this.editedItem.color,
        }

        let response = null;
        if (this.editedItem.id) {
          response = await this.$http.put('/branch/' + this.editedItem.id, postData);
        } else {
          response = await this.$http.post('/branch', postData);
        }
        if (response && response.data.error) {
          this.$emit("showSnackbar", "Sparte konnte nicht gespeichert werden", "error")
        } else {
          this.$emit("showSnackbar", "Sparte gespeichert", "success")
          this.showDialog = false;
          await this.loadData();
          this.updateMasterData();
        }
      } catch (error) {
        console.error(error);
      }
    },
    createItem() {
      this.titleDialog = "Sparte erstellen"
      this.editedItem.id = null
      this.editedItem.name = null
      this.editedItem.shortName = null
      this.editedItem.color = "#000000"
      this.showDialog = true;
    },
    editItem(branch) {
      this.titleDialog = "Sparte bearbeiten"
      this.editedItem.id = branch.id
      this.editedItem.name = branch.name
      this.editedItem.shortName = branch.shortName
      this.editedItem.color = branch.colorHex
      this.showDialog = true;
    },
    closeDialog() {
      this.$refs.form.resetValidation();
      this.showDialog = false;
    },
    updateMasterData() {
      this.$store.commit('masterData/setBranches', this.branches);
    },
  }
}
</script>

<style scoped>

</style>
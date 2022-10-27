<template>
  <v-dialog
      v-model="show"
      max-width="400px"
      persistent>
    <v-card>
      <v-toolbar flat>
        <v-btn
            icon
            @click="show=false">
          <v-icon>close</v-icon>
        </v-btn>
        <v-toolbar-title>Übungsleiter-Abrechnung</v-toolbar-title>
      </v-toolbar>
      <v-divider></v-divider>
      <v-card-text>
        <v-form
            ref="form"
            v-model="valid">
          <v-container>
            <v-row>
              <v-col>
                <v-menu
                    ref="fromDateMenuOpened"
                    :close-on-content-click="false"
                    v-model="fromDateMenuOpened">
                  <template v-slot:activator="{ on }">
                    <v-text-field
                        slot="activator"
                        v-model="fromDateFormatted"
                        required
                        label="Von"
                        prepend-icon="event"
                        readonly
                        v-on="on"
                    ></v-text-field>
                  </template>
                  <v-date-picker v-model="dateFrom" @input="fromDateMenuOpened = false"></v-date-picker>
                </v-menu>
              </v-col>
            </v-row>
            <v-row>
              <v-col>
                <v-menu
                    ref="toDateMenuOpened"
                    :close-on-content-click="false"
                    v-model="toDateMenuOpened">
                  <template v-slot:activator="{ on }">
                    <v-text-field
                        slot="activator"
                        v-model="toDateFormatted"
                        required
                        label="Bis"
                        prepend-icon="event"
                        readonly
                        v-on="on"
                    ></v-text-field>
                  </template>
                  <v-date-picker v-model="dateTo" @input="toDateMenuOpened = false"></v-date-picker>
                </v-menu>
              </v-col>
            </v-row>
            <v-row>
              <v-col>
                <v-btn
                    v-if="!creatingExcelReport && !url"
                    color="primary"
                    :disabled="!valid"
                    @click="createExcelReport()">
                  <v-icon left>update</v-icon>
                  Erstellen
                </v-btn>
                <v-btn
                    v-else
                    :loading="creatingExcelReport"
                    :href="url"
                    download
                    color="primary"
                >
                  <v-icon left>save_alt</v-icon>
                  Download Excel
                </v-btn>
              </v-col>
            </v-row>
          </v-container>
        </v-form>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script lang="ts">
import Vue from 'vue'
import {mapGetters} from 'vuex'
import {formatDate, parseDate} from "../helpers/date-helpers"

export default Vue.extend({
  name: "TrainingAccountingExportDialog",
  props: {
    'visible': Boolean,
  },
  data: function () {
    return {
      valid: true,
      dateFrom: new Date(new Date().setMonth(new Date().getMonth() - 6)).toISOString().substr(0, 10) as Date,
      dateTo: new Date().toISOString().substr(0, 10) as Date,
      fromDateMenuOpened: false,
      toDateMenuOpened: false,
      url: null,
      creatingExcelReport: false,
      serverUrl: process.env.VUE_APP_IMAGE_FOLDER_URL,
    }
  },
  computed: {
    ...mapGetters({loggedInUser: 'loggedInUser'}),
    fromDateFormatted(): String {
      return this.formatDate(this.dateFrom)
    },
    toDateFormatted(): String {
      return this.formatDate(this.dateTo)
    },
    show: {
      get() {
        return this.visible;
      },
      set(value) {
        if (!value) {
          this.reset();
          this.$emit('close')
        }
      }
    },
  },
  methods: {
    reset(): void {
      this.url = null;
    },
    async createExcelReport(): void {
      this.creatingExcelReport = true;
      try {
        const {data} = await this.$http.post('/trainingevaluation/exportaccountingtimes',
            {
              userId: this.loggedInUser.id,
              from: this.moment(this.dateFrom, 'YYYY-MM-DD').format(),
              to: this.moment(this.dateTo, 'YYYY-MM-DD').format(),
            });
        if (data.status == 'ok') {
          this.url = this.serverUrl + "/" + data.fileName;
        }
      } catch (error) {
        console.error(error);
      } finally {
        this.creatingExcelReport = false;
      }

    },
    formatDate,
    parseDate,
  }
});
</script>

<style scoped>

</style>

<template>
  <v-container>
    <v-row no-gutters>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Informationen</v-toolbar-title>
          </v-toolbar>
          <v-divider></v-divider>
          <v-card-text flat>
            <v-card class="mt-1">
              <v-card-title>Release Notes</v-card-title>
              <v-card-text>
                <v-expansion-panels
                    flat
                    focusable
                    accordion>
                  <v-expansion-panel>
                    <v-expansion-panel-header>Update 12.10.2021</v-expansion-panel-header>
                    <v-expansion-panel-content>
                      <h4>Features</h4>
                      <ul>
                        <li>Trainings die durch Trainingsserien erstellt wurden und danach gelöscht wurden werden nicht wieder neu erstellt</li>
                      </ul>
                      <h4>Bug Fixes</h4>
                      <ul>
                        <li>Hochgeladene Handybilder werden nicht mehr verdreht</li>
                        <li>Trainingsserien erstellen nun die Trainings richtig auch wenn mehrere Tage ausgewählt sind</li>
                      </ul>
                    </v-expansion-panel-content>
                  </v-expansion-panel>
                  <v-expansion-panel>
                    <v-expansion-panel-header>Update 17.09.2021</v-expansion-panel-header>
                    <v-expansion-panel-content>
                      <h4>Features</h4>
                      <ul>
                        <li>Registrierung nur noch für Trainer</li>
                        <li>Möglichkeit längere Abwesenheiten einzutragen unter "Abwesenheit eintragen"</li>
                        <li>Bei Training Nachbereiten kann man nun auch Sportler nachträglich hinzufügen, die nicht in der Liste "Teilnehmer" oder "Absagen" sind</li>
                        <li>Automatiches auswählen der Sparte für Trainer auf der Startseite/Trainingsanmeldung</li>
                        <li>In der Trainings- und Trainingsserien-Tabelle werden nun auch die Gruppen angezeigt</li>
                        <li>Editier und Lösch Icons in den Tabellen sind kompakter</li>
                      </ul>
                      <h4>Bug Fixes</h4>
                      <ul>
                        <li>Nach dem editieren von Trainings wird nun nicht mehr unbeabsichtigt "Automatisches anmelden"
                          abgewählt
                        </li>
                      </ul>
                    </v-expansion-panel-content>
                  </v-expansion-panel>
                </v-expansion-panels>
              </v-card-text>
            </v-card>
            <v-card class="mt-1">
              <v-card-text>
                <v-expansion-panels
                    flat
                    v-model="openedPanel"
                    focusable
                    accordion>
                  <v-expansion-panel
                      v-for="(item, index) in faqs"
                      :key="index">
                    <v-expansion-panel-header>{{ item.headline }}</v-expansion-panel-header>
                    <v-expansion-panel-content v-html="item.content" class="ma-2"></v-expansion-panel-content>
                  </v-expansion-panel>
                </v-expansion-panels>
              </v-card-text>
            </v-card>

          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script lang="ts">

import Vue from "vue";

export default Vue.extend({
  name: "InfoPage",
  data: function () {
    return {
      openedPanel: 0,
      faqs: [],
      filePaths: [],
      serverUrl: process.env.VUE_APP_IMAGE_FOLDER_URL,
    }
  },
  created(): void {
    this.fetchFaqs();
  },
  methods: {
    async fetchFaqs() {
      let res = await this.$http.get('/faq/');
      this.faqs = res.data;
      res = await this.$http.get('/faq/files');
      this.filePaths = res.data;

    },
  }
})
</script>

<style scoped lang="scss">
.v-expansion-panel-content {
  display: block;
}
</style>

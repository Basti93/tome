<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Abwesenheit</v-toolbar-title>
          </v-toolbar>
          <v-divider></v-divider>
          <v-card-text flat class="pa-2 pa-md-4">
            <v-card>
              <v-card-subtitle>In deiner Abwesenheit wirst du nicht automatisch an Trainings angemeldet. Falls du deine Abwesenheit ändern oder vorzeitig beenden möchtest, melde dich bei deinem Trainer.</v-card-subtitle>
              <v-card-text class="pa-0 pa-md-4">
                <v-container>
                  <v-row no-gutters>
                    <v-col>
                      <v-form ref="form" v-model="valid">
                        <v-container>
                          <v-row>
                            <v-col
                                cols="12"
                                md="4"
                            >
                              <v-autocomplete
                                  :items="users"
                                  v-model="userId"
                                  item-value="id"
                                  clearable
                                  required
                                  :rules="requiredRule"
                                  label="Sportler auswählen"
                              >

                                <template v-slot:selection="data">
                                  {{data.item.getFullName()}}
                                </template>
                                <template v-slot:item="data">
                                  {{data.item.getFullName()}}
                                </template>

                              </v-autocomplete>
                            </v-col>
                            <v-col
                                cols="12"
                                md="4"
                            >
                              <v-menu
                                  v-model="menuStart"
                                  :close-on-content-click="false"
                                  :nudge-right="40"
                                  transition="scale-transition"
                                  offset-y
                                  min-width="auto"
                              >
                                <template v-slot:activator="{ on, attrs }">
                                  <v-text-field
                                      v-model="absenceStartFormatted"
                                      label="Beginn der Abwesenheit"
                                      required
                                      readonly
                                      :rules="requiredRule"
                                      v-bind="attrs"
                                      v-on="on"
                                  ></v-text-field>
                                </template>
                                <v-date-picker
                                    v-model="absenceStart"
                                    :min="new Date().toISOString().substr(0, 10)"
                                    @input="menuStart = false"
                                ></v-date-picker>
                              </v-menu>
                            </v-col>
                            <v-col
                                cols="12"
                                md="4"
                            >
                              <v-menu
                                  v-model="menuEnd"
                                  :close-on-content-click="false"
                                  :nudge-right="40"
                                  transition="scale-transition"
                                  offset-y
                                  min-width="auto"
                              >
                                <template v-slot:activator="{ on, attrs }">
                                  <v-text-field
                                      v-model="absenceEndFormatted"
                                      label="Ende der Abwesenheit"
                                      readonly
                                      :rules="requiredRule"
                                      required
                                      v-bind="attrs"
                                      v-on="on"
                                  ></v-text-field>
                                </template>
                                <v-date-picker
                                    v-model="absenceEnd"
                                    :min="new Date().toISOString().substr(0, 10)"
                                    @input="menuEnd = false"
                                ></v-date-picker>
                              </v-menu>
                            </v-col>
                            <v-col
                                cols="12"
                                md="6"
                            >
                                <v-textarea
                                    v-model="absenceReason"
                                    label="Grund der Abwesenheit (Nur für Trainer sichtbar)"
                                    :rules="requiredRule"
                                    outlined
                                    required
                                ></v-textarea>
                            </v-col>
                            <v-col cols="12">
                              <v-btn
                                  :disabled="!valid"
                                  color="success"
                                  class="mr-4"
                                  v-on:click="sendAbsence"
                              >
                                Abschicken
                              </v-btn>
                            </v-col>
                          </v-row>
                        </v-container>
                      </v-form>
                    </v-col>
                  </v-row>
                </v-container>
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
import {mapGetters} from "vuex";
import {formatDate} from "@/helpers/date-helpers"

export default Vue.extend({
  name: "AbsenceForm",
  components: {},
  data: () => ({
    absenceStart: new Date().toISOString().substr(0, 10),
    absenceEnd: null,
    absenceReason: null,
    menuStart: false,
    menuEnd: false,
    valid: true,
    userId: null,
    users: [],
    requiredRule: [
      v => !!v || 'Bitte ausfüllen'
      ],
  }),
  created() {
    this.users = this.getAllSimpleUsersWithGroup();
    if (this.cookieUser) {
      this.userId = this.cookieUser.id;
    }
  },
  computed: {
    ...mapGetters({cookieUser: 'cookieUser'}),
    ...mapGetters('masterData', {
      getAllSimpleUsersWithGroup: 'getAllSimpleUsersWithGroup',
    }),
    absenceStartFormatted(): String {
      return this.formatDate(this.absenceStart)
    },
    absenceEndFormatted(): String {
      return this.formatDate(this.absenceEnd)
    },
  },
  methods: {
    async sendAbsence () {
      if (this.$refs.form.validate()) {
        let postData = {
          absenceStart: this.moment(this.absenceStart, 'YYYY-MM-DDTHH:mm').format(),
          absenceEnd: this.moment(this.absenceEnd, 'YYYY-MM-DDTHH:mm').format(),
          absenceReason: this.absenceReason
        }
        const {data} = await this.$http.post('simpleuser/' + this.userId + '/storeAbsence', postData);
        if (data.status === 'ok') {
          this.$emit("showSnackbar", "Abwesenheit erfolgreich eingetragen", "success");
        } else if (data.status === 'absence_exists') {
          this.$emit("showSnackbar", "Eine Abwesenheit ist für diesen Benutzer bereits eingetragen. Bitte kontaktiere einen Trainer um sie zu ändern.", "error");
        }
      }
    },
    formatDate
  },

})

</script>

<style scoped lang="scss">

</style>

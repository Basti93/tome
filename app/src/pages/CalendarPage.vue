<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Kalender</v-toolbar-title>
          </v-toolbar>
          <v-divider></v-divider>
          <v-card-text flat class="pa-2 pa-md-4">
            <v-card>
              <v-card-text class="pa-0 pa-md-4">
                <v-container>
                  <v-row no-gutters>
                    <v-col>
                      <TrainingCalendar
                          :privateMode="userLoggedIn">
                      </TrainingCalendar>
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
import {mapGetters} from 'vuex'
import TrainingCalendar from "../components/TrainingCalendar.vue";

export default Vue.extend({
  name: "CalendarPage",
  components: {TrainingCalendar},

  computed: {
    ...mapGetters({loggedInUser: 'loggedInUser'}),
    userLoggedIn() {
      if (this.loggedInUser) {
        return true;
      }
      return false;
    }
  }
})

</script>

<style scoped lang="scss">

</style>

<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Statistiken</v-toolbar-title>
          </v-toolbar>
          <v-card-text class="pa-0 pa-md-4">
              <v-row no-gutters>
                <v-col>
                  <v-card class="ma-1">
                    <v-toolbar flat>
                      <v-toolbar-title>Teilnahmen am Training pro Jahr</v-toolbar-title>
                      <v-spacer></v-spacer>
                    </v-toolbar>
                    <v-card-text class="pa-0 pa-lg-4">
                      <TrainingParticipationBarChart></TrainingParticipationBarChart>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
              <v-row no-gutters>
                <v-col>
                  <v-card v-if="loggedInUser.isTrainer" class="ma-1">
                    <v-toolbar flat>
                      <v-toolbar-title>Abgehaltene Trainingsstunden (45 min) pro Monat</v-toolbar-title>
                      <v-spacer></v-spacer>
                    </v-toolbar>
                    <v-card-text class="pa-0 pa-lg-4">
                      <TrainerTimeline></TrainerTimeline>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import {mapGetters} from 'vuex'
import TrainingParticipationBarChart from "@/components/TrainingParticipationBarChart.vue";
import TrainerTimeline from "@/components/TrainerTimeline.vue";

export default {
  name: "StatisticsPage",
  components: {TrainerTimeline, TrainingParticipationBarChart},
  computed: {
    ...mapGetters({loggedInUser: 'loggedInUser'}),
  }
}
</script>

<style scoped>

</style>

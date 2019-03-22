<template>
  <v-container fluid ma-0 pa-0 fill-height>
    <v-layout row wrap>
      <v-flex xs12 md8 offset-md1 top>
        <v-toolbar card prominent>
          <v-toolbar-title>Teilnahmen am Training pro Jahr</v-toolbar-title>
          <v-spacer></v-spacer>
        </v-toolbar>
        <TrainingParticipationBarChart></TrainingParticipationBarChart>

      </v-flex>
      <v-flex xs12 md8 offset-md1 top pt-2 v-if="loggedInUser.isTrainer">
        <v-card>
          <v-toolbar card prominent>
            <v-toolbar-title>Abgehaltene Trainings pro Monat</v-toolbar-title>
            <v-spacer></v-spacer>
          </v-toolbar>
          <v-card-text>
            <TrainerTimeline></TrainerTimeline>
          </v-card-text>
        </v-card>
      </v-flex>
    </v-layout>


  </v-container>
</template>

<script>
  import {mapGetters} from 'vuex'
  import TrainingParticipationBarChart from "@/components/TrainingParticipationBarChart.vue";
  import TrainerTimeline from "@/components/TrainerTimeline.vue";

  export default {
    name: "Statistics",
    components: {TrainerTimeline, TrainingParticipationBarChart},
    computed: {
      ...mapGetters({loggedInUser: 'loggedInUser'}),
    }
  }
</script>

<style scoped>

</style>

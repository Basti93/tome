<template>
  <v-sheet>
    <v-toolbar flat class="mb-2">
      <v-btn fab text small @click="prev">
        <v-icon small>chevron_left</v-icon>
      </v-btn>
      <v-toolbar-title>{{ title }}</v-toolbar-title>
      <v-btn fab text small @click="next">
        <v-icon small>chevron_right</v-icon>
      </v-btn>
      <v-spacer></v-spacer>


      <template #extension>
        <v-spacer></v-spacer>
        <v-toolbar-items>
          <v-menu>
            <template v-slot:activator="{ on }">
              <v-btn
                  small
                  color="primary"
                  outlined
                  v-on="on"
              >
                <span>{{ typeToLabel[type] }}</span>
                <v-icon right>arrow_drop_down</v-icon>
              </v-btn>
            </template>
            <v-list>
              <v-list-item @click="type = 'week'">
                <v-list-item-title>Woche</v-list-item-title>
              </v-list-item>
              <v-list-item @click="type = 'month'">
                <v-list-item-title>Monat</v-list-item-title>
              </v-list-item>
              <v-list-item @click="type = 'category'">
                <v-list-item-title>Kategorie</v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>
          <v-btn text
                 color="primary"
                 small
                 class="mr-4 ml-4"
                 @click="setToday">
            <v-icon left>adjust</v-icon>
            Heute
          </v-btn>
        </v-toolbar-items>
      </template>
    </v-toolbar>
    <v-calendar
        ref="calendar"
        :events="events"
        v-model="focus"
        :type="type"
        first-interval="8"
        interval-count="16"
        :interval-format="formatDayTime"
        :weekdays="weekday"
        category-show-all
        :categories="categories"
        category-text="name"
        @click:date="viewDay"
        @click:more="viewDay"
        @click:event="showEvent"
        @change="updateRange"
        :event-color="getEventColor"
        color="primary">
    </v-calendar>
    <v-menu
        v-model="selectedOpen"
        :close-on-content-click="false"
        :activator="selectedElement"
        offset-x
    >
      <v-card
          min-width="350px"
          flat
      >
        <v-toolbar
            :color="selectedEvent.color"
        >
          <v-btn icon>
            <v-icon>event</v-icon>
          </v-btn>
          <v-toolbar-title v-if="selectedEvent.type == 'training'">Training {{ selectedEvent.branchName }}
          </v-toolbar-title>
          <v-toolbar-title v-else-if="selectedEvent.type == 'planed-training'">Training {{ selectedEvent.branchName }}
          </v-toolbar-title>
          <v-toolbar-title v-else-if="selectedEvent.type == 'birthday'">Geburtstag {{ selectedEvent.name }}
          </v-toolbar-title>
          <v-spacer></v-spacer>
          <v-btn @click="selectedOpen = false"
                 text>
            <v-icon>cancel</v-icon>
          </v-btn>
        </v-toolbar>
        <v-card-text v-if="selectedEvent.type == 'training'">
          <v-label v-if="privateMode && selectedEvent.evaluated">
            <v-icon left color="primary">check</v-icon>
            Abgeschlossen
          </v-label>
          <v-label v-if="privateMode && !selectedEvent.evaluated">
            <v-icon left color="error">cancel</v-icon>
            Nicht Abgeschlossen
          </v-label>
          <br/>
          <span class="label text-small">Von</span>
          <v-chip small outlined class="ma-1">
            <v-icon left color="primary">query_builder</v-icon>
            {{ moment(selectedEvent.start).format('HH:mm') }}
          </v-chip>
          <span class="label text-small">bis</span>
          <v-chip small outlined class="ml-1">
            <v-icon left color="primary">query_builder</v-icon>
            {{ moment(selectedEvent.end).format('HH:mm') }}
          </v-chip>
          <br />
          <v-chip v-if="selectedEvent.location" outlined small class="ma-1">
            <v-icon left color="primary">room</v-icon>
            {{ selectedEvent.location }}
          </v-chip>
          <br />
          <h4 v-if="selectedEvent.participantCount">{{ selectedEvent.participantCount }} Teilnehmer</h4>
          <h4>Gruppen</h4>
          <v-chip
              outlined
              small
              v-for="(groupId) in selectedEvent.groupIds"
              :key="'groupId' + groupId"
              class="ma-1">
            <v-icon left color="primary">group</v-icon>
            {{ getGroupById(groupId).name }}
          </v-chip>
          <h4>Trainer</h4>
          <v-chip
              outlined
              small
              v-for="(trainerId) in selectedEvent.trainerIds"
              :key="'trainerId' + trainerId"
              class="ma-1">
            <v-icon left color="primary">person</v-icon>
            {{ getFullName(trainerId) }}
          </v-chip>
        </v-card-text>
        <v-card-text v-else-if="selectedEvent.type == 'planed-training'">
          <v-label>
            <v-icon left color="info">info</v-icon>
            Geplantes Training, noch nicht bestätigt
          </v-label>
          <br/>
          <h4>Gruppen</h4>
          <v-chip
              outlined
              small
              v-for="(groupId) in selectedEvent.groupIds"
              :key="'groupId_2_' + groupId"
              class="ma-1">
            <v-icon left color="primary">group</v-icon>
            {{ getGroupById(groupId).name }}
          </v-chip>
          <h4>Trainer</h4>
          <v-chip
              outlined
              small
              v-for="(trainerId) in selectedEvent.trainerIds"
              :key="'trainerId_2_' + trainerId"
              class="ma-1">
            <v-icon left color="primary">person</v-icon>
            {{ getFullName(trainerId) }}
          </v-chip>
        </v-card-text>
        <v-card-text v-else-if="selectedEvent.type == 'birthday'">
          Happy Birthday
        </v-card-text>
      </v-card>
    </v-menu>
  </v-sheet>
</template>

<script lang="ts">
import Vue from "vue";
import {mapGetters, mapState} from 'vuex';
import Training from "@/models/Training";

export default Vue.extend({
  name: "TrainingCalendar",
  props: {
    privateMode: Boolean,
  },
  data: () => ({
    events: [],
    weekday: [1, 2, 3, 4, 5, 6, 0],
    categories: [],
    trainings: [] as Training[],
    start: null as Date,
    end: null as Date,
    focus: null as String,
    selectedEvent: {},
    selectedElement: null,
    selectedOpen: false,
    type: 'month' as String,
    typeToLabel: {
      month: 'Monat',
      week: 'Woche',
      day: 'Tag',
      category: 'Sparten',
    },
  }),
  mounted() {
    this.focus = this.moment().format('YYYY-MM-DD')
  },
  computed: {
    ...mapGetters('masterData', {
      getBranchByGroupId: 'getBranchByGroupId',
      getGroupById: 'getGroupById',
      getSimpleTrainerById: 'getSimpleTrainerById',
      getBranchById: 'getBranchById',
      getLocationNameById: 'getLocationNameById',
    }),
    ...mapState('masterData', {
      branches: 'branches',
    }),
    title() {
      const {start, end} = this
      if (!start || !end) {
        return ''
      }

      const startMonth = this.moment(start.date).format('MMMM');
      const startYear = start.year
      const startDay = start.day

      switch (this.type) {
        case 'month':
          return `${startMonth} ${startYear}`
        case 'week':
        case 'day':
        case 'category':
          return `${startMonth} ${startDay} ${startYear}`
      }
      return ''
    },
  },
  methods: {
    async loadData() {
      this.trainings = [];
      this.events = [];
      this.categories = [];
      this.categories = this.branches;
      this.loadTrainingEvents();
      this.loadPlannedTrainingEvents();
      if (this.privateMode) {
        this.loadBirthdayEvents();
      }
    },
    async loadTrainingEvents() {
      let url = '/training/'
      if (this.privateMode) {
        url += 'calendar'
      } else {
        url += 'simplecalendar'
      }
      const {data} = await this.$http.get(url + '?start=' + this.start.date + '&end=' + this.end.date);
      for (let trainingData of data.data) {
        let branch = null;
        if (trainingData.groupIds && trainingData.groupIds.length > 0) {
          branch = this.getBranchByGroupId(trainingData.groupIds[0]);
        }
        if (branch) {
          let training = new Training(trainingData.id,
              this.moment(trainingData.start),
              this.moment(trainingData.end),
              trainingData.locationId,
              trainingData.groupIds,
              trainingData.contentIds,
              trainingData.trainerIds,
              trainingData.participants,
              trainingData.comment,
              trainingData.prepared === 1 ? true : false,
              trainingData.evaluated === 1 ? true : false,
              trainingData.automaticAttend === 1 ? true : false
          );
          this.trainings.push(training)

          this.events.push({
            name: branch.shortName,
            branchName: branch.name,
            start: training.start.format('YYYY-MM-DD HH:mm'),
            end: training.end.format('YYYY-MM-DD HH:mm'),
            groupIds: training.groupIds,
            trainerIds: training.trainerIds,
            location: this.getLocationNameById(training.locationId),
            participantCount: training.participants ? training.participants.filter(p => p.attend).length : null,
            type: 'training',
            color: branch.colorHex,
            evaluated: training.evaluated,
            category: branch.name
          })
        }
      }
    },
    async loadPlannedTrainingEvents() {
      let url = '/training/simplecalendar/planned'
      const {data} = await this.$http.get(url + '?start=' + this.start.date + '&end=' + this.end.date);
      for (let trainingData of data.data) {
        let branch = null;
        if (trainingData.groupIds && trainingData.groupIds.length > 0) {
          branch = this.getBranchByGroupId(trainingData.groupIds[0]);
        }
        if (branch) {
          let training = new Training(trainingData.id,
              this.moment(trainingData.start),
              this.moment(trainingData.end),
              trainingData.locationId,
              trainingData.groupIds,
              trainingData.contentIds,
              trainingData.trainerIds,
              [],
              trainingData.comment,
              false,
              false,
          );
          this.trainings.push(training)

          this.events.push({
            name: branch.shortName,
            branchName: branch.name,
            start: training.start.format('YYYY-MM-DD HH:mm'),
            end: training.end.format('YYYY-MM-DD HH:mm'),
            groupIds: training.groupIds,
            trainerIds: training.trainerIds,
            type: 'planed-training',
            color: branch.colorHex,
            category: branch.name
          })
        }
      }
    },
    async loadBirthdayEvents() {
      const {data} = await this.$http.get('/user/birthdays?start=' + this.start.date + '&end=' + this.end.date);
      for (let birthdayData of data) {
        this.events.push({
          name: birthdayData.firstName + " " + birthdayData.familyName,
          start: this.start.date.slice(0, 4) + "-" + this.moment(birthdayData.birthdate).format('MM-DD'),
          color: '#F57F17',
          type: 'birthday',
        })
      }
    },
    showEvent({nativeEvent, event}) {
      const open = () => {
        this.selectedEvent = event
        this.selectedElement = nativeEvent.target
        setTimeout(() => {
          this.selectedOpen = true
        }, 10)
      }

      if (this.selectedOpen) {
        this.selectedOpen = false
        setTimeout(open, 10)
      } else {
        open()
      }

      nativeEvent.stopPropagation()
    },
    viewDay({date}): void {
      this.focus = date
      this.type = 'day'
    },
    getEventColor(event) {
      return event.color
    },
    setToday(): void {
      this.focus = new Date();
    },
    prev(): void {
      this.$refs.calendar.prev()
    },
    next(): void {
      this.$refs.calendar.next()
    },
    updateRange({start, end}) {
      this.start = start
      this.end = end
      this.loadData();
    },
    formatDayTime(timeObj) {
      return timeObj.time;
    },
    getFullName(trainerId) {
      let trainer = this.getSimpleTrainerById(trainerId);
      return trainer.firstName + " " + trainer.familyName;
    }
  },
})
</script>

<style scoped>
.my-event {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  border-radius: 2px;
  background-color: #1867c0;
  color: #ffffff;
  border: 1px solid #1867c0;
  font-size: 12px;
  padding: 3px;
  cursor: pointer;
  margin-bottom: 1px;
  left: 4px;
  margin-right: 8px;
  position: relative;
}

.my-event.with-time {
  position: absolute;
  right: 4px;
  margin-right: 0px;
}
</style>

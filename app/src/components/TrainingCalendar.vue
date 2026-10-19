<template>
  <v-sheet>
    <v-toolbar flat class="mb-2">
      <v-btn icon variant="text" size="small" @click="prev">
        <v-icon size="small">mdi-chevron-left</v-icon>
      </v-btn>
      <v-toolbar-title>{{ title }}</v-toolbar-title>
      <v-btn icon variant="text" size="small" @click="next">
        <v-icon size="small">mdi-chevron-right</v-icon>
      </v-btn>
      <v-spacer></v-spacer>


      <template #extension>
        <v-spacer></v-spacer>
        <v-toolbar-items>
          <v-menu>
            <template v-slot:activator="{ props }">
              <v-btn
                  size="small"
                  color="primary"
                  variant="outlined"
                  v-bind="props"
              >
                <span>{{ typeToLabel[type] }}</span>
                <v-icon end>mdi-menu-down</v-icon>
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
          <v-btn
                 color="primary"
                 size="small"
                 class="mr-4 ml-4"
                 @click="setToday" variant="text">
            <v-icon start>mdi-palette</v-icon>
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
    >
      <v-card
          min-width="350px"
          flat
      >
        <v-toolbar
            :color="selectedEvent.color"
        >
          <v-btn icon>
            <v-icon>mdi-calendar-month</v-icon>
          </v-btn>
          <v-toolbar-title v-if="selectedEvent.type == 'training'">Training {{ selectedEvent.branchName }}
          </v-toolbar-title>
          <v-toolbar-title v-else-if="selectedEvent.type == 'planed-training'">Training {{ selectedEvent.branchName }}
          </v-toolbar-title>
          <v-toolbar-title v-else-if="selectedEvent.type == 'birthday'">Geburtstag {{ selectedEvent.name }}
          </v-toolbar-title>
          <v-spacer></v-spacer>
          <v-btn @click="selectedOpen = false"
                 variant="text">
            <v-icon>mdi-cancel</v-icon>
          </v-btn>
        </v-toolbar>
        <v-card-text v-if="selectedEvent.type == 'training'">
          <v-label v-if="privateMode && selectedEvent.evaluated">
            <v-icon start color="primary">mdi-check</v-icon>
            Abgeschlossen
          </v-label>
          <v-label v-if="privateMode && !selectedEvent.evaluated">
            <v-icon start color="error">mdi-cancel</v-icon>
            Nicht Abgeschlossen
          </v-label>
          <br/>
          <span class="label text-small">Von</span>
          <v-chip size="small" class="ma-1" variant="outlined">
            <v-icon start color="primary">mdi-clock-outline</v-icon>
            {{ dayjs(selectedEvent.start).format('HH:mm') }}
          </v-chip>
          <span class="label text-small">bis</span>
          <v-chip size="small" class="ml-1" variant="outlined">
            <v-icon start color="primary">mdi-clock-outline</v-icon>
            {{ dayjs(selectedEvent.end).format('HH:mm') }}
          </v-chip>
          <br />
          <v-chip v-if="selectedEvent.location" size="small" class="ma-1" variant="outlined">
            <v-icon start color="primary">mdi-map-marker</v-icon>
            {{ selectedEvent.location }}
          </v-chip>
          <br />
          <h4 v-if="selectedEvent.participantCount">{{ selectedEvent.participantCount }} Teilnehmer</h4>
          <h4>Gruppen</h4>
          <v-chip
              size="small"
              v-for="(groupId) in selectedEvent.groupIds"
              :key="'groupId' + groupId"
              class="ma-1" variant="outlined">
            <v-icon start color="primary">mdi-account</v-icon>
            {{ getGroupById(groupId).name }}
          </v-chip>
          <h4>Trainer</h4>
          <v-chip
              size="small"
              v-for="(trainerId) in selectedEvent.trainerIds"
              :key="'trainerId' + trainerId"
              class="ma-1" variant="outlined">
            <v-icon start color="primary">mdi-account</v-icon>
            {{ getFullName(trainerId) }}
          </v-chip>
        </v-card-text>
        <v-card-text v-else-if="selectedEvent.type == 'planed-training'">
          <v-label>
            <v-icon start color="info">mdi-information</v-icon>
            Geplantes Training, noch nicht bestätigt
          </v-label>
          <br/>
          <h4>Gruppen</h4>
          <v-chip
              size="small"
              v-for="(groupId) in selectedEvent.groupIds"
              :key="'groupId_2_' + groupId"
              class="ma-1" variant="outlined">
            <v-icon start color="primary">mdi-account</v-icon>
            {{ getGroupById(groupId).name }}
          </v-chip>
          <h4>Trainer</h4>
          <v-chip
              size="small"
              v-for="(trainerId) in selectedEvent.trainerIds"
              :key="'trainerId_2_' + trainerId"
              class="ma-1" variant="outlined">
            <v-icon start color="primary">mdi-account</v-icon>
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
import Training from "@/models/Training";
import { useMasterDataStore } from '@/store/masterData';
import httpClient from '@/http/api';
import dayjs from '@/helpers/dayjs'

export default {
  name: "TrainingCalendar",
  props: {
    privateMode: Boolean,
  },
  data: () => ({
    events: [] as any[],
    weekday: [1, 2, 3, 4, 5, 6, 0],
    categories: [] as any[],
    trainings: [] as Training[],
    start: null as any,
    end: null as any,
    focus: null as any,
    selectedEvent: {} as any,
    selectedElement: null as any,
    selectedOpen: false,
    type: 'month' as 'month' | 'week' | 'day' | 'category',
    typeToLabel: {
      month: 'Monat',
      week: 'Woche',
      day: 'Tag',
      category: 'Sparten',
    },
  }),
  mounted() {
    this.focus = dayjs().format('YYYY-MM-DD');
  },
  computed: {
    branches() {
      return useMasterDataStore().branches;
    },
    title() {
      const {start, end} = this;
      if (!start || !end) {
        return '';
      }

      const startMonth = dayjs(start.date).format('MMMM');
      const startYear = start.year;
      const startDay = start.day;

      switch (this.type) {
        case 'month':
          return `${startMonth} ${startYear}`;
        case 'week':
        case 'day':
        case 'category':
          return `${startMonth} ${startDay} ${startYear}`;
      }
      return '';
    },
  },
  methods: {
    dayjs,
    getGroupById(groupId: number) {
      return useMasterDataStore().getGroupById(groupId) ?? { name: '' };
    },
    getBranchByGroupId(groupId: number) {
      return useMasterDataStore().getBranchByGroupId(groupId);
    },
    getSimpleTrainerById(trainerId: number) {
      return useMasterDataStore().getSimpleTrainerById(trainerId);
    },
    getLocationNameById(locationId: number) {
      return useMasterDataStore().getLocationNameById(locationId);
    },
    async loadData() {
      this.trainings = [];
      this.events = [];
      this.categories = [];
      this.categories = this.branches;
      await this.loadTrainingEvents();
      await this.loadPlannedTrainingEvents();
      if (this.privateMode) {
        await this.loadBirthdayEvents();
      }
    },
    async loadTrainingEvents() {
      let url = '/training/';
      if (this.privateMode) {
        url += 'calendar';
      } else {
        url += 'simplecalendar';
      }
      const {data} = await httpClient.get(url + '?start=' + this.start.date + '&end=' + this.end.date);
      for (const trainingData of data.data) {
        let branch = null;
        if (trainingData.groupIds && trainingData.groupIds.length > 0) {
          branch = this.getBranchByGroupId(trainingData.groupIds[0]);
        }
        if (branch) {
          const training = new Training(trainingData.id,
              dayjs(trainingData.start),
              dayjs(trainingData.end),
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
          this.trainings.push(training);

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
          });
        }
      }
    },
    async loadPlannedTrainingEvents() {
      const url = '/training/simplecalendar/planned';
      const {data} = await httpClient.get(url + '?start=' + this.start.date + '&end=' + this.end.date);
      for (const trainingData of data.data) {
        let branch = null;
        if (trainingData.groupIds && trainingData.groupIds.length > 0) {
          branch = this.getBranchByGroupId(trainingData.groupIds[0]);
        }
        if (branch) {
          const training = new Training(trainingData.id,
              dayjs(trainingData.start),
              dayjs(trainingData.end),
              trainingData.locationId,
              trainingData.groupIds,
              trainingData.contentIds,
              trainingData.trainerIds,
              [],
              trainingData.comment,
              false,
              false,
              false,
          );
          this.trainings.push(training);

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
          });
        }
      }
    },
    async loadBirthdayEvents() {
      const {data} = await httpClient.get('/user/birthdays?start=' + this.start.date + '&end=' + this.end.date);
      for (const birthdayData of data) {
        this.events.push({
          name: birthdayData.firstName + " " + birthdayData.familyName,
          start: this.start.date.slice(0, 4) + "-" + dayjs(birthdayData.birthdate).format('MM-DD'),
          color: '#F57F17',
          type: 'birthday',
        });
      }
    },
    // Vuetify 4 calls the click handlers with (nativeEvent, scope), not with one merged object
    showEvent(nativeEvent: Event, {event}: {event: any}) {
      const open = () => {
        this.selectedEvent = event;
        this.selectedElement = nativeEvent.target;
        setTimeout(() => {
          this.selectedOpen = true;
        }, 10);
      };

      if (this.selectedOpen) {
        this.selectedOpen = false;
        setTimeout(open, 10);
      } else {
        open();
      }

      nativeEvent.stopPropagation();
    },
    viewDay(_nativeEvent: Event, {date}: {date: string}): void {
      this.focus = date;
      this.type = 'day';
    },
    getEventColor(event: any) {
      return event.color;
    },
    setToday(): void {
      this.focus = new Date();
    },
    prev(): void {
      this.$refs.calendar.prev();
    },
    next(): void {
      this.$refs.calendar.next();
    },
    updateRange({start, end}: {start: any, end: any}) {
      this.start = start;
      this.end = end;
      this.loadData();
    },
    formatDayTime(timeObj: any) {
      return timeObj.time;
    },
    getFullName(trainerId: number) {
      const trainer = this.getSimpleTrainerById(trainerId);
      if (!trainer) return '';
      return trainer.firstName + " " + trainer.familyName;
    }
  },
};
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

<template>
  <v-container>
    <v-row>
      <v-col cols="12" md="6" v-if="!hideDate">
        <v-menu
            ref="dateMenuOpened"
            :close-on-content-click="false"
            v-model="dateMenuOpened"
        >
          <template v-slot:activator="{ on }">
            <v-text-field
                slot="activator"
                v-model="trainingDateFormatted"
                required
                label="Datum"
                prepend-icon="event"
                readonly
                v-on="on"
            ></v-text-field>
          </template>
          <v-date-picker v-model="trainingDate" @input="dateMenuOpened = false"></v-date-picker>
        </v-menu>
      </v-col>
      <v-col cols="6" md="3">
        <v-menu
            ref="startMenuOpened"
            v-model="startMenuOpened"
            :close-on-content-click="false">
          <template v-slot:activator="{ on }">
            <v-text-field
                slot="activator"
                v-model="startTime"
                label="Start"
                required
                prepend-icon="schedule"
                readonly
                lazy
                v-on="on"
            ></v-text-field>
          </template>
          <v-time-picker
              v-model="startTime"
              @click:minute="$refs.startMenuOpened.save(startTime)"
              format="24hr">
          </v-time-picker>
        </v-menu>
      </v-col>
      <v-col cols="6" md="3">
        <v-menu
            ref="endMenuOpened"
            :close-on-content-click="false"
            v-model="endMenuOpened">
          <template v-slot:activator="{ on }">
            <v-text-field
                slot="activator"
                v-model="endTime"
                required
                label="Ende"
                prepend-icon="schedule"
                readonly
                lazy
                v-on="on"
            ></v-text-field>
          </template>
          <v-time-picker
              v-model="endTime"
              format="24hr"
          ></v-time-picker>
        </v-menu>
      </v-col>
      <v-col cols="12" md="6">
        <v-select
            :items="locations"
            item-value="id"
            item-text="name"
            v-model="selectedLocationId"
            clearable
            required
            label="Ort"
            prepend-icon="add_location"
        ></v-select>
      </v-col>
      <v-col cols="12" md="6">
        <v-checkbox
            v-model.lazy="editedAutomaticAttend"
            label="Benutzer automatisch anmelden"
            prepend-icon="active"
        ></v-checkbox>
      </v-col>
      <v-col cols="12">
        <v-select
            v-model="selectedTrainerIds"
            :items="trainers"
            item-value="id"
            :item-text="fullName"
            attach
            clearable
            chips
            deletable-chips
            label="Trainer"
            prepend-icon="verified_user"
            multiple
        >
        </v-select>
      </v-col>
      <v-col cols="12">
        <v-select
            :items="groups"
            v-model="selectedGroupIds"
            item-value="id"
            :item-text="branchAndGroupName"
            label="Gruppen"
            prepend-icon="groups"
            multiple
            clearable
            chips
            deletable-chips>
          <template v-slot:prepend-item>
            <v-list-item
                ripple
                @click="toggleSelectAllGroups"
            >
              <v-list-item-action>
                <v-icon :color="selectedGroupIds.length > 0 ? 'indigo darken-4' : ''">
                  {{ allGroupsSelectedIcon }}
                </v-icon>
              </v-list-item-action>
              <v-list-item-content>
                <v-list-item-title>
                  Alle Selektieren
                </v-list-item-title>
              </v-list-item-content>
            </v-list-item>
            <v-divider class="mt-2"></v-divider>
          </template>
        </v-select>
      </v-col>
      <v-col cols="12" class="ml-2" v-if="!hideContents">
        <v-label>Trainingsinhalte</v-label>
        <TrainingContent
            :contentIds="branchContentIds"
            :initContentIds="selectedContentIds"
            selectable
            v-on:change="contentIdsChanged"
        >
        </TrainingContent>
      </v-col>
      <v-col cols="12">
        <v-textarea
            filled
            label="Kommentar"
            v-model.lazy="editedComment"
        ></v-textarea>
      </v-col>
    </v-row>
  </v-container>
</template>

<script lang="ts">
import Vue from "vue";
import TrainingContent from "./TrainingContent"
import {formatDate, parseDate} from "@/helpers/date-helpers"
import {mapGetters, mapState} from 'vuex'
import Group from "../models/Group";

export default Vue.extend({
  name: "EditTrainingBase",
  components: {TrainingContent},
  props: {
    hideDate: Boolean,
    hideContents: Boolean,
    id: Number,
    date: String,
    start: String,
    end: String,
    trainerIds: Array,
    groupIds: Array,
    contentIds: Array,
    locationId: Number,
    branchId: Number,
    trainers: Array,
    groups: Array,
    comment: String,
    automaticAttend: Boolean,
  },
  data: function () {
    return {
      trainingDate: new Date().toISOString().substr(0, 10) as Date,
      dataId: null as Number,
      endTime: '12:00' as String,
      startTime: '09:00' as String,
      selectedLocationId: null as Number,
      selectedGroupIds: [] as Array<Number>,
      selectedContentIds: [] as Array<Number>,
      selectedTrainerIds: [] as Array<Number>,
      editedComment: null as String,
      dateMenuOpened: false,
      startMenuOpened: false,
      endMenuOpened: false,
      editedAutomaticAttend: true,
    }
  },
  computed: {
    ...mapState('masterData', {
      locations: 'locations',
    }),
    ...mapGetters('masterData', {getContentIdsByBranchId: 'getContentIdsByBranchId', getBranchById: 'getBranchById', getGroupById: 'getGroupById'}),
    trainingDateFormatted(): String {
      return this.formatDate(this.trainingDate)
    },
    branchContentIds(): Array<Number> {
      return this.getContentIdsByBranchId(this.branchId);
    },
    allGroupsSelected() {
      return this.selectedGroupIds.length === this.groups.length
    },
    someGroupsSelected () {
      return this.selectedGroupIds.length > 0 && !this.allGroupsSelected
    },
    allGroupsSelectedIcon() {
      if (this.allGroupsSelected) return 'check_box'
      if (this.someGroupsSelected) return 'indeterminate_check_box'
      return 'check_box_outline_blank'
    },
  },
  methods: {
    contentIdsChanged(contentIds: Array<Number>): void {
      this.selectedContentIds = contentIds;
    },
    fireChangeEvent() {
      this.$emit("change", {
        id: this.dataId,
        date: this.trainingDate,
        start: this.startTime,
        end: this.endTime,
        locationId: this.selectedLocationId,
        trainerIds: this.selectedTrainerIds,
        groupIds: this.selectedGroupIds,
        contentIds: this.selectedContentIds,
        comment: this.editedComment,
        automaticAttend: this.editedAutomaticAttend,
      })
    },
    branchAndGroupName(item: Group) {
      return item.getWithBranchName();
    },
    fullName: item => item.firstName + ' ' + item.familyName,
    toggleSelectAllGroups () {
      this.$nextTick(() => {
        if (this.allGroupsSelected) {
          this.selectedGroupIds = []
        } else {
          this.selectedGroupIds = this.groups.map(g => g.id).slice()
        }
      })
    },
    formatDate,
    parseDate,
  },
  watch: {
    id: {
      immediate: true,
      handler(newVal) {
        this.dataId = newVal;
      },
    },
    date: {
      immediate: true,
      handler(newVal) {
        this.trainingDate = newVal;
      },
    },
    start: {
      immediate: true,
      handler(newVal) {
        this.startTime = newVal;
      },
    },
    end: {
      immediate: true,
      handler(newVal) {
        if (newVal) {
          this.endTime = newVal;
        }
      },
    },
    locationId: {
      immediate: true,
      handler(newVal) {
        this.selectedLocationId = newVal;
      },
    },
    groupIds: {
      immediate: true,
      handler(newVal) {
        if (newVal) {
          this.selectedGroupIds = newVal;
        }
      },
    },
    trainerIds: {
      immediate: true,
      handler(newVal) {
        this.selectedTrainerIds = newVal;
      },
    },
    contentIds: {
      immediate: true,
      handler(newVal) {
        this.selectedContentIds = newVal;
      },
    },
    comment: {
      immediate: true,
      handler(newVal) {
        this.editedComment = newVal;
      },
    },
    automaticAttend: {
      immediate: true,
      handler(newVal) {
        this.editedAutomaticAttend = newVal;
      },
    },
    trainingDate() {
      this.fireChangeEvent();
    },
    startTime() {
      this.fireChangeEvent();
    },
    endTime() {
      this.fireChangeEvent();
    },
    selectedTrainerIds() {
      this.fireChangeEvent();
    },
    selectedGroupIds() {
      this.fireChangeEvent();
    },
    selectedContentIds() {
      this.fireChangeEvent();
    },
    selectedLocationId() {
      this.fireChangeEvent();
    },
    editedComment() {
      this.fireChangeEvent();
    },
    editedAutomaticAttend() {
      this.fireChangeEvent();
    },
  },
})
</script>

<style scoped>

</style>

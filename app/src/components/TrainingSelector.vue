<template>
  <!-- Desktop sidebar list -->
  <div v-if="mdAndUp" class="tp-training-selector">
    <div class="tp-training-selector__accordion">
      <div
          v-for="(dayGroup, index) in groupedTrainings"
          :key="index"
          class="tp-training-selector__day-section"
      >
        <div
            class="tp-training-selector__day-header"
            @click="toggleDayExpanded(index)"
        >
          <div class="tp-training-selector__panel-header">
            <div class="tp-training-selector__panel-date">{{ dayGroup.dateLabel }}</div>
            <div class="tp-training-selector__panel-count">{{ dayGroup.trainings.length }}</div>
          </div>
          <span class="tp-training-selector__chevron" :class="{ 'tp-training-selector__chevron--open': expandedDays[index] }">›</span>
        </div>

        <div v-if="expandedDays[index]" class="tp-training-selector__list">
          <div
              v-for="item in dayGroup.trainings"
              :key="item.id"
              class="tp-training-selector__item"
              :class="{'tp-training-selector__item--active': item === selectedTraining}"
              @click="selectTraining(item)"
              :style="{ borderLeftColor: timelineColor(item) }"
          >
            <div class="tp-training-selector__item-header">
              <div>
                <div class="tp-training-selector__item-time">{{ item.start.format('HH:mm') }} - {{ item.end.format('HH:mm') }}</div>
              </div>
              <v-icon size="small" :color="getStatusIconColor(item)">{{ getStatusIconName(item) }}</v-icon>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Mobile FAB and full-screen dialog -->
  <v-btn
      v-if="!mdAndUp"
      color="primary"
      title="Training auswählen"
      @click="showMobileList = true"
      fab
      elevation="6"
      fixed
      bottom
      right
      class="mb-4 mr-4"
  >
    <v-icon>mdi-format-list-bulleted</v-icon>
  </v-btn>

  <v-dialog
      v-model="showMobileList"
      :fullscreen="xs"
      scrollable
      persistent
  >
    <v-card>
      <v-toolbar flat>
        <v-toolbar-title>Training auswählen</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn icon @click="showMobileList = false">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-toolbar>
      <v-divider></v-divider>
      <v-card-text class="pa-0">
        <div class="tp-training-selector__list tp-training-selector__list--dialog">
          <v-card
              v-for="(item) in trainings"
              :key="item.id"
              class="tp-training-selector__card ma-2"
              :class="{'tp-training-selector__card--active': item === selectedTraining}"
              @click="selectTraining(item)"
              :style="{
                borderLeftColor: timelineColor(item),
                '--active-color': timelineColor(item)
              }"
          >
            <div class="tp-training-selector__tab">{{ item.start.format('DD') }}</div>
            <v-card-text>
              <div class="tp-training-selector__card-header">
                <div>
                  <div class="tp-training-selector__date">{{ formattedDate(item) }}</div>
                  <div class="tp-training-selector__time">{{ item.start.format('HH:mm') }} - {{ item.end.format('HH:mm') }}</div>
                  <div class="tp-training-selector__location">{{ getBranchShortNameByGroupIds(item.groupIds) }} • {{ getLocationById(item.locationId)?.name }}</div>
                </div>
                <v-icon size="small" :color="getStatusIconColor(item)">{{ getStatusIconName(item) }}</v-icon>
              </div>
            </v-card-text>
          </v-card>
        </div>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script lang="ts">
import { defineComponent, ref, computed, watch } from 'vue'
import { useDisplay } from 'vuetify'
import Training from "../models/Training";
import { useAuthStore } from '@/store/auth'
import { useCookieAuthStore } from '@/store/cookieAuth'
import { useMasterDataStore } from '@/store/masterData'

export default defineComponent({
  name: "TrainingSelector",
  props: {
    trainings: Array as () => Training[],
    application: String,
    selectedTrainingId: Number,
  },
  emits: ['change'],
  setup(props, { emit }) {
    const { xs, mdAndUp } = useDisplay()
    const selectedTraining = ref<Training | null>(null)
    const showMobileList = ref(false)
    const expandedDays = ref<Record<number, boolean>>({})

    const loggedInUser = computed(() => useAuthStore().user)
    const cookieUser = computed(() => useCookieAuthStore().cookieUser)

    const currentUser = computed(() => {
      if (loggedInUser.value) {
        return loggedInUser.value;
      } else if (cookieUser.value) {
        return cookieUser.value;
      }
      return null;
    })

    const isEvaluation = computed(() => props.application === 'evaluation')
    const isCheckIn = computed(() => props.application === 'checkin')
    const isNoStatus = computed(() => !isEvaluation.value && !isCheckIn.value)

    const groupedTrainings = computed(() => {
      const groups: Record<string, { dateLabel: string; trainings: Training[] }> = {}

      ;(props.trainings as Training[]).forEach(training => {
        const dateKey = training.start.format('YYYY-MM-DD')
        const dateLabel = training.start.format('ddd DD.MMM')

        if (!groups[dateKey]) {
          groups[dateKey] = { dateLabel, trainings: [] }
        }
        groups[dateKey].trainings.push(training)
      })

      return Object.values(groups)
    })

    const getTrainingById = (id: number): Training | undefined => {
      return (props.trainings as Training[]).find(t => t.id === id)
    }

    const getBranchShortNameByGroupIds = (groupIds: number[]): string => {
      return useMasterDataStore().getBranchShortNameByGroupIds(groupIds)
    }

    const getLocationById = (locationId: number): any => {
      return useMasterDataStore().getLocationById(locationId)
    }

    const attendingStatus = (training: Training): boolean | null => {
      if (currentUser.value) {
        const tp = training.participants.filter(p => p.userId === currentUser.value!.id);
        if (tp && tp.length > 0) {
          return tp[0].attend;
        }
      }
      return null;
    }

    const attending = (training: Training): boolean => {
      return attendingStatus(training) ? true : false;
    }

    const evaluated = (training: Training): boolean => {
      return training.evaluated;
    }

    const canceled = (training: Training): boolean => {
      const status = attendingStatus(training);
      return (status !== null && !status);
    }

    const checkStatusDone = (training: Training): boolean => {
      return (isCheckIn.value && attending(training)) || (isEvaluation.value && evaluated(training))
    }

    const timelineColor = (training: Training): string => {
      if (checkStatusDone(training)) {
        return '#60cc69' // primary color
      } else if (isCheckIn.value && canceled(training)) {
        return '#ef5350' // red lighten-2
      }
      const mainBranch = useMasterDataStore().getBranchByGroupIds(training.groupIds);
      if (mainBranch) {
        return mainBranch.colorHex
      }
      return '#64b5f6' // blue lighten-2
    }

    const formattedDate = (training: Training): string => {
      const dayAbbr = training.start.format('dddd').slice(0, 2)
      const date = training.start.format('DD')
      const month = training.start.format('MMM')
      return `${dayAbbr} ${date}.${month}`
    }

    const statusIcon = (training: Training): string => {
      if (checkStatusDone(training)) {
        return isCheckIn.value ? 'mdi-thumb-up' : 'mdi-check'
      } else if (isCheckIn.value && canceled(training)) {
        return 'mdi-thumb-down'
      }
      return 'mdi-alert-decagram'
    }

    const selectTraining = (training: Training): void => {
      selectedTraining.value = training
      showMobileList.value = false
      emit('change', training.id)
    }

    const toggleDayExpanded = (dayIndex: number): void => {
      expandedDays.value[dayIndex] = !expandedDays.value[dayIndex]
    }

    const getStatusIconName = (training: Training): string => {
      if (checkStatusDone(training)) {
        return isCheckIn.value ? 'mdi-thumb-up' : 'mdi-check'
      } else if (isCheckIn.value && canceled(training)) {
        return 'mdi-thumb-down'
      }
      return 'mdi-help-circle-outline'
    }

    const getStatusIconColor = (training: Training): string => {
      if (checkStatusDone(training)) {
        return 'success'
      } else if (isCheckIn.value && canceled(training)) {
        return 'error'
      }
      return 'warning'
    }

    // Watch selectedTrainingId prop to sync internal state
    watch(
      () => props.selectedTrainingId,
      (newId) => {
        selectedTraining.value = getTrainingById(newId as number) || null
      }
    )

    // Initialize selected training on mount
    selectedTraining.value = getTrainingById(props.selectedTrainingId as number) || null

    return {
      xs,
      mdAndUp,
      selectedTraining,
      showMobileList,
      expandedDays,
      groupedTrainings,
      selectTraining,
      toggleDayExpanded,
      getStatusIconName,
      getStatusIconColor,
      getBranchShortNameByGroupIds,
      getLocationById,
      timelineColor,
      formattedDate,
      statusIcon,
      checkStatusDone,
      attending,
      evaluated,
      canceled,
      isCheckIn,
      isEvaluation,
      isNoStatus,
    }
  }
})
</script>

<style lang="scss" scoped>

.tp-training-selector {
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;

  &__accordion {
    overflow-y: auto;
    flex: 1;
    scrollbar-gutter: stable;
  }

  &__day-section {
    border-bottom: 1px solid rgba(0, 0, 0, 0.12);

    &:last-child {
      border-bottom: none;
    }
  }

  &__day-header {
    cursor: pointer;
    padding: 0.6rem 0.75rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    user-select: none;
    transition: background-color 0.2s ease;

    &:hover {
      background-color: rgba(0, 0, 0, 0.04);
    }
  }

  &__chevron {
    color: rgba(0, 0, 0, 0.5);
    transition: transform 0.2s ease;
    flex-shrink: 0;
    font-size: 1.5rem;
    line-height: 1;
    display: inline-flex;
    align-items: center;

    &--open {
      transform: rotate(90deg);
    }
  }

  &__panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
  }

  &__panel-date {
    font-weight: 600;
    font-size: 0.95rem;
  }

  &__panel-count {
    font-size: 0.8rem;
    color: rgba(0, 0, 0, 0.5);
    background-color: rgba(0, 0, 0, 0.05);
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
  }

  &__list {
    display: flex;
    flex-direction: column;
    gap: 0;
    overflow-y: auto;
    padding: 0;
    flex: 1;

    &--dialog {
      padding: 0.5rem;
      gap: 0.5rem;
    }
  }

  &__item {
    cursor: pointer;
    transition: all 0.2s ease;
    border-left: 4px solid;
    padding: 0.5rem 0.75rem;
    position: relative;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);

    &:last-child {
      border-bottom: none;
    }

    &:hover {
      background-color: rgba(0, 0, 0, 0.02);
      box-shadow: inset 2px 0 0 rgba(0, 0, 0, 0.08);
    }

    &--active {
      border-left-width: 6px;
      background-color: color-mix(in srgb, var(--active-color, #60cc69) 12%, white);
      box-shadow: inset 2px 0 0 var(--active-color, #60cc69);
      font-weight: 500;
    }
  }

  &__item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.5rem;
  }

  &__item-time {
    font-size: 0.8rem;
    color: rgba(0, 0, 0, 0.7);
  }

  &__card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.75rem;
  }

  &__date {
    font-size: 0.875rem;
    font-weight: 600;
    line-height: 1.1;
  }

  &__time {
    font-size: 0.8rem;
    color: rgba(0, 0, 0, 0.6);
    line-height: 1.2;
    margin-top: 0.2rem;
  }

  &__location {
    font-size: 0.75rem;
    color: rgba(0, 0, 0, 0.5);
    line-height: 1.2;
    margin-top: 0.15rem;
  }
}

</style>

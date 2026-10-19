<template>
    <v-row>
        <v-col
                v-for="(item) in weekdaysNumbers"
                :key="item"
                cols="auto">
            <v-checkbox
                    :label="getWeekdayString(item)"
                    :value="item"
                    v-model="weekdaysSelected">
            </v-checkbox>
        </v-col>
    </v-row>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

const props = defineProps<{
  weekdays?: number[]
}>()

const emit = defineEmits<{
  change: [weekdays: number[]]
}>()

const weekdaysStrings = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']
const weekdaysNumbers = [1, 2, 3, 4, 5, 6, 7]
const weekdaysSelected = ref<number[]>([])

function getWeekdayString(weekday: number): string {
  return weekdaysStrings[weekday - 1]
}

watch(weekdaysSelected, () => emit('change', weekdaysSelected.value))
watch(
  () => props.weekdays,
  (newVal) => {
    weekdaysSelected.value = newVal as number[]
  },
  { immediate: true }
)
</script>

<style scoped>

</style>

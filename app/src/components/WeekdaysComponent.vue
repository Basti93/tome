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

<script lang="ts">
    export default {
        name: "WeekdaysComponent",
        emits: ['change'],
        props: {
          weekdays: Array,
        },
        data: function () {
            return {
                weekdaysStrings: ["Mo", "Di", "Mi", "Do", "Fr", "Sa", "So"] as string[],
                weekdaysNumbers: [1, 2, 3, 4, 5, 6, 7] as number[],
                weekdaysSelected: [] as number[],
            }
        },
        methods: {
           getWeekdayString(weekday: number): string {
               return this.weekdaysStrings[weekday - 1];
           }
        },
        watch: {
            weekdaysSelected() {
              this.$emit("change", this.weekdaysSelected)
            },
            weekdays: {
                immediate: true,
                handler(newVal: number[]) {
                    this.weekdaysSelected = newVal;
                },
            },
        }
    }
</script>

<style scoped>

</style>

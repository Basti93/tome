<template>
    <v-layout row wrap>
        <v-flex
                v-for="(item) in weekdaysNumbers"
                :key="item">
            <v-checkbox
                    :label="getWeekdayString(item)"
                    :value="item"
                    v-model="weekdaysSelected">
            </v-checkbox>
        </v-flex>
    </v-layout>
</template>

<script lang="ts">
    import Vue from "vue";

    export default Vue.extend({
        name: "WeekdaysComponent",
        props: {
          weekdays: Array,
        },
        data: function () {
            return {
                weekdaysStrings: ["Mo", "Di", "Mi", "Do", "Fr", "Sa", "So"] as String[],
                weekdaysNumbers: [1, 2, 3, 4, 5, 6, 7] as Number[],
                weekdaysSelected: [] as Number[],
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
                handler(newVal: Number[]) {
                    this.weekdaysSelected = newVal;
                },
            },
        }
    })
</script>

<style scoped>

</style>

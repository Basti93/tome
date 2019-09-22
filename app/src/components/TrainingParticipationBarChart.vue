<template>
    <v-card flat>
        <v-card-title>
            <StatisticsFilter1
                    v-bind:groupIds="filterGroupIds"
                    v-bind:year="filterYear"
                    v-on:groupsSelected="filterGroupIdsChanged"
                    v-on:yearSelected="filterYearChanged">
            </StatisticsFilter1>
        </v-card-title>
        <v-divider></v-divider>
        <v-card-text>
            <apexchart ref="trainingParticipationBarChart" height="600" type="bar" :options="options" :series="series"></apexchart>
        </v-card-text>
    </v-card>
</template>

<script>
    import {mapGetters} from 'vuex'
    import StatisticsFilter1 from "./StatisticsFilter1";
    import { getRandomColor } from  "../helpers/color-helper"

    export default {
        name: "TrainingParticipationBarChart",
        components: {StatisticsFilter1},
        data: function () {
            return {
                filterGroupIds: [],
                filterYear: null,
                options: {
                    chart: {
                        id: 'training-participation',
                        foreColor: '#fff',
                        height: 400
                    },
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            distributed: true,
                            dataLabels: {
                                position: 'bottom'
                            },
                        }
                    },
                    tooltip: {
                        theme: 'dark',
                        x: {
                            show: false
                        },
                    },
                    dataLabels: {
                        enabled: true,
                        textAnchor: 'start',
                        style: {
                            colors: ['#fff']
                        },
                        formatter: function (val, opt) {
                            return opt.w.globals.labels[opt.dataPointIndex] + ":  " + val
                        },
                        offsetX: 0,
                        dropShadow: {
                            enabled: true
                        }
                    },
                    legend: {
                        show: true,
                    },
                    yaxis: {
                        labels: {
                            show: false
                        }
                    },
                    xaxis: {
                        type: 'category',
                        categories: [],
                    },
                },
                series: [{
                    name: 'Teilnahmen',
                    data: []
                }],
            }
        },
        created() {
            if (this.loggedInUser.isTrainer) {
                this.filterGroupIds = this.loggedInUser.trainerGroupIds;
            } else {
                this.filterGroupIds.push(this.loggedInUser.groupIds);
            }
            this.filterYear = this.moment().year();
        },
        computed: {
            ...mapGetters('masterData', {getGroupColorById: 'getGroupColorById', getGroupIdsByBranchId: 'getGroupIdsByBranchId'}),
            ...mapGetters({loggedInUser: 'loggedInUser'}),
        },
        methods: {
            async fetchData() {
                this.series = [];
                let colors = [];
                if (this.filterGroupIds.length > 0 && this.filterYear) {
                    let url = '/training/participationcount?groupIds=' + this.filterGroupIds + '&year=' + this.filterYear;
                    let {data} = await this.$http.get(url);
                    let names = [];
                    let count = [];
                    for (let i = 0; i < data.length; i++) {
                        names.push(data[i].firstName + " " + data[i].familyName)
                        count.push(data[i].total)
                        colors.push(this.getRandomColor());
                    }
                    this.options = {
                        xaxis: {
                            categories: names,
                        },
                        fill: {
                            colors: colors,
                        },
                    };
                    this.series = [{
                        data: count
                    }];

                }
            },
            filterGroupIdsChanged: function (groupIds) {
                this.filterGroupIds = groupIds;
                this.fetchData();
            },
            filterYearChanged: function (year) {
                this.filterYear = year;
                this.fetchData();
            },
            getRandomColor,
        },
    }
</script>

<style scoped>

</style>

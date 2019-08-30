<template>
    <v-card>
        <v-card-title>
            <StatisticsFilter1
                    v-bind:groupIds="filterGroupIds"
                    v-bind:year="filterYear"
                    v-on:groupsSelected="filterGroupIdsChanged"
                    v-on:yearSelected="filterYearChanged">
            </StatisticsFilter1>
        </v-card-title>
        <v-card-text>
            <apexchart ref="trainerTimeLineChart" type="bar" :options="options" :series="series"/>
        </v-card-text>
    </v-card>
</template>

<script>
    import {mapGetters} from 'vuex'
    import StatisticsFilter1 from "./StatisticsFilter1";
    import {getRandomColor} from "../helpers/color-helper"

    export default {
        name: "TrainerTimeline",
        components: {StatisticsFilter1},
        props: ['trainerId'],
        data: function () {
            return {
                filterGroupIds: [],
                filterYear: null,
                options: {
                    chart: {
                        id: 'trainerTimeLineChart',
                        foreColor: '#fff',
                        stacked: true,
                    },
                    responsive: [{
                        breakpoint: 720,
                        options: {
                            chart: {
                                height: 480
                            },
                            plotOptions: {
                                bar: {
                                    horizontal: true
                                }
                            },
                            grid: {
                                yaxis: {
                                    lines: {
                                        show: false
                                    }
                                },
                                xaxis: {
                                    lines: {
                                        show: true
                                    }
                                }
                            },
                        },
                    }],
                    dataLabels: {
                        enabled: true
                    },

                    grid: {
                        yaxis: {
                            lines: {
                                show: true
                            }
                        },
                        xaxis: {
                            lines: {
                                show: false
                            }
                        }
                    },
                    legend: {
                        showForNullSeries: false,
                        showForZeroSeries: false,
                        labels: {
                            color: '#fff'

                        },
                    },
                    tooltip: {
                        theme: 'dark',
                        y: {
                            formatter: function (val) {
                                return val + " Trainingsstunden"
                            }
                        }
                    },
                    xaxis: {
                        categories: ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'],
                    },
                    fill: {
                        opacity: 1,
                    },
                    colors: [],
                },
                series: [],
            }
        },
        created() {
            if (this.loggedInUser.isTrainer || this.loggedInUser.isTrainer) {
                this.filterGroupIds = this.loggedInUser.trainerGroupIds;
            } else {
                this.filterGroupIds.push(this.loggedInUser.groupId);
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
                let url = '/trainingevaluation/accountingtimestatistics?year=' + this.filterYear + "&groupIds=" + this.filterGroupIds;
                let {data} = await this.$http.get(url);
                for (let z = 0; z < data.length; z++) {
                    let count = [];
                    for (let i = 0; i < 12; i++) {
                        count.push(0);
                    }
                    for (let i = 0; i < data[z].data.length; i++) {
                        let monthNumber = parseInt(data[z].data[i].month);
                        count.splice((monthNumber - 1), 1, data[z].data[i].accountingHours);
                    }

                    this.series.push({
                        name: data[z].trainer.firstName + ' ' + data[z].trainer.familyName,
                        data: count
                    });
                    colors.push(this.getRandomColor());
                }
                this.options = {
                    colors: colors,

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

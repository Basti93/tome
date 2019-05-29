<template>
    <v-dialog
            v-model="visible"
            max-width="400px"
            persistent>
        <v-card>
            <v-toolbar card>
                <v-btn
                    icon
                    @click="show=false">
                    <v-icon>close</v-icon>
                </v-btn>
                <v-toolbar-title>ÜL-Abrechnung</v-toolbar-title>
            </v-toolbar>

            <v-card-text>
                <v-form
                    ref="form"
                    v-model="valid">
                    <v-flex xs12>
                        <v-menu
                                ref="fromDateMenuOpened"
                                :close-on-content-click="false"
                                v-model="fromDateMenuOpened"
                                lazy
                                full-width
                        >
                            <v-text-field
                                    slot="activator"
                                    v-model="fromDateFormatted"
                                    required
                                    label="Von"
                                    prepend-icon="event"
                                    readonly
                            ></v-text-field>
                            <v-date-picker v-model="dateFrom" @input="fromDateMenuOpened = false"></v-date-picker>
                        </v-menu>
                    </v-flex>
                    <v-flex xs12>
                        <v-menu
                                ref="toDateMenuOpened"
                                :close-on-content-click="false"
                                v-model="toDateMenuOpened"
                                lazy
                                full-width
                        >
                            <v-text-field
                                    slot="activator"
                                    v-model="toDateFormatted"
                                    required
                                    label="Bis"
                                    prepend-icon="event"
                                    readonly
                            ></v-text-field>
                            <v-date-picker v-model="dateTo" @input="toDateMenuOpened = false"></v-date-picker>
                        </v-menu>
                    </v-flex>
                    <v-flex xs12>
                        <v-btn
                                v-if="!creatingExcelReport && !url"
                                color="primary"
                                :disabled="!valid"
                                @click="createExcelReport()">
                            <v-icon left>update</v-icon>
                            Erstellen
                        </v-btn>
                    </v-flex>
                    <v-flex xs12>
                        <v-btn
                                v-if="creatingExcelReport || url"
                                :loading="creatingExcelReport"
                               :href="url"
                                color="primary"
                                >
                            <v-icon left>save_alt</v-icon>
                            Download Excel
                        </v-btn>
                    </v-flex>
                </v-form>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script lang="ts">
    import Vue from 'vue'
    import {mapGetters} from 'vuex'
    import {formatDate, parseDate} from "../helpers/date-helpers"

    export default Vue.extend({
        name: "TrainingAccountingExportDialog",
        props: {
            'visible': false,
        },
        data: function () {
            return {
                valid: true,
                dateFrom: new Date(new Date().setMonth(new Date().getMonth() - 6)).toISOString().substr(0, 10) as Date,
                dateTo: new Date().toISOString().substr(0, 10) as Date,
                fromDateMenuOpened: false,
                toDateMenuOpened: false,
                url: null,
                creatingExcelReport: false,
            }
        },
        computed: {
            ...mapGetters({loggedInUser: 'loggedInUser'}),
            fromDateFormatted(): String {
                return this.formatDate(this.dateFrom)
            },
            toDateFormatted(): String {
                return this.formatDate(this.dateTo)
            },
            show: {
                get() {
                    return this.visible;
                },
                set(value) {
                    if (!value) {
                        this.reset();
                        this.$emit('close')
                    }
                }
            },
        },
        methods: {
            reset(): void {
                this.url = null;
            },
            async createExcelReport(): void {
                this.creatingExcelReport = true;
                try {
                    const response = await this.$http.post('/trainingevaluation/exportaccountingtimes',
                        {
                            userId: this.loggedInUser.id,
                            from: this.moment(this.dateFrom, 'YYYY-MM-DD').format(),
                            to: this.moment(this.dateTo, 'YYYY-MM-DD').format(),
                        });
                    if (response.data.status == 'ok') {
                        this.url = process.env.VUE_APP_URL + response.data.url;
                    }
                } catch (error) {
                    console.error(error);
                } finally {
                    this.creatingExcelReport = false;
                }

            },
            formatDate,
            parseDate,
        }
    });
</script>

<style scoped>

</style>

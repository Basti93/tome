<template>
    <v-layout align-top>
        <v-flex xs12 md10 offset-md1 top>
            <v-card>
                <v-toolbar card prominent>
                    <v-toolbar-title>Meine kürzlich abgehaltenen Trainings</v-toolbar-title>
                    <v-spacer></v-spacer>

                </v-toolbar>
                <v-divider></v-divider>
                <v-card-text flat>
                    <div v-show="dataLoaded">
                        <div class="tp-training-prepare__navigation">
                            <v-card
                                    v-for="(item) in pastTrainings"
                                    :key="item.id"
                                    hover
                                    @click="selectTraining(item.id)"
                                    class="tp-training-prepare__navigation-card"
                                    :class="{'tp-training-prepare__navigation-card--evaluated': item.evaluated, 'tp-training-prepare__navigation-card--active': item === selectedTraining, 'tp-training-prepare__navigation-card--mobile': $vuetify.breakpoint.smAndDown, 'tp-training-prepare__navigation-card--desktop': $vuetify.breakpoint.mdAndUp}"
                            >
                                <v-card-title>
                                    <h2 class="subheading">{{ item.start.format('dddd').slice(0, 2) }}</h2>
                                    <p class="title pt-1">{{ item.start.format('DD')}}</p>
                                    <v-icon v-if="item.evaluated" small>check</v-icon>
                                    <v-icon v-else small>new_releases</v-icon>
                                </v-card-title>
                            </v-card>
                        </div>
                        <v-divider></v-divider>
                        <v-slide-x-transition>
                            <div>
                                <div v-if="selectedTraining">
                                    <v-list>
                                        <v-list-tile>
                                            <v-list-tile-content>
                                                <v-list-tile-title>
                                                    <h3>{{ selectedTraining.start.format('dddd [den] Do MMMM') }}&nbsp;({{selectedTraining.start.fromNow()}})</h3>
                                                </v-list-tile-title>
                                            </v-list-tile-content>
                                        </v-list-tile>
                                        <v-btn v-if="!selectedTraining.evaluated" @click="confirmEvaluationDialog = true" color="primary">Abschließen</v-btn>
                                        <v-dialog v-model="confirmEvaluationDialog" persistent max-width="290">
                                            <v-toolbar card>
                                                <v-toolbar-title>Training abschließen?</v-toolbar-title>
                                            </v-toolbar>
                                            <v-divider></v-divider>
                                            <v-card>
                                                <v-card-text>Nachdem das Training abgeschlossen ist, kann es nicht mehr verändert werden.</v-card-text>
                                                <v-card-actions>
                                                    <v-spacer></v-spacer>
                                                    <v-btn color="primary" flat @click="confirmEvaluationDialog = false">Abbrechen</v-btn>
                                                    <v-btn color="primary" flat @click="evaluated()">Bestätigen</v-btn>
                                                </v-card-actions>
                                            </v-card>
                                        </v-dialog>
                                        <v-list-group
                                                v-model="participantsListGroupActive"
                                                prepend-icon="check"
                                                group="participants"
                                                key="0"
                                                no-action
                                        >
                                            <template slot="activator">
                                                <v-list-tile>
                                                    <v-list-tile-content>
                                                        <v-list-tile-title>{{participatingUsers.length}} Teilnehmer</v-list-tile-title>
                                                    </v-list-tile-content>
                                                </v-list-tile>
                                            </template>
                                            <v-list-tile
                                                    v-for="(item, index) in participatingUsers"
                                                    :key="item.id"
                                                    avatar
                                                    @click=""
                                            >

                                                <v-list-tile-avatar>
                                                    <v-icon>account_circle</v-icon>
                                                </v-list-tile-avatar>

                                                <v-list-tile-content>
                                                    <v-list-tile-title v-html="fullName(item)"></v-list-tile-title>
                                                </v-list-tile-content>

                                                <v-list-tile-action v-if="!selectedTraining.evaluated">
                                                    <v-btn color="primary" @click="removeParticipant(item.id)" outline>
                                                        <v-icon>remove</v-icon>
                                                    </v-btn>
                                                </v-list-tile-action>

                                            </v-list-tile>
                                        </v-list-group>
                                        <v-list-group
                                                v-model="canceledUserListGroupActive"
                                                prepend-icon="cancel"
                                                group="canceledusers"
                                                key="1"
                                                no-action
                                        >
                                            <template  slot="activator">
                                                <v-list-tile>
                                                    <v-list-tile-content>
                                                        <v-list-tile-title>{{canceledUsers.length}} <span v-if="canceledUsers.length == 1">Absage</span><span v-else>Absagen</span></v-list-tile-title>
                                                    </v-list-tile-content>
                                                </v-list-tile>
                                            </template>
                                            <v-list-tile
                                                    v-for="(item, index) in canceledUsers"
                                                    :key="item.id"
                                                    avatar
                                                    @click=""
                                            >
                                                <v-list-tile-avatar>
                                                    <v-icon>account_circle</v-icon>
                                                </v-list-tile-avatar>

                                                <v-list-tile-content>
                                                    <v-list-tile-title v-html="fullName(item)"></v-list-tile-title>
                                                    <v-list-tile-sub-title class="warning--text" v-html="getCancelReason(item.id)"></v-list-tile-sub-title>
                                                </v-list-tile-content>

                                                <v-list-tile-action v-if="!selectedTraining.evaluated">
                                                    <v-btn color="primary" @click="addParticipant(item.id)" outline>
                                                        <v-icon>add</v-icon>
                                                    </v-btn>
                                                </v-list-tile-action>
                                            </v-list-tile>
                                        </v-list-group>
                                    </v-list>
                            </div>
                                <div v-else>
                                    <v-alert
                                            v-bind:value="true"
                                            type="info"
                                            class="text-small"
                                            pa-0
                                            ma-0
                                            outline>
                                        Keine anstehenden Trainings für dich verfügbar
                                    </v-alert>
                                </div>
                            </div>
                        </v-slide-x-transition>
                    </div>
                </v-card-text>
            </v-card>
        </v-flex>
    </v-layout>
</template>

<script lang="ts">

    import Vue from "vue";
    import {mapGetters, mapState} from 'vuex'
    import Training from "@/models/Training";
    import TrainingParticipant from "@/models/TrainingParticipant";

    export default Vue.extend({
        name: "TrainingsEvaluation",
        data: function () {
            return {
                pastTrainings: [] as Training[],
                dataLoaded: false,
                selectedTrainingId: null,
                animationTrigger: true,
                users: [],
                selectedLocationId: null,
                participantsListGroupActive: true,
                canceledUserListGroupActive: false,
                confirmEvaluationDialog: false,
            }
        },
        computed: {
            ...mapGetters({loggedInUser: 'loggedInUser'}),
            ...mapGetters('masterData', {
                getBranchByGroupId: 'getBranchByGroupId',
                getGroupById: 'getGroupById',
                getBranchById: 'getBranchById',
            }),
            ...mapState('masterData', {
                locations: 'locations',
            }),
            selectedTraining() {
                return this.getUpcomingTrainingById(this.selectedTrainingId);
            },
            participatingUsers() {
                if (this.selectedTraining.participants) {
                    const filteredUserIds = this.selectedTraining.participants.filter(p => p.attend).map(p => p.userId);
                    if (filteredUserIds.length > 0) {
                        return this.users.filter(u => filteredUserIds.indexOf(u.id) >= 0);
                    }
                }
                return [];
            },
            canceledUsers() {
                if (this.selectedTraining.participants) {
                    const filteredUserIds = this.selectedTraining.participants.filter(p => !p.attend).map(p => p.userId);
                    if (filteredUserIds.length > 0) {
                        return this.users.filter(u => filteredUserIds.indexOf(u.id) >= 0);
                    }
                }
                return [];
            },
            currentUserGroupId() {
                if (this.currentUser && this.currentUser.groupIds && this.currentUser.groupIds.length > 0) {
                    return this.currentUser.groupIds[0]
                }
                return null
            },
        },
        created() {
            this.fetchData();
        },
        methods: {
            getCancelReason(userId: Number) {
                const cancelreason = this.selectedTraining.participants.filter(p => p.userId == userId)[0].cancelreason;
                if (cancelreason) {
                    return "Grund: " + cancelreason
                }
                return null;
            },
            async fetchData() {
                try {
                    this.dataLoaded = false;
                    this.pastTrainings = [];
                    //load data
                    const res = await this.$http.get('/training/past/trainer/' + this.loggedInUser.id);
                    if (res.data.data && res.data.data.length > 0) {
                        //json result to objects
                        for (let trObj of res.data.data) {
                            let participants = [] as TrainingParticipant[];
                            for (let partObj of trObj.participants) {
                                participants.push(new TrainingParticipant(partObj.trainingId, partObj.userId, partObj.attend === 1 ? true : false, partObj.cancelreason));
                            }
                            this.pastTrainings.push(new Training(trObj.id, this.moment(trObj.start, 'YYYY-MM-DDTHH:mm'), this.moment(trObj.end, 'YYYY-MM-DDTHH:mm'), trObj.locationId, trObj.groupIds, trObj.contentIds, trObj.trainerIds, participants, trObj.comment, trObj.prepared === 1 ? true : false, trObj.evaluated === 1 ? true : false));
                        }
                        //select first training
                        this.selectTraining(this.pastTrainings[0].id);
                    }
                    const allGroupIds = this.pastTrainings
                        .map(t => t.groupIds)
                        .reduce(function(pre, cur) {
                            return pre.concat(cur);
                        });
                    const res2 = await this.$http.get('/user?groupIds=' + allGroupIds);
                    this.users = res2.data;
                } catch (error) {
                    console.error(error);
                } finally {
                    this.dataLoaded = true;
                }
            },
            async removeParticipant(userId) {
                const {data} = await this.$http.post('/training/' + this.selectedTraining.id + '/removeparticipant/' + userId)
                if (data.status == 'ok') {
                    this.selectedTraining.participants.filter(p => p.userId === userId)[0].attend = false
                    this.$emit("showSnackbar", "Benutzer entfernt", "success");
                }
            },
            async addParticipant(userId) {
                const {data} = await this.$http.post('/training/' + this.selectedTraining.id + '/addparticipant/' + userId)
                if (data.status == 'ok') {
                    this.selectedTraining.participants.filter(p => p.userId === userId)[0].attend = true
                    this.$emit("showSnackbar", "Benutzer hinzugefügt", "success");
                }
            },
            async evaluated() {
                this.confirmEvaluationDialog = false;
                const {data} = await this.$http.post('/training/' + this.selectedTraining.id + '/evaluated')
                if (data.status == 'ok') {
                    this.selectedTraining.evaluated = true
                    this.$emit("showSnackbar", "Training abgeschlossen", "success");
                }
            },
            selectTraining(id) {
                this.animationTrigger = false;
                this.selectedTrainingId = id;
                setTimeout(() => {
                    this.animationTrigger = true;
                }, 100);
            },
            getUpcomingTrainingById(id) {
                return this.pastTrainings.filter(ut => ut.id == id)[0];
            },
            fullName: item => item.firstName + ' ' + item.familyName,
        },
    })

</script>

<style scoped lang="scss">
    .tp-training-prepare {

        &__navigation-card {
            flex-grow: 1;

            .v-card__title {
                display: block;
            }

            &--desktop {
                margin: 2rem;
            }

            &--mobile {
                margin: 0.5rem;
            }

            &--active {
                filter: brightness(80%);
            }
            &--evaluated {
                background-color: #60cc69 !important;
            }

        }

        &__card {
            margin-bottom: 2rem;
        }

        &__navigation {
            display: flex;
            flex-flow: row;
            justify-content: center;

            &-card {
                max-width: 12rem;
            }
        }

    }
</style>

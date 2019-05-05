<template>
    <v-layout align-top>
        <v-flex xs12 md10 offset-md1 top>
            <v-card>
                <v-toolbar card prominent>
                    <v-toolbar-title>Trainingsanmeldung</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-chip v-if="cookieUser" close @input="removeCookieUser()" v-model="cookieUser">{{cookieUser.getFullName()}}</v-chip>
                    <CookieUserDialog
                            :visible="cookieUserDialogVisible"
                            v-on:close="cookieUserDialogClosed()"
                    ></CookieUserDialog>
                    <v-spacer></v-spacer>
                    <v-chip>{{filterDisplayValue}}</v-chip>
                    <v-btn icon color="primary" @click="filterDialogVisible = true">
                        <v-icon>filter_list</v-icon>
                    </v-btn>
                    <v-dialog v-model="filterDialogVisible" max-width="500px" :fullscreen="$vuetify.breakpoint.xsOnly" persistent>
                        <v-card>
                            <v-card-title>
                                <span class="title">Gruppe wechseln</span>
                            </v-card-title>
                            <v-card-text>
                                <GroupSelect
                                        v-bind:groupId="currentUserGroupId"
                                        v-on:groupSelected="groupChanged"
                                        v-on:branchSelected="branchChanged"
                                >
                                </GroupSelect>
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn color="primary" @click="filterDialogVisible = false">
                                    <v-icon>close</v-icon>
                                </v-btn>
                                <v-btn color="primary" @click="filterDone()">
                                    <v-icon>done</v-icon>
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>

                </v-toolbar>
                <v-divider></v-divider>
                <v-card-text flat>
                    <div v-show="dataLoaded">
                        <h3>Aktuelle Trainings</h3>
                        <div class="tp-training-check-in__navigation">
                            <v-card
                                    v-for="(item) in upcomingTrainings"
                                    :key="item.id"
                                    hover
                                    @click="selectTraining(item.id)"
                                    class="tp-training-check-in__navigation-card"
                                    :class="{'tp-training-check-in__navigation-card--attending': attending(item.id), 'tp-training-check-in__navigation-card--canceled': canceled(item.id), 'tp-training-check-in__navigation-card--active': item === selectedTraining, 'tp-training-check-in__navigation-card--mobile': $vuetify.breakpoint.smAndDown, 'tp-training-check-in__navigation-card--desktop': $vuetify.breakpoint.mdAndUp}"
                            >
                                <v-card-title>
                                    <h2 class="subheading">{{ item.start.format('dddd').slice(0, 2) }}</h2>
                                    <p class="title pt-1">{{ item.start.format('DD')}}</p>
                                    <v-icon v-if="attending(item.id)" small>check</v-icon>
                                    <v-icon v-else-if="canceled(item.id)" small>not_interested</v-icon>
                                    <v-icon v-else small>new_releases</v-icon>
                                </v-card-title>
                            </v-card>
                        </div>
                        <v-divider></v-divider>
                        <v-slide-x-transition>
                            <TrainingCheckIn
                                    v-if="selectedTraining && animationTrigger"
                                    :currentUser="currentUser"
                                    :isCookieUser="isCookieUser"
                                    :training="selectedTraining"
                                    :participants="selectedTraining.participants"
                                    v-on:checkedIn="updateCheckedIn()"
                                    v-on:checkedOut="updateCheckedOut()"
                                    v-on:showCookieUserLogin="showCookieUserLogin()"
                                    class="tp-training-check-in__card">
                            </TrainingCheckIn>
                        </v-slide-x-transition>
                    </div>
                </v-card-text>
            </v-card>
        </v-flex>
    </v-layout>
</template>

<script lang="ts">

    import Vue from "vue";
    import {mapGetters} from 'vuex'
    import TrainingCheckIn from "../components/TrainingCheckIn.vue";
    import GroupSelect from "../components/GroupSelect.vue";
    import CookieUserDialog from "../components/CookieUserDialog.vue";
    import User from "../models/User";
    import Training from "@/models/Training";
    import TrainingParticipant from "@/models/TrainingParticipant";
    import { getCookie, eraseCookie } from "@/helpers/cookie-helper";

    export default Vue.extend({
        name: "TrainingsCheckIn",
        components: {CookieUserDialog, TrainingCheckIn, GroupSelect},
        data: function () {
            return {
                filterGroupId: null,
                filterBranchId: null,
                tempFilterGroupId: null,
                tempFilterBranchId: null,
                upcomingTrainings: [] as Training[],
                dataLoaded: false,
                filterDialogVisible: false,
                cookieUserDialogVisible: false,
                selectedTrainingId: null,
                cookieUser: null,
                animationTrigger: true,
                initializing: false,
            }
        },
        computed: {
            ...mapGetters({loggedInUser: 'loggedInUser'}),
            ...mapGetters('masterData', {
                getBranchByGroupId: 'getBranchByGroupId',
                getGroupById: 'getGroupById',
                getBranchById: 'getBranchById',
            }),
            currentUser() {
                if (this.loggedInUser) {
                    return this.loggedInUser;
                } else if (this.cookieUser) {
                    return this.cookieUser;
                }
                return null;
            },
            isCookieUser() {
                return this.cookieUser != null;
            },
            selectedTraining() {
                return this.getUpcomingTrainingById(this.selectedTrainingId);
            },
            currentUserGroupId() {
                if (this.currentUser && this.currentUser.groupIds && this.currentUser.groupIds.length > 0) {
                    return this.currentUser.groupIds[0]
                }
                return null
            },
            filterDisplayValue() {
                if (this.filterGroupId) {
                    let group = this.getGroupById(this.filterGroupId)
                    if (group) {
                        return group.name;
                    }
                } else if (this.filterBranchId) {
                    let branch = this.getBranchById(this.filterBranchId)
                    if (branch) {
                        return branch.name;
                    }
                }
                return "Alle Gruppen"
            },
            currentUserId() {
                if (this.currentUser) {
                    return this.currentUser.id;
                }
                return null;
            },
        },
        async created() {
            this.initializing = true;
            if (!this.loggedInUser) {
                this.cookieUser = User.from(this.getCookie('cookieUser'));
            }
            this.filterGroupId = this.currentUserGroupId;
            await this.fetchData();
            this.initializing = false;
        },
        methods: {
            groupChanged(groupId) {
                this.tempFilterGroupId = groupId;
            },
            branchChanged(branchId) {
                this.tempFilterBranchId = branchId;
            },
            filterDone() {
                this.filterBranchId = this.tempFilterBranchId;
                this.filterGroupId = this.tempFilterGroupId;
                this.fetchData();
                this.filterDialogVisible = false;
            },
            async fetchData() {
                try {
                    this.dataLoaded = false;
                    this.upcomingTrainings = [];
                    //build fetch url
                    let url = '/training/upcoming';
                    if (this.filterGroupId) {
                        url += '?groupIds=' + this.filterGroupId;
                    } else if (this.filterBranchId) {
                        url += '?branchId=' + this.filterBranchId;
                    }
                    //load data
                    const {data} = await this.$http.get(url);
                    if (data.data && data.data.length > 0) {
                        //json result to objects
                        for (let trObj of data.data) {
                            let participants = [] as TrainingParticipant[];
                            for (let partObj of trObj.participants) {
                                participants.push(new TrainingParticipant(partObj.trainingId, partObj.userId, partObj.attend === 1 ? true : false));
                            }
                            this.upcomingTrainings.push(new Training(trObj.id, this.moment(trObj.start, 'YYYY-MM-DDTHH:mm'), this.moment(trObj.end, 'YYYY-MM-DDTHH:mm'), trObj.locationId, trObj.groupIds, trObj.contentIds, trObj.trainerIds, participants, trObj.comment));
                        }
                        //select first training
                        this.selectTraining(this.upcomingTrainings[0].id);
                    }
                } catch (error) {
                    console.error(error);
                } finally {
                    this.dataLoaded = true;
                }
            },
            updateCheckedIn() {
                let participant = this.selectedTraining.participants.filter(p => p.userId === this.currentUserId);
                if (participant && participant.length > 0) {
                    const index = this.selectedTraining.participants.indexOf(participant[0]);
                    participant[0].attend = true;
                    this.$set(this.selectedTraining.participants[index], 'attend', true);
                } else {
                    this.selectedTraining.participants.push(new TrainingParticipant(this.selectedTraining.id, this.currentUserId, true));
                }
                this.$emit("showSnackbar", "Für das Training angemeldet", "success");
            },
            updateCheckedOut() {
                let participant = this.selectedTraining.participants.filter(p => p.userId === this.currentUserId);
                if (participant && participant.length > 0) {
                    const index = this.selectedTraining.participants.indexOf(participant[0]);
                    this.$set(this.selectedTraining.participants[index], 'attend', false);
                } else {
                    this.selectedTraining.participants.push(new TrainingParticipant(this.selectedTraining.id, this.currentUserId, false));
                }
                this.$emit("showSnackbar", "Vom Training abgemeldet", "info");
            },
            showCookieUserLogin() {
                this.cookieUserDialogVisible = true;
            },
            selectTraining(id) {
                this.animationTrigger = false;
                this.selectedTrainingId = id;
                setTimeout(() => {
                    this.animationTrigger = true;
                }, 100);
            },
            getUpcomingTrainingById(id) {
                return this.upcomingTrainings.filter(ut => ut.id == id)[0];
            },
            removeCookieUser() {
                this.eraseCookie('cookieUser');
                this.cookieUser = null;
            },
            cookieUserDialogClosed() {
                this.cookieUser = User.from(this.getCookie('cookieUser'));
                this.cookieUserDialogVisible = false;
            },
            attendingStatus(trainingId) {
                const training = this.getUpcomingTrainingById(trainingId);
                if (training) {
                    const tp = training.participants.filter(p => (p.userId === this.currentUserId));
                    if (tp && tp.length > 0) {
                        return tp[0].attend;
                    }
                }
                return null;
            },
            attending(trainingId) {
                return this.attendingStatus(trainingId) ? true : false;
            },
            canceled(trainingId) {
                const status = this.attendingStatus(trainingId);
                return (status !== null && !status) ? true : false;
            },
            getCookie,
            eraseCookie,
        },
        watch: {
            currentUser() {
                this.filterGroupId = this.currentUserGroupId;
                if (!this.initializing) {
                    this.fetchData();
                }
            }
        }
    })

</script>

<style scoped lang="scss">
    .tp-training-check-in {

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

            &--attending {
                background-color: #60cc69 !important;
            }

            &--canceled {
                background-color: #ff5252 !important;
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

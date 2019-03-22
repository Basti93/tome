<template>
    <v-layout align-top>
        <v-flex xs12 md10 offset-md1 top>
            <v-card>
                <v-toolbar card prominent>
                    <v-toolbar-title v-show="$vuetify.breakpoint.mdAndUp">Trainingsanmeldung</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <div v-if="cookieUser" class="text-small tp-training-check-in__cookie-user mt-3">
                        <div>
                            <h5>Ausgewählter Sportler</h5>
                            <p>{{cookieUser.getFullName()}}</p>
                        </div>
                        <v-btn
                                icon
                                @click="removeCookieUser()">
                            <v-icon color="red">cancel</v-icon>
                        </v-btn>
                    </div>
                    <CookieUserDialog
                            :visible="cookieUserDialogVisible"
                            v-on:close="cookieUserDialogClosed()"
                    ></CookieUserDialog>
                    <v-spacer></v-spacer>
                    <v-chip v-show="$vuetify.breakpoint.mdAndUp">{{filterDisplayValue}}</v-chip>
                    <v-btn icon color="primary" @click="filterDialogVisible = true">
                        <v-icon>filter_list</v-icon>
                    </v-btn>
                    <v-dialog v-model="filterDialogVisible" max-width="500px" :fullscreen="$vuetify.breakpoint.xsOnly">
                        <v-card>
                            <v-card-title>
                                <span class="title">Filter ändern</span>
                            </v-card-title>
                            <v-card-text>
                                <GroupSelect
                                        v-bind:groupId="currentUserGroupId"
                                        v-bind:branchId="currentUserBranchId"
                                        v-on:groupSelected="groupChanged"
                                        v-on:branchSelected="branchChanged"
                                >
                                </GroupSelect>
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn color="primary" @click="filterDone()">
                                    <v-icon>done</v-icon>
                                </v-btn>
                                <v-btn color="primary" @click="filterDialogVisible = false">
                                    <v-icon>close</v-icon>
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
                                    :class="{'tp-training-check-in__navigation-card--attending': attending(item.id), 'tp-training-check-in__navigation-card--active': item === selectedTraining, 'tp-training-check-in__navigation-card--mobile': $vuetify.breakpoint.smAndDown, 'tp-training-check-in__navigation-card--desktop': $vuetify.breakpoint.mdAndUp}"
                            >
                                <v-card-title>
                                    <h2 class="subheading">{{ moment(item.start).format('dddd').slice(0, 2) }}</h2>
                                    <p class="title pt-1">{{moment(item.start).format('DD')}}</p>
                                    <v-icon v-show="attending(item.id)" small>check</v-icon>
                                    <v-icon v-show="!attending(item.id)" small>priority_high</v-icon>
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
                if (this.currentUser && this.currentUser.groupId) {
                    return this.currentUser.groupId
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
            currentUserBranchId() {
                if (this.currentUser && this.currentUser.groupId) {
                    return this.getBranchByGroupId(this.currentUser.groupId).id
                }
                return null
            },
            currentUserId() {
                if (this.currentUser) {
                    return this.currentUser.id;
                }
                return null;
            },
        },
        created() {
            if (!this.loggedInUser) {
                this.cookieUser = User.from(this.getCookie('cookieUser'));
            }
            this.filterGroupId = this.currentUserGroupId;
            this.filterBranchId = this.currentUserBranchId;
            this.fetchData();
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
                    //build fetch url
                    let url = '/training/upcoming';
                    if (this.filterGroupId) {
                        url += '?groupIds=' + this.filterGroupId;
                    }
                    //load data
                    let response = await this.$http.get(url);
                    //json result to objects
                    for (let trObj of response.data.data) {
                        let participants = [] as TrainingParticipant[];
                        for (let partObj of trObj.participants) {
                            participants.push(new TrainingParticipant(partObj.trainingId, partObj.userId, partObj.attend === 1 ? true : false));
                        }
                        this.upcomingTrainings.push(new Training(trObj.id, new Date(trObj.start), new Date(trObj.end), trObj.locationId, trObj.groupIds, trObj.contentIds, trObj.trainerIds, participants, trObj.comment));
                    }
                    //select first training
                    this.selectTraining(this.upcomingTrainings[0].id);
                } catch (error) {
                    console.error(error);
                } finally {
                    this.dataLoaded = true;
                }
            },
            updateCheckedIn() {
                this.selectedTraining.participants.push(new TrainingParticipant(this.selectTraining.id, this.currentUserId, true));
                this.$emit("showSnackbar", "Im Training angemeldet", "success");
            },
            updateCheckedOut() {
                this.selectedTraining.participants = this.selectedTraining.participants.filter(p => p.userId !== this.currentUserId);
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
            attending(trainingId) {
                let training = this.getUpcomingTrainingById(trainingId);
                if (training) {
                    return training.participants.filter(p => (p.userId === this.currentUserId && p.attend)).length > 0;
                }
            },
            removeCookieUser() {
                this.eraseCookie('cookieUser');
                this.cookieUser = null;
            },
            cookieUserDialogClosed() {
                this.cookieUser = User.from(this.getCookie('cookieUser'));
                this.cookieUserDialogVisible = false;
            },
        },
        watch: {
            currentUser() {
                this.filterGroupId = this.currentUserGroupId;
                this.filterBranchId = this.currentUserBranchId;
                this.fetchData();
            }
        }
    })

</script>

<style scoped lang="scss">
    .tp-training-check-in {
        &__cookie-user {
            display: flex;
        }

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
        }

        &__card {
            margin-bottom: 2rem;
        }

        &__navigation {
            display: flex;
            flex-flow: row;
        }

    }
</style>

<template>
    <v-layout align-top>
        <v-flex xs12 md10 offset-md1 top>
            <v-card>
                <v-toolbar flat>
                    <v-toolbar-title>Meine kommenden Trainings</v-toolbar-title>
                    <v-spacer></v-spacer>

                </v-toolbar>
                <v-divider></v-divider>
                <v-card-text flat>
                    <div v-show="dataLoaded">
                        <div class="tp-training-prepare__navigation">
                            <v-card
                                    v-for="(item) in upcomingTrainings"
                                    :key="item.id"
                                    hover
                                    @click="selectTraining(item.id)"
                                    class="tp-training-prepare__navigation-card"
                                    :class="{'tp-training-prepare__navigation-card--active': item === selectedTraining, 'tp-training-prepare__navigation-card--mobile': $vuetify.breakpoint.smAndDown, 'tp-training-prepare__navigation-card--desktop': $vuetify.breakpoint.mdAndUp}"
                            >
                                <v-card-title>
                                    <h2 class="subtitle-1">{{ item.start.format('dddd').slice(0, 2) }}</h2>
                                    <p class="title pt-1">{{ item.start.format('DD')}}</p>
                                    <v-icon small>new_releases</v-icon>
                                </v-card-title>
                            </v-card>
                        </div>
                        <v-divider></v-divider>
                        <v-slide-x-transition>
                            <div>
                                <div v-if="selectedTraining">
                                    <v-list>
                                        <v-list-item>
                                            <v-list-item-content>
                                                <v-list-item-title><h3>{{ selectedTraining.start.format('dddd [den] Do MMMM') }}&nbsp;({{selectedTraining.start.fromNow()}})</h3></v-list-item-title>
                                            </v-list-item-content>
                                        </v-list-item>
                                        <v-list-group
                                                v-model="trainingDataGroupActive"
                                                prepend-icon="verified_user"
                                                group="trainingData"
                                                key="0"
                                                no-action
                                        >
                                            <template slot="activator">
                                                <v-list-item>
                                                    <v-list-item-content>
                                                        <v-list-item-title>Trainingsdaten</v-list-item-title>
                                                    </v-list-item-content>
                                                </v-list-item>
                                            </template>
                                            <v-container text-xs-left>
                                                <v-layout row align-center>
                                                    <v-flex shrink fill-height>
                                                        Von <v-chip>{{selectedTraining.start.format('HH:mm')}}</v-chip> Uhr bis <v-chip>{{selectedTraining.end.format('HH:mm')}}</v-chip> Uhr
                                                    </v-flex>
                                                    <v-flex grow>
                                                        <v-btn @click="editTime()"
                                                               color="primary"
                                                               flat>
                                                            <v-icon>edit</v-icon>
                                                        </v-btn>
                                                    </v-flex>
                                                </v-layout>
                                            </v-container>
                                            <v-container text-xs-left :class="{ 'elevation-4' : editingLocation}">
                                                <v-layout row align-center>
                                                    <v-flex shrink v-if="editingLocation">
                                                        <v-select
                                                                :items="locations"
                                                                item-text="name"
                                                                item-value="id"
                                                                v-model="editLocationId"
                                                                clearable
                                                                required
                                                                label="Ort"
                                                                prepend-icon="add_location"
                                                        ></v-select>
                                                    </v-flex>
                                                    <v-flex shrink fill-height v-else>
                                                        <v-chip>{{getLocationNameById(selectedTraining.locationId)}}</v-chip>
                                                    </v-flex>

                                                    <v-flex grow v-if="editingLocation">
                                                        <v-btn @click="saveEditLocation()"
                                                               color="primary"
                                                               flat>
                                                            <v-icon>check</v-icon>
                                                        </v-btn>
                                                        <v-btn @click="cancelEditLocation()"
                                                               color="primary"
                                                               flat>
                                                            <v-icon>cancel</v-icon>
                                                        </v-btn>
                                                    </v-flex>
                                                    <v-flex grow v-else>
                                                        <v-btn
                                                               @click="editLocation()"
                                                               color="primary"
                                                               flat>
                                                            <v-icon>edit</v-icon>
                                                        </v-btn>
                                                    </v-flex>
                                                </v-layout>
                                            </v-container>
                                            <v-container text-xs-left :class="{ 'elevation-4' : editingComment}">
                                                <v-layout row align-center>
                                                    <v-flex grow v-if="editingComment">
                                                        <v-textarea
                                                                filled
                                                                label="Kommentar"
                                                                v-model="editComment"
                                                        ></v-textarea>
                                                    </v-flex>
                                                    <v-flex grow v-else>
                                                        <v-textarea
                                                                solo
                                                                flat
                                                                outlined
                                                                label="Kommentar"
                                                                v-model="selectedTraining.comment"
                                                                readonly
                                                        ></v-textarea>
                                                    </v-flex>

                                                    <v-flex shrink v-if="editingComment">
                                                        <v-btn @click="saveEditComment()"
                                                           color="primary"
                                                           flat>
                                                            <v-icon>check</v-icon>
                                                        </v-btn>
                                                        <v-btn @click="cancelEditComment()"
                                                           color="primary"
                                                           flat>
                                                            <v-icon>cancel</v-icon>
                                                        </v-btn>
                                                    </v-flex>
                                                    <v-flex shrink v-else>
                                                        <v-btn @click="startEditComment()"
                                                               color="primary"
                                                               flat>
                                                            <v-icon>edit</v-icon>
                                                        </v-btn>
                                                    </v-flex>
                                                </v-layout>
                                            </v-container>
                                            <v-container text-xs-left :class="{ 'elevation-4' : editingTrainingContent}">
                                                <v-layout row align-center>
                                                    <v-flex shrink fill-height v-if="editingTrainingContent">
                                                        <TrainingContent
                                                                :contentIds="branchContentIds"
                                                                :initContentIds="editTrainingContentIds"
                                                                @change="editTrainingContentIdsChanged"
                                                                :selectable="editingTrainingContent"
                                                        >
                                                        </TrainingContent>
                                                    </v-flex>
                                                    <v-flex shrink fill-height v-else>
                                                        <TrainingContent
                                                                :contentIds="branchContentIds"
                                                                :initContentIds="selectedTraining.contentIds"
                                                                :selectable="editingTrainingContent"
                                                        >
                                                        </TrainingContent>
                                                    </v-flex>
                                                    <v-flex grow v-if="editingTrainingContent">
                                                        <v-btn @click="saveEditTrainingContent()"
                                                               color="primary"
                                                               flat>
                                                            <v-icon>check</v-icon>
                                                        </v-btn>
                                                        <v-btn @click="cancelEditTrainingContent()"
                                                               color="primary"
                                                               flat>
                                                            <v-icon>cancel</v-icon>
                                                        </v-btn>
                                                    </v-flex>
                                                    <v-flex grow v-else>
                                                        <v-btn @click="startEditTrainingContent()"
                                                               color="primary"
                                                               flat>
                                                            <v-icon>edit</v-icon>
                                                        </v-btn>
                                                    </v-flex>
                                                </v-layout>
                                            </v-container>
                                        </v-list-group>
                                        <v-list-group
                                                v-model="participantsListGroupActive"
                                                prepend-icon="check"
                                                group="participants"
                                                key="0"
                                                no-action
                                        >
                                            <template slot="activator">
                                                <v-list-item>
                                                    <v-list-item-content>
                                                        <v-list-item-title>{{participatingUsers.length}} Teilnehmer bis jetzt</v-list-item-title>
                                                    </v-list-item-content>
                                                </v-list-item>
                                            </template>
                                            <v-list-item
                                                    v-for="(item, index) in participatingUsers"
                                                    :key="item.id"
                                                    @click=""
                                            >
                                                <v-list-item-avatar>
                                                    <v-icon>account_circle</v-icon>
                                                </v-list-item-avatar>

                                                <v-list-item-content>
                                                    <v-list-item-title v-html="fullName(item)"></v-list-item-title>
                                                </v-list-item-content>
                                            </v-list-item>
                                        </v-list-group>
                                        <v-list-group
                                                v-model="canceledUserListGroupActive"
                                                prepend-icon="cancel"
                                                group="canceledusers"
                                                key="1"
                                                no-action
                                        >
                                            <template  slot="activator">
                                                <v-list-item>
                                                    <v-list-item-content>
                                                        <v-list-item-title>{{canceledUsers.length}} <span v-if="canceledUsers.length == 1">Absage</span><span v-else>Absagen</span></v-list-item-title>
                                                    </v-list-item-content>
                                                </v-list-item>
                                            </template>
                                            <v-list-item
                                                    v-for="(item, index) in canceledUsers"
                                                    :key="item.id"
                                                    @click=""
                                            >
                                                <v-list-item-avatar>
                                                    <v-icon>account_circle</v-icon>
                                                </v-list-item-avatar>

                                                <v-list-item-content @click="openCancelReasonDialog(item.id)">
                                                    <v-list-item-title>{{fullName(item)}}</v-list-item-title>
                                                    <v-list-item-sub-title v-if="getCancelReason(item.id)" class="warning--text">Grund: {{getCancelReason(item.id)}}</v-list-item-sub-title>
                                                </v-list-item-content>
                                            </v-list-item>
                                        </v-list-group>

                                    </v-list>
                            </div>
                                <div v-else>
                                    <v-alert
                                            type="info"
                                            class="text-small"
                                            pa-0
                                            ma-0
                                            outlined>
                                        Keine Trainings für dich verfügbar
                                    </v-alert>
                                </div>
                            </div>
                        </v-slide-x-transition>
                    </div>
                </v-card-text>
            </v-card>
        </v-flex>
        <v-dialog
                v-model="showCancelReasonDialog"
                max-width="800px">
            <v-card>
                <v-toolbar flat>
                    <v-btn icon @click="showCancelReasonDialog=false">
                        <v-icon>close</v-icon>
                    </v-btn>
                    <v-toolbar-title>Grund</v-toolbar-title>
                </v-toolbar>

                <v-card-text class="warning--text">{{cancelReasonDialogText}}</v-card-text>
            </v-card>
        </v-dialog>
        <v-dialog
                v-model="timeDialogOpened"
                max-width="800px"
                :fullscreen="$vuetify.breakpoint.xsOnly" persistent>
            <v-card>
                <v-toolbar flat>
                    <v-btn icon @click="timeDialogOpened=false">
                        <v-icon>close</v-icon>
                    </v-btn>
                    <v-toolbar-title>Trainingszeit ändern</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-toolbar-items>
                        <v-btn flat color="primary" @click="updateTrainingTime">Speichern</v-btn>
                    </v-toolbar-items>
                </v-toolbar>

                <v-card-text>
                    <v-layout row wrap>
                        <v-flex xs12 md6>
                            <h2>Von</h2>
                            <v-time-picker flat v-model="editStartTime" :landscape="$vuetify.breakpoint.xsOnly" format="24hr"></v-time-picker>
                        </v-flex>
                        <v-flex xs12 md6>
                            <h2>Bis</h2>
                            <v-time-picker v-model="editEndTime" :landscape="$vuetify.breakpoint.xsOnly" format="24hr"></v-time-picker>
                        </v-flex>
                    </v-layout>
                </v-card-text>
            </v-card>
        </v-dialog>
    </v-layout>
</template>

<script lang="ts">

    import Vue from "vue";
    import {mapGetters, mapState} from 'vuex'
    import Training from "@/models/Training";
    import TrainingParticipant from "@/models/TrainingParticipant";
    import TrainingContent from "../components/TrainingContent"

    export default Vue.extend({
        name: "TrainingsPrepare",
        components: {TrainingContent},
        data: function () {
            return {
                upcomingTrainings: [] as Training[],
                dataLoaded: false,
                selectedTrainingId: null,
                animationTrigger: true,
                users: [],
                selectedLocationId: null,
                trainingDataGroupActive: true,
                participantsListGroupActive: false,
                canceledUserListGroupActive: false,
                showCancelReasonDialog: false,
                cancelReasonDialogText: null,
                timeDialogOpened: false,
                editStartTime: null,
                editEndTime: null,
                editingLocation: false,
                editLocationId: null,
                editingComment: false,
                editingTrainingContent: false,
                editComment: null,
                editTrainingContentIds: [],
                branchId: null,
            }
        },
        computed: {
            ...mapGetters({loggedInUser: 'loggedInUser'}),
            ...mapGetters('masterData', {
                getBranchByGroupId: 'getBranchByGroupId',
                getGroupById: 'getGroupById',
                getBranchById: 'getBranchById',
                getLocationNameById: 'getLocationNameById',
                getContentIdsByBranchId: 'getContentIdsByBranchId',
            }),
            ...mapState('masterData', {
                locations: 'locations',
            }),
            branchContentIds(): Array {
                return this.getContentIdsByBranchId(this.branchId);
            },
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
                    return cancelreason
                }
                return null;
            },
            openCancelReasonDialog(userId: Number) {
                const cancelReason = this.getCancelReason(userId);
                if (cancelReason) {
                    this.cancelReasonDialogText = cancelReason;
                    this.showCancelReasonDialog = true;
                }
            },
            async fetchData() {
                try {
                    this.dataLoaded = false;
                    this.upcomingTrainings = [];
                    //load data
                    const res = await this.$http.get('/trainingprepare/' + this.loggedInUser.id);
                    if (res.data.data && res.data.data.length > 0) {
                        //json result to objects
                        for (let trObj of res.data.data) {
                            let participants = [] as TrainingParticipant[];
                            for (let partObj of trObj.participants) {
                                participants.push(new TrainingParticipant(partObj.trainingId, partObj.userId, partObj.attend === 1 ? true : false, partObj.cancelreason));
                            }
                            this.upcomingTrainings.push(new Training(trObj.id, this.moment(trObj.start, 'YYYY-MM-DDTHH:mm'), this.moment(trObj.end, 'YYYY-MM-DDTHH:mm'), trObj.locationId, trObj.groupIds, trObj.contentIds, trObj.trainerIds, participants, trObj.comment));
                        }
                        //select first training
                        this.selectTraining(this.upcomingTrainings[0].id);
                    }
                    const allGroupIds = this.upcomingTrainings
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
            selectTraining(id) {
                this.animationTrigger = false;
                this.selectedTrainingId = id;
                if (this.selectedTraining.groupIds) {
                    this.branchId =  this.getBranchByGroupId(this.selectedTraining.groupIds[0]).id;
                }
                setTimeout(() => {
                    this.animationTrigger = true;
                }, 100);
            },
            getUpcomingTrainingById(id) {
                return this.upcomingTrainings.filter(ut => ut.id == id)[0];
            },

            editTime() {
                this.editStartTime = this.selectedTraining.start.format('HH:mm')
                this.editEndTime = this.selectedTraining.end.format('HH:mm')
                this.timeDialogOpened = true;
            },
            editLocation() {
                this.editingLocation = true;
                this.editLocationId = this.selectedTraining.locationId;
            },
            startEditComment() {
                this.editingComment = true;
                this.editComment = this.selectedTraining.comment;
            },
            async saveEditComment() {
                const postData = {
                    'comment': this.editComment,
                };
                const {data} = await this.$http.post('/trainingprepare/' + this.selectedTraining.id + '/updatecomment', postData)
                if (data.status == 'ok') {
                    this.selectedTraining.comment = this.editComment;
                    this.cancelEditComment();
                    this.$emit("showSnackbar", "Kommentar aktualisiert", "success");
                }
            },
            startEditTrainingContent() {
                this.editingTrainingContent = true;
                this.editTrainingContentIds = this.selectedTraining.contentIds;
            },
            editTrainingContentIdsChanged(contentIds) {
                this.editTrainingContentIds = contentIds;
            },
            cancelEditComment() {
                this.editComment = null;
                this.editingComment = false;
            },
            async saveEditTrainingContent() {
                const postData = {
                    'contentIds': this.editTrainingContentIds,
                };
                const {data} = await this.$http.post('/trainingprepare/' + this.selectedTraining.id + '/updatecontent', postData)
                if (data.status == 'ok') {
                    this.selectedTraining.contentIds = this.editTrainingContentIds;
                    this.cancelEditTrainingContent();
                    this.$emit("showSnackbar", "Trainingsinhalte aktualisiert", "success");
                }
            },
            cancelEditTrainingContent() {
                this.editTrainingContentIds = [];
                this.editingTrainingContent = false;
            },
            async saveEditLocation() {
                const postData = {
                    'locationId': this.editLocationId,
                };
                const {data} = await this.$http.post('/trainingprepare/' + this.selectedTraining.id + '/updatelocation', postData)
                if (data.status == 'ok') {
                    this.selectedTraining.locationId = this.editLocationId;
                    this.cancelEditLocation();
                    this.$emit("showSnackbar", "Ort aktualisiert", "success");
                }
            },
            cancelEditLocation() {
                this.editLocationId = null;
                this.editingLocation = false;
            },
            async updateTrainingTime() {
                this.timeDialogOpened = false;
                const startDateTime = this.selectedTraining.start.clone().set({h: this.editStartTime.split(":")[0], m: this.editStartTime.split(":")[1]});
                const endDateTime = this.selectedTraining.end.clone().set({h: this.editEndTime.split(":")[0], m: this.editEndTime.split(":")[1]});
                const postData = {
                    'start': startDateTime.format(),
                    'end': endDateTime.format(),
                };
                const {data} = await this.$http.post('/trainingprepare/' + this.selectedTraining.id + '/updatetrainingtime', postData)
                if (data.status == 'ok') {
                    this.selectedTraining.start = startDateTime;
                    this.selectedTraining.end = endDateTime;
                    this.$emit("showSnackbar", "Zeiten aktualisiert", "success");
                }
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

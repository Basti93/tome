<template>
    <div>
        <v-card class="tp-upcoming-training white--text" :class="'ma-3'">
            <v-card-title>
                <h3>{{ moment(start).format('dddd [den] Do MMMM') }}&nbsp;({{moment(start).fromNow()}})</h3>
            </v-card-title>
            <v-divider></v-divider>
            <v-card-text class="tp-upcoming-training__text">
                <v-container grid-list-md>
                    <v-layout wrap>
                        <v-flex xs6>
                            <p class="font-weight-bold">Von {{moment(start).format('HH:mm')}} Uhr</p>
                            <p class="font-weight-bold">Bis {{moment(end).format('HH:mm')}} Uhr</p>
                        </v-flex>
                        <v-flex xs6 v-if="allowedToCheckIn()">
                            <v-btn
                                    color="primary"
                                    @click="currentUser ? participate() : showCookieUserLogin()"
                                    v-show="!attending">
                                Teilnehmen
                            </v-btn>
                            <v-btn
                                    color="error"
                                    @click="cancelParticipation()"
                                    v-show="attending">
                                Absagen
                            </v-btn>
                        </v-flex>
                        <v-flex xs12 md6 v-else>
                            <v-alert
                                    :value="true"
                                    type="info"
                                    outline
                                    pa-1
                                    ma-0
                                    class="caption"
                            >
                                Teilnehmen nicht möglich! Du bist keiner dieser Gruppen zugeordnet.
                            </v-alert>
                        </v-flex>
                        <v-flex xs12>
                            <p class="font-weight-bold">{{location}}</p>
                        </v-flex>
                    </v-layout>
                </v-container>
            </v-card-text>
        </v-card>
        <v-card :class="'ma-3'" v-show="comment">
            <v-card-title>
                <h3>Zusätzliche Informationen</h3>
            </v-card-title>
            <v-divider></v-divider>
            <v-card-text class="tp-upcoming-training__text">
                <v-alert
                        v-bind:value="comment"
                        type="info"
                        class="text-small"
                        pa-0
                        ma-0
                        outline
                >
                    {{comment}}
                </v-alert>
            </v-card-text>
        </v-card>
        <v-card :class="'ma-3'" v-show="contentIds.length">
            <v-card-title>
                <h3>Trainingsinhalte</h3>
            </v-card-title>
            <v-divider></v-divider>
            <v-card-text class="tp-upcoming-training__text">
                <TrainingContent
                        :contentIds="contentIds"
                        :initContentIds="contentIds"
                >
                </TrainingContent>
            </v-card-text>
        </v-card>
        <v-card :class="'ma-3'">
            <v-card-title>
                <h3>Gruppen</h3>
            </v-card-title>
            <v-divider></v-divider>
            <v-card-text class="tp-upcoming-training__text">
                <v-chip v-for="(item) in groups"
                        :key="item.id">{{item.name}}
                </v-chip>
            </v-card-text>
        </v-card>
        <v-dialog v-model="showCancelDialog" max-width="500px">
            <v-card>
                <v-card-title>
                    <span class="title">Kurzfristige Absage</span>
                </v-card-title>

                <v-card-text>
                    <v-form v-model="cancelDialogValid">
                    <v-container grid-list-md>
                        <v-layout wrap>
                            <v-flex xs12>
                                <v-alert
                                        :value="true"
                                        type="warning"
                                        outline
                                        pa-1
                                        ma-0
                                        class="caption"
                                >
                                    Das Training findet innerhalb der nächsten 24 Stunden statt. Bitte gib einen Grund für deine Absage an.
                                </v-alert>
                            </v-flex>
                            <v-flex xs12>
                                <v-textarea
                                        box
                                        label="Gib hier einen Grund an"
                                        required
                                        :rules="cancelReasonRules"
                                        v-model="cancelReason">

                                </v-textarea>
                                <v-spacer></v-spacer>
                                <v-btn
                                        :disabled="!cancelDialogValid"
                                        @click="cancelParticipation(cancelReason)"
                                        color="primary"
                                >
                                    Absage abschicken
                                </v-btn>
                            </v-flex>
                        </v-layout>
                    </v-container>
                    </v-form>
                </v-card-text>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
    import TrainingContent from "./TrainingContent";
    import User from "@/models/User";
    import {mapGetters} from 'vuex'

    export default {
        name: "TrainingCheckIn",
        components: {TrainingContent},
        //from parent
        props: {
            training: Object,
            currentUser: User,
            isCookieUser: Boolean,
            participantIds: Array,
        },
        data: function () {
            return {
                id: null,
                date: null,
                start: null,
                end: null,
                location: null,
                groups: [],
                contentIds: [],
                comment: null,
                showCancelDialog: false,
                cancelReason: null,
                cancelDialogValid: true,
                cancelReasonRules: [
                    v => !!v || 'Pflichtfeld'
                ],
            }
        },
        created() {
            this.id = this.training.id;
            this.date = this.moment(this.training.date).toDate();
            this.start = this.moment(this.training.start).toDate();
            this.end = this.moment(this.training.end).toDate();
            this.location = this.getLocationNameById(this.training.locationId);
            this.groups = this.getGroupsByIds(this.training.groupIds);
            this.contentIds = this.training.contentIds;
            this.comment = this.training.comment;
        },
        computed: {
            attending: function () {
                if (this.currentUser && this.participantIds) {
                    return this.participantIds.filter(pId => pId === this.currentUser.id).length > 0;
                }
                return false;
            },
            ...mapGetters('masterData', {
                getLocationNameById: 'getLocationNameById',
                getGroupsByIds: 'getGroupsByIds'
            }),
        },
        watch: {
            training: function () {
                this.id = this.training.id;
                this.date = this.moment(this.training.date).toDate();
                this.start = this.moment(this.training.start).toDate();
                this.end = this.moment(this.training.end).toDate();
                this.location = this.getLocationNameById(this.training.locationId);
                this.groups = this.getGroupsByIds(this.training.groupIds);
                this.contentIds = this.training.contentIds;
                this.comment = this.training.comment;

            },
        },
        methods: {
            allowedToCheckIn() {
                if (this.currentUser) {
                    return this.groups.map(g => g.id).filter(gId => gId === this.currentUser.groupId).length > 0;
                }
                return true;
            },
            participate() {
                if (this.isCookieUser) {
                    this.$http.post('training/' + this.id + '/checkinunregistered/' + this.currentUser.id)
                        .then(res => this.processCheckinRequest(res.data))
                } else {
                    this.$http.post('training/' + this.id + '/checkin/' + this.currentUser.id)
                        .then(res => this.processCheckinRequest(res.data))
                }
            },
            showCookieUserLogin() {
                this.$emit('showCookieUserLogin');
            },
            cancelParticipation(reason) {
                let url = 'training/' + this.id;
                if (this.isCookieUser) {
                    url += '/checkoutunregistered/';
                } else {
                    url += '/checkout/';
                }
                url += this.currentUser.id;
                if (reason) {
                    let data = {
                        reason: reason,
                    }
                    this.$http.post(url, data)
                        .then(res => this.processCheckoutRequest(res.data))
                } else {
                    this.$http.post(url)
                        .then(res => this.processCheckoutRequest(res.data))
                }

            },
            processCheckinRequest(data) {
                if (data.status === 'ok') {
                    this.$emit('checkedIn')
                }
            },
            processCheckoutRequest(data) {
                if (data.status === 'ok') {
                    this.cancelReason = null;
                    this.showCancelDialog = false;
                    this.$emit('checkedOut')
                } else if (data.status === 'cancel_needs_reason') {
                    this.showCancelDialog = true;
                }
            },
        },
    }
</script>

<style lang="scss" scoped>
    .tp-upcoming-training {
        .v-input {
            flex-grow: 0;
        }

        &__text {
            text-align: left;
        }
    }
</style>

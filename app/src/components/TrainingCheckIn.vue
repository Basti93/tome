<template>
  <div>
    <v-card class="tp-upcoming-training ma-1">
      <v-card-title class="justify-center">{{ start.format('dddd, Do MMMM') }}</v-card-title>
      <v-card-subtitle class="text-center">{{ start.fromNow() }}</v-card-subtitle>
      <v-divider></v-divider>
      <v-card-text class="text-center">
        <v-container>
          <v-row>
            <v-col>
              <span class="label text-small">Von</span>
              <v-chip size="small" class="ma-1" variant="outlined">
                <v-icon start color="primary">mdi-clock-outline</v-icon>
                {{ start.format('HH:mm') }}
              </v-chip>
              <span class="label text-small">bis</span>
              <v-chip size="small" class="ml-1" variant="outlined">
                <v-icon start color="primary">mdi-clock-outline</v-icon>
                {{ end.format('HH:mm') }}
              </v-chip>
            </v-col>
          </v-row>
          <v-row>
            <v-col>
              <v-chip size="small" class="ma-1" variant="outlined">
                <v-icon start color="primary">mdi-map-marker</v-icon>
                {{ location }}
              </v-chip>
            </v-col>
          </v-row>
          <v-row v-if="allowedToCheckIn()">
            <v-col>
              <v-btn
                  style="min-width: 150px"
                  elevation="1"
                  v-if="!attending || notYet"
                  color="primary"
                  class="ma-2"
                  @click="currentUser ? participate() : showCookieUserLogin()">
                Teilnehmen
                <v-icon end>mdi-thumb-up</v-icon>
              </v-btn>
              <v-btn
                  style="min-width: 150px"
                  v-if="attending || notYet"
                  color="red lighten-2"
                  class="ma-2"
                  @click="currentUser ? cancelParticipation() : showCookieUserLogin()" variant="outlined">
                <v-icon start>mdi-thumb-down</v-icon>
                Absagen
              </v-btn>
              <v-alert
                  v-if="notYet && automaticAttend"
                  type="info"
                  pa-1
                  ma-0
                  class="caption"
                  density="compact" variant="outlined">
                Du wirst 24 Stunden vor dem Training automatisch angemeldet.
              </v-alert>
            </v-col>
          </v-row>
          <v-row v-else>
            <v-col>
              <v-alert
                  type="warning"
                  pa-1
                  ma-0
                  class="caption"
                  density="compact" variant="outlined"
              >
                Teilnehmen nicht möglich. Du bist keiner dieser Gruppen zugeordnet. Frage einen Trainer bei Problemen.
              </v-alert>
            </v-col>
          </v-row>
          <v-row>
            <v-col>
              <h4>{{ participantCount }} Teilnehmer bis jetzt</h4>
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>
    </v-card>
    <v-card v-show="comment" class="ma-1">
      <v-card-text>
        <v-alert
            class="tp-upcoming-training__text ma-1"
            style="white-space: pre-line;word-break: break-all;"
            type="info"
            density="compact"
            variant="outlined"
        >
          <div v-html="urlify(comment)"></div>
        </v-alert>
      </v-card-text>
    </v-card>
    <v-card class="ma-1" v-show="contentIds.length">
      <v-card-title>
        <h6>Trainingsinhalte</h6>
      </v-card-title>
      <v-divider></v-divider>
      <v-card-text class="tp-upcoming-training__text">
        <v-container>
          <TrainingContent
              :contentIds="contentIds"
              :initContentIds="contentIds"
          >
          </TrainingContent>
        </v-container>
      </v-card-text>
    </v-card>
    <v-card class="ma-1">
      <v-card-title>
        <h6>Trainer</h6>
      </v-card-title>
      <v-divider></v-divider>
      <v-card-text class="tp-upcoming-training__text">
        <ProfileImage
            v-for="(item) in trainers"
            :key="item.id"
            class="ma-1"
            :firstName="item.firstName"
            :familyName="item.familyName"
            :imagePath="item.profileImageName"
            left
            clickable
        ></ProfileImage>
      </v-card-text>
    </v-card>
    <v-card class="ma-1">
      <v-card-title>
        <h6>Gruppen</h6>
      </v-card-title>
      <v-divider></v-divider>
      <v-card-text class="tp-upcoming-training__text">
        <GroupChip
            v-for="(item) in groups"
            :key="item.id"
            :group="item">
        </GroupChip>
      </v-card-text>
    </v-card>
    <v-dialog v-model="showCancelDialog" max-width="500px">
      <v-card>
        <v-toolbar flat>
          <v-btn icon @click="showCancelDialog=false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
          <v-toolbar-title>Kurzfristige Absage</v-toolbar-title>
          <v-spacer></v-spacer>
          <v-toolbar-items>
            <v-btn
                   color="primary"
                   @click="cancelParticipation(cancelReason)"
                   :disabled="!cancelDialogValid" variant="text">
              <v-icon start>mdi-check</v-icon>
              Absage abschicken
            </v-btn>
          </v-toolbar-items>
        </v-toolbar>
        <v-divider></v-divider>
        <v-card-text>
          <v-form v-model="cancelDialogValid">
            <v-container>
              <v-row>
                <v-col cols="12">
                  <v-alert
                      type="warning"
                      pa-1
                      ma-0
                      class="caption" variant="outlined"
                  >
                    Das Training findet innerhalb der nächsten 24 Stunden statt. Bitte gib einen Grund für deine Absage
                    an.
                  </v-alert>
                </v-col>
                <v-col cols="12">
                  <v-textarea
                      label="Gib hier einen Grund an"
                      required
                      :rules="cancelReasonRules"
                      v-model="cancelReason" variant="filled">

                  </v-textarea>
                </v-col>
              </v-row>
            </v-container>
          </v-form>
        </v-card-text>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import TrainingContent from './TrainingContent.vue'
import ProfileImage from './ProfileImage.vue'
import User from '../models/User'
import Training from '../models/Training'
import GroupChip from './GroupChip.vue'
import { useMasterDataStore } from '@/store/masterData'
import httpClient from '@/http/api'
import { analytics } from '@/services/analytics'

const props = defineProps<{
  training: Training
  currentUser?: User | null
  isCookieUser?: boolean
  participants?: any[]
}>()

const emit = defineEmits<{
  checkedIn: []
  checkedOut: []
  showCookieUserLogin: []
}>()

const store = useMasterDataStore()

const id = ref<any>(null)
const start = ref<any>(null)
const end = ref<any>(null)
const location = ref<any>(null)
const groups = ref<any[]>([])
const trainers = ref<any[]>([])
const contentIds = ref<any[]>([])
const comment = ref<string | null | undefined>(null)
const showCancelDialog = ref(false)
const cancelReason = ref<string | null>(null)
const cancelDialogValid = ref(true)
const cancelReasonRules = [(v: string) => !!v || 'Pflichtfeld']
const automaticAttend = ref<boolean | undefined>(false)

function loadTraining() {
  id.value = props.training.id
  start.value = props.training.start
  end.value = props.training.end
  location.value = store.getLocationNameById(props.training.locationId)
  groups.value = store.getGroupsByIds(props.training.groupIds)
  trainers.value = store.getSimpleTrainersByIds(props.training.trainerIds)
  contentIds.value = props.training.contentIds
  comment.value = props.training.comment
}

loadTraining()
automaticAttend.value = props.training.automaticAttend

watch(
  () => props.training,
  () => loadTraining()
)

const attending = computed(() => {
  if (props.currentUser && props.participants) {
    return props.participants.filter((p) => p.userId === props.currentUser!.id && p.attend).length > 0
  }
  return false
})

const notYet = computed(() => {
  if (props.currentUser && props.participants) {
    const found = props.participants.filter((p) => p.userId === props.currentUser!.id)
    return !found || found.length === 0 || found[0].attend === null
  }
  return false
})

const participantCount = computed(() =>
  props.training.participants ? props.training.participants.filter((p: any) => p.attend).length : null
)

function allowedToCheckIn() {
  if (props.currentUser) {
    if (props.currentUser.groupIds) {
      return groups.value.map((g) => g.id).filter((gId) => props.currentUser!.groupIds.includes(gId)).length > 0
    }
  }
  return true
}

function showCookieUserLogin() {
  emit('showCookieUserLogin')
}

async function participate() {
  try {
    let url = 'training/' + id.value
    if (props.isCookieUser) {
      url += '/checkinunregistered/' + props.currentUser!.id
    } else {
      url += '/checkin/' + props.currentUser!.id
    }
    //send post
    const { data } = await httpClient.post(url)
    if (data.status === 'ok') {
      // Track check-in event
      analytics.trackEvent('Training', 'CheckIn')
      emit('checkedIn')
    }
  } catch (error) {
    console.error(error)
  }
}

async function cancelParticipation(reason?: string | null) {
  try {
    let url = 'training/' + id.value
    if (props.isCookieUser) {
      url += '/checkoutunregistered/'
    } else {
      url += '/checkout/'
    }
    url += props.currentUser!.id
    let postData = {}
    if (reason) {
      postData = {
        reason: reason
      }
    }
    //send post
    const { data } = await httpClient.post(url, postData)
    if (data.status === 'ok') {
      // Track check-out event
      analytics.trackEvent('Training', 'CheckOut')
      cancelReason.value = null
      showCancelDialog.value = false
      emit('checkedOut')
    } else if (data.status === 'cancel_needs_reason') {
      showCancelDialog.value = true
    }
  } catch (error) {
    console.error(error)
  }
}

function urlify(text?: string | null) {
  if (text) {
    const urlRegex = /(https?:\/\/[^\s]+)/g
    return text.replace(urlRegex, function (url) {
      return '<a target="_blank" href="' + url + '">' + url + '</a>'
    })
  }
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

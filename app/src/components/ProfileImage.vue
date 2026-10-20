<template>
  <v-menu
      v-if="clickable"
      v-model="menu"
      location="bottom end"
      transition="scale-transition"
      origin="top left"
  >
    <template v-slot:activator="{ props }">
      <v-chip
          class="ma-1"
          v-bind="props" variant="outlined"
      >
        <v-avatar start size="28" class="profile-chip-avatar">
          <v-img v-if="imageUrl"
                 :src="imageUrl"
                 :alt="fullName"
          ></v-img>
          <v-icon v-else size="28">mdi-account-circle</v-icon>
        </v-avatar>
        {{ fullName }}
      </v-chip>
    </template>
    <v-card width="300">
      <v-list>
        <v-list-item>
          <template v-slot:prepend>
            <v-avatar size="60">
              <v-img v-if="imageUrl"
                     :src="imageUrl"
                     :alt="fullName"
              ></v-img>
              <v-icon v-else size="60">mdi-account-circle</v-icon>
            </v-avatar>
          </template>
          <v-list-item-title>{{ fullName }}</v-list-item-title>
          <v-list-item-subtitle>Trainer</v-list-item-subtitle>
          <template v-slot:append>
            <v-btn
                icon
                size="x-small"
                @click="menu = false"
            >
              <v-icon>mdi-close</v-icon>
            </v-btn>
          </template>
        </v-list-item>
      </v-list>
    </v-card>
  </v-menu>
  <v-avatar v-else :size="dSize" :class="{'mr-2': left, 'ml-2': right}">
    <v-img v-if="imageUrl"
           :src="imageUrl"
           :alt="fullName"
    ></v-img>
    <v-icon v-else :size="dSize">mdi-account-circle</v-icon>
  </v-avatar>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { profileImageUrl } from '@/helpers/profile-image'

const props = defineProps<{
  imagePath?: string | null
  firstName?: string
  familyName?: string
  clickable?: boolean
  size?: string
  right?: boolean
  left?: boolean
}>()

const menu = ref(false)
const dSize = computed(() => props.size || 24)
const imageUrl = computed(() => profileImageUrl(props.imagePath))
const fullName = computed(() => props.firstName + ' ' + props.familyName)
</script>

<style scoped>
/* Vuetify 4 `start` avatars sit flush with the chip edge: leave some air to the edge and to the name */
.profile-chip-avatar {
  margin-inline-start: 0;
  margin-inline-end: 8px;
}
</style>

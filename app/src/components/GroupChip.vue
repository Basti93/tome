<template>
  <v-menu
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
        <v-icon start :color="group.branch.colorHex">mdi-account-multiple</v-icon>
        {{ group.getWithBranchName() }}
      </v-chip>
    </template>
    <v-card width="400">
      <v-list>
        <v-list-item dense>
          <v-list-item-title>Trainer {{ group.branch.name }}</v-list-item-title>
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
        <v-list-item
            v-for="item in getSimpleTrainersByBranchId(group.branchId)"
            :key="item.id"
        >
          <template v-slot:prepend>
            <v-avatar size="small">
              <v-img
                  v-if="imageUrl(item.profileImageName)"
                  :src="imageUrl(item.profileImageName)"
              ></v-img>
              <v-icon v-else>mdi-account-circle</v-icon>
            </v-avatar>
          </template>
          {{ item.firstName + " " + item.familyName }}
        </v-list-item>
      </v-list>
    </v-card>
  </v-menu>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { profileImageUrl } from '@/helpers/profile-image'
import Group from '../models/Group'
import { useMasterDataStore } from '@/store/masterData'

defineProps<{
  group: Group
}>()

const masterData = useMasterDataStore()
const menu = ref(false)

function getSimpleTrainersByBranchId(branchId: number) {
  return masterData.getSimpleTrainersByBranchId(branchId)
}

function imageUrl(imagePath?: string | null) {
  return profileImageUrl(imagePath)
}
</script>


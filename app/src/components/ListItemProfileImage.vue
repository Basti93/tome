<template>
    <v-avatar :size="dSize">
        <v-img v-if="imageUrl" :src="imageUrl"></v-img>
        <v-icon v-else :size="dSize">mdi-account-circle</v-icon>
    </v-avatar>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { profileImageUrl } from '@/helpers/profile-image'

const props = defineProps<{
  imagePath?: string
  size?: string
}>()

const dSize = computed(() => props.size || 24)
const imageUrl = computed(() => profileImageUrl(props.imagePath))
</script>

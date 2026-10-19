<template>
    <v-avatar :size="dSize">
        <v-img v-if="imageUrl" :src="imageUrl"></v-img>
        <v-icon v-else :size="dSize">mdi-account-circle</v-icon>
    </v-avatar>
</template>

<script lang="ts">
import { profileImageUrl } from '@/helpers/profile-image'
    export default {
        name: "tome-list-item-profile-image",
        props: {
            imagePath: String,
            size: String,
        },
        data: function () {
            return {
                dSize: 24,
            }
        },
        created() {
          if (this.size) {
              this.dSize = this.size;
          }
        },
        computed: {
            imageUrl() {
                return profileImageUrl(this.imagePath);
            },
        },
    }
</script>

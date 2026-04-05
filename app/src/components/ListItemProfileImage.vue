<template>
    <v-avatar :size="dSize">
        <v-img v-if="imageUrl" :src="imageUrl"></v-img>
        <v-icon v-else :size="dSize">mdi-account-circle</v-icon>
    </v-avatar>
</template>

<script lang="ts">
    export default {
        name: "tome-list-item-profile-image",
        props: {
            imagePath: String,
            size: String,
        },
        data: function () {
            return {
                dSize: 24,
                serverUrl: import.meta.env.VITE_IMAGE_FOLDER_URL,
            }
        },
        created() {
          if (this.size) {
              this.dSize = this.size;
          }
        },
        computed: {
            imageUrl() {
                if (this.imagePath) {
                    return this.serverUrl + "/" + this.imagePath;
                }
                return null;
            },
        },
    }
</script>

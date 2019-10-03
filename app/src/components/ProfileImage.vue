<template>
    <v-avatar :size="dSize" :class="{'mr-2': left, 'ml-2': right}">
        <v-img class="tome-profile-image__image"
               v-if="imageUrl"
               :src="imageUrl"
               :alt="fullName"
        ></v-img>
        <v-icon v-else :size="dSize">account_circle</v-icon>
    </v-avatar>
</template>

<script lang="ts">
    import Vue from 'vue'
    import {mapGetters} from 'vuex'

    export default Vue.extend({
        name: "ProfileImage",
        props: {
            imagePath: String,
            firstName: String,
            familyName: String,
            size: String,
            right: Boolean,
            left: Boolean,
        },
        data: function () {
            return {
                dSize: 24,
                serverUrl: process.env.VUE_APP_IMAGE_FOLDER_URL,
            }
        },
        created() {
          if (this.size) {
              this.dSize = this.size;
          }
        },
        computed: {
            ...mapGetters({loggedInUser: 'loggedInUser'}),
            imageUrl() {
                if (this.imagePath) {
                    return this.serverUrl + "/" + this.imagePath;
                }
                return null;
            },
            fullName() {
                return this.firstName + " " + this.familyName;
            }
        },
    });
</script>

<style scoped lang="scss">
    .tome-profile-image {
        &__image {
        }
    }
</style>

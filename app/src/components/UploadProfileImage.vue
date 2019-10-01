<template>
    <div class="text-center">
        <v-avatar
                v-if="imageUrl"
                class="profile"
                color="grey"
                size="164"
                tile
        >
            <v-img :src="imageUrl"></v-img>
        </v-avatar>
            <v-avatar
                    v-else
                    size="128">
                <v-icon size="128">account_circle</v-icon>
            </v-avatar>
        <v-file-input
                :rules="imageRule"
                accept="image/png, image/jpeg, image/bmp"
                placeholder="Lade ein Profilbild hoch"
                prepend-icon="add_a_photo"
                show-size
                v-model="files"
                @change="imageChanged"
                label="Profilbild ändern"
        ></v-file-input>
    </div>
</template>

<script lang="ts">
    import Vue from 'vue'
    import {mapGetters} from 'vuex'

    export default Vue.extend({
        name: "UploadProfileImage",
        props: {
            imagePath: String,
        },
        data: function () {
            return {
                editImagePath: null,
                uploading: false,
                changeImage: false,
                files: [],
                serverUrl: process.env.VUE_APP_IMAGE_FOLDER_URL,
                imageRule: [
                    value => !value || value.size < 2000000 || 'Bildgröße muss kleiner wie 2 MB sein!',
                ],
            }
        },
        created() {
          this.editImagePath = this.imagePath;
        },
        computed: {
            ...mapGetters({loggedInUser: 'loggedInUser'}),
            imageUrl() {
                console.log(this.serverUrl);
                if ((this.files && this.files.size) || this.files.length > 0) {
                    return URL.createObjectURL(this.files);
                } else if (this.editImagePath && this.serverUrl) {
                    console.log(this.serverUrl + "/" + this.editImagePath)
                    return this.serverUrl + "/" + this.editImagePath;
                }
                return null;
            }
        },
        methods: {
             imageChanged() {
                 this.$emit('imageChanged', this.files)
            }
        }
    });
</script>

<style scoped>

</style>

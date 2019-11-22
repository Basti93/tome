<template>
    <div class="text-center">
        <div v-if="imageUrl">
            <v-avatar
                    style="position: relative"
                    class="profile"
                    color="grey"
                    size="164"
                    tile
            >
                <v-img :src="imageUrl"></v-img>
                <v-btn
                    style="right: -20px;"
                    absolute
                    bottom
                    small
                    fab
                    @click="removeImage"
                    color="error"
                    title="Profilbild löschen">
                    <v-icon>delete</v-icon>
                </v-btn>
            </v-avatar>

        </div>
        <v-avatar
                v-else
                size="128">
            <v-icon size="128">account_circle</v-icon>
        </v-avatar>


        <v-file-input
                class="mt-5"
                :rules="imageRule"
                accept="image/png, image/jpeg, image/bmp"
                placeholder="Profilbild ändern"
                prepend-icon="add_a_photo"
                show-size
                v-model="files"
                @change="imageChanged"
                label="Lade ein Profilbild hoch"
        >
        </v-file-input>
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
                    value => !value.size || value.size < 10000000 || 'Bildgröße muss kleiner wie 10 MB sein!',
                ],
            }
        },
        created() {
            this.editImagePath = this.imagePath;
        },
        computed: {
            ...mapGetters({loggedInUser: 'loggedInUser'}),
            imageUrl() {
                if ((this.files && this.files.size) || this.files.length > 0) {
                    return URL.createObjectURL(this.files);
                } else if (this.editImagePath && this.serverUrl) {
                    return this.serverUrl + "/" + this.editImagePath;
                }
                return null;
            }
        },
        methods: {
            removeImage() {
                this.resetLocalFile();
                this.editImagePath = null;
                this.$emit('imageRemoved')
            },
            imageChanged() {
                if (this.files && this.files.size) {
                    this.$emit('imageChanged', this.files)
                } else {
                    this.$emit('imageChanged', null)
                }
            },
            resetLocalFile() {
                this.files = null;
                this.files = [];
            }
        },
        watch: {
            imagePath: {
                immediate: true,
                handler(newVal) {
                    this.resetLocalFile();
                    this.editImagePath = newVal;
                },
            },
        }
    });
</script>

<style scoped>

</style>

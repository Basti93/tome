<template>
  <v-container>
    <v-row>
      <v-col
          cols="12"
          align="center">
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
            cols="12"
            size="128">
          <v-icon size="128">account_circle</v-icon>
        </v-avatar>
      </v-col>
      <v-col
          cols="12"
          v-if="imageUrl"
          align="center"
      >
        <v-btn
            @click="removeImage"
            color="error"
            title="Profilbild löschen">
          <v-icon left>delete</v-icon>
          Löschen
        </v-btn>
      </v-col>

      <v-file-input
          class="mt-5"
          :rules="imageRule"
          accept="image/png, image/jpeg, image/bmp"
          prepend-icon="add_a_photo"
          show-size
          v-model="files"
          @change="imageChanged"
          label="Lade ein Profilbild hoch"
      >
      </v-file-input>
    </v-row>
  </v-container>
</template>

<script lang="ts">
import { useAuthStore } from '@/store/auth'

export default {
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
      serverUrl: import.meta.env.VITE_IMAGE_FOLDER_URL,
      imageRule: [
        value => !value || !Array.isArray(value) || value.length === 0 || value[0].size < 10000000 || 'Bildgröße muss kleiner wie 10 MB sein!',
      ],
    }
  },
  created() {
    this.editImagePath = this.imagePath;
  },
  computed: {
    loggedInUser() {
      return useAuthStore().user
    },
    imageUrl() {
      if (this.files && Array.isArray(this.files) && this.files.length > 0) {
        return URL.createObjectURL(this.files[0]);
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
      if (this.files && Array.isArray(this.files) && this.files.length > 0) {
        this.$emit('imageChanged', this.files[0])
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
}
</script>

<style scoped>

</style>

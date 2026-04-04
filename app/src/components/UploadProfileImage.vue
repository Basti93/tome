<template>
  <v-container>
    <v-row>
      <v-col
          cols="12"
          align="center">
        <div class="avatar-container">
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
            <v-icon size="128">mdi-account-circle</v-icon>
          </v-avatar>
          <v-badge
              v-if="hasChanges"
              color="amber"
              icon="mdi-pencil"
              overlap
              bordered
          ></v-badge>
        </div>
      </v-col>
      <v-col
          cols="12"
          align="center"
      >
        <v-btn
            v-if="imageUrl"
            @click="removeImage"
            color="error"
            title="Profilbild löschen"
            size="small">
          <v-icon left>mdi-trash-can</v-icon>
          Löschen
        </v-btn>
      </v-col>

      <v-file-input
          ref="fileInput"
          class="mt-5"
          :rules="imageRule"
          accept="image/png, image/jpeg, image/bmp"
          prepend-icon="add_a_photo"
          show-size
          v-model="files"
          @change="imageChanged"
          @update:modelValue="imageChanged"
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
      let file = null;

      // Handle both single File object and array of Files
      if (this.files) {
        if (Array.isArray(this.files)) {
          file = this.files.length > 0 ? this.files[0] : null;
        } else if (this.files instanceof File) {
          file = this.files;
        }
      }

      if (file) {
        return URL.createObjectURL(file);
      } else if (this.editImagePath && this.serverUrl) {
        return this.serverUrl + "/" + this.editImagePath;
      }
      return null;
    },
    hasChanges() {
      if (!this.files) return false;
      if (Array.isArray(this.files)) {
        return this.files.length > 0;
      }
      return this.files instanceof File;
    }
  },
  methods: {
    removeImage() {
      this.resetLocalFile();
      this.editImagePath = null;
      this.$emit('imageRemoved')
    },
    imageChanged() {
      let file = null;

      // Handle both single File object and array of Files
      if (this.files) {
        if (Array.isArray(this.files)) {
          file = this.files.length > 0 ? this.files[0] : null;
        } else if (this.files instanceof File) {
          file = this.files;
        }
      }

      if (file) {
        this.$emit('imageChanged', file)
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
    }
  }
}
</script>

<style scoped>
.avatar-container {
  position: relative;
  display: inline-block;
}
</style>

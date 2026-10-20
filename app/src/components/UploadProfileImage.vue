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
          <v-icon start>mdi-trash-can</v-icon>
          Löschen
        </v-btn>
      </v-col>

      <v-file-input
          ref="fileInput"
          class="mt-5"
          :rules="imageRule"
          accept="image/png, image/jpeg, image/bmp"
          prepend-icon="mdi-camera-plus"
          show-size
          v-model="files"
          @update:modelValue="imageChanged"
          label="Lade ein Profilbild hoch"
      >
      </v-file-input>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { profileImageUrl } from '@/helpers/profile-image'

const props = defineProps<{
  imagePath?: string | null
}>()

const emit = defineEmits<{
  imageChanged: [file: File | null]
  imageRemoved: []
}>()

const editImagePath = ref<string | null | undefined>(props.imagePath)
const files = ref<File | File[] | null>([])
const imageRule = [
  (value: any) =>
    !value ||
    !Array.isArray(value) ||
    value.length === 0 ||
    value[0].size < 10000000 ||
    'Bildgröße muss kleiner wie 10 MB sein!'
]

// Handle both single File object and array of Files
function currentFile(): File | null {
  if (files.value) {
    if (Array.isArray(files.value)) {
      return files.value.length > 0 ? files.value[0] : null
    } else if (files.value instanceof File) {
      return files.value
    }
  }
  return null
}

const imageUrl = computed(() => {
  const file = currentFile()
  if (file) {
    return URL.createObjectURL(file)
  }
  return profileImageUrl(editImagePath.value)
})

const hasChanges = computed(() => {
  if (!files.value) return false
  if (Array.isArray(files.value)) {
    return files.value.length > 0
  }
  return files.value instanceof File
})

function resetLocalFile() {
  files.value = null
  files.value = []
}

function removeImage() {
  resetLocalFile()
  editImagePath.value = null
  emit('imageRemoved')
}

function imageChanged() {
  emit('imageChanged', currentFile())
}

watch(
  () => props.imagePath,
  (newVal) => {
    resetLocalFile()
    editImagePath.value = newVal
  },
  { immediate: true }
)
</script>

<style scoped>
.avatar-container {
  position: relative;
  display: inline-block;
}
</style>

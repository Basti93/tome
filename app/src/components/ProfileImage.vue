<template>
  <v-menu
      v-if="clickable"
      v-model="menu"
      location="bottom end"
      transition="scale-transition"
      origin="top left"
  >
    <template v-slot:activator="{ props }">
      <v-chip
          outlined
          class="ma-1"
          v-bind="props"
      >
        <v-avatar left>
          <v-img v-if="imageUrl"
                 :src="imageUrl"
                 :alt="fullName"
          ></v-img>
          <v-icon v-else>mdi-account-circle</v-icon>
        </v-avatar>
        {{ fullName }}
      </v-chip>
    </template>
    <v-card width="300">
      <v-list>
        <v-list-item>
          <template v-slot:prepend>
            <v-avatar size="60">
              <v-img v-if="imageUrl"
                     :src="imageUrl"
                     :alt="fullName"
              ></v-img>
              <v-icon v-else size="60">mdi-account-circle</v-icon>
            </v-avatar>
          </template>
          <v-list-item-title>{{ fullName }}</v-list-item-title>
          <v-list-item-subtitle>Trainer</v-list-item-subtitle>
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
      </v-list>
    </v-card>
  </v-menu>
  <v-avatar v-else :size="dSize" :class="{'mr-2': left, 'ml-2': right}">
    <v-img v-if="imageUrl"
           :src="imageUrl"
           :alt="fullName"
    ></v-img>
    <v-icon v-else :size="dSize">mdi-account-circle</v-icon>
  </v-avatar>
</template>

<script lang="ts">
export default {
  name: "ProfileImage",
  props: {
    imagePath: String,
    firstName: String,
    familyName: String,
    clickable: Boolean,
    size: String,
    right: Boolean,
    left: Boolean,
  },
  data: function () {
    return {
      dSize: 24,
      menu: false,
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
    fullName() {
      return this.firstName + " " + this.familyName;
    }
  },
}
</script>


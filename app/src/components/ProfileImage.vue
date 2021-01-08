<template>
  <v-menu
      v-if="clickable"
      v-model="menu"
      bottom
      right
      transition="scale-transition"
      origin="top left"
  >
    <template v-slot:activator="{ on }">
      <v-chip
          outlined
          class="ma-1"
          v-on="on"
      >
        <v-avatar left>
          <v-img
              :src="imageUrl"
              :alt="fullName"
          ></v-img>
        </v-avatar>
        {{ fullName }}
      </v-chip>
    </template>
    <v-card width="300">
      <v-list>
        <v-list-item>
          <v-list-item-avatar size="60">
            <v-img
                :src="imageUrl"
                :alt="fullName"
            ></v-img>
          </v-list-item-avatar>
          <v-list-item-content>
            <v-list-item-title>{{ fullName }}</v-list-item-title>
            <v-list-item-subtitle>Trainer</v-list-item-subtitle>
          </v-list-item-content>
          <v-list-item-action>
            <v-btn
                icon
                @click="menu = false"
            >
              <v-icon>close</v-icon>
            </v-btn>
          </v-list-item-action>
        </v-list-item>
      </v-list>
    </v-card>
  </v-menu>
  <v-avatar v-else :size="dSize" :class="{'mr-2': left, 'ml-2': right}">
    <v-img v-if="imageUrl"
           :src="imageUrl"
           :alt="fullName"
    ></v-img>
    <v-icon v-else :size="dSize">account_circle</v-icon>
  </v-avatar>
</template>

<script lang="ts">
import Vue from 'vue'

export default Vue.extend({
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
      serverUrl: process.env.VUE_APP_IMAGE_FOLDER_URL,
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
});
</script>


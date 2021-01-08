<template>
  <v-menu
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
        <v-icon left color="primary">group</v-icon>
        {{ group.getWithBranchName() }}
      </v-chip>
    </template>
    <v-card width="400">
      <v-list>
        <v-list-item dense>
          <v-list-item-content>
            <v-list-item-title>Trainer {{ group.branch.name }}</v-list-item-title>
            <v-list>
              <v-list-item
                  v-for="item in getSimpleTrainersByBranchId(group.branchId)"
                  :key="item.id"
              >
                <v-list-item-avatar left>
                  <v-img
                      :src="imageUrl(item.profileImageName)"
                  ></v-img>
                </v-list-item-avatar>
                {{ item.firstName + " " + item.familyName }}
              </v-list-item>
            </v-list>
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
</template>

<script lang="ts">
import Vue from 'vue'
import Group from "../models/Group";
import {mapGetters} from "vuex";

export default Vue.extend({
  name: "GroupChip",
  props: {
    group: Group,
  },
  data: function () {
    return {
      menu: false,
      serverUrl: process.env.VUE_APP_IMAGE_FOLDER_URL,
    }
  },
  computed: {
    ...mapGetters('masterData', {getSimpleTrainersByBranchId: 'getSimpleTrainersByBranchId'}),
  },
  methods: {
    imageUrl(imagePath) {
      if (imagePath) {
        return this.serverUrl + "/" + imagePath;
      }
      return null;
    },
  }
});
</script>


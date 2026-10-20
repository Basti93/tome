<template>
  <div class="theme-mode-setting">
    <div class="text-subtitle-2 mb-1">Darstellung</div>
    <v-radio-group :model-value="themeMode" inline hide-details @update:model-value="setThemeMode">
      <v-radio label="System" value="system"></v-radio>
      <v-radio label="Hell" value="light"></v-radio>
      <v-radio label="Dunkel" value="dark"></v-radio>
    </v-radio-group>
  </div>
</template>

<script setup lang="ts">
import { setThemeMode, themeMode } from '@/composables/themeMode'
</script>

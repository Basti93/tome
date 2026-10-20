<template>
  <v-snackbar
    :key="snackbar.count"
    location="bottom right"
    v-model="snackbar.visible"
    :timeout="snackbar.timeout"
    v-bind:color="snackbar.color">
    {{ snackbar.text }}
  </v-snackbar>
</template>
<script setup lang="ts">
import { useSnackbarStore } from '@/store/snackbar'

const snackbar = useSnackbarStore()
</script>

<template>
    <v-dialog
            v-model="show"
            max-width="600px"
            persistent>
        <v-card>
            <v-toolbar flat>
                <v-btn
                        icon
                        @click="show=false">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
                <v-toolbar-title>Passwort ändern</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-toolbar-items>
                    <v-btn color="primary" @click="save()" :disabled="!valid" variant="text">
                        <v-icon end>mdi-check</v-icon>
                        Speichern
                    </v-btn>
                </v-toolbar-items>
            </v-toolbar>

            <v-card-text>
                <v-form
                        ref="form"
                        v-model="valid">
                    <v-row>
                        <v-col cols="12">
                            <v-text-field
                                    type="password"
                                    :rules="passwordRules"
                                    v-model="password"
                                    prepend-icon="security"
                                    label="Passwort"
                                    required
                            ></v-text-field>

                            <v-text-field
                                    type="password"
                                    :rules="[ confirmPassword ]"
                                    v-model="passwordConfirm"
                                    prepend-icon="security"
                                    label="Passwort bestätigen"
                                    required
                            ></v-text-field>
                        </v-col>
                    </v-row>
                </v-form>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import httpClient from '@/http/api'
import { useSnackbarStore } from '@/store/snackbar'

const props = defineProps<{
  visible?: boolean
}>()

const emit = defineEmits<{
  close: []
}>()

const valid = ref(true)
const password = ref<string | null>(null)
const passwordConfirm = ref<string | null>(null)
const passwordRules = [(v: string) => !!v || 'Wird benötigt']

function reset() {
  password.value = null
  passwordConfirm.value = null
}

const show = computed({
  get: () => props.visible,
  set: (value) => {
    if (!value) {
      reset()
      emit('close')
    }
  }
})

function confirmPassword(confirmation: string) {
  if (!confirmation) {
    return 'Bitte passwort bestätigen'
  }
  if (password.value === confirmation) {
    return true
  } else {
    return 'Passwörter müssen identisch sein!'
  }
}

async function save() {
  try {
    const response = await httpClient.post('/user/me/changepassword', { password: password.value })
    if (response.data.status === 'ok') {
      useSnackbarStore().show('Passwort geändert', 'success')
      show.value = false
    } else {
      useSnackbarStore().show('Fehler beim Ändern des Passworts', 'error')
    }
  } catch {
    useSnackbarStore().show('Fehler beim Ändern des Passworts', 'error')
  }
}
</script>

<style scoped>

</style>

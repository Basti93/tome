<template>
  <v-container>
    <v-row
        align="center"
        justify="center"
    >
      <v-col cols="12" md="8">
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Passwort vergessen</v-toolbar-title>
          </v-toolbar>
          <v-divider></v-divider>
          <v-card-text>
            <v-card>
              <v-card-text>
                <v-text-field v-model="email"
                              prepend-icon="email"
                              label="E-Mail"
                              required></v-text-field>
                <v-btn :disabled="!email" v-on:click="resetPassword()">
                  Passwort zurücksetzen
                </v-btn>
              </v-card-text>
            </v-card>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>

</template>

<script lang="ts">
import Vue from "vue";

export default Vue.extend({
  name: "ResetPassword",
  created() {
    console.log(this.$route.params.resetToken)
  },
  data: function () {
    return {
      email: null,
    }
  },
  methods: {
    async resetPassword() {
      try {
        await this.$http.post('/auth/recovery', {email: this.email});
      } catch (e) {
        console.error(e)
      }
    }
  }
})
</script>

<style scoped>

</style>
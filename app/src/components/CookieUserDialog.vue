<template>
  <v-dialog
          v-model="show"
          max-width="500px"
          :propGroupId="groupId"
          :propUserId="userId"
          :propBranchId="branchId"
          persistent>
    <v-card>
      <v-toolbar flat>
        <v-btn icon @click="show=false">
          <v-icon>close</v-icon>
        </v-btn>
        <v-toolbar-title>Wer bist du?</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-toolbar-items>
          <v-btn text color="primary" @click="done"><v-icon left>check</v-icon>Auswählen</v-btn>
        </v-toolbar-items>
      </v-toolbar>
        <v-divider></v-divider>
        <v-card-text class="mt-2">
          <v-alert
                  type="info"
                  outlined
                  pa-1
                  ma-0
                  class="caption"
          >
            Falls du dich bereits registriert hast, melde dich an um an einem Training teilzunehmen.
          </v-alert>
          <v-select
            :items="$store.state.masterData.branches"
            item-text="name"
            item-value="id"
            v-model="branchId"
            v-on:change="branchSelect"
            clearable
            label="Sparte"
          ></v-select>
          <v-select
            :disabled="!branchId"
            :items="branchGroups"
            item-text="name"
            item-value="id"
            v-model="groupId"
            v-on:change="groupSelect"
            clearable
            label="Gruppe"
          ></v-select>
          <v-select
            :disabled="!groupId"
            :items="groupUsers"
            v-model="userId"
            :item-text="fullName"
            item-value="id"
            clearable
            label="Name"
          ></v-select>
        </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script>
  import User from "@/models/User";
  import { setCookie } from  "../helpers/cookie-helper"

  export default {
    name: "CookieUserDialog",
    props: ['visible', 'propUserId', 'propGroupId', 'propBranchId'],
    data() {
      return {
        branchGroups: [],
        groupUsers: [],
        userId: null,
        branchId: null,
        groupId: null,
      }
    },
    methods: {
      resetUserId: function () {
        this.userId = null;
      },
      branchSelect: function (id) {
        this.resetUserId();
        if (id) {
          this.branchGroups = [];
          this.$store.state.masterData.groups.forEach(function (item) {
            if (id === item.branchId) {
              this.branchGroups.push(item);
            }
          }.bind(this));
        }
      },
      groupSelect: function (id) {
        this.resetUserId();
        if (id) {
          this.loadUsersForGroup(id);
        }
      },
      loadUsersForGroup: function (groupId) {
        var self = this;
        this.$http.get('simpleuser?groupIds=' + groupId)
          .then(function(response) {
            let resData = response.data;
            if (resData) {
              for (let i = 0; i < resData.data.length; i++) {
                self.groupUsers.push(User.from(JSON.stringify(resData.data[i])));
              }
            }
          });
      },
      setCookie,
      fullName: item => item.firstName + ' ' + item.familyName,
      done: function () {
        if (this.userId) {
          this.setCookie('cookieUser', JSON.stringify(this.groupUsers.filter(u => u.id === this.userId)[0]));
          this.$emit('cookieUserChanged', this.userId)
          this.$emit('close')
        }
      },

    },
    watch: {
      propBranchId: function (id) {
        this.branchSelect(id);
      },
      propGroupId: function (id) {
        this.groupSelect(id);
      },
    },
    computed: {
      show: {
        get() {
          return this.visible;
        },
        set(value) {
          if (!value) {
            this.$emit('close')
          }
        }
      }
    }
  }
</script>

<style scoped>
  .v-dialog__container {
    display: unset;
  }
</style>

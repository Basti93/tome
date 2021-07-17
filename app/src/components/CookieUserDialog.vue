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
          <v-autocomplete
            :items="filteredUsers"
            v-model="userId"
            :item-text="fullName"
            item-value="id"
            clearable

            label="Name"
          ></v-autocomplete>
        </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script>
  import User from "@/models/User";
  import {mapGetters} from 'vuex'

  export default {
    name: "CookieUserDialog",
    props: ['visible', 'propUserId', 'propGroupId', 'propBranchId'],
    data() {
      return {
        branchGroups: [],
        users: [],
        filteredUsers: [],
        userId: null,
        branchId: null,
        groupId: null,
      }
    },
    created() {
      this.loadUsers();
    },
    computed: {
      ...mapGetters({cookieUser: 'cookieUser'}),
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
    },
    methods: {
      reset: function () {
        this.userId = null;
        this.filteredUsers = this.users;
      },
      branchSelect: function (id) {
        this.reset();
        this.branchGroups = [];
        this.$store.state.masterData.groups.forEach(function (item) {
          if (id === item.branchId) {
            this.branchGroups.push(item);
          }
        }.bind(this));
        this.filteredUsers = this.users.filter(u => this.branchGroups.map(g => g.id).some(r => u.groupIds.includes(r)))
      },
      groupSelect: function (id) {
        this.reset();
        this.filteredUsers = this.users.filter(u => u.groupIds.includes(id))
      },
      async loadUsers() {
        this.reset();
        let response = await this.$http.get('simpleuser');
        let resData = response.data;
        if (resData) {
          for (let i = 0; i < resData.data.length; i++) {
            this.users.push(User.from(JSON.stringify(resData.data[i])));
          }
          this.filteredUsers = this.users
        }
      },
      fullName: item => item.firstName + ' ' + item.familyName,
      done: function () {
        if (this.userId) {
          this.$store.dispatch('selectCookieUser', {cookieUser: JSON.stringify(this.filteredUsers.filter(u => u.id === this.userId)[0])})
          this.$emit('close')
        }
      },

    }
  }
</script>

<style scoped>
  .v-dialog__container {
    display: unset;
  }
</style>

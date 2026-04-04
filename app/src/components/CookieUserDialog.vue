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
          <v-icon>mdi-close</v-icon>
        </v-btn>
        <v-toolbar-title>Wer bist du?</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-toolbar-items>
          <v-btn text color="primary" @click="done"><v-icon left>mdi-check</v-icon>Auswählen</v-btn>
        </v-toolbar-items>
      </v-toolbar>
        <v-divider></v-divider>
        <v-card-text class="mt-2">
          <v-alert
                  type="info"
                  outlined
                  pa-1
                  ma-0
                  dense
                  class="caption"
          >
            Wähle deinen Namen aus um deine Trainings zu sehen.
          </v-alert>
          <v-select
            :items="branches"
            item-title="name"
            item-value="id"
            v-model="branchId"
            v-on:change="branchSelect"
            clearable
            label="Sparte"
          ></v-select>
          <v-select
            :disabled="!branchId"
            :items="branchGroups"
            item-title="name"
            item-value="id"
            v-model="groupId"
            v-on:change="groupSelect"
            clearable
            label="Gruppe"
          ></v-select>
          <v-autocomplete
            :items="filteredUsers"
            v-model="userId"
            :item-title="fullName"
            item-value="id"
            clearable

            label="Name"
          ></v-autocomplete>
        </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script>
  import { useMasterDataStore } from '@/store/masterData'
  import { useCookieAuthStore } from '@/store/cookieAuth'

  export default {
    name: "CookieUserDialog",
    props: ['visible', 'propUserId', 'propGroupId', 'propBranchId'],
    data() {
      return {
        branchGroups: [],
        filteredUsers: [],
        userId: null,
        branchId: null,
        groupId: null,
      }
    },
    created() {
      this.reset();
      this.filteredUsers = this.users
    },
    computed: {
      users() {
        return useMasterDataStore().simpleUsers
      },
      branches() {
        return useMasterDataStore().branches
      },
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
        const masterData = useMasterDataStore()
        masterData.groups.forEach(function (item) {
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
      fullName: item => item.firstName + ' ' + item.familyName,
      done: function () {
        if (this.userId) {
          useCookieAuthStore().selectCookieUser(JSON.stringify(this.filteredUsers.filter(u => u.id === this.userId)[0]))
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

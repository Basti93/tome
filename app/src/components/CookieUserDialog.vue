<template>
  <v-dialog v-model="show" max-width="500px" :propGroupId="groupId" :propUserId="userId" :propBranchId="branchId">
    <v-card>
      <v-card-title>
        <span class="title">Wer bist du?</span>
        <v-spacer></v-spacer>
        <v-card-text>
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
            :disabled="!groupId"
            :items="groupUsers"
            v-model="userId"
            :item-text="fullName"
            item-value="id"
            clearable
            label="Name"
          ></v-autocomplete>
        </v-card-text>
      </v-card-title>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn color="primary" @click="show=false">Abbrechen</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script>
  import User from "@/models/User";

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
      fullName: item => item.firstName + ' ' + item.familyName,
    },
    watch: {
      propBranchId: function (id) {
        this.branchSelect(id);
      },
      propGroupId: function (id) {
        this.groupSelect(id);
      },
      userId: function (id) {
        if (id) {
            this.setCookie('cookieUser', JSON.stringify(this.groupUsers.filter(u => u.id === this.userId)[0]));
            this.$emit('cookieUserChanged', id)
            this.$emit('close')
        }
      }
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

</style>

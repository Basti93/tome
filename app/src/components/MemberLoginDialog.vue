<template>
  <v-dialog v-model="show" max-width="500px" v-bind:loggedInGroupId="groupId" v-bind:loggedInMemberId="memberId">
    <v-card>
      <v-card-title>
        <span class="title">Turner Login</span>
        <v-spacer></v-spacer>
        <v-card-text>
          <v-select
            :items="$store.state.groups"
            item-text="name"
            item-value="id"
            v-model="loggedInGroupId"
            v-on:change="groupdIdChanged"
            clearable
            label="Gruppe"
          ></v-select>
          <v-autocomplete
            v-bind:disabled="!loggedInGroupId"
            v-bind:items="groupMembers"
            v-model="loggedInMemberId"
            item-text="firstName"
            item-value="id"
            clearable
            label="Wer bist du?"
          ></v-autocomplete>
        </v-card-text>
      </v-card-title>
      <v-card-actions>
        <v-btn color="primary" flat @click="show=false">Schließen</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script>
  export default {
    name: "MemberLoginDialog",
    props: ['visible', 'memberId', 'groupId'],
    data() {
      return {
        groupMembers: [],
        loggedInMemberId: null,
        loggedInGroupId: null,
      }
    },
    methods: {
      groupdIdChanged: function () {
        this.loggedInMemberId = null;
      },
      groupSelect: function (id) {
        if (id) {
          localStorage.setItem("groupId", id);
          this.groupMembers = [];
          this.$store.state.members.forEach(function (item) {
            if (id === item.groupId) {
              this.groupMembers.push(item);
            }
          }.bind(this));
        }
      },
      memberSelect: function (id) {
        if (id) {
          if (localStorage.getItem("memberId") !== id) {
            localStorage.setItem("memberId", id);
            this.$emit('memberIdChanged', id)
            this.$emit('close')
          }
        }
      }
    },
    watch: {
      loggedInGroupId: function (id) {
        this.groupSelect(id);
      },
      loggedInMemberId: function (id) {
        this.memberSelect(id);
      },
      visible: function() {
        if (this.show) {
          this.loggedInMemberId = this.memberId;
          this.loggedInGroupId = this.groupId;
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

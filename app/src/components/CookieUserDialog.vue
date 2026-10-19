<template>
  <v-dialog
          v-model="show"
          max-width="500px"
          persistent>
    <v-card>
      <v-toolbar flat>
        <v-btn icon @click="show=false">
          <v-icon>mdi-close</v-icon>
        </v-btn>
        <v-toolbar-title>Wer bist du?</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-toolbar-items>
          <v-btn color="primary" @click="done" variant="text"><v-icon start>mdi-check</v-icon>Auswählen</v-btn>
        </v-toolbar-items>
      </v-toolbar>
        <v-divider></v-divider>
        <v-card-text class="mt-2">
          <v-alert
                  type="info"
                  pa-1
                  ma-0
                  density="compact"
                  class="caption" variant="outlined"
          >
            Wähle deinen Namen aus um deine Trainings zu sehen.
          </v-alert>
          <v-select
            :items="branches"
            item-title="name"
            item-value="id"
            v-model="branchId"
            clearable
            label="Sparte"
          ></v-select>
          <v-select
            :disabled="!branchId"
            :items="branchGroups"
            item-title="name"
            item-value="id"
            v-model="groupId"
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
    emits: ['close'],
    data() {
      return {
        userId: null,
        branchId: null,
        groupId: null,
      }
    },
    created() {
      this.applyProps()
    },
    computed: {
      users() {
        return useMasterDataStore().simpleUsers
      },
      branches() {
        return useMasterDataStore().branches
      },
      // groups of the selected branch (none until a branch is selected)
      branchGroups() {
        if (!this.branchId) return []
        return useMasterDataStore().groups.filter(g => g.branchId === this.branchId)
      },
      // users narrowed down by the selected group, else by the selected branch
      filteredUsers() {
        if (this.groupId) {
          return this.users.filter(u => (u.groupIds || []).includes(this.groupId))
        }
        if (this.branchId) {
          const ids = this.branchGroups.map(g => g.id)
          return this.users.filter(u => (u.groupIds || []).some(id => ids.includes(id)))
        }
        return this.users
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
    watch: {
      // Vuetify 3/4 selects emit `update:modelValue` (never `change`), so react to the bound values
      branchId() {
        if (this.groupId && !this.branchGroups.some(g => g.id === this.groupId)) {
          this.groupId = null
        }
        this.dropUnavailableUser()
      },
      groupId() {
        this.dropUnavailableUser()
      },
      visible(value) {
        if (value) this.applyProps()
      },
    },
    methods: {
      dropUnavailableUser() {
        if (this.userId && !this.filteredUsers.some(u => u.id === this.userId)) {
          this.userId = null
        }
      },
      // preselect branch/group/user from the props (e.g. the previously chosen user) when (re)opening
      applyProps() {
        const masterData = useMasterDataStore()
        const user = this.propUserId ? this.users.find(u => u.id === this.propUserId) : null
        const groupId = this.propGroupId || null
        let branchId = this.propBranchId || null
        if (groupId && !branchId) {
          const group = masterData.groups.find(g => g.id === groupId)
          branchId = group ? group.branchId : null
        }
        this.branchId = branchId
        this.groupId = groupId
        this.userId = user ? user.id : null
      },
      fullName: item => item.firstName + ' ' + item.familyName,
      done: function () {
        if (this.userId) {
          useCookieAuthStore().selectCookieUser(JSON.stringify(this.users.find(u => u.id === this.userId)))
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

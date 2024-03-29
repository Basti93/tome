<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat extension-height="100">
            <v-toolbar-title>Benutzer</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-btn
                title="Liste nach Sparte und Gruppe filtern"
                color="primary"
                v-on:click="showFilterDialog = true">
              <v-icon left>filter_list</v-icon>
              Filtern
            </v-btn>

            <GroupsSelectDialog
                v-bind:visible="showFilterDialog"
                v-on:close="showFilterDialog = false"
                v-bind:groupIds="filterGroupIds"
                v-on:done="filterChanged">
            </GroupsSelectDialog>
            <template v-slot:extension>
              <v-container>
                <v-row no-gutters>
                  <v-col
                      class="text-right"
                      cols="6"
                      md="2"
                      v-for="(item) in filterGroups"
                      :key="item.id">
                    <v-chip
                        small
                        outlined
                        class="pl-2 pr-2 ml-2 mr-2">
                      <v-icon left color="primary">group</v-icon>
                      {{ item.getWithBranchName() }}
                    </v-chip>
                  </v-col>
                </v-row>
              </v-container>

              <v-btn
                  title="Vorläufigen Benutzer anlegen"
                  fab
                  absolute
                  bottom
                  left
                  elevation="2"
                  color="primary"
                  @click="editItem()">
                <v-icon>add</v-icon>
              </v-btn>
            </template>
          </v-toolbar>
          <v-divider></v-divider>
          <v-card-text class="mt-8 pa-0 pa-md-4">
            <v-card>
              <v-card-text class="pa-0 pa-md-4">
                <v-container>
                  <v-row>
                    <v-col>
                      <v-data-table
                          :headers="headers"
                          :items="users"
                          item-key="id"
                          :loading="loading"
                          :sort-desc.sync="sortDesc"
                          :server-items-length="total"
                          :footer-props="{
                                itemsPerPageOptions: rowsPerPageItems,
                            }"
                          :itemsPerPage.sync="itemsPerPage"
                          :page.sync="page"
                          :sort-by.sync="sortBy"
                          :search="searchText"
                      >
                        <template v-slot:top>
                          <v-text-field
                              v-model="searchText"
                              label="Suchen"
                              class="mx-4"
                              clearable
                          ></v-text-field>
                        </template>
                        <template v-slot:[`item.firstName`]="{ item }">
                          {{ item.firstName }}
                        </template>
                        <template v-slot:[`item.familyName`]="{ item }">
                          {{ item.familyName }}
                        </template>
                        <template v-slot:[`item.birthdate`]="{ item }">
                          {{
                            item.birthdate ? moment().diff(item.birthdate, 'years') + ' (Jg. ' + item.birthdate.format('YY') + ')' : 'NA'
                          }}
                        </template>
                        <template v-slot:[`item.groups`]="{ item }">
                          {{ getGroupsByIds(item.groupIds).map(g => g.getWithBranchName()).join(', ') }}
                        </template>
                        <template v-slot:[`item.active`]="{ item }">
                          {{ item.active ? 'Ja' : 'Nein' }}
                        </template>
                        <template v-slot:[`item.registered`]="{ item }">
                          {{ item.registered ? 'Ja' : 'Nein' }}
                        </template>
                        <template v-slot:[`item.action`]="{ item }">
                          <v-icon
                              class="mr-2"
                              v-if="canEditUser(item)"
                              @click="editItem(item)"
                              color="success">edit
                          </v-icon>
                          <v-icon
                              v-if="canDeleteUser(item)"
                              @click="confirmAndDelete(item)"
                              color="error">delete
                          </v-icon>
                        </template>
                        <template v-slot:no-data>
                          <v-container fluid>
                            <v-layout row justify-center>
                              <v-btn color="error" :disabled="loading" @click="reset()">
                                <v-icon left>cached</v-icon>
                                Keine Daten gefunden
                              </v-btn>
                            </v-layout>
                          </v-container>
                        </template>
                      </v-data-table>
                    </v-col>
                  </v-row>
                </v-container>
              </v-card-text>
            </v-card>

          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    <EditUserDialog
        v-bind:visible="showDialog"
        v-on:saved="loadData()"
        v-on:close="showDialog = false"
        :edit-user-id="editedItem.id"
        :edit-active="editedItem.active"
        :edit-birthdate="editedItem.birthdate"
        :edit-family-name="editedItem.familyName"
        :edit-group-ids="editedItem.groupIds"
        :edit-profile-image-name="editedItem.profileImageName"
        :edit-first-name="editedItem.firstName">
    </EditUserDialog>
    <ConfirmDialog
        :show="showConfirmDialog"
        action-text="Löschen"
        v-on:confirmed="deleteItem()"
        v-on:canceled="showConfirmDialog = false">
    </ConfirmDialog>
  </v-container>
</template>

<script lang="ts">
import {mapGetters} from 'vuex'
import GroupsSelectDialog from "../components/GroupsSelectDialog";
import User from "../models/User";
import EditUserDialog from "../components/EditUserDialog";
import {formatDate} from "../helpers/date-helpers"
import Group from "../models/Group";
import ConfirmDialog from "../components/ConfirmDialog.vue";

export default {
  name: "UsersTablePage",
  components: {ConfirmDialog, EditUserDialog, GroupsSelectDialog},
  data: function () {
    return {
      showDialog: false,
      showFilterDialog: false,
      showConfirmDialog: false,
      itemToDelete: null,
      filterBranchId: null,
      filterGroupIds: [],
      editGroups: [],
      loading: false,
      total: null,
      rowsPerPageItems: [5, 10, 20, 50, 100],
      page: 1,
      itemsPerPage: 10,
      sortBy: [],
      sortDesc: [],
      searchText: '',
      headers: [
        {text: 'Vorname', value: 'firstName', sortable: true},
        {text: 'Nachname', value: 'familyName', sortable: true},
        {text: 'Alter', value: 'birthdate', sortable: true},
        {text: 'Gruppen', value: 'groups', sortable: false},
        {text: 'Aktiv', value: 'active', sortable: true},
        {text: 'Registriert', value: 'registered', sortable: false},
        {text: '', value: 'action', sortable: false},
      ],
      users: [],
      editedItem: {
        id: null,
        firstName: null,
        familyName: null,
        birthdate: null,
        profileImageName: null,
        groupIds: [],
        isTrainer: false,
        active: false,
      },
      defaultItem: {
        id: null,
        firstName: '',
        familyName: '',
        birthdate: null,
        profileImageName: null,
        groupIds: [],
        isTrainer: false,
        active: true,
      },
      birthdateMenu: false,
    }
  },
  created() {
    if (this.trainerBranchIds && this.trainerBranchIds.length > 0) {
      this.filterBranchId = this.trainerBranchIds[0];
      this.filterGroupIds = this.getGroupsByBranchId(this.filterBranchId).map(g => g.id);
    }
    this.loadData();
  },
  computed: {
    ...mapGetters({loggedInUser: 'loggedInUser'}),
    ...mapGetters('masterData', {
      getGroupById: 'getGroupById',
      getGroupsByIds: 'getGroupsByIds',
      getGroupsByBranchId: 'getGroupsByBranchId',
      getBranchById: 'getBranchById',
      getBranchByGroupId: 'getBranchByGroupId'
    }),
    trainerBranchIds() {
      return this.loggedInUser.trainerBranchIds
    },
    filterGroups(): Array<Group> {
      if (this.filterGroupIds && this.filterGroupIds.length > 0) {
        let groups = this.getGroupsByIds(this.filterGroupIds);
        return groups
      } else if (this.filterBranchId) {
        let groups = this.getGroupsByBranchId(this.filterBranchId);
        return groups
      }
      return [];
    },
    birthdateFormatted() {
      return this.formatDate(this.editedItem.birthdate)
    },
  },
  methods: {
    filterChanged({branchId: branchId, groupdIds: groupIds}) {
      this.filterGroupIds = groupIds;
      this.filterBranchId = branchId;
      if (!this.filterGroupIds || this.filterGroupIds.length == 0) {
        this.filterGroupIds = this.getGroupsByBranchId(this.filterBranchId).map(g => g.id);
      }
      this.loadData();
    },
    async loadData() {
      this.loading = true;
      let url = null;
      // get by sort option
      if (this.sortBy[0]) {
        const direction = this.sortDesc[0] ? 'desc' : 'asc';
        url = '/user/sort?direction=' + direction + '&sortBy=' + this.sortBy[0] + '&page=' + this.page + '&per_page=' + this.itemsPerPage
      } else {
        url = '/user?page=' + this.page + '&per_page=' + this.itemsPerPage
      }
      if (this.filterGroupIds && this.filterGroupIds.length > 0) {
        url += '&groupIds=' + this.filterGroupIds;
      } else if (this.filterBranchId) {
        url += '&branchId=' + this.filterBranchId;
      }
      if (this.searchText && this.searchText.trim().length > 0) {
        url += '&searchText=' + this.searchText;
      }

      try {
        const request = await this.$http.get(url);
        this.dataLoaded(request)
      } catch (error) {
        console.error(error)
      } finally {
        this.loading = false
      }
    },
    dataLoaded(res) {
      this.users = [];
      const self = this;
      for (let i = 0; i < res.data.data.length; i++) {
        let userObj = res.data.data[i];
        self.users.push(new User(
            userObj.id,
            userObj.email,
            userObj.firstName,
            userObj.familyName,
            userObj.birthdate ? self.moment(userObj.birthdate, 'YYYY-MM-DDTHH:mm') : null,
            userObj.active === 1 ? true : false,
            userObj.groupIds,
            userObj.roleNames,
            userObj.trainerBranchIds,
            userObj.registered,
            userObj.profileImageName,
            userObj.absenceStart ? self.moment(userObj.absenceStart, 'YYYY-MM-DDTHH:mm') : null,
            userObj.absenceEnd ? self.moment(userObj.absenceStart, 'YYYY-MM-DDTHH:mm') : null,
            userObj.absenceReason
        ))
      }
      self.page = res.data.currentPage;
      self.total = res.data.total;
    },
    editItem(item) {
      if (item) {
        this.editedItem = {...item}
      } else {
        this.editedItem = {...this.defaultItem}
      }
      this.showDialog = true
    },
    canDeleteUser(item) {
      return this.loggedInUser.isAdmin && !item.isTrainer && !item.isAdmin
    },
    canEditUser(item) {
      return (this.loggedInUser.isAdmin || this.loggedInUser.isTrainer) && !item.isTrainer && !item.isAdmin
    },
    confirmAndDelete(item) {
      this.itemToDelete = item;
      this.showConfirmDialog = true;
    },
    async deleteItem() {
      this.showConfirmDialog = false;
      let response = await this.$http.delete('/user/' + this.itemToDelete.id);
      if (response.data.status === 'ok') {
        this.$emit("showSnackbar", "Benutzer " + this.itemToDelete.firstName + " " + this.itemToDelete.familyName + " erfolgreich gelöscht", "success")
        this.users.splice(this.users.indexOf(this.itemToDelete), 1)
      } else {
        this.$emit("showSnackbar", "Benutzer konnte nicht gelöscht werden", "error")
      }
    },
    reset() {
      this.filterGroupId = null;
      this.loadData();
    },
    formatDate,
  },
  watch: {
    searchText: {
      handler() {
        if (!this.loading) {
          this.loadData();
        }
      },
      deep: true,
    },
    page: {
      handler() {
        if (!this.loading) {
          this.loadData();
        }
      },
      deep: true,
    },
    itemsPerPage: {
      handler() {
        if (!this.loading) {
          this.loadData();
        }
      },
      deep: true,
    },
    sortBy: {
      handler() {
        if (!this.loading) {
          this.loadData();
        }
      },
      deep: true,
    },
    sortDesc: {
      handler() {
        if (!this.loading) {
          this.loadData();
        }
      },
      deep: true,
    },
    birthdateMenu(val) {
      val && setTimeout(() => (this.$refs.birthdatePicker.activePicker = 'YEAR'))
    },
  },
}
</script>

<style scoped>

</style>

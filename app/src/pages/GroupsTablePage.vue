<template>
  <v-container>
    <v-row>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Gruppen</v-toolbar-title>
            <template v-slot:extension>
              <v-dialog
                  v-model="showDialog"
                  :fullscreen="$vuetify.breakpoint.xsOnly"
                  persistent
                  max-width="1000px"
              >
                <template v-slot:activator="{ on, attrs }">
                  <v-btn
                      title="Neue Gruppe erstellen"
                      fab
                      absolute
                      bottom
                      left
                      elevation="2"
                      color="primary"
                      v-on="on"
                      v-bind="attrs"
                      v-on:click="createItem()">
                    <v-icon>add</v-icon>
                  </v-btn>
                </template>

                <v-card>
                  <v-toolbar flat>
                    <v-btn icon v-on:click="closeDialog()">
                      <v-icon>close</v-icon>
                    </v-btn>
                    <v-toolbar-title>{{ titleDialog }}</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-toolbar-items>
                      <v-btn text color="primary" v-on:click="save()">
                        <v-icon left>check</v-icon>
                        Speichern
                      </v-btn>
                    </v-toolbar-items>
                  </v-toolbar>
                  <v-divider class="pb-2"></v-divider>
                  <v-card-text>
                    <v-form
                        ref="form"
                        v-model="validDialog">
                      <v-container>
                        <v-row>
                          <v-col cols="12">

                            <v-text-field
                                v-model="editedItem.name"
                                label="Name"
                                required
                                :rules="[v => !!v || 'Wird benötigt']"
                                prepend-icon="groups"
                            ></v-text-field>
                          </v-col>
                          <v-col cols="12">
                            <v-select
                                :items="branches"
                                required
                                label="Sparte"
                                :rules="[v => !!v || 'Wird benötigt']"
                                v-model="editedItem.branchId"
                                :item-text="buildFullBranchname"
                                prepend-icon="bubble_chart"
                                item-value="id"
                            >

                            </v-select>
                          </v-col>
                          <v-col cols="12" md="6">
                            <v-autocomplete
                                :items="filteredUsers"
                                :item-text="fullName"
                                item-value="id"
                                prepend-icon="group"
                                v-model="selectedUserIds"
                                multiple
                                clearable
                                label="Mitglied auswählen"
                            >
                              <template v-slot:selection="{ item, index }">
                                <v-chip v-if="index < 3">
                                  <span>{{ item.firstName }}</span>
                                </v-chip>
                                <span
                                    v-if="index === 3"
                                    class="grey--text caption"
                                >
                                  (+{{ selectedUserIds.length - 3 }} weitere)
                                </span>
                              </template>
                            </v-autocomplete>
                            <v-btn
                                color="primary"
                                elevation="1"
                                v-on:click="addSelectedUsersToGroup()">
                              <v-icon left>add</v-icon>
                              Hinzufügen
                            </v-btn>
                          </v-col>
                          <v-col cols="12" md="6">
                            <v-list dense>
                              <v-list-item-title>{{ editedItem.userIds.length }} Mitglieder</v-list-item-title>
                              <v-list-item
                                  v-for="item in editedUsers"
                                  :key="item.id"
                              >
                                <tome-list-item-profile-image
                                    :image-path="item.profileImageName"
                                ></tome-list-item-profile-image>
                                {{ fullName(item) }}
                                <v-list-item-action>
                                  <v-btn
                                      icon
                                      v-on:click="removeUserFromGroup(item.id)">
                                    <v-icon>cancel</v-icon>
                                  </v-btn>

                                </v-list-item-action>
                              </v-list-item>
                            </v-list>
                          </v-col>
                        </v-row>
                      </v-container>
                    </v-form>
                  </v-card-text>
                </v-card>
              </v-dialog>
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
                          :items="groups"
                          item-key="id"
                          :loading="loading"
                          :server-items-length="total"
                          :footer-props="{
                                itemsPerPageOptions: rowsPerPageItems,
                            }"
                          :itemsPerPage.sync="itemsPerPage"
                          :page.sync="page"
                      >
                        <template v-slot:[`item.name`]="{ item }">
                          {{ item.name }}
                        </template>
                        <template v-slot:[`item.branchId`]="{ item }">
                          {{ item.branch.name }}
                        </template>
                        <template v-slot:[`item.userCount`]="{ item }">
                          {{ item.userIds.length }}
                        </template>
                        <template v-slot:[`item.action`]="{ item }">
                            <v-icon
                                class="mr-2"
                                v-on:click="editItem(item)"
                                color="success">edit</v-icon>
                            <v-icon
                                v-on:click="confirmAndDelete(item)"
                                color="error">delete</v-icon>
                        </template>
                        <template v-slot:no-data>
                          <v-container>
                            <v-row>
                              <v-col>
                                <v-btn color="error" :disabled="loading" v-on:click="loadData()">
                                  <v-icon left>cached</v-icon>
                                  Keine Daten gefunden
                                </v-btn>
                              </v-col>
                            </v-row>
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
    <ConfirmDialog
        :show="showConfirmDialog"
        action-text="Löschen"
        v-on:confirmed="deleteItem()"
        v-on:canceled="showConfirmDialog = false">
    </ConfirmDialog>
  </v-container>
</template>

<script lang="ts">
import Group from "../models/Group";
import {mapGetters, mapState} from "vuex";
import Branch from "../models/Branch";
import User from "../models/User"
import TomeListItemProfileImage from "../components/ListItemProfileImage.vue";
import ConfirmDialog from "../components/ConfirmDialog.vue";

export default {
  name: "GroupsTablePage",
  components: {ConfirmDialog, TomeListItemProfileImage},
  data: function () {
    return {
      loading: false,
      groups: [],
      users: [],
      filteredUsers: [],
      selectedUserIds: [],
      total: null,
      rowsPerPageItems: [5, 10, 20, 50, 100],
      page: 1,
      itemsPerPage: 10,
      headers: [
        {text: 'Name', value: 'name', sortable: false},
        {text: 'Sparte', value: 'branchId', sortable: false},
        {text: 'Mitglieder', value: 'userCount', sortable: false},
        {text: '', value: 'action', sortable: false},
      ],
      editedItem: {
        id: null,
        name: null,
        branchId: null,
        userIds: [],
      },
      defaultItem: {
        id: null,
        name: null,
        branchId: null,
        userIds: [],
      },
      showConfirmDialog: false,
      itemToDelete: null,
      showDialog: false,
      titleDialog: null,
      validDialog: false,
    }
  },
  created() {
    this.loadData();
  },
  computed: {
    ...mapGetters({loggedInUser: 'loggedInUser'}),
    ...mapGetters('masterData', {
      getBranchById: 'getBranchById',
    }),
    ...mapState('masterData', {
      branches: 'branches',
    }),
    editedUsers() {
      return this.users.filter(u => this.editedItem.userIds.indexOf(u.id) >= 0)
    },
  },
  methods: {
    async loadData() {
      this.loading = true
      try {
        await Promise.all([this.loadGroups(), this.loadUsers()]);
      } catch (error) {
        console.error(error)
      } finally {
        this.loading = false
      }
    },
    async loadGroups() {
      const {data} = await this.$http.get('/group')
      this.groups = [];
      for (let i = 0; i < data.data.length; i++) {
        let groupObj = data.data[i];
        this.groups.push(new Group(groupObj.id, groupObj.name, groupObj.branchId, this.getBranchById(groupObj.branchId), groupObj.userIds));
      }
      this.page = data.currentPage;
      this.total = data.total;
    },
    async loadUsers() {
      this.users = [];
      const userRes = await this.$http.get('/user');
      for (const userObj of userRes.data) {
        this.users.push(new User(
            userObj.id,
            userObj.email,
            userObj.firstName,
            userObj.familyName,
            userObj.birthdate ? this.moment(userObj.birthdate, 'YYYY-MM-DDTHH:mm') : null,
            userObj.active === 1 ? true : false,
            userObj.groupIds,
            userObj.roleNames,
            userObj.trainerBranchIds,
            userObj.registered,
            userObj.profileImageName,
            userObj.absenceStart ? this.moment(userObj.absenceStart, 'YYYY-MM-DDTHH:mm') : null,
            userObj.absenceEnd ? this.moment(userObj.absenceStart, 'YYYY-MM-DDTHH:mm') : null,
            userObj.absenceReason
        ));
      }
    },
    async save() {
      this.$refs.form.validate();
      try {
        let postData = {
          name: this.editedItem.name,
          branchId: this.editedItem.branchId,
          userIds: this.editedItem.userIds,
        }

        let response = null;
        if (this.editedItem.id) {
          response = await this.$http.put('/group/' + this.editedItem.id, postData);
        } else {
          response = await this.$http.post('/group', postData);
        }
        if (response && response.data.error) {
          this.$emit("showSnackbar", "Gruppe konnte nicht gespeichert werden", "error")
        } else {
          this.$emit("showSnackbar", "Gruppe gespeichert", "success")
          this.showDialog = false;
          await this.loadData();
          this.updateMasterData();
        }
      } catch (error) {
        console.error(error);
      }
    },
    createItem() {
      this.titleDialog = "Gruppe erstellen"
      this.showDialog = true;
      this.editedItem = {...this.defaultItem}
      this.selectedUserIds = [];
      this.filteredUsers = this.users.slice();
    },
    editItem(group: Group) {
      this.titleDialog = "Gruppe bearbeiten"
      this.showDialog = true;
      this.editedItem = {...group}
      this.selectedUserIds = [];
      this.filteredUsers = this.users.filter(u => !(this.editedItem.userIds.indexOf(u.id) >= 0));
    },
    confirmAndDelete(group: Group) {
      this.itemToDelete = group;
      this.showConfirmDialog = true;
    },
    async deleteItem() {
      this.showConfirmDialog = false
      let {data} = await this.$http.delete('/group/' + this.itemToDelete.id);
      await this.loadData();
      this.updateMasterData();

      if (data.status === 'ok') {
        this.$emit("showSnackbar", "Gruppe " + this.itemToDelete.name + " erfolgreich gelöscht", "success")
      } else {
        this.$emit("showSnackbar", "Gruppe konnte nicht gelöscht werden", "error")
      }
    },
    addSelectedUsersToGroup() {
      this.editedItem.userIds = this.editedItem.userIds .concat(this.selectedUserIds)
      this.filteredUsers = this.users.filter(u => !(this.editedItem.userIds.indexOf(u.id) >= 0));
      this.selectedUserIds = []
    },
    removeUserFromGroup(userIdToRemove) {
      this.editedItem.userIds = this.editedItem.userIds.filter(userId => userId !== userIdToRemove)
      this.filteredUsers.push(this.users.filter(u => u.id == userIdToRemove)[0])
    },
    closeDialog() {
      this.$refs.form.resetValidation();
      this.showDialog = false;
    },
    updateMasterData() {
      this.$store.commit('masterData/setGroups', this.groups);
    },
    buildFullBranchname(item: Branch) {
      return item.getShortNameAndName();
    },
    fullName: item => item.firstName + ' ' + item.familyName,
  }
}
</script>

<style scoped>

</style>
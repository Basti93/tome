<template>
    <v-layout align-top>
        <v-flex xs12 md10 offset-md1 top>
            <v-card>
                <v-toolbar flat>
                    <v-toolbar-title>Benutzerverwaltung</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-btn title="Vorläufigen Benutzer anlegen" color="primary" @click="editItem()">
                        <v-icon left>add_circle</v-icon>
                        Anlegen
                    </v-btn>
                    <v-spacer></v-spacer>
                    <div v-if="$vuetify.breakpoint.lgAndUp">
                        <v-chip
                                small
                                v-for="(item, index) in filterGroups"
                                :key="item.id"
                                class="ma-1">
                            <v-icon left color="primary">group</v-icon>
                            {{branchAndGroupName(item)}}
                        </v-chip>
                    </div>

                    <v-btn title="Liste nach Sparte und Gruppe filtern" icon color="primary" @click="showFilterDialog = true">
                        <v-icon>filter_list</v-icon>
                    </v-btn>
                    <GroupsSelectDialog
                            v-bind:visible="showFilterDialog"
                            v-on:close="showFilterDialog = false"
                            v-bind:groupIds="trainerGroupIds"
                            v-on:done="filterChanged">
                    </GroupsSelectDialog>
                </v-toolbar>
                <v-divider></v-divider>
                <v-card-text>
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
                        <template v-slot:item.firstName="{ item }">
                            {{ item.firstName }}
                        </template>
                        <template v-slot:item.familyName="{ item }">
                            {{ item.familyName }}
                        </template>
                        <template v-slot:item.groups="{ item }">
                            {{getGroupsByIds(item.groupIds).map(g => branchAndGroupName(g)).join(', ')}}
                        </template>
                        <template v-slot:item.active="{ item }">
                            {{ item.active ? 'Ja' : 'Nein' }}
                        </template>
                        <template v-slot:item.registered="{ item }">
                            {{ item.registered ? 'Nein' : 'Ja' }}
                        </template>
                        <template v-slot:item.action="{ item }">
                            <v-btn
                                    outlined
                                    v-if="canEditUser(item)"
                                    @click="editItem(item)"
                                    color="success">
                                <v-icon>edit</v-icon>
                            </v-btn>
                            <v-btn
                                    outlined
                                    class="ml-5"
                                    v-if="canDeleteUser(item)"
                                    @click="deleteItem(item)"
                                    color="error">
                                <v-icon>delete</v-icon>
                            </v-btn>
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
                </v-card-text>
            </v-card>
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
        </v-flex>
    </v-layout>
</template>

<script>
    import {mapGetters} from 'vuex'
    import GroupsSelectDialog from "../components/GroupsSelectDialog";
    import User from "../models/User";
    import EditUserDialog from "../components/EditUserDialog";
    import GroupsSelect from "../components/GroupsSelect";
    import {formatDate} from "../helpers/date-helpers"

    export default {
        name: "Users",
        components: {GroupsSelect, EditUserDialog, GroupsSelectDialog},
        data: function () {
            return {
                showDialog: false,
                showFilterDialog: false,
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
                    {text: 'Gruppen', value: 'groups', sortable: false},
                    {text: 'Aktiv', value: 'active', sortable: true},
                    {text: 'Vorläufiger Benutzer', value: 'registered', sortable: false},
                    {text: 'Actions', value: 'action', sortable: false },
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
            if (this.trainerGroupIds && this.trainerGroupIds.length > 0) {
                this.filterBranchId = this.getBranchByGroupId(this.trainerGroupIds[0]).id;
                let firstBranchId = this.getBranchByGroupId(this.trainerGroupIds[0]).id;
                this.filterGroupIds = this.getGroupsByBranchId(firstBranchId).map(g => g.id);
            }
            this.loadData();
        },
        computed: {
            ...mapGetters({loggedInUser: 'loggedInUser'}),
            ...mapGetters('masterData', {getGroupById: 'getGroupById', getGroupsByIds: 'getGroupsByIds', getGroupsByBranchId: 'getGroupsByBranchId', getBranchById: 'getBranchById', getBranchByGroupId: 'getBranchByGroupId'}),
            trainerGroupIds() {
                return this.loggedInUser.trainerGroupIds
            },
            filterGroups() {
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
                    self.users.push(new User({
                        id: userObj.id,
                        firstName: userObj.firstName,
                        familyName: userObj.familyName,
                        birthdate: userObj.birthdate ? self.moment(userObj.birthdate, 'YYYY-MM-DDTHH:mm').format('Y-MM-DD') : null,
                        active: userObj.active,
                        profileImageName: userObj.profileImageName,
                        groupIds: userObj.groupIds,
                        roleNames: userObj.roleNames,
                        registered: userObj.registered
                    }))
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
            async deleteItem(item) {
                if (confirm('Löschen bestätigen')) {
                    let response = await this.$http.delete('/user/' + item.id);
                    if (response.data.status === 'ok') {
                        this.$emit("showSnackbar", "Benutzer " + item.firstName + " " + item.familyName + " erfolgreich gelöscht", "success")
                        this.users.splice(this.users.indexOf(item), 1)
                    } else {
                        this.$emit("showSnackbar", "Benutzer konnte nicht gelöscht werden", "error")
                    }
                }
            },
            reset() {
                this.filterGroupId = null;
                this.loadData();
            },
            branchAndGroupName(item) {
                return this.getBranchById(item.branchId).shortName + '/' + item.name;
            },
            formatDate,
        },
        watch: {
            searchText: {
                handler () {
                    if (!this.loading) {
                        this.loadData();
                    }
                },
                deep: true,
            },
            page: {
                handler () {
                    if (!this.loading) {
                        this.loadData();
                    }
                },
                deep: true,
            },
            itemsPerPage: {
                handler () {
                    if (!this.loading) {
                        this.loadData();
                    }
                },
                deep: true,
            },
            sortBy: {
                handler () {
                    if (!this.loading) {
                        this.loadData();
                    }
                },
                deep: true,
            },
            sortDesc: {
                handler () {
                    if (!this.loading) {
                        this.loadData();
                    }
                },
                deep: true,
            },
            birthdateMenu (val) {
                val && setTimeout(() => (this.$refs.birthdatePicker.activePicker = 'YEAR'))
            },
        },
    }
</script>

<style scoped>

</style>

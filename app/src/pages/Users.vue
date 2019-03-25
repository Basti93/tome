<template>
    <v-layout align-top>
        <v-flex xs12 md10 offset-md1 top>
            <v-card>
                <v-toolbar card prominent>
                    <v-toolbar-title>Benutzerverwaltung</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <div v-if="$vuetify.breakpoint.lgAndUp">
                        <v-chip
                                small
                                v-for="(item, index) in filterGroups"
                                :key="item.id">{{item.name}}
                        </v-chip>
                    </div>

                    <v-btn title="Liste nach Sparte und Gruppe filtern" icon color="primary" @click="showFilterDialog = true">
                        <v-icon>filter_list</v-icon>
                    </v-btn>
                    <v-btn title="Vorläufigen Benutzer anlegen" color="primary" @click="showCreateDialog = true">
                        <v-icon left>add_circle</v-icon>
                        Benutzer anlegen
                    </v-btn>
                    <v-menu bottom left>
                        <v-btn
                                slot="activator"
                                dark
                                icon
                        >
                            <v-icon>more_vert</v-icon>
                        </v-btn>

                        <v-list>
                            <v-list-tile @click="onFilterUnassignedUsers">
                                <v-list-tile-title>Zeige Benutzer ohne Gruppe</v-list-tile-title>
                            </v-list-tile>
                        </v-list>
                    </v-menu>
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
                            :pagination.sync="pagination"
                            :total-items="total"
                            :rows-per-page-items="rowsPerPageItems"
                    >
                        <v-progress-linear slot="progress" color="primary" indeterminate></v-progress-linear>
                        <template slot="items" slot-scope="props">
                            <tr @click="editItem(props.item)" style="cursor: pointer">
                                <td>{{ props.item.firstName }}</td>
                                <td>{{ props.item.familyName }}</td>
                                <td>{{ props.item.active ? 'Ja' : 'Nein' }}</td>
                                <td>{{ props.item.registered ? 'Nein' : 'Ja' }}</td>
                                <td>
                                    <v-icon
                                            small
                                            v-if="loggedInUser.isAdmin && !props.item.isAdmin"
                                            @click="deleteItem(props.item)"
                                    >
                                        delete
                                    </v-icon>
                                </td>
                            </tr>
                        </template>

                        <template slot="no-data">
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
            <v-dialog v-model="dialog" max-width="1000px" :fullscreen="$vuetify.breakpoint.xsOnly" persistent>
                <v-card>
                    <v-card-title>
                        <span class="title">Benutzer Bearbeiten</span>
                    </v-card-title>

                    <v-card-text>
                        <v-container grid-list-md>
                            <v-layout wrap>
                                <v-flex xs12 sm6>
                                    <v-text-field
                                            v-model="editedItem.firstName"
                                            label="Vorname"
                                    ></v-text-field>
                                </v-flex>
                                <v-flex xs12 sm6>
                                    <v-text-field
                                            v-model="editedItem.familyName"
                                            label="Nachname"
                                    ></v-text-field>
                                </v-flex>
                                <!-- TODO: Geburtsdatum -->
                                <v-flex xs12>
                                    <GroupsSelect
                                            v-bind:groupIds="editedItem.groupIds"
                                            v-on:groupsChanged="editedItemGroupsChanged">
                                    </GroupsSelect>
                                </v-flex>
                                <v-flex xs12>
                                    <v-checkbox
                                            v-model="editedItem.active"
                                            label="Aktiv"
                                    ></v-checkbox>
                                </v-flex>
                            </v-layout>
                        </v-container>
                    </v-card-text>

                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="primary" @click="closeDialog" right><v-icon>close</v-icon>Abbrechen</v-btn>
                        <v-btn color="primary" @click="save" right><v-icon>save</v-icon>Speichern</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
            <CreateUnregistredUserDialog
                    v-bind:visible="showCreateDialog"
                    v-on:userCreated="fetchData()">
            </CreateUnregistredUserDialog>
        </v-flex>
    </v-layout>
</template>

<script>
    import {mapGetters} from 'vuex'
    import GroupsSelectDialog from "../components/GroupsSelectDialog";
    import User from "../models/User";
    import CreateUnregistredUserDialog from "../components/CreateUnregistredUserDialog";
    import GroupsSelect from "../components/GroupsSelect";

    export default {
        name: "Users",
        components: {GroupsSelect, CreateUnregistredUserDialog, GroupsSelectDialog},
        data: function () {
            return {
                dialog: false,
                showFilterDialog: false,
                showCreateDialog: false,
                filterBranchId: null,
                filterGroupIds: [],
                filterUnassignedUsers: false,
                editGroups: [],
                loading: false,
                total: null,
                rowsPerPageItems: [10, 20, 50, 100],
                pagination: {},
                headers: [
                    {text: 'Vorname', value: 'firstName'},
                    {text: 'Nachname', value: 'familyName'},
                    {text: 'Aktiv', value: 'active'},
                    {text: 'Vorläufiger Benutzer', value: 'registered'},
                ],
                users: [],
                editedId: null,
                editedItem: {
                    id: null,
                    firstName: '',
                    familyName: '',
                    groupIds: [],
                    isTrainer: false,
                    active: false,
                },
                defaultItem: {
                    id: null,
                    firstName: '',
                    familyName: '',
                    groupIds: [],
                    isTrainer: false,
                    active: false,
                },
            }
        },
        created() {
            this.filterGroupIds = this.trainerGroupIds;
        },
        computed: {
            ...mapGetters({loggedInUser: 'loggedInUser'}),
            ...mapGetters('masterData', {getGroupById: 'getGroupById', getGroupsByIds: 'getGroupsByIds', getGroupsByBranchId: 'getGroupsByBranchId'}),
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
        },
        watch: {
            pagination: {
                handler() {
                    if (!this.loading) {
                        this.fetchData();
                    }
                },
                deep: true
            },
            dialog(val) {
                val || this.closeDialog()
            },
        },
        methods: {
            filterChanged({branchId: branchId, groupdIds: groupIds}) {
                this.filterBranchId = branchId;
                this.filterGroupIds = groupIds;
                this.filterUnassignedUsers = false;
                this.getUsers();
            },
            fetchData() {
                this.loading = true;
                let url = null;
                // get by sort option
                if (this.pagination.sortBy) {
                    const direction = this.pagination.descending ? 'desc' : 'asc';
                    url = '/user/sort?direction=' + direction + '&sortBy=' + this.pagination.sortBy + '&page=' + this.pagination.page + '&per_page=' + this.pagination.rowsPerPage
                } else {
                    url = '/user?page=' + this.pagination.page + '&per_page=' + this.pagination.rowsPerPage
                }
                if (this.filterGroupIds && this.filterGroupIds.length > 0) {
                    url += '&groupIds=' + this.filterGroupIds;
                } else if (this.filterBranchId) {
                    url += '&branchId=' + this.filterBranchId;
                } else if (this.filterUnassignedUsers) {
                    url += '&unassigned=true';
                }
                this.$http.get(url)
                    .then(request => this.dataLoaded(request))
                    .catch(err => console.log(err))
                    .finally(() => this.loading = false);
            },
            dataLoaded(res) {
                this.users = [];
                for (let i = 0; i < res.data.data.length; i++) {
                    let userObj = res.data.data[i];
                    this.users.push(new User({
                        id: userObj.id,
                        firstName: userObj.firstName,
                        familyName: userObj.familyName,
                        active: userObj.active,
                        groupIds: userObj.groupIds,
                        roleNames: userObj.roleNames,
                        registered: userObj.registered
                    }))
                }
                this.pagination.page = res.data.currentPage;
                this.pagination.totalItems = res.data.total;
                this.total = res.data.total;
            },
            onFilterUnassignedUsers() {
                this.filterBranchId = null;
                this.filterGroupIds = [];
                this.filterUnassignedUsers = true;
                this.fetchData();
            },
            editItem(item) {
                if ((this.loggedInUser.isAdmin && !item.isAdmin) || (this.loggedInUser.isTrainer && !item.isTrainer && !item.isAdmin)) {
                    this.editedId = item.id
                    this.editedItem = Object.assign({}, item)
                    this.dialog = true
                } else {
                    this.$emit("showSnackbar", "Trainer und Admins können sich nur selbst bearbeiten.", "info")
                }
            },
            deleteItem(item) {
                if (confirm('Löschen bestätigen')) {
                    this.$http.delete('/user/' + item.id)
                        .then(this.userDeleted(item)).catch(function (err) {
                        console.log(err);
                        this.$emit("showSnackbar", "Benutzer konnte nicht gelöscht werden", "error")
                    })
                }
            },
            userDeleted(item) {
                this.$emit("showSnackbar", "Benutzer " + item.firstName + " " + item.familyName + " erfolgreich gelöscht", "success")
                this.users.splice(this.users.indexOf(item), 1)
            },
            editedItemGroupsChanged({groupIds}) {
                this.editedItem.groupIds = groupIds;
            },
            closeDialog() {
                this.dialog = false
                this.editedItem = {...this.defaultItem}
            },
            reset() {
                this.filterGroupId = null;
                this.fetchData();
            },
            save() {
                if (this.editedId) {
                    var self = this;

                    self.$http.put('/user/' + self.editedId, self.editedItem)
                        .then(function (res) {
                            if (!res.data.error) {
                                self.closeDialog()
                                self.$emit("showSnackbar", "Benutzer gespeichert", "success")
                                self.fetchData();
                            } else {
                                self.$emit("showSnackbar", "Benutzer konnte nicht gespeichert werden", "error")
                            }
                        })
                        .catch(function (err) {
                            console.log(err);
                            self.$emit("showSnackbar", "Benutzer konnte nicht gespeichert werden", "error")
                        })
                }
            },
            getGroupName: function (id) {
                if (id) {
                    return this.getGroupById(id).name;
                } else {
                    return "Keine"
                }
            }
        },
    }
</script>

<style scoped>

</style>

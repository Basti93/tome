<template>
    <v-layout align-top>
        <v-flex xs12 md10 offset-md1 top>
            <v-card>
                <v-toolbar card prominent>
                    <v-toolbar-title>Benutzerverwaltung</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-btn title="Vorläufigen Benutzer anlegen" color="primary" @click="showCreateDialog = true">
                        <v-icon left>add_circle</v-icon>
                        Anlegen
                    </v-btn>
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
                                <td>
                                    <v-chip v-for="(group) in getGroupsByIds(props.item.groupIds)"
                                            :key="group.id">
                                        {{ group.name }}
                                    </v-chip>


                                </td>
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
            <v-dialog
                    v-model="showDialog"
                      max-width="1000px"
                      :fullscreen="$vuetify.breakpoint.xsOnly"
                      persistent
            >
                <v-card>
                    <v-toolbar card>
                        <v-btn icon @click="closeDialog">
                            <v-icon>close</v-icon>
                        </v-btn>
                        <v-toolbar-title>Benutzer Bearbeiten</v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-toolbar-items>
                            <v-btn flat color="primary" @click="save">Speichern</v-btn>
                        </v-toolbar-items>
                    </v-toolbar>
                    <v-divider></v-divider>

                    <v-card-text>
                        <v-container grid-list-md>
                            <v-layout wrap>
                                <v-flex xs12 sm6>
                                    <v-text-field
                                            v-model="editedItem.firstName"
                                            label="Vorname"
                                            prepend-icon="account_circle"
                                    ></v-text-field>
                                </v-flex>
                                <v-flex xs12 sm6>
                                    <v-text-field
                                            v-model="editedItem.familyName"
                                            label="Nachname"
                                            prepend-icon="account_circle"
                                    ></v-text-field>
                                </v-flex>
                                <v-flex xs12 md6>
                                    <v-menu
                                            ref="birthdateMenu"
                                            :close-on-content-click="false"
                                            v-model="birthdateMenu"
                                            lazy
                                            full-width>
                                        <v-text-field
                                                slot="activator"
                                                v-model="birthdateFormatted"
                                                required
                                                label="Geburtsdatum"
                                                prepend-icon="event"
                                                readonly
                                        ></v-text-field>
                                        <v-date-picker v-model="editedItem.birthdate" @input="birthdateMenu = false"></v-date-picker>
                                    </v-menu>
                                </v-flex>
                                <v-flex xs12 md6>
                                    <GroupsSelect
                                            v-bind:groupIds="editedItem.groupIds"
                                            v-on:groupsChanged="editedItemGroupsChanged">
                                    </GroupsSelect>
                                </v-flex>
                                <v-flex xs12>
                                    <v-checkbox
                                            v-model="editedItem.active"
                                            label="Aktiv"
                                            prepend-icon="active"
                                    ></v-checkbox>
                                </v-flex>
                            </v-layout>
                        </v-container>
                    </v-card-text>
                </v-card>
            </v-dialog>
            <CreateUnregistredUserDialog
                    v-bind:visible="showCreateDialog"
                    v-on:userCreated="fetchData()"
                    v-on:close="showCreateDialog = false">
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
    import {formatDate} from "../helpers/date-helpers"

    export default {
        name: "Users",
        components: {GroupsSelect, CreateUnregistredUserDialog, GroupsSelectDialog},
        data: function () {
            return {
                showDialog: false,
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
                    {text: 'Vorname', value: 'firstName', sortable: true},
                    {text: 'Nachname', value: 'familyName', sortable: true},
                    {text: 'Gruppen', value: 'groups', sortable: false},
                    {text: 'Aktiv', value: 'active', sortable: true},
                    {text: 'Vorläufiger Benutzer', value: 'registered', sortable: false},
                ],
                users: [],
                editedId: null,
                editedItem: {
                    id: null,
                    firstName: null,
                    familyName: null,
                    birthdate: null,
                    groupIds: [],
                    isTrainer: false,
                    active: false,
                },
                defaultItem: {
                    id: null,
                    firstName: '',
                    familyName: '',
                    birthdate: null,
                    groupIds: [],
                    isTrainer: false,
                    active: false,
                },
                birthdateMenu: false,
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
            birthdateFormatted() {
                return this.formatDate(this.editedItem.birthdate)
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
        },
        methods: {
            filterChanged({branchId: branchId, groupdIds: groupIds}) {
                this.filterBranchId = branchId;
                this.filterGroupIds = groupIds;
                this.filterUnassignedUsers = false;
                this.fetchData();
            },
            async fetchData() {
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
                        birthdate: self.moment(userObj.birthdate, 'YYYY-MM-DDTHH:mm').format('Y-MM-DD'),
                        active: userObj.active,
                        groupIds: userObj.groupIds,
                        roleNames: userObj.roleNames,
                        registered: userObj.registered
                    }))
                }
                self.pagination.page = res.data.currentPage;
                self.pagination.totalItems = res.data.total;
                self.total = res.data.total;
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
                    this.editedItem = {...item}
                    this.showDialog = true
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
                this.showDialog = false
                this.editedItem = {...this.defaultItem}
            },
            reset() {
                this.filterGroupId = null;
                this.fetchData();
            },
            async save() {
                if (this.editedId) {
                    const self = this;
                    const postData = {firstName: self.editedItem.firstName, familyName: self.editedItem.familyName, birthdate: self.moment(self.editedItem.birthdate, 'YYYY-MM-DDTHH:mm').format("YYYY-MM-DDTHH:mm:ss"), groupIds: self.editedItem.groupIds, active: self.editedItem.active};
                    const {data} = await self.$http.put('/user/' + self.editedId, postData);
                    if (data.error) {
                        self.$emit("showSnackbar", "Benutzer konnte nicht gespeichert werden", "error")
                    } else {
                        self.closeDialog()
                        self.$emit("showSnackbar", "Benutzer gespeichert", "success")
                        self.fetchData();
                    }
                }
            },
            formatDate,
        },
    }
</script>

<style scoped>

</style>

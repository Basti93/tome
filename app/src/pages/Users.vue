<template>
    <v-layout align-top>
        <v-flex xs12 md10 offset-md1 top>
            <v-card>
                <v-toolbar flat>
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
                                :key="item.id">{{branchAndGroupName(item)}}
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
                            :server-items-length="totalItems"
                            :items-per-page.sync="itemsPerPage"
                            :page.sync="page"
                            :sort-by.sync="sortBy"
                            :expanded.sync="expanded"
                            :total-items="total"
                            :rows-per-page-items="rowsPerPageItems"
                            show-expand
                            single-expand
                    >
                        <v-progress-linear slot="progress" color="primary" indeterminate></v-progress-linear>
                        <template v-slot:item.firstName="{ item }">
                            {{ item.firstName }}
                        </template>
                        <template v-slot:item.familyName="{ item }">
                            {{ item.familyName }}
                        </template>
                        <template v-slot:item.groups="{ item }">
                            <v-chip v-for="(group) in getGroupsByIds(item.groupIds)"
                                    :key="group.id">
                                {{ group.name }}
                            </v-chip>
                        </template>
                        <template v-slot:item.active="{ item }">
                            {{ item.active ? 'Ja' : 'Nein' }}
                        </template>
                        <template v-slot:item.registered="{ item }">
                            {{ item.registered ? 'Nein' : 'Ja' }}
                        </template>
                        <template v-slot:expanded-item="{ headers }">
                            <td :colspan="headers.length">
                                <v-dialog
                                        v-model="showDialog"
                                        max-width="1000px"
                                        :fullscreen="$vuetify.breakpoint.xsOnly"
                                        persistent
                                >
                                    <template v-slot:activator="{ on }">
                                        <v-btn
                                                outlined
                                                v-if="loggedInUser.isAdmin || loggedInUser.isTrainer"
                                                @click="editItem()"
                                                color="success">
                                            <v-icon>edit</v-icon>
                                        </v-btn>
                                    </template>
                                    <v-card>
                                        <v-toolbar flat>
                                            <v-btn icon @click="closeDialog">
                                                <v-icon>close</v-icon>
                                            </v-btn>
                                            <v-toolbar-title>Benutzer Bearbeiten</v-toolbar-title>
                                            <v-spacer></v-spacer>
                                            <v-toolbar-items>
                                                <v-btn text color="primary" @click="save">Speichern</v-btn>
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
                                                            <template v-slot:activator="{ on }">
                                                                <v-text-field
                                                                        slot="activator"
                                                                        v-model="birthdateFormatted"
                                                                        required
                                                                        label="Geburtsdatum"
                                                                        prepend-icon="event"
                                                                        readonly
                                                                        v-on="on"
                                                                ></v-text-field>
                                                            </template>
                                                            <v-date-picker
                                                                    ref="birthdatePicker"
                                                                    v-model="editedItem.birthdate"
                                                                    @input="birthdateMenu = false"
                                                                    :max="new Date().toISOString().substr(0, 10)"
                                                                    min="1950-01-01">
                                                            </v-date-picker>
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
                                <v-btn
                                        outlined
                                        class="ml-5"
                                        v-if="canDeleteUser"
                                        @click="deleteItem()"
                                        color="error">
                                    <v-icon>delete</v-icon>
                                </v-btn>
                            </td>
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
            <CreateUnregistredUserDialog
                    v-bind:visible="showCreateDialog"
                    v-on:userCreated="loadData()"
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
                editGroups: [],
                loading: false,
                total: null,
                rowsPerPageItems: [5, 10, 20, 50, 100],
                totalItems: 0,
                page: 1,
                itemsPerPage: 10,
                sortBy: null,
                sortDesc: null,
                expanded: [],
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
                if (this.sortBy) {
                    const direction = this.sortDesc ? 'desc' : 'asc';
                    url = '/user/sort?direction=' + direction + '&sortBy=' + this.sortBy + '&page=' + this.page + '&per_page=' + this.itemsPerPage
                } else {
                    url = '/user?page=' + this.page + '&per_page=' + this.itemsPerPage
                }
                if (this.filterGroupIds && this.filterGroupIds.length > 0) {
                    url += '&groupIds=' + this.filterGroupIds;
                } else if (this.filterBranchId) {
                    url += '&branchId=' + this.filterBranchId;
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
                self.page = res.data.currentPage;
                self.totalItems = res.data.total;
                self.total = res.data.total;
            },
            editItem() {
                const item = this.expanded[0];
                if (this.loggedInUser.isAdmin &&  this.loggedInUser.isTrainer && !item.isTrainer && !item.isAdmin) {
                    this.editedId = item.id
                    this.editedItem = {...item}
                    this.showDialog = true
                } else {
                    this.$emit("showSnackbar", "Trainer und Admins können sich nur selbst bearbeiten.", "info")
                }
            },
            canDeleteUser() {
              return this.loggedInUser.isAdmin && !this.expanded[0].isTrainer && !this.expanded[0].isAdmin
            },
            async deleteItem() {
                if (confirm('Löschen bestätigen')) {
                    const item = this.expanded[0];
                    let response = await this.$http.delete('/user/' + item.id);
                    if (response.data.status === 'ok') {
                        this.$emit("showSnackbar", "Benutzer " + item.firstName + " " + item.familyName + " erfolgreich gelöscht", "success")
                        this.users.splice(this.users.indexOf(item), 1)
                    } else {
                        this.$emit("showSnackbar", "Benutzer konnte nicht gelöscht werden", "error")
                    }
                }
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
                this.loadData();
            },
            async save() {
                if (this.editedId) {
                    const postData = {firstName: this.editedItem.firstName, familyName: this.editedItem.familyName, birthdate: this.moment(this.editedItem.birthdate, 'YYYY-MM-DDTHH:mm').format("YYYY-MM-DDTHH:mm:ss"), groupIds: this.editedItem.groupIds, active: this.editedItem.active};
                    const {data} = await this.$http.put('/user/' + this.editedId, postData);
                    if (data.error) {
                        this.$emit("showSnackbar", "Benutzer konnte nicht gespeichert werden", "error")
                    } else {
                        this.closeDialog()
                        this.$emit("showSnackbar", "Benutzer gespeichert", "success")
                        this.loadData();
                    }
                }
            },
            branchAndGroupName(item) {
                return this.getBranchById(item.branchId).shortName + '/' + item.name;
            },
            formatDate,
        },
        watch: {
            page: {
                handler () {
                    if (!this.loading) {
                        this.loadData();
                    }
                },
                deep: true,
            },
            itemPerPage: {
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

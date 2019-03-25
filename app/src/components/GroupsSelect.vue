<template>
    <div>
        <v-autocomplete
                v-bind:items="groups"
                v-model="selectedGroupIds"
                :item-text="branchAndGroupName"
                item-value="id"
                multiple
                clearable
                chips
                deletable-chips
                label="Gruppen"
                prepend-icon="group"
        >
        </v-autocomplete>
    </div>
</template>

<script>
    import {mapGetters, mapState} from 'vuex'

    export default {

        name: "GroupsSelect",
        props: {
            'groupIds': Array,
        },
        data() {
            return {
                selectedGroupIds: [],
            }
        },
        computed: {
            ...mapGetters('masterData', {getBranchById: 'getBranchById'}),
            ...mapState('masterData', {
                groups: 'groups',
            }),
        },
        methods: {
            branchAndGroupName(item) {
               return this.getBranchById(item.branchId).name + ' / ' + item.name;
            }
        },
        watch: {
            groupIds: function () {
                if (this.groupIds) {
                    this.selectedGroupIds = this.groupIds;
                }
            },
            selectedGroupIds: function () {
                this.$emit('groupsChanged', {groupIds: this.selectedGroupIds})
            },
        }
    }
</script>

<style scoped>

</style>

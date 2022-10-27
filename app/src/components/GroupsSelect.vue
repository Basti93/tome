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

<script lang="ts">
    import {mapGetters, mapState} from 'vuex'
    import Group from "../models/Group";

    export default {

        name: "GroupsSelect",
        props: {
            groupIds: Array,
        },
        data() {
            return {
                selectedGroupIds: [],
            }
        },
        created() {

        },
        computed: {
            ...mapGetters('masterData', {getBranchById: 'getBranchById'}),
            ...mapState('masterData', {
                groups: 'groups',
            }),
        },
        methods: {
            branchAndGroupName(item: Group) {
               return item.getWithBranchName();
            }
        },
        watch: {
            groupIds: {
                immediate: true,
                    handler(newVal) {
                        if (newVal) {
                            this.selectedGroupIds = newVal;
                        }
                },

            },
            selectedGroupIds: function () {
                this.$emit('groupsChanged', {groupIds: this.selectedGroupIds})
            },
        }
    }
</script>

<style scoped>

</style>

<template>
    <div>
        <v-autocomplete
                v-bind:items="groups"
                v-model="selectedGroupIds"
                :item-title="branchAndGroupName"
                item-value="id"
                multiple
                clearable
                chips
                closable-chips
                label="Gruppen"
                prepend-icon="group"
        >
        </v-autocomplete>
    </div>
</template>

<script lang="ts">
    import Group from "../models/Group";
    import { useMasterDataStore } from '@/store/masterData'

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
        computed: {
            groups() {
                return useMasterDataStore().groups
            },
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

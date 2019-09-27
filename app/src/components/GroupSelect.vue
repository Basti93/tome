<template>
    <v-card flat>
        <v-select
          v-bind:items="branches"
          item-text="name"
          item-value="id"
          v-model="selectedBranchId"
          clearable
          single-line
          label="Sparte"
          prepend-icon="bubble_chart"
        ></v-select>
        <v-select
          v-bind:disabled="!selectedBranchId"
          v-bind:items="groupItems"
          v-model="selectedGroupId"
          item-text="name"
          item-value="id"
          clearable
          label="Gruppe"
          prepend-icon="group"
        ></v-select>
    </v-card>
</template>

<script>
  import {mapGetters, mapState} from 'vuex'

  export default {

    name: "GroupSelect",
    props: {
      'branchId': Number,
      'groupId': Number,
    },
    data() {
      return {
        selectedBranchId: null,
        selectedGroupId: null,
        groupItems: [],
      }
    },
    computed: {
      ...mapGetters('masterData', {getBranchByGroupId: 'getBranchByGroupId'}),
      ...mapState('masterData', {
        groups: 'groups',
        branches: 'branches',
      }),
    },
    methods: {
      fillGroupSelect: function () {
        this.groupItems = [];
        for (const group of this.groups) {
            if (this.selectedBranchId === group.branchId) {
                this.groupItems.push(group);
            }
        }
      },
    },
    watch: {
      groupId: function () {
        this.selectedGroupId = this.groupId;
      },
      branchId: function () {
        this.selectedBranchId = this.branchId;
      },
      selectedBranchId: function () {
        if (this.selectedBranchId) {
          this.fillGroupSelect();
        } else {
          this.selectedGroupId = null;
          this.groupItems = []
        }
        this.$emit('branchSelected', this.selectedBranchId)
        this.$emit('groupSelected', this.selectedGroupId)
      },
      selectedGroupId: function () {
        this.$emit('groupSelected', this.selectedGroupId)
      },
    }
  }
</script>

<style scoped>

</style>

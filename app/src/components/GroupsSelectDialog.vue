<template>
  <v-dialog v-model="show" max-width="600px" :fullscreen="$vuetify.breakpoint.xsOnly" persistent>
    <v-card>
      <v-toolbar flat>
        <v-btn icon @click="show=false">
          <v-icon>close</v-icon>
        </v-btn>
        <v-toolbar-title>Filter ändern</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-toolbar-items>
          <v-btn text color="primary" v-on:click="done" :disabled="!valid"><v-icon left>done</v-icon>Auswählen</v-btn>
        </v-toolbar-items>
      </v-toolbar>
      <v-divider></v-divider>
      <v-card-text>
        <v-form
          v-model="valid">
        <v-select
          v-bind:items="branches"
          item-text="name"
          item-value="id"
          v-model="selectedBranchId"
          clearable
          label="Sparte"
          prepend-icon="bubble_chart"
        ></v-select>
        <v-select
          v-bind:disabled="!selectedBranchId"
          v-bind:items="groupItems"
          v-model="selectedGroupIds"
          item-text="name"
          item-value="id"
          multiple
          clearable
          chips
          deletable-chips
          label="Gruppen"
          prepend-icon="group"
        ></v-select>
        </v-form>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script>
  import {mapGetters, mapState} from 'vuex'

  export default {

    name: "GroupsSelectDialog",
    props: {
      'visible': Boolean,
      'groupIds': Array,
    },
    created() {
      this.initSelect()
    },
    data() {
      return {
        valid: true,
        selectedBranchId: null,
        selectedGroupIds: [],
        groupItems: [],
      }
    },
    computed: {
      ...mapGetters('masterData', {getBranchByGroupId: 'getBranchByGroupId'}),
      ...mapState('masterData', {
        groups: 'groups',
        branches: 'branches',
      }),
      show: {
        get() {
          return this.visible;
        },
        set(value) {
          if (!value) {
            this.$emit('close')
          }
        }
      }
    },
    methods: {
      fillGroupSelect: function () {
        this.groupItems = [];
        this.selectedGroupIds = [];
        this.groups.forEach(function (item) {
          if (this.selectedBranchId === item.branchId) {
            this.groupItems.push(item);
          }
        }.bind(this));
      },
      initSelect: function () {
        this.selectedGroupIds = this.groupIds;
        if (this.groupIds.length > 0) {
          this.selectedBranchId = this.getBranchByGroupId(this.groupIds[0]).id;
        }
      },
      done: function() {
        this.$emit('done', {branchId: this.selectedBranchId, groupdIds: this.selectedGroupIds})
        this.show = false;
      },
    },
    watch: {
      selectedBranchId: function () {
        if (this.selectedBranchId) {
          this.fillGroupSelect();
        } else {
          this.selectedGroupIds = null;
          this.groupItems = []
        }
      },
      branchId: function () {
        this.initSelect();
      },
    }
  }
</script>

<style scoped>

</style>

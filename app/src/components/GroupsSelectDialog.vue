<template>
  <v-dialog v-model="show" max-width="500px" :fullscreen="$vuetify.breakpoint.xsOnly">
    <v-card>
      <v-card-title>
        <span class="title">Filter ändern</span>
      </v-card-title>
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
          required
          :rules="branchRules"
        ></v-select>
        <v-autocomplete
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
        ></v-autocomplete>
        </v-form>
      </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn color="primary" flat @click="done()" :disabled="!valid"><v-icon>done</v-icon></v-btn>
        <v-btn color="primary" flat @click="show=false"><v-icon>close</v-icon></v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script>
  import {mapGetters, mapState} from 'vuex'

  export default {

    name: "GroupsSelectDialog",
    props: {
      'visible': false,
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
        branchRules: [
          v => !!v || 'Pflichtfeld'
        ],
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

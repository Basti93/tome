<template>
  <v-layout justify-start row pt-2 pb-2 class="tp-training-content" :class="{'tp-training-content--selectable' : selectable}">
    <div class="tp-training-content__icon"
         v-for="(item, index) in contents"
         :key="item.id"
         style=""
         v-bind:style="[ isSelected(item, selectedContentIds) ? {'fill': '#60cc69'} : {}]"
         @click="selectContent(item)"
         v-html="item.svgIcon">
    </div>
  </v-layout>
</template>

<script>
  import {mapGetters} from 'vuex'

  export default {
    name: "TrainingContent",
    props: {
      contentIds: Array,
      selectable: Boolean,
      initContentIds: Array,
    },
    data: function () {
      return {
        contents: [],
        selectedContentIds: [],
      }
    },
    computed: {
      ...mapGetters('masterData', {getContentsByIds: 'getContentsByIds', getContentsByIds: 'getContentsByIds'}),
    },
    created() {
      this.selectedContentIds = [];
      for (let i = 0; i < this.initContentIds.length; i++) {
        this.selectedContentIds.push(this.initContentIds[i]);
      }
      this.contents = this.getContentsByIds(this.contentIds);
    },
    methods: {
      isSelected: function (item) {
          return this.selectedContentIds.includes(item.id);
      },
      selectContent: function (content) {
        if (this.selectable) {
          if (this.selectedContentIds.includes(content.id)) {
            var index = this.selectedContentIds.indexOf(content.id);
            if (index > -1) {
              this.selectedContentIds.splice(index, 1);
            }
            this.$emit('contentUnselected', content.id)

          } else {
            this.selectedContentIds.push(content.id);
            this.$emit('contentSelected', content.id)
          }
        }
      },
    },
    watch: {
      initContentIds: function () {
        this.selectedContentIds = [];
        for (let i = 0; i < this.initContentIds.length; i++) {
          this.selectedContentIds.push(this.initContentIds[i]);
        }
      }
    },
  }
</script>

<style lang="scss" scoped>
  .tp-training-content {
    &__icon {
      display: inline-block;
      margin: 5px;
      margin: 5px;
      height: 40px;
      width: 40px;

      svg {
        max-height: 100%;
      }
    }

    &--selectable {
      .tp-training-content__icon {
        cursor: pointer;
      }
    }

  }

</style>

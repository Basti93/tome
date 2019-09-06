<template>
  <v-layout justify-start row wrap pt-2 pb-2 class="tp-training-content" :class="{'tp-training-content--selectable' : selectable}">
    <figure class="tp-training-content" v-for="(item, index) in contents" :key="item.id">
      <img class="tp-training-content__icon"
           v-bind:style="[ isSelected(item, selectedContentIds) ? {'filter': 'invert(100%)'} : {}]"
           @click="selectContent(item)"
           :src="getImagePath(item.imageName)"
           :title="item.name"
           :alt-text="item.name"
           height="40"
      />
      <figcaption class="caption">{{item.name}}</figcaption>
    </figure>
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
      readonly: Boolean,
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
            const index = this.selectedContentIds.indexOf(content.id);
            if (index > -1) {
              this.selectedContentIds.splice(index, 1);
            }

          } else {
            this.selectedContentIds.push(content.id);
          }
          this.$emit('change', this.selectedContentIds)
        }
      },
      getImagePath(imageName) {
        return '/img/contents/' + imageName;
      }
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
    padding: 5px;
    text-align: center;

    &__icon {
      display: block;
      max-width: 50px;
      margin-left: auto;
      margin-right: auto;

    }

    &--selectable {
      .tp-training-content__icon {
        cursor: pointer;
      }
    }

  }

</style>

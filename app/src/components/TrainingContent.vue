<template>
  <v-container
      class="tp-training-content"
      :class="{'tp-training-content--selectable' : selectable}">
    <v-row no-gutters>

      <v-col
          v-for="(item) in contents"
          :key="item.id"
          @click="selectContent(item.id)"
          cols="4"
          md="2">
        <figure
            class="tp-training-content__item"
            :class="{'tp-training-content__item--selected' : isSelected(item, selectedContentIds)}"
        >
          <img class="tp-training-content__icon"
               :src="getImagePath(item.imageName)"
               :title="item.name"
               :alt-text="item.name"
               height="40"
          />
          <figcaption class="caption text-center">{{ item.name }}</figcaption>
        </figure>
      </v-col>
    </v-row>
  </v-container>
</template>

<script lang="ts">
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
    ...mapGetters('masterData', {getContentsByIds: 'getContentsByIds'}),
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
    selectContent: function (contentId) {
      console.log("selected", this.selectedContentIds)
      if (this.selectable) {
        let index = this.selectedContentIds.indexOf(contentId);
        if (index === -1) {
          this.selectedContentIds.push(contentId);
        } else {
          this.selectedContentIds.splice(index, 1);
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

  &__item {
    margin: 5px;
    padding: 5px;
  }

  &__icon {
    position: relative;
    display: block;
    max-width: 50px;
    margin-left: auto;
    margin-right: auto;

  }


  &--selectable {
    .tp-training-content {
      &__item {
        position: relative;
        cursor: pointer;
        width: 110px;
        text-align: center;

        &--selected {
          background-color: #efefef;

          &:after {
            font-family: "Material Icons";
            position: absolute;
            content: "check";
            color: #60cc69;
            top: 5px;
            right: 5px;
            font-weight: 900;
            font-size: 2rem;
          }
        }
      }
    }
  }

}

</style>

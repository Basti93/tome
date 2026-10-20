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
            :class="{'tp-training-content__item--selected' : isSelected(item)}"
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

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useMasterDataStore } from '@/store/masterData'

const props = defineProps<{
  contentIds?: any[]
  selectable?: boolean
  initContentIds: any[]
  readonly?: boolean
}>()

const emit = defineEmits<{
  change: [contentIds: any[]]
}>()

const selectedContentIds = ref<any[]>([...props.initContentIds])
const contents = ref<any[]>(useMasterDataStore().getContentsByIds(props.contentIds))

function isSelected(item: { id: number }) {
  return selectedContentIds.value.includes(item.id)
}

function selectContent(contentId: number) {
  if (props.selectable) {
    const index = selectedContentIds.value.indexOf(contentId)
    if (index === -1) {
      selectedContentIds.value.push(contentId)
    } else {
      selectedContentIds.value.splice(index, 1)
    }
    emit('change', selectedContentIds.value)
  }
}

function getImagePath(imageName: string) {
  return '/img/contents/' + imageName
}

watch(
  () => props.initContentIds,
  () => {
    selectedContentIds.value = [...props.initContentIds]
  }
)
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
          background-color: rgba(var(--v-theme-on-surface), 0.08);

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

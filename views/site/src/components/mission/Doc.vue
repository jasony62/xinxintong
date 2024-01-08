<template>
  <div class="docs">
    <tms-flex direction="column" align-items="stretch" gap="x">
      <tms-card v-for="doc in docs" :key="doc.id" :thumb="doc.pic" :url="doc.entryUrl" gap-content="1"
        class="tms-card__doc">
        <template #title>
          <div class="van-multi-ellipsis">{{ doc.title }}</div>
        </template>
        <template #desc>
          <div class="van-multi-ellipsis--l3">{{ doc.summary }}</div>
        </template>
      </tms-card>
    </tms-flex>
  </div>
</template>

<script setup lang="ts">
import Vue, { PropType } from 'vue'
import { Cell, DropdownMenu, DropdownItem } from 'vant'

// Vue.use(Cell)
//   .use(DropdownMenu)
//   .use(DropdownItem)

defineProps(
  { docs: Array as PropType<Array<any>> }
)
</script>

<style lang="less">
@thumbHeight: 80px;

.tms-card__doc {
  padding: 8px;
  height: @thumbHeight;

  .tms-card__thumb img {
    width: @thumbHeight*1.8;
    height: @thumbHeight;
    object-fit: cover;
  }

  .tms-card__content {
    height: 100%;
  }

  .tms-card__desc {
    font-size: 80%;
  }
}

.tms-flex.tms-flex_column.tms-flex_gap_x>.tms-flex__item+.tms-flex__item {
  margin-top: 1px;
}
</style>

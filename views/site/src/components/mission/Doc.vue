<template>
  <div class="docs">
    <tms-flex direction="column" align-items="stretch" gap="x">
      <tms-card
        v-for="doc in docs"
        :key="doc.id"
        :thumb="doc.pic"
        :title="doc.title"
        :desc="doc.summary"
        :url="doc.entryUrl"
        gap-content="1"
        class="tms-card__doc"
      />
    </tms-flex>
  </div>
</template>

<script>
import Vue from 'vue'
import { Cell, DropdownMenu, DropdownItem } from 'vant'

Vue.use(Cell)
  .use(DropdownMenu)
  .use(DropdownItem)

export default {
  name: 'MisDoc',
  props: { docs: Array }
}
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
.tms-flex.tms-flex_column.tms-flex_gap_x > .tms-flex__item + .tms-flex__item {
  margin-top: 1px;
}
</style>

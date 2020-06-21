<template>
  <div class="docs">
    <tms-flex direction="column" align-items="stretch">
      <tms-card
        v-for="doc in docs"
        :key="doc.id"
        :title="doc.title"
        :desc="doc.summary"
        :url="doc.entryUrl"
        class="doc"
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

<style lang="less" scoped>
.doc {
  padding: 8px;
  height: 100px;
}
</style>

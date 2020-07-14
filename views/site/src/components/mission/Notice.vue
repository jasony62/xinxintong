<template>
  <tms-flex direction="column" align-items="stretch" gap="x">
    <tms-card
      v-for="notice in notices"
      :key="notice.id"
      :title="notice.createAt"
      class="tms-card__notice"
    >
      <template #desc>
        <div v-html="notice.remark"></div>
      </template>
      <template #footer v-if="notice.close_at<=0">
        <tms-flex direction="row-reverse">
          <van-button type="default" size="small" @click="onClose(notice)">关闭</van-button>
        </tms-flex>
      </template>
    </tms-card>
  </tms-flex>
</template>

<script>
import Vue from 'vue'
import { Cell, Button } from 'vant'

Vue.use(Cell).use(Button)
export default {
  name: 'MisNotice',
  props: { notices: Array },
  methods: {
    onClose(notice) {
      this.$emit('notice-close', notice)
    }
  }
}
</script>

<style lang="less">
.tms-card__notice {
  padding: 8px;
}
.tms-flex.tms-flex_column.tms-flex_gap_x > .tms-flex__item + .tms-flex__item {
  margin-top: 1px;
}
</style>

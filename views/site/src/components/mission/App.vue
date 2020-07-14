<template>
  <tms-flex direction="column" align-items="stretch" gap="x">
    <tms-card
      v-for="app in apps"
      :key="app.id"
      :title="app.title"
      :url="app.entryUrl"
      class="tms-card__app"
    />
  </tms-flex>
</template>

<script>
export default {
  name: 'MisApp',
  props: { apps: Array }
}
</script>

<style lang="less">
.tms-card__app {
  padding: 8px;
  height: 100px;
}
.tms-flex.tms-flex_column.tms-flex_gap_x > .tms-flex__item + .tms-flex__item {
  margin-top: 1px;
}
</style>
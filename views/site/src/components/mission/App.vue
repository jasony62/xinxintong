<template>
  <tms-flex direction="column" align-items="stretch" gap="x">
    <tms-card
      v-for="app in apps"
      :thumb="app.pic"
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
@thumbHeight: 50px;
.tms-card__app {
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
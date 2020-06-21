<template>
  <tms-flex direction="column" align-items="stretch">
    <tms-card v-for="app in apps" :key="app.id" :title="app.title" :url="app.entryUrl" class="app" />
  </tms-flex>
</template>

<script>
export default {
  name: 'MisApp',
  props: { apps: Array }
}
</script>

<style lang="less" scoped>
.app {
  padding: 8px;
  height: 100px;
}
</style>
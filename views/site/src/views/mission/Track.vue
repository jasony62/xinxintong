<template>
  <div class="track">
    <mis-app :apps="apps" />
  </div>
</template>

<script>
import Vue from 'vue'
import MisApp from '@/components/mission/App.vue'

export default {
  name: 'Home',
  components: {
    MisApp
  },
  data() {
    return { apps: [] }
  },
  props: { siteId: String, missionId: String },
  mounted() {
    Vue.$apis.mission
      .userTrack(this.siteId, this.missionId)
      .then(apps => apps.forEach(app => this.apps.push(app)))
  }
}
</script>

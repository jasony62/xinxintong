<template>
  <tms-flex direction="column" class="doc">
    <van-dropdown-menu v-if="channels.length">
      <van-dropdown-item
        v-model="selectedChannelId"
        :options="channelItems"
        @change="onChangeSelectedChannel"
      />
    </van-dropdown-menu>
    <mis-doc :channels="channels" :docs="docs" />
  </tms-flex>
</template>

<script>
import Vue from 'vue'
import MisDoc from '@/components/mission/Doc.vue'

export default {
  name: 'Home',
  components: {
    MisDoc
  },
  props: { siteId: String, missionId: String },
  data() {
    return { docs: [], channels: [], selectedChannelId: null, matterTypes: [] }
  },
  computed: {
    channelItems: function() {
      const items = this.channels.map(({ id, title }) => ({
        text: title,
        value: id
      }))
      items.splice(0, 0, { text: '全部频道', value: null })
      return items
    }
  },
  methods: {
    onChangeSelectedChannel() {
      this.docs = []
      Vue.$apis.mission
        .docList(
          this.siteId,
          this.missionId,
          this.matterTypes,
          this.selectedChannelId
        )
        .then(docs => docs.forEach(doc => this.docs.push(doc)))
    }
  },
  mounted() {
    if (Vue.$mission) {
      const mission = Vue.$mission
      const pageConfig = mission.pageConfig ? mission.pageConfig : {}
      const channelVisible =
        pageConfig.channel && pageConfig.channel.visible == true ? true : false
      const channelAsFilter =
        pageConfig.channel && pageConfig.channel.asFilter == true ? true : false

      this.matterTypes = channelVisible ? ['channel'] : ['article', 'link']

      if (channelVisible === false && channelAsFilter === true)
        Vue.$apis.mission
          .channelList(this.siteId, this.missionId)
          .then(channels => channels.forEach(chan => this.channels.push(chan)))

      Vue.$apis.mission
        .docList(this.siteId, this.missionId, this.matterTypes)
        .then(docs => docs.forEach(doc => this.docs.push(doc)))
    }
  }
}
</script>

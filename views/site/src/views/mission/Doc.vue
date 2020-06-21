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
    return { docs: [], channels: [], selectedChannelId: null }
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
          ['article', 'link'],
          this.selectedChannelId
        )
        .then(docs => docs.forEach(doc => this.docs.push(doc)))
    }
  },
  mounted() {
    Vue.$apis.mission
      .channelList(this.siteId, this.missionId)
      .then(channels => channels.forEach(chan => this.channels.push(chan)))
    Vue.$apis.mission
      .docList(this.siteId, this.missionId, ['article', 'link'])
      .then(docs => docs.forEach(doc => this.docs.push(doc)))
  }
}
</script>

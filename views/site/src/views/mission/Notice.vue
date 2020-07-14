<template>
  <tms-flex direction="column" class="notice">
    <div>
      <van-tabs v-model="noticeScope" @change="onChangeNoticeScope">
        <van-tab name="unclose" title="未关闭"></van-tab>
        <van-tab name="all" title="全部"></van-tab>
      </van-tabs>
      <mis-notice :notices="notices" v-on:notice-close="closeNotice" />
    </div>
    <van-button type="default" block v-if="batchDone===false" @click="moreNotice">
      更多
      <span v-if="batch&&batch.total">
        <span>({{notices.length}}/{{batch.total}})</span>
      </span>
    </van-button>
  </tms-flex>
</template>

<script>
import Vue from 'vue'
import moment from 'moment'
import { Tabs, Tab } from 'vant'
Vue.use(Tabs).use(Tab)

import MisNotice from '@/components/mission/Notice.vue'

import { Batch } from 'tms-vue'

export default {
  name: 'Home',
  components: {
    MisNotice
  },
  props: { siteId: String, missionId: String },
  data() {
    return {
      noticeScope: 'unclose',
      notices: [],
      batch: null,
      batchDone: false
    }
  },
  methods: {
    moreNotice() {
      this.batch.next().then(({ result, done }) => {
        this.batchDone = done
        result.logs.forEach(log => {
          const { id, close_at, batch } = log
          const { create_at, remark, send_from } = batch
          this.notices.push({
            id,
            close_at,
            createAt: moment(create_at * 1000).format('YYYY-MM-DD HH:mm'),
            remark,
            app: { pic: send_from ? send_from.pic : '' }
          })
        })
      })
    },
    closeNotice(notice) {
      Vue.$apis.notice.close(this.siteId, notice.id).then(() => {
        const index = this.notices.indexOf(notice)
        this.notices.splice(index, 1)
        this.batch.total--
        this.$toast.success('已关闭')
      })
    },
    onChangeNoticeScope() {
      this.notices = []
      if (this.noticeScope === 'unclose') {
        this.batch = new Batch(
          Vue.$apis.notice.uncloseList,
          this.siteId,
          this.missionId
        )
        this.batch.size = 10
        this.moreNotice()
      } else if (this.noticeScope === 'all') {
        this.batch = new Batch(
          Vue.$apis.notice.list,
          this.siteId,
          this.missionId
        )
        this.batch.size = 10
        this.moreNotice()
      }
    }
  },
  mounted() {
    this.onChangeNoticeScope()
  }
}
</script>

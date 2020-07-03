<template>
  <tms-frame
    :display="{header: true, footer: false, left: false, right: false} "
    center-color="#ddd"
  >
    <template #header>
      <van-nav-bar :title="mission.title" />
    </template>
    <template #center v-if="mission.id">
      <tms-flex direction="column" align-items="stretch">
        <van-tabbar route :fixed="false" :safe-area-inset-bottom="true">
          <van-tabbar-item :to="{ name: 'Track', query: $route.query}" icon="todo-list-o">活动</van-tabbar-item>
          <van-tabbar-item :to="{ name: 'Doc', query: $route.query}" icon="description">资料</van-tabbar-item>
          <van-tabbar-item
            :to="{ name: 'Notice', query: $route.query}"
            icon="comment-o"
            :dot="noticeCount>0"
          >通知</van-tabbar-item>
        </van-tabbar>
        <router-view />
      </tms-flex>
    </template>
    <template #center v-else-if="failure">
      <van-empty image="error" :description="failure" />
    </template>
    <template #center v-else>
      <tms-flex align-items="center" :elastic-items="[0]" style="width:100%;height:100%;">
        <van-loading size="48" vertical>加载中...</van-loading>
      </tms-flex>
    </template>
  </tms-frame>
</template>

<script>
import Vue from 'vue'
import { Loading, Empty, Toast, NavBar, Tabbar, TabbarItem } from 'vant'

import axios from 'axios'
import wx from 'weixin-js-sdk'

Vue.use(Loading)
  .use(Empty)
  .use(Toast)
  .use(NavBar)
  .use(Tabbar)
  .use(TabbarItem)

import { Frame, Flex, Card } from 'tms-vue-ui'
Vue.use(Frame)
  .use(Flex)
  .use(Card)

export default {
  data() {
    return {
      failure: null,
      noticeCount: 0,
      mission: { id: null, title: '' }
    }
  },
  mounted() {
    const { site, mission } = this.$route.query
    Vue.$apis.mission.entryRule(site, mission).then(result => {
      new Promise((resolve, reject) => {
        if (/MicroMessenger/i.test(navigator.userAgent)) {
          const url = '/rest/site/fe/wxjssdksignpackage2'
          const params = {
            site,
            url: encodeURIComponent(location.href.split('#')[0])
          }
          axios.get(url, { params }).then(res => {
            const result = res.data
            if (result.err_code !== 0) return reject(result.err_msg)
            const { appId, timestamp, nonceStr, signature } = result.data
            wx.config({
              appId,
              timestamp,
              nonceStr,
              signature,
              jsApiList: [
                'hideOptionMenu',
                'onMenuShareTimeline',
                'onMenuShareAppMessage'
              ]
            })
            wx.ready(() => {
              resolve(true)
            })
          })
        } else resolve(false)
      })
        .then(wxReady => {
          if (result[0] === false) this.failure = result[1]
          else {
            Vue.$apis.mission.get(site, mission).then(mission => {
              Object.assign(this.mission, mission)
              Vue.$mission = mission
              if (wxReady) {
                const shareOptions = {
                  title: mission.title,
                  desc: mission.summary,
                  link: mission.entryUrl,
                  imgUrl: mission.pic,
                  fail: () => alert('分享失败')
                }
                wx.onMenuShareTimeline(shareOptions)
                wx.onMenuShareAppMessage(shareOptions)
              }
              this.$tmsEmit('mission.ready', mission)
            })
            Vue.$apis.notice.count(site).then(noticeCount => {
              this.noticeCount = noticeCount
            })
          }
        })
        .catch(errmsg => {
          this.failure = errmsg
        })
    })
  }
}
</script>


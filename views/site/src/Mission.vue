<template>
  <!-- <tms-frame :display="{ header: true, footer: false, left: false, right: false }" center-color="#ddd"
    class="tms-frame__mission">
    <template #header>
      <van-nav-bar :title="mission.title" />
    </template>
    <template #center v-if="mission.id">
      <tms-flex direction="column" align-items="stretch">
        <van-tabbar route :fixed="false" :safe-area-inset-bottom="true">
          <van-tabbar-item class="van-tabbar-item__app" :to="{ name: 'Track', query: $route.query }">
            <span>活动</span>
            <template #icon>
              <div class="van-tabbar-item__icon__img" />
            </template>
          </van-tabbar-item>
          <van-tabbar-item class="van-tabbar-item__doc" :to="{ name: 'Doc', query: $route.query }">
            <span>资料</span>
            <template #icon>
              <div class="van-tabbar-item__icon__img" />
            </template>
          </van-tabbar-item>
          <van-tabbar-item class="van-tabbar-item__notice" :to="{ name: 'Notice', query: $route.query }"
            :dot="noticeCount > 0">
            <span>通知</span>
            <template #icon>
              <div class="van-tabbar-item__icon__img" />
            </template>
          </van-tabbar-item>
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
  </tms-frame> -->
</template>

<script setup lang="ts">
// import Vue from 'vue'
// import { Loading, Empty, Toast, NavBar, Tabbar, TabbarItem } from 'vant'

// import wxapi from './utils/wx'
// import { Frame, Flex, Card } from 'tms-vue3-ui'
// import { onMounted } from 'vue'

// Vue.use(Loading)
//   .use(Empty)
//   .use(Toast)
//   .use(NavBar)
//   .use(Tabbar)
//   .use(TabbarItem)

// Vue.use(Frame)
//   .use(Flex)
//   .use(Card)

// const failure = null
// const noticeCount = 0
// const mission = { id: null, title: '' }

// onMounted(() => {
//   const { site, mission } = this.$route.query
//   Vue.$apis.mission.entryRule(site, mission).then(result => {
//     if (result[0] === false) this.failure = result[1]
//     else
//       wxapi
//         .config(site)
//         .then(wxReady => {
//           Vue.$apis.mission.get(site, mission).then(mission => {
//             if (wxReady) {
//               const { title, summary, pic, entryUrl } = mission
//               wxapi.setShare(title, summary, pic, entryUrl + '&version=new')
//             }
//             Object.assign(this.mission, mission)
//             Vue.$mission = mission
//             this.$tmsEmit('mission.ready', mission)
//           })
//           Vue.$apis.notice.count(site).then(noticeCount => {
//             this.noticeCount = noticeCount
//           })
//         })
//         .catch(errmsg => {
//           this.failure = errmsg
//         })
//   })
// })
</script>

<style lang="less">
@import '@theme/mission.less';

.tms-frame__mission {
  .van-nav-bar {
    background-color: @brand-primary;

    .van-nav-bar__title {
      color: @brand-primary-text;
    }
  }

  .van-tabbar {
    background-color: @brand-second;

    .van-tabbar-item--active {
      color: @brand-second-text;
    }
  }
}
</style>
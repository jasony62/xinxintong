<template>
  <nav-bar v-if="initialized" :left-text="returnText" :left-arrow="showReturn" @click-left="back" />
  <swipe class="headpic" :show-indicators="false" v-if="showHeadpic">
    <swipe-item>
      <img :src="data.channel.pic" />
    </swipe-item>
  </swipe>
  <div v-if="showSummary">
    <cell :title="data.channel.summary"></cell>
  </div>
  <div id='qrCode' class="hidden md:block">
    <img :src="qrcode">
  </div>
  <div id="matters-empty" v-if="Matter.matters.length === 0 && Matter.end">
    <notice-bar>没有可以访问的内容</notice-bar>
  </div>
  <div id='matters' v-if="data.channel.id">
    <list v-model:loading="Matter.busy" :finished="Matter.end" @load="Matter.nextPage">
      <card v-for="m in Matter.matters" :desc="m.summary" :title="m.title" :thumb="m.pic" @click='openMatter(m)'>
        <template #num>
          <span>{{ dealCreateAt(m) }}</span>
        </template>
      </card>
    </list>
  </div>
</template>

<style lang="scss">
.van-cell__title {
  @apply text-base;
}

.van-card__title {
  @apply text-lg;
}

.van-card__desc {
  @apply text-base;
}
</style>

<script setup lang="ts">
import { TmsAxios } from 'tms-vue3';
import { computed, onMounted, reactive, ref } from 'vue';
import queryString from 'query-string'
import * as dayjs from 'dayjs'
import { Cell, NavBar, Swipe, SwipeItem, List, Card, NoticeBar } from 'vant';

const tmsAxios = TmsAxios.ins('xxt-axios')

const { id, site, type, shareby, inviteToken } = queryString.parse(location.search)

const initialized = ref(false)

const data = reactive<any>({})
data.channel = { title: '', pic: '', summary: '', config: { show: { headpic: false } } }

const qrcode = ref('')

const showReturn = !/\/ue\/site\/fe\/channel/.test(document.referrer)
const returnText = showReturn ? '返回' : ''

let user

const back = () => {
  history.back()
}

const supportRedirectSingle = () => {
  return (
    data.channel.config.redirectSingle === 'Y'
  )
}

const dealImgSrc = (item: any) => {
  if (Object.keys(item).indexOf('pic') !== -1 && item.pic == null) {
    item.src = item.pic = ''
  } else if (
    Object.keys(item).indexOf('thumbnail') !== -1 &&
    item.thumbnail == null
  ) {
    item.src = item.thumnail = ''
  } else {
    item.src = item.pic ? item.pic : item.thumbnail
  }
  return item
}

const dealCreateAt = (matter: any) => {
  return dayjs.unix(matter.create_at).format('YYYY-MM-DD')
}

const showHeadpic = computed(() => {
  if (!initialized.value) return false
  if (!data.channel.pic) return false
  // 没有设置显示头图
  if (data.channel.config?.show?.headpic !== 'Y') return false
  // 根据是否有可见素材决定是否显示头图
  if (data.channel.config?.hide?.headpicHasMatters === 'Y' && Matter.matters.length > 0) {
    return false
  }

  return true
})

const showSummary = computed(() => {
  if (!initialized.value) return false
  if (!data.channel.summary) return false
  // 根据是否有可见素材决定是否显示说明
  if (data.channel.config?.hide?.summaryHasMatters === 'Y' && Matter.matters.length > 0) {
    return false
  }

  return true
})

const Matter = reactive<any>({
  matters: [],
  busy: true,
  end: false,
  page: 1,
  keyword: '',
  reset: function () {
    this.matters = []
    this.busy = false
    this.end = false
    this.page = 1
    this.nextPage()
  },
  nextPage: function () {
    if (Matter.end) return
    Matter.busy = true
    let url = `/rest/site/fe/matter/channel/mattersGet?site=${site}&id=${id}&page=${Matter.page}&size=10`
    if (Matter.keyword) url += `&keyword=${Matter.keyword}`
    tmsAxios.get(url).then((rsp: any) => {
      let { matters } = rsp.data.data
      if (
        Matter.page === 1 &&
        matters.length === 1 &&
        supportRedirectSingle()
      ) {
        openMatter(matters[0])
      } else {
        if (matters.length) {
          for (var i = 0, l = matters.length; i < l; i++) {
            dealImgSrc(matters[i])
            Matter.matters.push(matters[i])
          }
          Matter.page++
        } else {
          Matter.end = true
        }
        Matter.busy = false
        initialized.value = true
      }
    })
  },
})

const openMatter = (opened: any) => {
  if (data.channel.invite) {
    location.href = opened.url + '&inviteToken=' + inviteToken
  } else {
    location.href = opened.url
  }
}

const getChannel = async () => {
  let url = `/rest/site/fe/matter/channel/get?site=${site}&id=${id}`
  // 获取频道基本信息
  const rsp = await tmsAxios.get(url)
  data.channel = rsp.data.data.channel
  qrcode.value = `/rest/site/fe/matter/channel/qrcode?site=${site}&url=` +
    encodeURIComponent(location.href)
  user = rsp.data.data.user
  // 记录访问日志
  let logUrl = `/rest/site/fe/matter/logAccess?site=${site}`
  tmsAxios.post(logUrl, {
    id,
    type,
    title: data.channel.title,
    shareby,
    search: location.search.replace('?', ''),
    referer: document.referrer,
  })
}

onMounted(() => {
  getChannel()
})
</script>
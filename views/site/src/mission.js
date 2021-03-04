import Vue from 'vue'
import Mission from './Mission.vue'
import router from './router/mission'
import { TmsEventPlugin, TmsAxiosPlugin } from 'tms-vue'
import ApiPlugin from './apis'

Vue.config.productionTip = false

Vue.use(TmsEventPlugin).use(TmsAxiosPlugin)

const tmsAxios = {}
tmsAxios.mission = Vue.TmsAxios({
  name: 'mission-api',
  config: { withCredentials: true },
})
tmsAxios.notice = Vue.TmsAxios({
  name: 'notice-api',
  config: { withCredentials: true },
})
Vue.use(ApiPlugin, { tmsAxios })

new Vue({
  router,
  render: (h) => h(Mission),
}).$mount('#app')

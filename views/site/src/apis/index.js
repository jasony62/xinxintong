import createMissionApi from './mission'
import createNoticeApi from './notice'

function init(options) {
  return {
    mission: createMissionApi(options.tmsAxios.mission),
    notice: createNoticeApi(options.tmsAxios.notice),
  }
}

export default function install(Vue, options) {
  Vue.$apis = init(options)
  Vue.prototype.$apis = Vue.$apis
}

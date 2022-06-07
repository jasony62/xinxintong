import { createApp } from 'vue'
import Channel from './Channel.vue'
import { TmsAxiosPlugin, TmsAxios } from 'tms-vue3'
import Frame from 'tms-vue3-ui/dist/es/frame'
import 'tms-vue3-ui/dist/es/frame/style/index.css'
import './index.css'

const app = createApp(Channel)
app.use(TmsAxiosPlugin).use(Frame)

let name = 'xxt-axios'
let rules: any[] = [] // 见下面的说明
let config = {} // 参考axios的config
TmsAxios.ins({ name, rules, config })

app.mount('#app')

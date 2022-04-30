import { createApp } from 'vue'
import Login from './Login.vue'
import { TmsAxiosPlugin, TmsAxios } from 'tms-vue3'
import { Frame } from 'tms-vue3-ui'
import 'tms-vue3-ui/dist/es/frame/style/index.css'
import 'tms-vue3-ui/dist/es/flex/style/index.css'
import './index.css'

import router from './router/login'

const app = createApp(Login)
app.use(router).use(TmsAxiosPlugin).use(Frame)

let name = 'xxt-axios'
let rules: any[] = [] // 见下面的说明
let config = {} // 参考axios的config
TmsAxios.ins({ name, rules, config })

app.mount('#app')

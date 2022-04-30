<template>
  <sms-code :schema="schema" action-text="登录" :fn-captcha="fnSendCode" :fn-register="fnVerify"
    :on-success="fnVerifySuccess" :on-fail="fnVerifyFail">
  </sms-code>
</template>

<script setup lang="ts">
import { SmsCode } from 'tms-vue3-ui'
import 'tms-vue3-ui/dist/es/sms-code/style/tailwind.scss'

const schema = [
  {
    // 当前双向绑定的属性名
    key: 'mobile',
    // 组件类型
    type: 'text',
    placeholder: '请输入手机号',
  },
  {
    key: 'code',
    type: 'code',
    placeholder: '请出入验证码',
  },
]

const fnSendCode = () => Promise.resolve({ code: 0, result: '1234' })
const fnVerify = () => Promise.resolve({
  code: 0,
  msg: '成功',
  result: { access_token: '89898989' },
})
const fnVerifySuccess = () => Promise.resolve()
const fnVerifyFail = () => Promise.resolve()

</script>
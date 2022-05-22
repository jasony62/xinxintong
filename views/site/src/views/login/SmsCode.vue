<template>
  <sms-code
    :schema="schema"
    action-text="进入"
    :fn-verify="fnLogin"
    :on-success="fnSuccessVerify"
    :fn-send-sms-code="fnSendSmsCode"
    :on-fail="fnFailVerify"
  >
  </sms-code>
</template>

<script setup lang="ts">
import { TmsAxios } from 'tms-vue3'
import SmsCode from 'tms-vue3-ui/dist/es/sms-code'
import 'tms-vue3-ui/dist/es/sms-code/style/tailwind.scss'

let tmsAxios = TmsAxios.ins('xxt-axios')

const schema = [
  {
    key: 'uname',
    type: 'text',
    placeholder: '请输入手机号',
  },
  {
    key: 'captcha',
    type: 'smscode',
    placeholder: '请出入验证码',
  },
]

const appId = 'app001'
let captchaId = ''

const fnSendSmsCode = () => {
  captchaId = 'captcha001'
  return Promise.resolve({ code: 0, result: '1234' })
}

const fnLogin = async (userInput: any) => {
  let { uname, captcha } = userInput
  let authData = { uname, captcha, appId, captchaId }
  const url = import.meta.env.VITE_SMSCODE_LOGIN_URL
  const rsp = await tmsAxios.post(url, authData)
  const { err_code, err_msg, data } = rsp.data
  if (err_code !== 0) {
    alert(err_msg)
  }

  return { code: err_code, msg: err_msg, data }
}

const fnSuccessVerify = () => Promise.resolve()

const fnFailVerify = () => Promise.resolve()
</script>

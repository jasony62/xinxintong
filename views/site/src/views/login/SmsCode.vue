<template>
  <tms-sms-code
    :schema="schema"
    action-text="进入"
    :fn-send-sms-code="sendSmsCode"
    :fn-verify="login"
    :on-success="fnSuccessVerify"
    :on-fail="fnFailVerify"
  >
  </tms-sms-code>
</template>

<script setup lang="ts">
import { TmsAxios } from 'tms-vue3'
import TmsSmsCode from 'tms-vue3-ui/dist/es/sms-code'
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

const appId = import.meta.env.VITE_SMSCODe_APPID

const SMSCODE_SEND_URL = import.meta.env.VITE_SMSCODE_SEND_URL

const SMSCODE_LOGIN_URL = import.meta.env.VITE_SMSCODE_LOGIN_URL

const genCaptchaId = () => {
  let rand = Math.floor(Math.random() * 1000 + 1)
  let id = Date.now() * 1000 + rand
  return `${id}`
}

let captchaId = ''

const sendSmsCode = async () => {
  captchaId = genCaptchaId()
  const url = SMSCODE_SEND_URL + `?appid=${appId}&captchaId=${captchaId}`

  const rsp = await tmsAxios.get(url)
  const { code, msg } = rsp.data

  return { code, msg }
}

const login = async (userInput: any) => {
  let { uname, captcha } = userInput
  let loginData = { uname, captcha }
  const url = SMSCODE_LOGIN_URL + `?appid=${appId}&captchaId=${captchaId}`

  const rsp = await tmsAxios.post(url, loginData)
  const { err_code, err_msg, data } = rsp.data
  if (err_code !== 0) {
    alert(err_msg)
  }

  return { code: err_code, msg: err_msg, data }
}

const fnSuccessVerify = () => Promise.resolve()

const fnFailVerify = () => Promise.resolve()
</script>

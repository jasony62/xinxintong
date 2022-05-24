<template>
  <tms-sms-code :schema="schema" action-text="进入" :fn-send-sms-code="sendSmsCode" :fn-verify="login"
    :on-success="fnLoginSuccess">
  </tms-sms-code>
</template>

<script setup lang="ts">
import { TmsAxios } from 'tms-vue3'
import TmsSmsCode from 'tms-vue3-ui/dist/es/sms-code'
import 'tms-vue3-ui/dist/es/sms-code/style/tailwind.scss'

let tmsAxios = TmsAxios.ins('xxt-axios')

const schema = [
  {
    key: 'mobile',
    type: 'text',
    placeholder: '请输入手机号',
  },
  {
    key: 'captcha',
    type: 'smscode',
    placeholder: '请出入验证码',
  },
]

const SMSCODE_SEND_URL = import.meta.env.VITE_SMSCODE_SEND_URL

const SMSCODE_LOGIN_URL = import.meta.env.VITE_SMSCODE_LOGIN_URL

const genCaptchaId = () => {
  let rand = Math.floor(Math.random() * 1000 + 1)
  let id = Date.now() * 1000 + rand
  return `${id}`
}

let captchaId = ''

const sendSmsCode = async (userInput: any) => {
  captchaId = genCaptchaId()
  const url = SMSCODE_SEND_URL + `&captchaId=${captchaId}`

  const rsp = await tmsAxios.post(url, { mobile: userInput.mobile })
  const { err_code: code, err_msg: msg } = rsp.data

  return { code, msg }
}

const login = async (userInput: any) => {
  let { mobile, captcha } = userInput
  let loginData = { mobile, captcha }
  const url = SMSCODE_LOGIN_URL + `&captchaId=${captchaId}`

  const rsp = await tmsAxios.post(url, loginData)
  const { err_code, err_msg, data } = rsp.data

  return { code: err_code, msg: err_msg, data }
}

const fnLoginSuccess = (rsp: any) => {
  if (rsp.data._loginReferer) {
    location.replace(rsp.data._loginReferer)
    // } else if ($scope.loginData.gotoConsole === 'Y') {
    //   location.href = '/rest/pl/fe'
  } else {
    location.replace('/rest/site/fe/user?site=platform')
  }
}
</script>

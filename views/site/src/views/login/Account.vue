<template>
  <tms-login
    :schema="schema"
    :fn-captcha="genCaptcha"
    :fn-login="login"
    :on-success="onLoginSuccess"
  >
  </tms-login>
</template>

<script setup lang="ts">
import { TmsAxios } from 'tms-vue3'
import TmsLogin from 'tms-vue3-ui/dist/es/login'
import { LoginResponse } from 'tms-vue3-ui/dist/es/types'
import 'tms-vue3-ui/dist/es/login/style/tailwind.scss'

const tmsAxios = TmsAxios.ins('xxt-axios')

const CAPTCHA_URL = import.meta.env.VITE_GENERATE_CAPTCHA_URL

const LOGIN_URL = import.meta.env.VITE_ACCOUNT_LOGIN_URL

const schema = [
  {
    key: 'uname',
    type: 'text',
    placeholder: '用户名',
  },
  {
    key: 'password',
    type: 'password',
    placeholder: '密码',
  },
  {
    key: 'captcha',
    type: 'captcha',
    placeholder: '验证码',
  },
]

const genCaptchaId = () => {
  let rand = Math.floor(Math.random() * 1000 + 1)
  let id = Date.now() * 1000 + rand
  return `${id}`
}

// 验证码的ID
let captchaId: string

const genCaptcha = async () => {
  captchaId = genCaptchaId()
  const url = CAPTCHA_URL + `&captchaid=${captchaId}`

  const rsp = await tmsAxios.get(url)
  const { code, msg, result: captcha } = rsp.data
  return { code, msg, captcha }
}

const login = async (input: any): Promise<LoginResponse> => {
  const url = LOGIN_URL + `&captchaId=${captchaId}`
  const rsp = await tmsAxios.post(url, input)
  const { err_code: code, err_msg: msg, data } = rsp.data

  return { code, msg, data }
}

const onLoginSuccess = (rsp: any) => {
  if (rsp.data._loginReferer) {
    location.replace(rsp.data._loginReferer)
    // } else if ($scope.loginData.gotoConsole === 'Y') {
    //   location.href = '/rest/pl/fe'
  } else {
    location.replace('/rest/site/fe/user?site=platform')
  }
}
</script>

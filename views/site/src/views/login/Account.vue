<template>
  <login :schema="schema" :fn-captcha="fnCaptcha" :fn-login="fnLogin" :on-success="fnSuccess" :on-fail="fnFail">
  </login>
</template>

<script setup lang="ts">
import { TmsAxios } from 'tms-vue3'
import { Login, LoginResponse } from 'tms-vue3-ui'
import 'tms-vue3-ui/dist/es/login/style/tailwind.scss'

let tmsAxios = TmsAxios.ins('xxt-axios')

const schema = [
  {
    // 当前双向绑定的属性名
    key: 'uname',
    // 组件类型
    type: 'text',
    placeholder: '用户名',
  },
  {
    key: 'password',
    type: 'password',
    placeholder: '密码',
  },
  {
    key: 'pin',
    type: 'captcha',
    placeholder: '验证码',
  },
]

const fnCaptcha = async () => {
  const rsp = await tmsAxios.get('http://localhost:3000/auth/captcha')
  const { code, msg, result: captcha } = rsp.data
  return { code, msg, captcha }
}
const fnLogin = async (loginData: { [k: string]: string }): Promise<LoginResponse> => {
  const url = 'http://localhost:8000/rest/site/fe/user/login/do?site=platform'
  const rsp = await tmsAxios.post(url, loginData)
  const { err_code: code, err_msg: msg, data } = rsp.data
  return { code, msg, data }
}
const fnSuccess = (rsp) => {
  console.log('登录成功', rsp)
  location.href = 'http://localhost:8000/rest/pl/fe';
}
const fnFail = (rsp) => {
  console.log('登录失败', rsp)
}
</script>
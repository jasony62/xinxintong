<template>
  <register
    :schema="schema"
    :fn-captcha="fnCaptcha"
    :fn-register="fnRegister"
    :on-success="fnSuccess"
    :on-fail="fnFail"
  >
  </register>
</template>

<script setup lang="ts">
import { TmsAxios } from 'tms-vue3'
import Register from 'tms-vue3-ui/dist/es/register'
import 'tms-vue3-ui/dist/es/register/style/tailwind.scss'

let tmsAxios = TmsAxios.ins('xxt-axios')

const schema = [
  {
    key: 'uname',
    type: 'text',
    placeholder: '用户名',
  },
  {
    key: 'nickname',
    type: 'text',
    placeholder: '账号昵称',
  },
  {
    key: 'password',
    type: 'password',
    placeholder: '密码',
  },
  {
    key: 'password2',
    type: 'password',
    placeholder: '重复输入密码',
  },
  {
    key: 'pin',
    type: 'captcha',
    placeholder: '验证码',
  },
]

const fnCaptcha = async () => {
  const rsp = await tmsAxios.get(
    'http://localhost:3000/auth/captcha?appid=xxt&userid=123'
  )
  const { code, msg, result: captcha } = rsp.data
  return { code, msg, captcha }
}
const fnRegister = async (loginData: { [k: string]: string }): Promise<any> => {
  const url =
    'http://localhost:8000/rest/site/fe/user/register/do?site=platform'
  const rsp = await tmsAxios.post(url, loginData)
  const { err_code: code, err_msg: msg, data } = rsp.data
  return { code, msg, data }
}
const fnSuccess = (rsp: any) => {
  console.log('注册成功', rsp)
  location.href = 'http://localhost:8000/rest/pl/fe'
}
const fnFail = (rsp: any) => {
  console.log('注册失败', rsp)
}
</script>

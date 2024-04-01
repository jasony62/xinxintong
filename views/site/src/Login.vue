<template>
  <tms-frame left-width="30%" right-width="30%" :display="{ header: true, left: true, right: true }"
    :display-sm="{ header: true }" main-direction-sm="column-reverse">
    <template v-slot:header>
      <div class="bg-yellow-400 h-16 px-1 flex flex-row justify-end items-center">
        <div class="text-white cursor-pointer" @click="logout">退出</div>
      </div>
    </template>
    <template v-slot:center>
      <div class="flex gap-8 p-8">
        <router-link class="router-link" v-if="SupportSmscode" to="/smscode">验证码
        </router-link>
        <router-link class="router-link" to="/account">账号</router-link>
      </div>
      <router-view></router-view>
    </template>
  </tms-frame>
</template>

<script setup lang="ts">
import { TmsAxios } from 'tms-vue3'

const SupportSmscode = /yes|true/i.test(import.meta.env.VITE_SMSCODE_SUPPORT)

const tmsAxios = TmsAxios.ins('xxt-axios')

const logout = async () => {
  const url = '/rest/site/fe/user/logout/do?site=platform'
  const rsp = await tmsAxios.get(url)
  const { err_code, err_msg } = rsp.data
  if (err_code === 0) return window.alert('退出成功！')
  return window.alert(err_msg)
}

</script>

<style lang="scss">
.router-link {
  @apply p-4 rounded;
}

.router-link-active {
  @apply bg-yellow-400 text-white;
}
</style>
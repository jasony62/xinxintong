<template>
  <tms-frame left-width="30%" right-width="30%" :display="{ header: true, left: true, right: true }"
    :display-sm="{ header: true }" main-direction-sm="column-reverse">
    <template v-slot:header>
      <div class="bg-yellow-400 h-16"></div>
    </template>
    <template v-slot:center>
      <div class="flex gap-8 p-8">
        <router-link to="/account">账号登录</router-link>
        <router-link to="/smscode">验证码登录</router-link>
        <router-link to="/register">注册</router-link>
      </div>
      <router-view></router-view>
    </template>
  </tms-frame>
</template>

<script setup lang="ts">
</script>
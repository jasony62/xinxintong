<template>
  <tms-frame left-width="30%" right-width="30%" :display="{ header: true, left: true, right: true }"
    :display-sm="{ header: true }" main-direction-sm="column-reverse">
    <template v-slot:header>
      <div class="bg-yellow-400 h-16"></div>
    </template>
    <template v-slot:center>
      <div class="flex gap-8 p-8">
        <router-link class="router-link" v-if="supportSmscode" to="/smscode">验证码
        </router-link>
        <router-link class="router-link" to="/account">账号</router-link>
      </div>
      <router-view></router-view>
    </template>
  </tms-frame>
</template>

<script setup lang="ts">

const supportSmscode = /yes|true/i.test(import.meta.env.VITE_SMSCODE_SUPPORT)

</script>

<style lang="scss">
.router-link {
  @apply p-4 rounded;
}

.router-link-active {
  @apply bg-yellow-400 text-white;
}
</style>
import { createRouter, createWebHashHistory } from 'vue-router'

import Account from '../views/login/Account.vue'
import SmsCode from '../views/login/SmsCode.vue'
import Register from '../views/Login/Register.vue'

const VITE_BASE_URL = import.meta.env.VITE_BASE_URL ?? '/smscode'

const routes = [
  {
    path: '/account',
    name: 'account',
    component: Account,
  },
  {
    path: '/smscode',
    name: 'smscode',
    component: SmsCode,
  },
  {
    path: '/register',
    name: 'register',
    component: Register,
  },
  {
    path: '/:pathMatch(.*)*',
    component: SmsCode,
  },
]
const router = createRouter({
  history: createWebHashHistory(VITE_BASE_URL),
  routes,
})

export default router

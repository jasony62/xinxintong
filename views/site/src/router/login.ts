import { createRouter, createWebHashHistory } from 'vue-router'

import Account from '../views/login/Account.vue'
import SmsCode from '../views/login/SmsCode.vue'
import Register from '../views/login/Register.vue'

const supportSmscode = /yes|true/i.test(import.meta.env.VITE_SMSCODE_SUPPORT)
const supportRegister = /yes|true/i.test(import.meta.env.VITE_SMSCODE_REGISTER)

const VITE_BASE_URL = import.meta.env.VITE_BASE_URL ?? '/smscode'

let routes = [
  {
    path: '/account',
    name: 'account',
    component: Account,
  },
]
if (supportSmscode) {
  routes.push({
    path: '/smscode',
    name: 'smscode',
    component: SmsCode,
  })
  if (supportRegister)
    routes.push({
      path: '/register',
      name: 'register',
      component: Register,
    })
  routes.push({
    path: '/:pathMatch(.*)*',
    name: 'smscode',
    component: SmsCode,
  })
} else {
  if (supportRegister)
    routes.push({
      path: '/register',
      name: 'register',
      component: Register,
    })
  routes.push({
    path: '/:pathMatch(.*)*',
    name: 'account',
    component: Account,
  })
}

const router = createRouter({
  history: createWebHashHistory(VITE_BASE_URL),
  routes,
})

export default router

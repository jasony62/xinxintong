import Vue from 'vue'
import VueRouter from 'vue-router'
import Track from '../views/mission/Track.vue'
import Doc from '../views/mission/Doc.vue'
import Notice from '../views/mission/Notice.vue'

Vue.use(VueRouter)

const routes = [
  {
    path: '/mission',
    name: 'Track',
    component: Track,
    props: ({ query }) => ({
      siteId: query.site,
      missionId: query.mission,
    }),
  },
  {
    path: '/mission/doc',
    name: 'Doc',
    component: Doc,
    props: ({ query }) => ({
      siteId: query.site,
      missionId: query.mission,
    }),
  },
  {
    path: '/mission/notice',
    name: 'Notice',
    component: Notice,
    props: ({ query }) => ({
      siteId: query.site,
      missionId: query.mission,
    }),
  },
]

const router = new VueRouter({
  mode: 'history',
  base: process.env.BASE_URL,
  routes,
})

export default router

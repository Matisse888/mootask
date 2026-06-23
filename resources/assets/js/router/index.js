import Vue from 'vue'
import VueRouter from 'vue-router'

Vue.use(VueRouter)

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/pages/auth/Login.vue'),
    meta: { guest: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/pages/auth/Register.vue'),
    meta: { guest: true }
  },
  {
    path: '/',
    component: () => import('@/layouts/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('@/pages/Dashboard.vue')
      },
      {
        path: 'projects',
        name: 'Projects',
        component: () => import('@/pages/project/ProjectList.vue')
      },
      {
        path: 'projects/:id',
        name: 'ProjectDetail',
        component: () => import('@/pages/project/ProjectDetail.vue')
      },
      {
        path: 'my-tasks',
        name: 'MyTasks',
        component: () => import('@/pages/task/MyTasks.vue')
      },
      {
        path: 'messages',
        name: 'Messages',
        component: () => import('@/pages/dialog/MessageList.vue')
      },
      {
        path: 'messages/:id',
        name: 'MessageDetail',
        component: () => import('@/pages/dialog/MessageDetail.vue')
      },
      {
        path: 'files',
        name: 'Files',
        component: () => import('@/pages/file/FileList.vue')
      },
      {
        path: 'settings',
        name: 'Settings',
        component: () => import('@/pages/Settings.vue')
      }
    ]
  }
]

const router = new VueRouter({
  mode: 'history',
  base: process.env.BASE_URL,
  routes
})

// Navigation guard
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')

  if (to.meta.requiresAuth && !token) {
    next('/login')
  } else if (to.meta.guest && token) {
    next('/')
  } else {
    next()
  }
})

export default router

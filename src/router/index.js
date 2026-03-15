import { createRouter, createWebHistory } from 'vue-router'

import UserRegister from '../components/UserRegister.vue'
import UserLogin from '../components/UserLogin.vue'
import UserDashboard from '../components/UserDashboard.vue'
import OwnerDashboard from '../components/OwnerDashboard.vue'
import AdminDashboard from '../components/AdminDashboard.vue'
import UserBrowseTurfs from '../components/UserBrowseTurfs.vue'
import UserMyBookingsPage from '../components/UserMyBookingsPage.vue'
import UserTransactionHistoryPage from '../components/UserTransactionHistoryPage.vue'

function getUser() {
  const raw = localStorage.getItem('user')
  return raw ? JSON.parse(raw) : null
}

const authGuard = (next) => {
  const user = getUser()
  if (!user) {
    next('/login')
    return false
  }
  return true
}

const routes = [
  {
    path: '/',
    component: UserRegister
  },
  {
    path: '/login',
    component: UserLogin
  },
  {
    path: '/dashboard',
    component: UserDashboard,
    beforeEnter: (to, from, next) => {
      if (!authGuard(next)) return
      localStorage.setItem('mode', 'user')
      next()
    }
  },
  {
    path: '/owner-dashboard',
    component: OwnerDashboard,
    beforeEnter: (to, from, next) => {
      const user = getUser()
      if (!user) {
        next('/login')
      } else if (user.role !== 'owner') {
        next('/dashboard')
      } else {
        localStorage.setItem('mode', 'owner')
        next()
      }
    }
  },
  {
    path: '/admin-dashboard',
    component: AdminDashboard,
    beforeEnter: (to, from, next) => {
      const user = getUser()
      if (!user) {
        next('/login')
      } else if (user.role !== 'admin') {
        next('/dashboard')
      } else {
        localStorage.setItem('mode', 'admin')
        next()
      }
    }
  },
  {
    path: '/browse',
    alias: '/turfs',
    component: UserBrowseTurfs,
    beforeEnter: (to, from, next) => {
      if (!authGuard(next)) return
      next()
    }
  },
  {
    path: '/bookings',
    component: UserMyBookingsPage,
    beforeEnter: (to, from, next) => {
      if (!authGuard(next)) return
      next()
    }
  },
  {
    path: '/transactions',
    component: UserTransactionHistoryPage,
    beforeEnter: (to, from, next) => {
      if (!authGuard(next)) return
      next()
    }
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router

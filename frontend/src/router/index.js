import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../composable/useAuth' // adjust path
import Home from '../pages/Home.vue'
import Login from '../pages/auth/Login.vue'
import Register from '../pages/auth/Register.vue'
import Dashboard from '../pages/dashboard/Dashboard.vue'
import Computers from '../pages/computer/Computers.vue'
import Users from '../pages/users/Users.vue'
import Reports from '../pages/reports/Reports.vue'
import ComputerLogs from '../pages/computer/ComputerLogs.vue'
import Laboratory from '../pages/laboratory/Laboratory.vue'
import Admin from '../pages/users/Admin.vue'
import Students from '../pages/users/Students.vue'
import Profile from '../pages/auth/profile/Profile.vue'
import RequestAccess from '../pages/request/RequestAccess.vue'
import RequestForm from '../pages/auth/RequestForm.vue'
import Scan from '../pages/Scan.vue'
import Sections from '../pages/settings/academic/Sections.vue'
import Programs from '../pages/settings/academic/Programs.vue'
import YearLevels from '../pages/settings/academic/YearLevels.vue'
import BrowserActivity from '../pages/activity/BrowserActivity.vue'
import ComputerActivity from '../pages/activity/ComputerActivity.vue'
import HeartBeat from '../pages/activity/HeartBeat.vue'
import AuditLogs from '../pages/activity/AuditLogs.vue'
import WorkStationMapping from '../pages/users/WorkStationMapping.vue'




const routes = [
  { path: '',                       name: 'Home',               component: Home },
  { path: '/login',                 name: 'login',              component: Login },
  { path: '/register',              name: 'register',           component: Register },
  { path: '/request-account',       name: 'request-account',    component: RequestForm },
  { path: '/profile',               name: 'profile',            component: Profile,           meta: { requiresAuth: true }},
  { path: '/dashboard',             name: 'dashboard',          component: Dashboard,         meta: { requiresAuth: true }},
  { path: '/computers',             name: 'computers',          component: Computers,         meta: { requiresAuth: true }},
  { path: '/laboratory',            name: 'laboratory',         component: Laboratory,        meta: { requiresAuth: true }},
  { path: '/users',                 name: 'users',              component: Users,             meta: { requiresAuth: true }},
  { path: '/admin',                 name: 'admins',             component: Admin,             meta: { requiresAuth: true }},
  { path: '/students',              name: 'students',           component: Students,          meta: { requiresAuth: true }},
  { path: '/profile',               name: 'profile',            component: Profile,           meta: { requiresAuth: true }},
  { path: '/computer_logs',         name: 'computer_logs',      component: ComputerLogs,      meta: { requiresAuth: true }},
  { path: '/reports',               name: 'reports',            component: Reports,           meta: { requiresAuth: true }},
  { path: '/request-access',        name: 'request',            component: RequestAccess,     meta: { requiresAuth: true }},
  { path: '/section',               name: 'section',            component: Sections,          meta: { requiresAuth: true }},
  { path: '/program',               name: 'program',            component: Programs,          meta: { requiresAuth: true }},
  { path: '/year-level',            name: 'year-level',         component: YearLevels,        meta: { requiresAuth: true }},
  { path: '/browser-activity',      name: 'browser-activity',   component: BrowserActivity,   meta: { requiresAuth: true }},
  { path: '/computer-activity',     name: 'computer-activity',  component: ComputerActivity,  meta: { requiresAuth: true }},
  { path: '/audit-logs',            name: 'audit-logs',         component: AuditLogs,         meta: { requiresAuth: true }},
  { path: '/heartbeat',             name: 'heartbeat',          component: HeartBeat,         meta: { requiresAuth: true }},
  { path: '/scan',                  name: 'scan',               component: Scan},
  { path: '/workstation-mapping',   name: 'workstation-mapping',component: WorkStationMapping, meta: { requiresAuth: true }},

]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Route Guard
router.beforeEach((to, from, next) => {
  const tokenStore = useAuthStore()

  if (!tokenStore.token) {
    tokenStore.loadToken()
  }

  const token = tokenStore.token

  if (to.meta.requiresAuth && !token) {
    return next({           name: 'login' })
  }

  if ((to.    name === 'login' || to.   name === 'register') && token) {
    return next({           name: 'dashboard' })
  }

  next()
})

export default router

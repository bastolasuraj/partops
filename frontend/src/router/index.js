import { createRouter, createWebHistory } from 'vue-router'
import { authApi } from '@/services/api'

// Cached auth state to avoid a round-trip on every navigation.
// Cleared on logout (call clearAuthCache() from the logout handler).
let _authCache = null

export const clearAuthCache = () => { _authCache = null }

const getAuthState = async () => {
  if (_authCache !== null) return _authCache
  try {
    const response = await authApi.me()
    const user = response?.data?.user
    _authCache = user ? { authenticated: true, role: user.role } : { authenticated: false, role: null }
  } catch {
    _authCache = { authenticated: false, role: null }
  }
  return _authCache
}

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/LoginView.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('@/views/DashboardView.vue')
  },
  {
    path: '/guide',
    name: 'UserGuide',
    component: () => import('@/views/UserGuideView.vue')
  },
  {
    path: '/parts',
    name: 'Parts',
    component: () => import('@/views/PartsView.vue')
  },
  {
    path: '/suppliers',
    name: 'Suppliers',
    component: () => import('@/views/SuppliersView.vue')
  },
  {
    path: '/technicians',
    name: 'Technicians',
    component: () => import('@/views/TechniciansView.vue')
  },
  {
    path: '/units',
    name: 'Units',
    component: () => import('@/views/UnitsView.vue')
  },
  {
    path: '/incoming',
    name: 'Incoming',
    component: () => import('@/views/IncomingView.vue')
  },
  {
    path: '/outgoing',
    name: 'Outgoing',
    component: () => import('@/views/OutgoingView.vue')
  },
  // Legacy route redirect
  {
    path: '/checkout',
    redirect: '/outgoing'
  },
  {
    path: '/returns',
    name: 'Returns',
    component: () => import('@/views/ReturnsView.vue')
  },
  {
    path: '/transactions',
    name: 'Transactions',
    component: () => import('@/views/TransactionsView.vue')
  },
  {
    path: '/reports',
    name: 'Reports',
    component: () => import('@/views/ReportsView.vue')
  },
  {
    path: '/work-orders',
    name: 'WorkOrders',
    component: () => import('@/views/WorkOrdersView.vue')
  },
  {
    path: '/vendor-returns',
    name: 'VendorReturns',
    component: () => import('@/views/VendorReturnsView.vue')
  },
  {
    path: '/fowler-mapping',
    name: 'FowlerMapping',
    component: () => import('@/views/FowlerMappingView.vue')
  },
  {
    path: '/low-stock',
    name: 'LowStock',
    component: () => import('@/views/LowStockView.vue')
  },
  {
    path: '/scanner',
    name: 'Scanner',
    component: () => import('@/views/ScannerView.vue')
  },
  {
    path: '/sitemap',
    name: 'Sitemap',
    component: () => import('@/views/SitemapView.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/settings',
    name: 'Settings',
    component: () => import('@/views/SettingsView.vue'),
    meta: { requiresAdmin: true }
  },
  {
    path: '/logs',
    name: 'Logs',
    component: () => import('@/views/LogsView.vue'),
    meta: { requiresAdmin: true }
  },
  {
    path: '/tests',
    name: 'Tests',
    component: () => import('@/tests/TestView.vue'),
    meta: { requiresAdmin: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard for authentication
router.beforeEach(async (to, from, next) => {
  // Check if route requires authentication (default: true)
  const requiresAuth = to.meta.requiresAuth !== false

  if (!requiresAuth) {
    next()
    return
  }

  try {
    const auth = await getAuthState()

    if (!auth.authenticated) {
      next(`/login?redirect=${encodeURIComponent(to.fullPath)}`)
      return
    }

    if (to.meta.requiresAdmin && auth.role !== 'admin') {
      next('/dashboard')
      return
    }

    next()
  } catch {
    next(`/login?redirect=${encodeURIComponent(to.fullPath)}`)
  }
})

export default router

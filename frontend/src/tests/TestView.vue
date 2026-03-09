<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8 slide-up">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">System Test Center</h1>
          <p class="text-gray-600">Run non-destructive checks against the frontend and API.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button @click="runAllTests" :disabled="isRunning" class="btn-primary">
            <span v-if="isRunning" class="spinner w-4 h-4"></span>
            Run All Tests
          </button>
          <button @click="runBackendTests" :disabled="isRunning" class="btn-outline">
            Backend Only
          </button>
          <button @click="runFrontendTests" :disabled="isRunning" class="btn-outline">
            Frontend Only
          </button>
          <button @click="clearResults" :disabled="isRunning" class="btn-outline">
            Clear
          </button>
        </div>
      </div>
      <div class="text-xs text-gray-500">
        Last run: <span class="font-medium">{{ lastRunLabel }}</span>
      </div>
    </div>

    <!-- Environment -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <div class="card slide-up">
        <div class="card-header">
          <h2 class="text-xl font-semibold text-gray-800">Environment</h2>
        </div>
        <div class="p-6 space-y-3 text-sm">
          <div class="flex items-center justify-between">
            <span class="text-gray-500">Mode</span>
            <span class="font-medium text-gray-900">{{ mode }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-gray-500">API Base URL</span>
            <span class="font-medium text-gray-900">{{ apiBaseUrl }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-gray-500">Origin</span>
            <span class="font-medium text-gray-900">{{ origin }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-gray-500">Online</span>
            <span :class="isOnline ? 'text-green-600 font-medium' : 'text-red-600 font-medium'">
              {{ isOnline ? 'Yes' : 'No' }}
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-gray-500">User Agent</span>
            <span class="font-medium text-gray-900 truncate max-w-[180px]" :title="userAgent">{{ userAgent }}</span>
          </div>
        </div>
      </div>

      <div class="card slide-up">
        <div class="card-header">
          <h2 class="text-xl font-semibold text-gray-800">Summary</h2>
        </div>
        <div class="p-6 grid grid-cols-2 gap-4 text-sm">
          <div class="bg-green-50 border border-green-100 rounded-lg p-3">
            <div class="text-xs text-green-700 uppercase font-semibold">Passed</div>
            <div class="text-2xl font-bold text-green-700">{{ summary.pass }}</div>
          </div>
          <div class="bg-red-50 border border-red-100 rounded-lg p-3">
            <div class="text-xs text-red-700 uppercase font-semibold">Failed</div>
            <div class="text-2xl font-bold text-red-700">{{ summary.fail }}</div>
          </div>
          <div class="bg-yellow-50 border border-yellow-100 rounded-lg p-3">
            <div class="text-xs text-yellow-700 uppercase font-semibold">Running</div>
            <div class="text-2xl font-bold text-yellow-700">{{ summary.running }}</div>
          </div>
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
            <div class="text-xs text-gray-600 uppercase font-semibold">Total</div>
            <div class="text-2xl font-bold text-gray-800">{{ summary.total }}</div>
          </div>
        </div>
      </div>

      <div class="card slide-up">
        <div class="card-header">
          <h2 class="text-xl font-semibold text-gray-800">Manual Checks</h2>
        </div>
        <div class="p-6 space-y-2 text-sm text-gray-700">
          <div>1) Login and logout flow</div>
          <div>2) Incoming: create vendor, add stock, core charge</div>
          <div>3) Outgoing: create work order, unit, technician</div>
          <div>4) Returns: work order, unit, technician returns</div>
          <div>5) Settings: toggle manual mode (allow untracked returns)</div>
          <div>6) Incoming manual mode: multi-part return</div>
          <div>7) Vendor returns with RMA</div>
          <div>8) Transactions filters and paging</div>
          <div>9) Scanner view and part lookup</div>
        </div>
      </div>
    </div>

    <!-- Results -->
    <div class="card slide-up">
      <div class="card-header">
        <h2 class="text-xl font-semibold text-gray-800">Test Results</h2>
        <div class="text-xs text-gray-500">Non-destructive API smoke tests</div>
      </div>
      <div class="overflow-x-auto">
        <table class="data-table">
          <thead>
            <tr>
              <th>Status</th>
              <th>Test</th>
              <th>Group</th>
              <th>Duration</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="result in results" :key="result.id">
              <td>
                <span class="badge" :class="statusClass(result.status)">
                  {{ result.status }}
                </span>
              </td>
              <td class="font-medium text-gray-900">{{ result.label }}</td>
              <td class="text-gray-600">{{ result.group }}</td>
              <td class="text-gray-600">{{ result.durationMs ? `${result.durationMs} ms` : '-' }}</td>
              <td class="text-gray-600">
                <div v-if="result.status === 'fail'" class="text-red-600">
                  {{ result.error || 'Unknown error' }}
                </div>
                <div v-else>
                  {{ result.details || '-' }}
                </div>
              </td>
            </tr>
            <tr v-if="results.length === 0">
              <td colspan="5" class="text-center py-8 text-gray-500">No results yet</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import api, { authApi, partsApi, suppliersApi, techniciansApi, unitsApi, workOrdersApi, assetApi, vendorReturnsApi, settingsApi } from '@/services/api'
import { useToast } from '@/composables/useToast'

const router = useRouter()
const { showToast } = useToast()

const isRunning = ref(false)
const results = ref([])
const lastRunAt = ref(null)

const mode = import.meta.env.MODE
const apiBaseUrl = api.defaults.baseURL || '/api'
const origin = window.location.origin
const userAgent = navigator.userAgent
const isOnline = navigator.onLine

const lastRunLabel = computed(() => {
  if (!lastRunAt.value) return 'Never'
  return new Date(lastRunAt.value).toLocaleString()
})

const summary = computed(() => {
  const counts = { pass: 0, fail: 0, running: 0, skipped: 0 }
  results.value.forEach(result => {
    if (counts[result.status] !== undefined) {
      counts[result.status] += 1
    }
  })
  return {
    pass: counts.pass,
    fail: counts.fail,
    running: counts.running,
    total: results.value.length
  }
})

const statusClass = (status) => {
  if (status === 'pass') return 'badge-success'
  if (status === 'fail') return 'badge-danger'
  if (status === 'running') return 'badge-warning'
  if (status === 'skipped') return 'badge-secondary'
  return 'badge-secondary'
}

const summarizeResponse = (payload) => {
  const data = payload && payload.data !== undefined ? payload.data : payload
  if (Array.isArray(data)) return `Array(${data.length})`
  if (data && Array.isArray(data.data)) return `data: Array(${data.data.length})`
  if (data && typeof data === 'object') {
    const keys = Object.keys(data)
    if (keys.length === 0) return 'Empty object'
    const preview = keys.slice(0, 6).join(', ')
    return `keys: ${preview}${keys.length > 6 ? '...' : ''}`
  }
  if (data === undefined || data === null) return 'No data'
  return String(data)
}

const testPlan = [
  {
    id: 'auth.check',
    type: 'backend',
    group: 'Backend: Auth',
    label: 'Auth check',
    run: async () => authApi.check()
  },
  {
    id: 'auth.me',
    type: 'backend',
    group: 'Backend: Auth',
    label: 'Auth user profile',
    run: async () => authApi.me()
  },
  {
    id: 'parts.list',
    type: 'backend',
    group: 'Backend: Catalog',
    label: 'Parts list (limit 10)',
    run: async () => partsApi.getAll({ limit: 10 })
  },
  {
    id: 'parts.lowStock',
    type: 'backend',
    group: 'Backend: Catalog',
    label: 'Low stock parts',
    run: async () => partsApi.getLowStock()
  },
  {
    id: 'suppliers.list',
    type: 'backend',
    group: 'Backend: Master Data',
    label: 'Suppliers list',
    run: async () => suppliersApi.getAll()
  },
  {
    id: 'technicians.list',
    type: 'backend',
    group: 'Backend: Master Data',
    label: 'Technicians list',
    run: async () => techniciansApi.getAll()
  },
  {
    id: 'units.list',
    type: 'backend',
    group: 'Backend: Master Data',
    label: 'Units list',
    run: async () => unitsApi.getAll()
  },
  {
    id: 'workOrders.list',
    type: 'backend',
    group: 'Backend: Work Orders',
    label: 'Work orders list',
    run: async () => workOrdersApi.getAll()
  },
  {
    id: 'transactions.list',
    type: 'backend',
    group: 'Backend: Transactions',
    label: 'Transactions (limit 5)',
    run: async () => assetApi.getTransactions({ limit: 5 })
  },
  {
    id: 'stock.levels',
    type: 'backend',
    group: 'Backend: Assets',
    label: 'Stock levels',
    run: async () => assetApi.getStockLevels()
  },
  {
    id: 'vendorReturns.list',
    type: 'backend',
    group: 'Backend: Vendor Returns',
    label: 'Vendor returns list',
    run: async () => vendorReturnsApi.getAll()
  },
  {
    id: 'settings.get',
    type: 'backend',
    group: 'Backend: Settings',
    label: 'Settings read (allow untracked returns)',
    run: async () => {
      const response = await settingsApi.get()
      const value = response?.data?.allow_untracked_returns ?? response?.allow_untracked_returns
      if (typeof value !== 'boolean') {
        throw new Error('allow_untracked_returns missing or invalid')
      }
      return { allow_untracked_returns: value }
    }
  },
  {
    id: 'frontend.localStorage',
    type: 'frontend',
    group: 'Frontend: Browser',
    label: 'localStorage read/write',
    run: async () => {
      const key = 'pam_test_key'
      localStorage.setItem(key, 'ok')
      const value = localStorage.getItem(key)
      localStorage.removeItem(key)
      if (value !== 'ok') throw new Error('localStorage failed')
      return { value }
    }
  },
  {
    id: 'frontend.sessionStorage',
    type: 'frontend',
    group: 'Frontend: Browser',
    label: 'sessionStorage read/write',
    run: async () => {
      const key = 'pam_session_test'
      sessionStorage.setItem(key, 'ok')
      const value = sessionStorage.getItem(key)
      sessionStorage.removeItem(key)
      if (value !== 'ok') throw new Error('sessionStorage failed')
      return { value }
    }
  },
  {
    id: 'frontend.router',
    type: 'frontend',
    group: 'Frontend: Router',
    label: 'Router resolves /dashboard',
    run: async () => {
      const match = router.resolve('/dashboard')
      if (!match.matched.length) throw new Error('Route not found')
      return { route: match.name }
    }
  },
  {
    id: 'frontend.time',
    type: 'frontend',
    group: 'Frontend: Runtime',
    label: 'Date and time available',
    run: async () => {
      const now = new Date()
      if (Number.isNaN(now.getTime())) throw new Error('Invalid date')
      return { now: now.toISOString() }
    }
  }
]

const runTests = async (type) => {
  if (isRunning.value) return
  isRunning.value = true
  lastRunAt.value = Date.now()
  results.value = []

  const testsToRun = type === 'all'
    ? testPlan
    : testPlan.filter(test => test.type === type)

  for (const test of testsToRun) {
    const result = {
      id: test.id,
      type: test.type,
      group: test.group,
      label: test.label,
      status: 'running',
      durationMs: null,
      details: ''
    }
    results.value.push(result)

    const start = performance.now()
    try {
      const response = await test.run()
      result.status = 'pass'
      result.details = summarizeResponse(response)
    } catch (error) {
      result.status = 'fail'
      result.error = error?.response?.data?.message || error?.message || 'Unknown error'
    } finally {
      result.durationMs = Math.round(performance.now() - start)
    }
  }

  isRunning.value = false
  showToast('Tests complete', 'Review the results table for details')
}

const runAllTests = () => runTests('all')
const runBackendTests = () => runTests('backend')
const runFrontendTests = () => runTests('frontend')

const clearResults = () => {
  if (isRunning.value) return
  results.value = []
}
</script>

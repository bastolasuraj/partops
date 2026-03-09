<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8 slide-up">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 mb-2">welcome to the dashboard, {{ firstName }}</h1>
          <p class="text-gray-600 text-base">Overview of your parts asset management system</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-sm font-medium border border-blue-200">
            <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
            Live Data
          </span>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="text-center">
        <div class="spinner mx-auto mb-4"></div>
        <p class="text-gray-500">Loading dashboard data...</p>
      </div>
    </div>

    <!-- Dashboard Content -->
    <div v-else class="space-y-6">
      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-6 slide-up">
        <!-- Total Parts -->
        <div class="card hover:shadow-xl transition-shadow cursor-pointer p-6" @click="navigateTo('/parts')">
          <div class="flex items-center justify-between gap-4">
            <div class="flex-1 min-w-0">
              <p class="text-sm text-gray-600 font-medium mb-2">Total Parts</p>
              <p :class="[statValueClass, 'text-gray-900']">{{ stats.totalParts }}</p>
              <p class="text-xs text-gray-500 mt-2 truncate">{{ stats.uniqueFowlerPns }} unique Fowler PNs</p>
            </div>
            <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Total Asset Cost -->
        <div class="card hover:shadow-xl transition-shadow cursor-pointer p-6" @click="navigateTo('/parts')">
          <div class="flex items-center justify-between gap-4">
            <div class="flex-1 min-w-0">
              <p class="text-sm text-gray-600 font-medium mb-2">Total Asset Cost</p>
                <p :class="[statValueClass, 'text-emerald-700']" :title="formattedTotalAssetCost">
                  {{ formattedTotalAssetCost }}
                </p>
              <p class="text-xs text-gray-500 mt-2">Current on-hand value</p>
            </div>
            <div class="w-14 h-14 bg-emerald-100 rounded-xl flex items-center justify-center flex-shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M8 12h8"></path>
                <path d="M12 8v8"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Low Stock Items -->
        <div class="card hover:shadow-xl transition-shadow cursor-pointer p-6" @click="navigateTo('/low-stock')">
          <div class="flex items-center justify-between gap-4">
            <div class="flex-1 min-w-0">
              <p class="text-sm text-gray-600 font-medium mb-2">Low Stock Items</p>
              <p :class="[statValueClass, 'text-red-600']">{{ stats.lowStockCount }}</p>
              <p class="text-xs text-gray-500 mt-2">Needs attention</p>
            </div>
            <div class="w-14 h-14 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
              </svg>
            </div>
          </div>
        </div>

        <!-- Total Suppliers -->
        <div class="card hover:shadow-xl transition-shadow cursor-pointer p-6" @click="navigateTo('/suppliers')">
          <div class="flex items-center justify-between gap-4">
            <div class="flex-1 min-w-0">
              <p class="text-sm text-gray-600 font-medium mb-2">Suppliers</p>
              <p :class="[statValueClass, 'text-gray-900']">{{ stats.totalSuppliers }}</p>
              <p class="text-xs text-gray-500 mt-2">Active vendors</p>
            </div>
            <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="1" y="3" width="15" height="13"></rect>
                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                <circle cx="5.5" cy="18.5" r="2.5"></circle>
                <circle cx="18.5" cy="18.5" r="2.5"></circle>
              </svg>
            </div>
          </div>
        </div>

        <!-- Recent Transactions -->
        <div class="card hover:shadow-xl transition-shadow cursor-pointer p-6" @click="navigateTo('/transactions')">
          <div class="flex items-center justify-between gap-4">
            <div class="flex-1 min-w-0">
              <p class="text-sm text-gray-600 font-medium mb-2 truncate">Today's Transactions</p>
              <p :class="[statValueClass, 'text-gray-900']">{{ stats.todayTransactions }}</p>
              <p class="text-xs text-gray-500 mt-2">Last 24 hours</p>
            </div>
            <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center flex-shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="card slide-up">
        <div class="card-header">
          <h2 class="text-xl font-semibold text-gray-800">Quick Actions</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 p-6">
          <button @click="navigateTo('/incoming')" class="quick-action-btn bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <polyline points="19 12 12 19 5 12"></polyline>
            </svg>
            <span class="font-semibold">Check In Parts</span>
            <span class="text-xs opacity-90">Receive assets</span>
          </button>

          <button @click="navigateTo('/outgoing')" class="quick-action-btn bg-gradient-to-br from-green-500 to-green-600 hover:from-green-600 hover:to-green-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="19" x2="12" y2="5"></line>
              <polyline points="5 12 12 5 19 12"></polyline>
            </svg>
            <span class="font-semibold">Check Out Parts</span>
            <span class="text-xs opacity-90">Issue to technicians</span>
          </button>

          <button @click="navigateTo('/returns')" class="quick-action-btn bg-gradient-to-br from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="1 4 1 10 7 10"></polyline>
              <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
            </svg>
            <span class="font-semibold">Process Returns</span>
            <span class="text-xs opacity-90">Return to assets</span>
          </button>

          <button @click="navigateTo('/parts')" class="quick-action-btn bg-gradient-to-br from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span class="font-semibold">Add New Part</span>
            <span class="text-xs opacity-90">Create part entry</span>
          </button>
        </div>
      </div>

      <!-- Two Column Layout -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Transactions -->
        <div class="card slide-up">
          <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-800">Recent Transactions</h2>
            <button @click="navigateTo('/transactions')" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
              View All →
            </button>
          </div>
          <div class="divide-y divide-gray-100">
            <div v-if="recentTransactions.length === 0" class="p-8 text-center text-gray-500">
              No recent transactions
            </div>
            <div 
              v-for="transaction in recentTransactions" 
              :key="transaction.id"
              class="p-4 hover:bg-gray-50 transition-colors"
            >
              <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-2">
                    <span 
                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold"
                      :class="{
                        'bg-green-100 text-green-800': transaction.transaction_type === 'incoming',
                        'bg-orange-100 text-orange-800': transaction.transaction_type === 'outgoing',
                        'bg-blue-100 text-blue-800': transaction.transaction_type === 'return'
                      }"
                    >
                      {{ formatTransactionType(transaction.transaction_type, transaction.reference_type) }}
                    </span>
                    <span class="font-semibold text-gray-900 truncate">{{ transaction.part_name }}</span>
                  </div>
                  <div class="text-sm text-gray-600 mb-1">
                    <span class="font-mono">{{ transaction.fowler_part_number }}</span>
                    <span class="mx-1">•</span>
                    <span class="font-medium">Qty: {{ Math.abs(transaction.quantity) }}</span>
                  </div>
                  <div class="flex items-center gap-2 text-xs text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                      <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span class="font-medium">{{ transaction.created_by || transaction.technician_name || 'System' }}</span>
                    <span class="mx-1">•</span>
                    <span>{{ formatDate(transaction.created_at) }}</span>
                  </div>
                </div>
                <div class="flex-shrink-0 text-right">
                  <div 
                    class="text-2xl font-bold"
                    :class="{
                      'text-green-600': transaction.transaction_type === 'incoming',
                      'text-orange-600': transaction.transaction_type === 'outgoing',
                      'text-blue-600': transaction.transaction_type === 'return'
                    }"
                  >
                    {{ transaction.transaction_type === 'outgoing' ? '-' : '+' }}{{ Math.abs(transaction.quantity) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Low Stock Alert -->
        <div class="card slide-up">
          <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-800">Low Stock Alert</h2>
            <button @click="navigateTo('/low-stock')" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
              View All →
            </button>
          </div>
          <div class="divide-y divide-gray-100">
            <div v-if="lowStockParts.length === 0" class="p-8 text-center text-gray-500">
              All parts are well stocked! 🎉
            </div>
            <div 
              v-for="part in lowStockParts" 
              :key="part.id"
              class="p-4 hover:bg-gray-50 transition-colors cursor-pointer"
              @click="navigateTo('/parts')"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <div class="font-medium text-gray-900">{{ part.name }}</div>
                  <div class="text-sm text-gray-600 mt-1">
                    {{ part.fowler_part_number }}
                  </div>
                  <div class="text-xs text-gray-500 mt-1">
                    {{ part.supplier_name || 'No supplier' }}
                  </div>
                </div>
                <div class="text-right">
                  <div class="text-2xl font-bold text-red-600">{{ part.stock }}</div>
                  <div class="text-xs text-gray-500">Min: {{ part.low_stock_threshold }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- System Overview -->
      <div class="card slide-up">
        <div class="card-header">
          <h2 class="text-xl font-semibold text-gray-800">System Overview</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
          <div class="text-center">
            <div class="text-3xl font-bold text-blue-600">{{ stats.totalTechnicians }}</div>
            <div class="text-sm text-gray-600 mt-1">Active Technicians</div>
            <button @click="navigateTo('/technicians')" class="text-xs text-blue-600 hover:text-blue-700 mt-2">
              Manage →
            </button>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-green-600">{{ stats.totalUnits }}</div>
            <div class="text-sm text-gray-600 mt-1">Registered Units</div>
            <button @click="navigateTo('/units')" class="text-xs text-blue-600 hover:text-blue-700 mt-2">
              View →
            </button>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-purple-600">{{ stats.totalStock }}</div>
            <div class="text-sm text-gray-600 mt-1">Total Stock Count</div>
            <button @click="navigateTo('/parts')" class="text-xs text-blue-600 hover:text-blue-700 mt-2">
              Details →
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { authApi, partsApi, suppliersApi, techniciansApi, unitsApi, assetApi } from '@/services/api'
import { useToast } from '@/composables/useToast'

const router = useRouter()
const { showToast } = useToast()

const currentUser = ref(null)
const firstName = computed(() => {
  if (!currentUser.value) {
    return 'User'
  }

  if (currentUser.value.first_name) {
    return currentUser.value.first_name
  }

  const displayName = currentUser.value.display_name || currentUser.value.username || ''
  const parts = displayName.trim().split(/\s+/)
  return parts[0] || 'User'
})

const loading = ref(true)
const stats = ref({
  totalParts: 0,
  uniqueFowlerPns: 0,
  lowStockCount: 0,
  totalSuppliers: 0,
  totalTechnicians: 0,
  totalUnits: 0,
  totalStock: 0,
  todayTransactions: 0,
  totalAssetCost: 0
})

const recentTransactions = ref([])
const lowStockParts = ref([])

const navigateTo = (path) => {
  router.push(path)
}

const formatTransactionType = (type, referenceType) => {
  if (type === 'outgoing') {
    if (referenceType === 'supplier' || referenceType === 'vendor') {
      return 'RETURN TO SUPPLIER'
    }
    return 'CHECKOUT'
  }
  if (type === 'return') {
    return 'BACK TO SHELF'
  }
  if (type === 'incoming') {
    return 'NEW/ADDED'
  }
  return type
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now - date
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return 'Just now'
  if (diffMins < 60) return `${diffMins}m ago`
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffDays < 7) return `${diffDays}d ago`
  
  return date.toLocaleDateString()
}

const formatCurrency = (value) => {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

const formattedTotalAssetCost = computed(() => formatCurrency(stats.value.totalAssetCost))
const statValueClass = 'text-2xl font-bold leading-tight tracking-tight whitespace-nowrap'

const loadCurrentUser = async () => {
  try {
    const response = await authApi.me()
    currentUser.value = response.data.user
  } catch (error) {
    console.error('Failed to load user info:', error)
  }
}

const fetchDashboardData = async () => {
  try {
    loading.value = true

    // Fetch all data in parallel
    const [partsRes, suppliersRes, techniciansRes, unitsRes, transactionsRes] = await Promise.all([
      partsApi.getAll(),
      suppliersApi.getAll(),
      techniciansApi.getAll(),
      unitsApi.getAll(),
      assetApi.getTransactions()
    ])

    const parts = partsRes.data || []
    const suppliers = suppliersRes.data || []
    const technicians = techniciansRes.data || []
    const units = unitsRes.data || []
    const transactions = transactionsRes.data || []

    // Calculate stats
    stats.value.totalParts = parts.length
    
    // Count unique Fowler PNs
    const uniquePns = new Set()
    parts.forEach(part => {
      if (part.fowler_part_number) {
        uniquePns.add(part.fowler_part_number)
      }
    })
    stats.value.uniqueFowlerPns = uniquePns.size

    // Count low stock items
    stats.value.lowStockCount = parts.filter(part => 
      (part.stock || 0) <= (part.low_stock_threshold || 0)
    ).length

    stats.value.totalSuppliers = suppliers.length
    stats.value.totalTechnicians = technicians.length
    stats.value.totalUnits = units.length

    // Calculate total stock
    stats.value.totalStock = parts.reduce((sum, part) => sum + (part.stock || 0), 0)

    // Calculate total asset cost (current stock * current unit price)
    stats.value.totalAssetCost = parts.reduce((sum, part) => {
      const stock = Number(part.stock || 0)
      const unitPrice = Number(part.unit_price || 0)
      return sum + (stock * unitPrice)
    }, 0)

    // Count today's transactions
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    stats.value.todayTransactions = transactions.filter(t => {
      const transDate = new Date(t.created_at)
      return transDate >= today
    }).length

    // Get recent transactions (last 5)
    recentTransactions.value = transactions.slice(0, 5)

    // Get low stock parts (top 5)
    lowStockParts.value = parts
      .filter(part => (part.stock || 0) <= (part.low_stock_threshold || 0))
      .sort((a, b) => (a.stock || 0) - (b.stock || 0))
      .slice(0, 5)

  } catch (error) {
    console.error('Failed to load dashboard data:', error)
    showToast('Error', 'Failed to load dashboard data', 'error')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadCurrentUser()
  fetchDashboardData()
})
</script>

<style scoped>
.quick-action-btn {
  @apply flex flex-col items-center justify-center gap-2 p-6 rounded-xl text-white transition-all duration-200 shadow-lg hover:shadow-xl hover:scale-105;
}

.slide-up {
  animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>

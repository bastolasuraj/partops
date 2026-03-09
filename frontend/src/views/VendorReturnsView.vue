<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8 slide-up">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Vendor Returns</h1>
          <p class="text-gray-600">View returns to suppliers</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="badge badge-info flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
            Live Data
          </span>
        </div>
      </div>
    </div>

    <!-- Vendor Returns Card -->
    <div class="card slide-up">
      <div class="card-header">
        <h2 class="text-xl font-semibold text-gray-800">All Vendor Returns</h2>
        <div class="flex gap-2">
          <button @click="filterStatus = 'all'" 
                  :class="filterStatus === 'all' ? 'btn-primary' : 'btn-outline'"
                  class="text-sm">
            All ({{ vendorReturns.length }})
          </button>
          <button @click="filterStatus = 'pending'" 
                  :class="filterStatus === 'pending' ? 'btn-primary' : 'btn-outline'"
                  class="text-sm">
            Pending ({{ pendingCount }})
          </button>
          <button @click="filterStatus = 'shipped'" 
                  :class="filterStatus === 'shipped' ? 'btn-primary' : 'btn-outline'"
                  class="text-sm">
            Shipped ({{ shippedCount }})
          </button>
          <button @click="filterStatus = 'credited'" 
                  :class="filterStatus === 'credited' ? 'btn-primary' : 'btn-outline'"
                  class="text-sm">
            Credited ({{ creditedCount }})
          </button>
        </div>
      </div>
      
      <!-- Loading State -->
      <div v-if="loading" class="p-8 text-center">
        <div class="spinner mx-auto mb-4"></div>
        <p class="text-gray-500">Loading vendor returns...</p>
      </div>
      
      <!-- Vendor Returns Table -->
      <div v-else class="overflow-x-auto">
        <table class="data-table">
          <thead>
            <tr>
              <th>RMA Number</th>
              <th>Supplier</th>
              <th>Part</th>
              <th>Quantity</th>
              <th>Core Info</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="vr in filteredReturns" :key="vr.id">
              <td>
                <div class="font-mono font-medium text-blue-600">
                  {{ vr.rma_number || 'N/A' }}
                </div>
              </td>
              <td>
                <div class="font-medium text-gray-900">{{ vr.supplier_name || 'Unknown' }}</div>
              </td>
              <td>
                <div class="text-sm">
                  <div class="font-medium text-gray-900">{{ vr.part_name || 'Unknown' }}</div>
                  <div class="text-xs text-gray-500">{{ vr.part_number || '-' }}</div>
                </div>
              </td>
              <td>
                <div class="font-semibold text-gray-900">{{ vr.quantity }}</div>
              </td>
              <td>
                <div v-if="vr.core_cost > 0 || vr.core_rebate > 0" class="text-sm">
                  <div class="text-gray-600">Cost: <span class="font-medium">${{ vr.core_cost }}</span></div>
                  <div class="text-gray-600">Rebate: <span class="font-medium">${{ vr.core_rebate }}</span></div>
                </div>
                <div v-else class="text-gray-400 text-sm">No core</div>
              </td>
              <td>
                <span class="badge" :class="getStatusBadgeClass(vr.status)">
                  {{ vr.status }}
                </span>
              </td>
              <td>
                <div class="text-sm text-gray-600">
                  {{ formatDate(vr.created_at) }}
                </div>
              </td>
            </tr>
            <tr v-if="filteredReturns.length === 0">
              <td colspan="7" class="text-center py-8 text-gray-500">
                No vendor returns found
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Summary Stats -->
      <div v-if="!loading && vendorReturns.length > 0" class="p-6 border-t border-gray-100 bg-gray-50">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="bg-white rounded-lg p-4 border border-gray-200">
            <div class="text-xs text-gray-500 uppercase font-bold mb-1">Total Returns</div>
            <div class="text-2xl font-bold text-gray-900">{{ vendorReturns.length }}</div>
          </div>
          <div class="bg-white rounded-lg p-4 border border-gray-200">
            <div class="text-xs text-gray-500 uppercase font-bold mb-1">Total Core Cost</div>
            <div class="text-2xl font-bold text-blue-600">${{ totalCoreCost.toFixed(2) }}</div>
          </div>
          <div class="bg-white rounded-lg p-4 border border-gray-200">
            <div class="text-xs text-gray-500 uppercase font-bold mb-1">Expected Rebate</div>
            <div class="text-2xl font-bold text-green-600">${{ totalCoreRebate.toFixed(2) }}</div>
          </div>
          <div class="bg-white rounded-lg p-4 border border-gray-200">
            <div class="text-xs text-gray-500 uppercase font-bold mb-1">Net Core Value</div>
            <div class="text-2xl font-bold" :class="netCoreValue >= 0 ? 'text-green-600' : 'text-red-600'">
              ${{ netCoreValue.toFixed(2) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from '@/composables/useToast'
import { vendorReturnsApi } from '@/services/api'

const { showToast } = useToast()

const vendorReturns = ref([])
const loading = ref(true)
const filterStatus = ref('all')

const filteredReturns = computed(() => {
  if (filterStatus.value === 'all') {
    return vendorReturns.value
  }
  return vendorReturns.value.filter(vr => vr.status === filterStatus.value)
})

const pendingCount = computed(() => {
  return vendorReturns.value.filter(vr => vr.status === 'pending').length
})

const shippedCount = computed(() => {
  return vendorReturns.value.filter(vr => vr.status === 'shipped').length
})

const creditedCount = computed(() => {
  return vendorReturns.value.filter(vr => vr.status === 'credited').length
})

const totalCoreCost = computed(() => {
  return vendorReturns.value.reduce((sum, vr) => sum + parseFloat(vr.core_cost || 0), 0)
})

const totalCoreRebate = computed(() => {
  return vendorReturns.value.reduce((sum, vr) => sum + parseFloat(vr.core_rebate || 0), 0)
})

const netCoreValue = computed(() => {
  return totalCoreRebate.value - totalCoreCost.value
})

const fetchVendorReturns = async () => {
  try {
    loading.value = true
    const response = await vendorReturnsApi.getAll()
    vendorReturns.value = response.data || []
  } catch (error) {
    showToast('Error', 'Failed to load vendor returns', 'error')
  } finally {
    loading.value = false
  }
}

const getStatusBadgeClass = (status) => {
  const classes = {
    'pending': 'badge-warning',
    'shipped': 'badge-info',
    'received': 'badge-success',
    'credited': 'badge-success'
  }
  return classes[status] || 'badge-secondary'
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric'
  })
}

onMounted(fetchVendorReturns)
</script>

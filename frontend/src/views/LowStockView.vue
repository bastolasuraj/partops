<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8 slide-up">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Low Stock Alert</h1>
          <p class="text-gray-600">Parts that need reordering</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="badge badge-danger flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
              <line x1="12" y1="9" x2="12" y2="13"></line>
              <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            {{ lowStockParts.length }} Alert(s)
          </span>
        </div>
      </div>
    </div>

    <!-- Alert Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 slide-up">
      <div class="card">
        <div class="card-body">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-xs text-gray-500 uppercase font-bold mb-1">Critical</div>
              <div class="text-3xl font-bold text-red-600">{{ criticalCount }}</div>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </div>
          </div>
          <div class="text-xs text-gray-500 mt-2">Stock = 0</div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-xs text-gray-500 uppercase font-bold mb-1">Low Stock</div>
              <div class="text-3xl font-bold text-orange-600">{{ lowCount }}</div>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-orange-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
              </svg>
            </div>
          </div>
          <div class="text-xs text-gray-500 mt-2">Below threshold</div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-xs text-gray-500 uppercase font-bold mb-1">Total Parts</div>
              <div class="text-3xl font-bold text-gray-900">{{ totalParts }}</div>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
              </svg>
            </div>
          </div>
          <div class="text-xs text-gray-500 mt-2">In assets</div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-xs text-gray-500 uppercase font-bold mb-1">Alert Rate</div>
              <div class="text-3xl font-bold text-purple-600">{{ alertRate }}%</div>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
              </svg>
            </div>
          </div>
          <div class="text-xs text-gray-500 mt-2">Parts needing attention</div>
        </div>
      </div>
    </div>

    <!-- Low Stock Parts Card -->
    <div class="card slide-up">
      <div class="card-header">
        <h2 class="text-xl font-semibold text-gray-800">Low Stock Parts</h2>
        <div class="flex gap-2">
          <button @click="filterLevel = 'all'" 
                  :class="filterLevel === 'all' ? 'btn-primary' : 'btn-outline'"
                  class="text-sm">
            All
          </button>
          <button @click="filterLevel = 'critical'" 
                  :class="filterLevel === 'critical' ? 'btn-primary' : 'btn-outline'"
                  class="text-sm">
            Critical
          </button>
          <button @click="filterLevel = 'low'" 
                  :class="filterLevel === 'low' ? 'btn-primary' : 'btn-outline'"
                  class="text-sm">
            Low
          </button>
        </div>
      </div>
      
      <!-- Loading State -->
      <div v-if="loading" class="p-8 text-center">
        <div class="spinner mx-auto mb-4"></div>
        <p class="text-gray-500">Loading low stock parts...</p>
      </div>
      
      <!-- Low Stock Table -->
      <div v-else class="overflow-x-auto">
        <table class="data-table">
          <thead>
            <tr>
              <th>Alert</th>
              <th>Fowler PN</th>
              <th>Part Details</th>
              <th>Current Stock</th>
              <th>Threshold</th>
              <th>Needed</th>
              <th>Location</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="part in filteredParts" :key="part.id" :class="getRowClass(part)">
              <td>
                <div class="flex items-center justify-center">
                  <svg v-if="part.stock === 0" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-orange-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                  </svg>
                </div>
              </td>
              <td>
                <div class="font-mono font-bold text-blue-600">{{ part.fowler_part_number }}</div>
              </td>
              <td>
                <div class="font-medium text-gray-900">{{ part.name }}</div>
                <div class="text-xs text-gray-500">
                  {{ part.supplier_name || 'N/A' }} • {{ part.supplier_part_number || 'N/A' }}
                </div>
              </td>
              <td>
                <div class="text-2xl font-bold" :class="part.stock === 0 ? 'text-red-600' : 'text-orange-600'">
                  {{ part.stock }}
                </div>
              </td>
              <td>
                <div class="text-gray-600">{{ part.low_stock_threshold }}</div>
              </td>
              <td>
                <div class="font-semibold text-blue-600">
                  {{ Math.max(0, part.low_stock_threshold - part.stock) }}
                </div>
              </td>
              <td>
                <div class="text-sm text-gray-600">
                  {{ formatLocation(part) }}
                </div>
              </td>
              <td>
                <router-link :to="`/incoming?partId=${part.id}`" class="btn-primary-outline btn-sm flex items-center gap-1 justify-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                  </svg>
                  Add Stock
                </router-link>
              </td>
            </tr>
            <tr v-if="filteredParts.length === 0">
              <td colspan="8" class="text-center py-8 text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 mb-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                  <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <p class="font-medium">All parts are well stocked!</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { partsApi } from '@/services/api'
import { useToast } from '@/composables/useToast'

const { showToast } = useToast()

const lowStockParts = ref([])
const loading = ref(true)
const filterLevel = ref('all')
const totalParts = ref(0)

const criticalCount = computed(() => {
  return lowStockParts.value.filter(p => p.stock === 0).length
})

const lowCount = computed(() => {
  return lowStockParts.value.filter(p => p.stock > 0 && p.stock <= p.low_stock_threshold).length
})

const alertRate = computed(() => {
  if (totalParts.value === 0) return 0
  return Math.round((lowStockParts.value.length / totalParts.value) * 100)
})

const filteredParts = computed(() => {
  if (filterLevel.value === 'all') {
    return lowStockParts.value
  } else if (filterLevel.value === 'critical') {
    return lowStockParts.value.filter(p => p.stock === 0)
  } else if (filterLevel.value === 'low') {
    return lowStockParts.value.filter(p => p.stock > 0 && p.stock <= p.low_stock_threshold)
  }
  return lowStockParts.value
})

const fetchLowStock = async () => {
  try {
    loading.value = true
    
    // Get all parts to calculate total
    const allPartsResponse = await partsApi.getAll()
    totalParts.value = (allPartsResponse.data || []).length
    
    // Get low stock parts
    const response = await partsApi.getLowStock()
    lowStockParts.value = response.data || []
  } catch (error) {
    showToast('Error', 'Failed to load low stock parts', 'error')
  } finally {
    loading.value = false
  }
}

const getRowClass = (part) => {
  if (part.stock === 0) {
    return 'bg-red-50'
  } else if (part.stock <= part.low_stock_threshold) {
    return 'bg-orange-50'
  }
  return ''
}

const formatLocation = (part) => {
  if (part?.location_alt) {
    return part.location_alt
  }
  const loc = [part.location_aisle, part.location_shelf, part.location_bay].filter(Boolean)
  return loc.length > 0 ? loc.join('-') : '-'
}

onMounted(fetchLowStock)
</script>

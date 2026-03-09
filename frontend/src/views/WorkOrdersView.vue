<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8 slide-up">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Work Orders</h1>
          <p class="text-gray-600">View all work orders</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="badge badge-info flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
            Live Data
          </span>
        </div>
      </div>
    </div>

    <!-- Work Orders Card -->
    <div class="card slide-up">
      <div class="card-header">
        <h2 class="text-xl font-semibold text-gray-800">All Work Orders</h2>
        <div class="text-sm text-gray-600">
          Total: {{ workOrders.length }}
        </div>
      </div>
      
      <!-- Loading State -->
      <div v-if="loading" class="p-8 text-center">
        <div class="spinner mx-auto mb-4"></div>
        <p class="text-gray-500">Loading work orders...</p>
      </div>
      
      <!-- Work Orders Table -->
      <div v-else class="overflow-x-auto">
        <table class="data-table">
          <thead>
            <tr>
              <th>WO Number</th>
              <th>Created Date</th>
              <th>Last Updated</th>
              <th>Parts Used</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="wo in filteredWorkOrders" :key="wo.id">
              <td>
                <div class="font-mono font-bold text-blue-600">{{ wo.wo_number }}</div>
              </td>
              <td>
                <div class="text-sm text-gray-600">
                  {{ formatDate(wo.created_at) }}
                </div>
              </td>
              <td>
                <div class="text-sm text-gray-600">
                  {{ formatDate(wo.updated_at) }}
                </div>
              </td>
              <td>
                <button @click="viewParts(wo)" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                  View Parts
                </button>
              </td>
            </tr>
            <tr v-if="filteredWorkOrders.length === 0">
              <td colspan="4" class="text-center py-8 text-gray-500">
                No work orders found
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Parts Modal -->
    <Transition name="fade">
      <div v-if="showPartsModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-3xl rounded-xl shadow-2xl flex flex-col max-h-[90vh] slide-up">
          <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-900">
              Parts Used - {{ selectedWO?.wo_number }}
            </h3>
            <button @click="showPartsModal = false" class="text-gray-400 hover:text-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          
          <div class="p-6 overflow-y-auto">
            <div v-if="loadingParts" class="text-center py-8">
              <div class="spinner mx-auto mb-4"></div>
              <p class="text-gray-500">Loading parts...</p>
            </div>
            
            <div v-else-if="woParts.length === 0" class="text-center py-8 text-gray-500">
              No parts used for this work order yet
            </div>
            
            <div v-else class="space-y-3">
              <div v-for="part in woParts" :key="part.id" 
                   class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <div class="font-semibold text-gray-900">{{ part.part_number }}</div>
                    <div class="text-sm text-gray-600">{{ part.part_name }}</div>
                    <div class="text-xs text-gray-500 mt-1">
                      {{ formatDate(part.created_at) }}
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-lg font-bold text-gray-900">
                      {{ Math.abs(part.quantity) }}
                    </div>
                    <div class="text-xs text-gray-500">
                      {{ part.transaction_type }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="p-6 border-t border-gray-100 flex justify-end bg-gray-50 rounded-b-xl">
            <button @click="showPartsModal = false" class="btn-outline">Close</button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { workOrdersApi, assetApi } from '@/services/api'
import { useToast } from '@/composables/useToast'

const { showToast } = useToast()

const workOrders = ref([])
const loading = ref(true)
const filterStatus = ref('all')
const showPartsModal = ref(false)
const selectedWO = ref(null)
const woParts = ref([])
const loadingParts = ref(false)

const filteredWorkOrders = computed(() => {
  if (filterStatus.value === 'all') {
    return workOrders.value
  }
  return workOrders.value.filter(wo => wo.status === filterStatus.value)
})

const openCount = computed(() => {
  return workOrders.value.filter(wo => wo.status === 'open').length
})

const fetchWorkOrders = async () => {
  try {
    loading.value = true
    const response = await workOrdersApi.getAll()
    workOrders.value = response.data || []
  } catch (error) {
    showToast('Error', 'Failed to load work orders', 'error')
  } finally {
    loading.value = false
  }
}

const viewParts = async (wo) => {
  selectedWO.value = wo
  showPartsModal.value = true
  loadingParts.value = true
  woParts.value = []
  
  try {
    // Get all transactions
    const response = await assetApi.getTransactions()
    const allTransactions = response.data || []
    
    console.log('All transactions:', allTransactions)
    console.log('Looking for WO:', wo.wo_number)
    
    // Filter transactions for this work order
    // Check both reference_number and wo_number fields
    woParts.value = allTransactions.filter(t => {
      const matchesWO = t.wo_number === wo.wo_number || 
                        t.reference_number === wo.wo_number ||
                        (t.reference_type === 'work_order' && t.reference_number === wo.wo_number)
      
      console.log('Transaction:', t.id, 'WO:', t.wo_number, 'Ref:', t.reference_number, 'Matches:', matchesWO)
      return matchesWO
    })
    
    console.log('Filtered parts:', woParts.value)
    
    if (woParts.value.length === 0) {
      showToast('Info', 'No parts found for this work order', 'info')
    }
  } catch (error) {
    console.error('Error loading parts:', error)
    showToast('Error', 'Failed to load parts: ' + (error.message || 'Unknown error'), 'error')
    woParts.value = []
  } finally {
    loadingParts.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(fetchWorkOrders)
</script>

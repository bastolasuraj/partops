<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8 slide-up">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Vendor Returns</h1>
          <p class="text-gray-600">Process return to vendor</p>
        </div>
      </div>
    </div>

    <!-- Returns Card -->
    <div class="card slide-up overflow-visible">
      <div class="card-header">
        <h2 class="text-xl font-semibold text-gray-800">Process Vendor Return</h2>
      </div>
      
      <div class="card-body overflow-visible">
        <form @submit.prevent="processReturn" class="space-y-6">
          <!-- Step 1: Select Part -->
          <div class="form-group relative">
            <label class="form-label">1. Select Part *</label>
            <input
              v-model="partSearch"
              @input="onPartInput"
              @focus="showPartDropdown = true"
              @blur="hidePartDropdown"
              type="text"
              class="form-input"
              :class="{ 
                'border-green-300 focus:border-green-500 focus:ring-green-500': selectedPart
              }"
              placeholder="Type to search parts..."
              required
            />
            
            <!-- Dropdown for parts -->
            <div 
              v-if="showPartDropdown && filteredParts.length > 0"
              class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
            >
              <button
                v-for="p in filteredParts"
                :key="p.id"
                @mousedown.prevent="selectPart(p)"
                type="button"
                class="w-full px-4 py-3 text-left hover:bg-blue-50 transition-colors border-b border-gray-100 last:border-b-0"
              >
                <div class="flex items-center justify-between">
                  <div class="flex-1">
                    <div class="font-medium text-gray-900">{{ p.fowler_part_number }}</div>
                    <div class="text-sm text-gray-600">{{ p.name }}</div>
                    <div class="text-xs text-gray-500 mt-1">
                      Supplier: {{ p.supplier_name || 'N/A' }}
                    </div>
                  </div>
                  <div class="ml-3">
                    <span 
                      class="px-2 py-1 text-xs font-medium rounded"
                      :class="(p.stock || 0) > 0 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                    >
                      Stock: {{ p.stock || 0 }}
                    </span>
                  </div>
                </div>
              </button>
            </div>
            
            <!-- Selected part display -->
            <div v-if="selectedPart && !showPartDropdown" class="mt-3 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg">
              <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                  <div class="text-sm font-medium text-gray-500 mb-1">Selected Part</div>
                  <div class="font-bold text-lg text-gray-900">{{ selectedPart.fowler_part_number }}</div>
                  <div class="text-sm text-gray-700">{{ selectedPart.name }}</div>
                </div>
                <button
                  @click="clearPart"
                  type="button"
                  class="text-blue-600 hover:text-blue-800"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                  </svg>
                </button>
              </div>
              
              <div class="grid grid-cols-2 gap-3">
                <div class="bg-white rounded-lg p-3 border border-blue-100">
                  <div class="text-xs text-gray-500 uppercase font-medium mb-1">Current Stock</div>
                  <div class="text-2xl font-bold" :class="(selectedPart.stock || 0) > 0 ? 'text-green-600' : 'text-gray-400'">
                    {{ selectedPart.stock || 0 }}
                  </div>
                </div>
                <div class="bg-white rounded-lg p-3 border border-blue-100">
                  <div class="text-xs text-gray-500 uppercase font-medium mb-1">Supplier</div>
                  <div class="font-semibold text-gray-900">{{ selectedPart.supplier_name || 'Multiple' }}</div>
                </div>
              </div>
              
              <div v-if="selectedPart.has_core" class="mt-3 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                <div class="flex items-center gap-2 text-yellow-800">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                  </svg>
                  <span class="text-sm font-medium">Core Item</span>
                </div>
                <div class="grid grid-cols-2 gap-2 mt-2 text-xs">
                  <div><span class="text-gray-600">Cost:</span> <span class="font-semibold">${{ selectedPart.core_cost || 0 }}</span></div>
                  <div><span class="text-gray-600">Rebate:</span> <span class="font-semibold">${{ selectedPart.core_rebate || 0 }}</span></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 2: Select Supplier (conditional) -->
          <Transition name="fade">
            <div v-if="selectedPart && showSupplierSelection" class="form-group relative">
              <label class="form-label">2. Select Supplier *</label>
              <input
                v-model="supplierSearch"
                @input="onSupplierInput"
                @focus="showSupplierDropdown = true"
                @blur="hideSupplierDropdown"
                type="text"
                class="form-input"
                :class="{ 
                  'border-green-300 focus:border-green-500 focus:ring-green-500': selectedSupplier
                }"
                placeholder="Type to search suppliers..."
                required
              />
              
              <!-- Dropdown for suppliers -->
              <div 
                v-if="showSupplierDropdown && filteredSuppliers.length > 0"
                class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
              >
                <button
                  v-for="s in filteredSuppliers"
                  :key="s.id"
                  @mousedown.prevent="selectSupplier(s)"
                  type="button"
                  class="w-full px-4 py-3 text-left hover:bg-blue-50 transition-colors border-b border-gray-100 last:border-b-0"
                >
                  <div class="font-medium text-gray-900">{{ s.name }}</div>
                  <div v-if="s.contact_name" class="text-xs text-gray-500">Contact: {{ s.contact_name }}</div>
                </button>
              </div>
              
              <!-- Selected supplier display -->
              <div v-if="selectedSupplier && !showSupplierDropdown" class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center justify-between">
                  <div>
                    <div class="font-medium text-blue-900">{{ selectedSupplier.name }}</div>
                    <div v-if="selectedSupplier.contact_name" class="text-sm text-blue-700">{{ selectedSupplier.contact_name }}</div>
                  </div>
                  <button
                    @click="clearSupplier"
                    type="button"
                    class="text-blue-600 hover:text-blue-800"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <line x1="18" y1="6" x2="6" y2="18"></line>
                      <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </Transition>

          <!-- Auto-selected supplier message -->
          <Transition name="fade">
            <div v-if="selectedPart && !showSupplierSelection && selectedSupplier" class="p-4 bg-green-50 border border-green-200 rounded-lg">
              <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-600 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                  <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <div class="flex-1">
                  <div class="text-sm font-semibold text-green-800 mb-1">Supplier Auto-Selected</div>
                  <div class="text-sm text-green-700">
                    <strong>{{ selectedSupplier.name }}</strong> - This part is only received from this supplier
                  </div>
                </div>
              </div>
            </div>
          </Transition>

          <!-- Step 3: RMA Number -->
          <Transition name="fade">
            <div v-if="selectedPart && form.supplierId" class="form-group">
              <label class="form-label">3. RMA Number</label>
              <input type="text" v-model="form.rmaNumber" class="form-input" placeholder="Optional">
            </div>
          </Transition>
          
          <!-- Step 4: Quantity -->
          <Transition name="fade">
            <div v-if="selectedPart && form.supplierId" class="form-group">
              <label class="form-label">4. Quantity *</label>
              <input type="number" v-model="form.qty" class="form-input" min="1" required>
              <p v-if="selectedPart && form.qty > selectedPart.stock" class="text-xs text-orange-600 mt-1 flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                  <line x1="12" y1="9" x2="12" y2="13"></line>
                  <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                Warning: Quantity exceeds available stock ({{ selectedPart.stock || 0 }})
              </p>
            </div>
          </Transition>

          <!-- Step 5: Notes -->
          <Transition name="fade">
            <div v-if="selectedPart && form.supplierId" class="form-group">
              <label class="form-label">5. Notes</label>
              <textarea v-model="form.notes" class="form-input" rows="3" placeholder="Optional notes about this return"></textarea>
            </div>
          </Transition>

          <!-- Core Section: Auto-Fetched -->
          <Transition name="fade">
            <div v-if="form.isCore" class="md:col-span-2 bg-purple-50 border border-purple-100 p-4 rounded-lg slide-up">
              <div class="flex items-center gap-2 mb-4 text-purple-700 font-semibold border-b border-purple-200 pb-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="12" y1="8" x2="12" y2="12"></line>
                  <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>Core Item Detected</span>
              </div>
              <div class="grid grid-cols-3 gap-4">
                <div>
                  <label class="form-label text-purple-700">Core Cost (Auto-fetched)</label>
                  <input 
                    type="number" 
                    step="0.01"
                    v-model="form.coreCost" 
                    class="form-input bg-purple-100 border-purple-200 text-purple-800 font-semibold cursor-not-allowed" 
                    disabled
                  >
                  <p class="text-xs text-purple-600 mt-1">Original core deposit paid</p>
                </div>
                <div>
                  <label class="form-label text-purple-700">Est. Rebate (Auto-fetched)</label>
                  <input 
                    type="number" 
                    step="0.01"
                    v-model="form.coreRebate" 
                    class="form-input bg-purple-100 border-purple-200 text-purple-800 font-semibold cursor-not-allowed" 
                    disabled
                  >
                  <p class="text-xs text-purple-600 mt-1">Expected rebate amount</p>
                </div>
                <div>
                  <label class="form-label text-purple-700">Rebate Received</label>
                  <input 
                    type="number" 
                    step="0.01"
                    v-model="form.coreRebateReceived" 
                    class="form-input border-purple-300 focus:border-purple-500 focus:ring-purple-500" 
                    placeholder="0.00"
                  >
                  <p class="text-xs text-purple-600 mt-1">Actual rebate received</p>
                </div>
              </div>
            </div>
          </Transition>

          <div class="md:col-span-2 flex justify-end pt-4">
            <button type="submit" :disabled="processing" class="btn-primary">
              <span v-if="processing" class="spinner w-4 h-4"></span>
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 14 4 9 9 4"></polyline>
                <path d="M20 20v-7a4 4 0 0 0-4-4H4"></path>
              </svg>
              Process Return
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Recent Returns -->
    <div class="card slide-up mt-8">
      <div class="card-header">
        <h2 class="text-xl font-semibold text-gray-800">Recent Returns</h2>
      </div>
      
      <div v-if="loadingReturns" class="p-8 text-center">
        <div class="spinner mx-auto mb-4"></div>
        <p class="text-gray-500">Loading returns...</p>
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="data-table">
          <thead>
            <tr>
              <th>Part</th>
              <th>Supplier</th>
              <th>Qty</th>
              <th>RMA #</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="ret in returns" :key="ret.id">
              <td class="font-mono text-blue-600">{{ ret.fowler_part_number }}</td>
              <td>{{ ret.supplier_name }}</td>
              <td>{{ ret.quantity }}</td>
              <td>{{ ret.rma_number || '-' }}</td>
              <td>
                <span class="badge" :class="getStatusBadge(ret.status)">
                  {{ ret.status }}
                </span>
              </td>
              <td class="text-sm text-gray-500">{{ formatDate(ret.created_at) }}</td>
            </tr>
            <tr v-if="returns.length === 0">
              <td colspan="6" class="text-center py-8 text-gray-500">No returns found</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { partsApi, suppliersApi, vendorReturnsApi } from '@/services/api'
import { useToast } from '@/composables/useToast'

const { showToast } = useToast()

const parts = ref([])
const suppliers = ref([])
const returns = ref([])
const processing = ref(false)
const loadingReturns = ref(true)

// Part search
const partSearch = ref('')
const selectedPart = ref(null)
const showPartDropdown = ref(false)

// Supplier search
const supplierSearch = ref('')
const selectedSupplier = ref(null)
const showSupplierDropdown = ref(false)
const showSupplierSelection = ref(false)

const form = ref({
  supplierId: '',
  partId: '',
  qty: 1,
  rmaNumber: '',
  notes: '',
  isCore: false,
  coreCost: 0,
  coreRebate: 0,
  coreRebateReceived: 0
})

// Computed properties for filtering
const filteredParts = computed(() => {
  if (!partSearch.value.trim()) {
    return parts.value
  }
  
  const search = partSearch.value.toLowerCase()
  return parts.value.filter(part => 
    part.fowler_part_number.toLowerCase().includes(search) ||
    part.name.toLowerCase().includes(search) ||
    (part.supplier_name && part.supplier_name.toLowerCase().includes(search))
  )
})

const filteredSuppliers = computed(() => {
  if (!supplierSearch.value.trim()) {
    return suppliers.value
  }
  
  const search = supplierSearch.value.toLowerCase()
  return suppliers.value.filter(supplier => 
    supplier.name.toLowerCase().includes(search) ||
    (supplier.contact_name && supplier.contact_name.toLowerCase().includes(search))
  )
})

const fetchData = async () => {
  try {
    const [partsRes, suppliersRes] = await Promise.all([
      partsApi.getAll(),
      suppliersApi.getAll()
    ])
    
    parts.value = partsRes.data || []
    suppliers.value = suppliersRes.data || []
  } catch (error) {
    showToast('Error', 'Failed to load data', 'error')
  }
}

const fetchReturns = async () => {
  try {
    loadingReturns.value = true
    const response = await vendorReturnsApi.getAll()
    returns.value = response.data || []
  } catch (error) {
    console.error('Failed to load returns:', error)
  } finally {
    loadingReturns.value = false
  }
}

// Part selection handlers
const onPartInput = () => {
  if (!selectedPart.value) {
    showPartDropdown.value = true
  }
}

const hidePartDropdown = () => {
  setTimeout(() => {
    showPartDropdown.value = false
  }, 200)
}

const selectPart = (part) => {
  selectedPart.value = part
  partSearch.value = `${part.fowler_part_number} — ${part.name}`
  showPartDropdown.value = false
  form.value.partId = part.id
  
  // Set core info
  form.value.isCore = !!part.has_core
  form.value.coreCost = part.core_cost || 0
  form.value.coreRebate = part.core_rebate || 0
  
  // Check if part has a single supplier
  if (part.supplier_id) {
    // Part has a single supplier - auto-select it
    const supplier = suppliers.value.find(s => s.id === part.supplier_id)
    if (supplier) {
      selectedSupplier.value = supplier
      supplierSearch.value = supplier.name
      form.value.supplierId = supplier.id
      showSupplierSelection.value = false
      showToast('Info', `Supplier auto-selected: ${supplier.name}`, 'success')
    }
  } else {
    // Part has multiple suppliers or no supplier - show selection
    showSupplierSelection.value = true
    clearSupplier()
  }
  
  if (part.has_core) {
    showToast('Info', 'Core Item Detected', 'success')
  }
}

const clearPart = () => {
  selectedPart.value = null
  partSearch.value = ''
  form.value.partId = ''
  form.value.isCore = false
  form.value.coreCost = 0
  form.value.coreRebate = 0
  clearSupplier()
  showSupplierSelection.value = false
}

// Supplier selection handlers
const onSupplierInput = () => {
  if (!selectedSupplier.value) {
    showSupplierDropdown.value = true
  }
}

const hideSupplierDropdown = () => {
  setTimeout(() => {
    showSupplierDropdown.value = false
  }, 200)
}

const selectSupplier = (supplier) => {
  selectedSupplier.value = supplier
  supplierSearch.value = supplier.name
  showSupplierDropdown.value = false
  form.value.supplierId = supplier.id
}

const clearSupplier = () => {
  selectedSupplier.value = null
  supplierSearch.value = ''
  form.value.supplierId = ''
}

const processReturn = async () => {
  if (!form.value.supplierId || !form.value.partId) {
    showToast('Error', 'Please select supplier and part', 'error')
    return
  }
  
  if (!form.value.qty || form.value.qty < 1) {
    showToast('Error', 'Quantity must be at least 1', 'error')
    return
  }
  
  // Check stock
  const part = parts.value.find(p => p.id === parseInt(form.value.partId))
  if (part && (part.stock || 0) < form.value.qty) {
    showToast('Error', `Insufficient stock: available ${part.stock || 0}`, 'error')
    return
  }
  
  try {
    processing.value = true
    
    await vendorReturnsApi.create({
      supplier_id: parseInt(form.value.supplierId),
      part_id: parseInt(form.value.partId),
      quantity: parseInt(form.value.qty),
      rma_number: form.value.rmaNumber || null,
      core_cost: parseFloat(form.value.coreCost) || 0,
      core_rebate: parseFloat(form.value.coreRebate) || 0,
      notes: form.value.notes || null
    })
    
    showToast('Success', 'Return processed successfully')
    
    // Reset form
    clearPart()
    form.value = {
      supplierId: '',
      partId: '',
      qty: 1,
      rmaNumber: '',
      notes: '',
      isCore: false,
      coreCost: 0,
      coreRebate: 0
    }
    
    // Refresh data
    await Promise.all([fetchData(), fetchReturns()])
    
  } catch (error) {
    showToast('Error', error.response?.data?.message || 'Failed to process return', 'error')
  } finally {
    processing.value = false
  }
}

const getStatusBadge = (status) => {
  const badges = {
    pending: 'badge-warning',
    shipped: 'badge-info',
    received: 'badge-success',
    credited: 'badge-success'
  }
  return badges[status] || 'badge-info'
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString()
}

onMounted(() => {
  fetchData()
  fetchReturns()
})
</script>

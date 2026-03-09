<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8 slide-up">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Parts Checkout</h1>
          <p class="text-gray-600">Issue parts to work orders</p>
        </div>
      </div>
    </div>

    <!-- Checkout Card -->
    <div class="card slide-up">
      <div class="card-header flex-col md:flex-row items-start md:items-center gap-4">
        <h2 class="text-xl font-semibold text-gray-800">Parts Checkout</h2>
        <div class="flex p-1 bg-gray-100 rounded-lg">
          <button 
            v-for="dest in checkoutDests" 
            :key="dest.val"
            @click="form.dest = dest.val"
            class="px-4 py-1.5 rounded-md text-xs font-medium transition-all"
            :class="form.dest === dest.val ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
          >
            {{ dest.label }}
          </button>
        </div>
      </div>
      
      <div class="card-body">
        <div class="grid grid-cols-1 gap-6">
          <div class="form-group">
            <label class="form-label">
              Select {{ destLabel }} Reference
            </label>
            <select v-model="form.refId" class="form-input">
              <option value="">-- Select --</option>
              <template v-if="form.dest === 'wo'">
                <option v-for="w in workOrders" :key="w.id" :value="w.id">{{ w.wo_number }}</option>
              </template>
              <template v-else-if="form.dest === 'unit'">
                <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
              </template>
              <template v-else>
                <option v-for="t in technicians" :key="t.id" :value="t.id">{{ t.name }}</option>
              </template>
            </select>
          </div>

          <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
            <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
              </svg>
              Part Basket
            </h3>
            <div class="space-y-3">
              <div 
                v-for="(item, idx) in basket" 
                :key="idx" 
                class="flex flex-col md:flex-row gap-3 items-end bg-white p-3 rounded-lg border border-gray-200 shadow-sm"
              >
                <div class="flex-1 w-full">
                  <label class="text-xs text-gray-500 font-medium mb-1 block">Part</label>
                  <select 
                    v-model="item.partId" 
                    @change="checkDuplicate(idx)"
                    class="form-input"
                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': item.isDuplicate }"
                  >
                    <option value="">Select Part</option>
                    <option 
                      v-for="p in getAvailablePartsForItem(idx)" 
                      :key="p.id" 
                      :value="p.id"
                    >
                      {{ p.fowler_part_number }} — {{ p.name }} (Stock: {{ p.stock || 0 }})
                    </option>
                  </select>
                  <p v-if="item.isDuplicate" class="text-xs text-red-600 mt-1 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="10"></circle>
                      <line x1="12" y1="8" x2="12" y2="12"></line>
                      <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    This part is already in the basket
                  </p>
                </div>
                <div class="w-full md:w-32">
                  <label class="text-xs text-gray-500 font-medium mb-1 block">Qty</label>
                  <input type="number" v-model="item.qty" class="form-input" min="1">
                </div>
                <button 
                  @click="removeFromBasket(idx)" 
                  class="p-2.5 text-red-500 hover:bg-red-50 rounded-lg border border-red-100 transition-colors"
                  :disabled="basket.length === 1"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                  </svg>
                </button>
              </div>
            </div>
            <button 
              v-if="hasAvailableParts" 
              @click="addToBasket" 
              class="mt-4 btn-outline text-sm w-full md:w-auto"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
              Add Another Part
            </button>
            <div v-else class="mt-4 text-sm text-gray-500 flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
              All available parts have been added to the basket
            </div>
          </div>

          <div class="flex justify-end pt-2">
            <button @click="processCheckout" :disabled="processing" class="btn-primary w-full md:w-auto justify-center">
              <span v-if="processing" class="spinner w-4 h-4"></span>
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
              Complete Checkout
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { partsApi, workOrdersApi, unitsApi, techniciansApi, assetApi } from '@/services/api'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const { showToast } = useToast()

const parts = ref([])
const workOrders = ref([])
const units = ref([])
const technicians = ref([])
const processing = ref(false)

const checkoutDests = [
  { val: 'wo', label: 'To Work Order' },
  { val: 'unit', label: 'To Unit' },
  { val: 'tech', label: 'To Tech' }
]

const form = ref({
  dest: 'wo',
  refId: ''
})

const basket = ref([{ partId: '', qty: 1, isDuplicate: false }])

const destLabel = computed(() => {
  const dest = checkoutDests.find(d => d.val === form.value.dest)
  return dest ? dest.label.replace('To ', '') : ''
})

// Get list of already selected part IDs
const selectedPartIds = computed(() => {
  return basket.value
    .map(item => item.partId)
    .filter(id => id !== '' && id !== null)
})

// Check if a part is already in the basket
const isPartSelected = (partId, currentIndex) => {
  return basket.value.some((item, idx) => 
    idx !== currentIndex && item.partId === partId
  )
}

// Get available parts for a specific basket item (excluding already selected)
const getAvailablePartsForItem = (currentIndex) => {
  return parts.value.filter(part => {
    const isAlreadySelected = basket.value.some((item, idx) => 
      idx !== currentIndex && item.partId === part.id
    )
    return !isAlreadySelected
  })
}

// Check if there are any unselected parts available
const hasAvailableParts = computed(() => {
  const selectedIds = basket.value
    .map(item => item.partId)
    .filter(id => id !== '' && id !== null)
  
  return parts.value.length > selectedIds.length
})

const fetchData = async () => {
  try {
    const [partsRes, woRes, unitsRes, techsRes] = await Promise.all([
      partsApi.getAll(),
      workOrdersApi.getAll(),
      unitsApi.getAll(),
      techniciansApi.getAll()
    ])
    
    parts.value = partsRes.data || []
    workOrders.value = woRes.data || []
    units.value = unitsRes.data || []
    technicians.value = techsRes.data || []
    
    // Check for pre-selected part from query
    if (route.query.partId) {
      basket.value = [{ partId: parseInt(route.query.partId), qty: 1 }]
    }
  } catch (error) {
    showToast('Error', 'Failed to load data', 'error')
  }
}

const checkDuplicate = (idx) => {
  const item = basket.value[idx]
  item.isDuplicate = isPartSelected(item.partId, idx)
  
  if (item.isDuplicate) {
    showToast('Warning', 'This part is already in the basket', 'warning')
  }
}

const addToBasket = () => {
  basket.value.push({ partId: '', qty: 1, isDuplicate: false })
}

const removeFromBasket = (idx) => {
  if (basket.value.length > 1) {
    basket.value.splice(idx, 1)
  }
}

const processCheckout = async () => {
  if (!form.value.refId) {
    showToast('Error', 'Please select a destination', 'error')
    return
  }
  
  const validItems = basket.value.filter(item => item.partId && item.qty > 0)
  if (validItems.length === 0) {
    showToast('Error', 'Please add at least one part to checkout', 'error')
    return
  }
  
  // Validate stock
  for (const item of validItems) {
    const part = parts.value.find(p => p.id === parseInt(item.partId))
    if (part && (part.stock || 0) < item.qty) {
      showToast('Error', `Insufficient stock for ${part.fowler_part_number}`, 'error')
      return
    }
  }
  
  try {
    processing.value = true
    
    const refType = form.value.dest === 'wo' ? 'work_order' : form.value.dest === 'unit' ? 'unit' : 'technician'
    
    await assetApi.processCheckout({
      items: validItems.map(item => ({
        part_id: parseInt(item.partId),
        quantity: parseInt(item.qty)
      })),
      reference_type: refType,
      reference_id: parseInt(form.value.refId)
    })
    
    showToast('Success', 'Checkout completed successfully')
    
    // Reset basket
    basket.value = [{ partId: '', qty: 1 }]
    form.value.refId = ''
    
    // Refresh parts to get updated stock
    const partsRes = await partsApi.getAll()
    parts.value = partsRes.data || []
    
  } catch (error) {
    showToast('Error', error.response?.data?.message || 'Failed to process checkout', 'error')
  } finally {
    processing.value = false
  }
}

onMounted(fetchData)
</script>

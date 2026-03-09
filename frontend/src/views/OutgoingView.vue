<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8 slide-up">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Parts Outgoing</h1>
          <p class="text-gray-600">Issue parts to work orders</p>
        </div>
      </div>
    </div>

    <!-- Outgoing Card -->
    <div class="card slide-up overflow-visible">
      <div class="card-header flex-col md:flex-row items-start md:items-center gap-4">
        <h2 class="text-xl font-semibold text-gray-800">Parts Outgoing</h2>
        <div class="flex p-1 bg-gray-100 rounded-lg">
          <button 
            v-for="dest in OutgoingDests" 
            :key="dest.val"
            @click="setDest(dest.val)"
            class="px-4 py-1.5 rounded-md text-xs font-medium transition-all"
            :class="form.dest === dest.val ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
          >
            {{ dest.label }}
          </button>
        </div>
      </div>
      
      <div class="card-body">
        <div class="grid grid-cols-1 gap-6">
          <!-- Work Order Autocomplete -->
          <div v-if="form.dest === 'wo'" class="form-group relative">
            <label class="form-label">
              Work Order Number
            </label>
            <input
              v-model="workOrderSearch"
              @input="onWorkOrderInput"
              @focus="showWorkOrderDropdown = true"
              @blur="hideWorkOrderDropdown"
              type="text"
              class="form-input"
              placeholder="Type to search or create new work order..."
            />
            
            <!-- Dropdown for existing work orders -->
            <div 
              v-if="showWorkOrderDropdown && filteredWorkOrders.length > 0"
              class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
            >
              <button
                v-for="wo in filteredWorkOrders"
                :key="wo.id"
                @mousedown.prevent="selectWorkOrder(wo)"
                class="w-full px-4 py-2 text-left hover:bg-blue-50 transition-colors border-b border-gray-100 last:border-b-0"
              >
                <div class="font-medium text-gray-900">{{ wo.wo_number }}</div>
                <div class="text-xs text-gray-500">Items: {{ wo.item_count }}</div>
              </button>
            </div>
            
            <!-- Selected work order display -->
            <div v-if="selectedWorkOrder" class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
              <div class="flex items-center justify-between">
                <div>
                  <div class="font-medium text-blue-900">{{ selectedWorkOrder.wo_number }}</div>
                  <div class="text-sm text-blue-700">Items: {{ selectedWorkOrder.item_count }}</div>
                </div>
                <button
                  @click="clearWorkOrder"
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
          
          <!-- Unit/Tech Autocomplete -->
          <div v-else class="form-group relative">
            <label class="form-label">
              Select {{ destLabel }}
            </label>
            <input
              v-model="referenceSearch"
              @input="onReferenceInput"
              @focus="showReferenceDropdown = true"
              @blur="hideReferenceDropdown"
              type="text"
              class="form-input"
              :class="{ 
                'border-green-300 focus:border-green-500 focus:ring-green-500': form.refId
              }"
              :placeholder="`Type to search ${form.dest === 'unit' ? 'units' : 'technicians'}...`"
            />
            
            <!-- Dropdown for references -->
            <div 
              v-if="showReferenceDropdown && filteredReferences.length > 0"
              class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
            >
              <button
                v-for="ref in filteredReferences"
                :key="ref.id"
                @mousedown.prevent="selectReference(ref)"
                type="button"
                class="w-full px-4 py-3 text-left hover:bg-blue-50 transition-colors border-b border-gray-100 last:border-b-0"
              >
                <div class="font-medium text-gray-900">{{ ref.name }}</div>
              </button>
            </div>
            
            <!-- Selected reference display -->
            <div v-if="selectedReference && !showReferenceDropdown" class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
              <div class="flex items-center justify-between">
                <div>
                  <div class="font-medium text-blue-900">{{ selectedReference.name }}</div>
                </div>
                <button
                  @click="clearReference"
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
                <div class="flex-1 w-full relative">
                  <label class="text-xs text-gray-500 font-medium mb-1 block">Part</label>
                  <input
                    v-model="item.partSearch"
                    @input="onPartInput(idx)"
                    @focus="item.showDropdown = true"
                    @blur="hidePartDropdown(idx)"
                    type="text"
                    class="form-input"
                    :class="{ 
                      'border-red-300 focus:border-red-500 focus:ring-red-500': item.isDuplicate || item.outOfStock,
                      'border-green-300 focus:border-green-500 focus:ring-green-500': item.partId && !item.isDuplicate && !item.outOfStock
                    }"
                    placeholder="Type to search parts..."
                  />
                  
                  <!-- Dropdown for parts -->
                  <div 
                    v-if="item.showDropdown && getFilteredPartsForItem(idx).length > 0"
                    class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                  >
                    <button
                      v-for="p in getFilteredPartsForItem(idx)"
                      :key="p.id"
                      @mousedown.prevent="selectPart(idx, p)"
                      class="w-full px-4 py-2 text-left hover:bg-blue-50 transition-colors border-b border-gray-100 last:border-b-0"
                    >
                      <div class="flex items-center justify-between">
                        <div class="flex-1">
                          <div class="font-medium text-gray-900">{{ p.fowler_part_number }}</div>
                          <div class="text-xs text-gray-500">{{ p.name }}</div>
                        </div>
                        <div class="ml-2">
                          <span 
                            class="px-2 py-1 text-xs font-medium rounded"
                            :class="p.stock > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                          >
                            Stock: {{ p.stock || 0 }}
                          </span>
                        </div>
                      </div>
                    </button>
                  </div>
                  
                  <!-- Selected part display -->
                  <div v-if="item.selectedPart && !item.showDropdown" class="mt-2 p-2 bg-gray-50 border border-gray-200 rounded-lg">
                    <div class="flex items-center justify-between">
                      <div class="flex-1">
                        <div class="text-sm font-medium text-gray-900">{{ item.selectedPart.fowler_part_number }}</div>
                        <div class="text-xs text-gray-500">{{ item.selectedPart.name }}</div>
                        <div class="text-xs text-gray-500 mt-1">Unit: {{ normalizeUnitOfMeasure(item.selectedPart.unit_of_measure) }}</div>
                      </div>
                      <div class="flex items-center gap-2">
                        <span 
                          class="px-2 py-1 text-xs font-medium rounded"
                          :class="item.selectedPart.stock > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                        >
                          Stock: {{ item.selectedPart.stock || 0 }}
                        </span>
                        <button
                          @click="clearPart(idx)"
                          class="text-gray-400 hover:text-gray-600"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                          </svg>
                        </button>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Validation messages -->
                  <p v-if="item.isDuplicate" class="text-xs text-red-600 mt-1 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="10"></circle>
                      <line x1="12" y1="8" x2="12" y2="12"></line>
                      <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    This part is already in the basket
                  </p>
                  <p v-else-if="item.outOfStock" class="text-xs text-red-600 mt-1 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="10"></circle>
                      <line x1="12" y1="8" x2="12" y2="12"></line>
                      <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    This part is out of stock
                  </p>
                  <p v-else-if="item.partId && item.selectedPart && getItemAvailableStock(item) < item.qty" class="text-xs text-orange-600 mt-1 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                      <line x1="12" y1="9" x2="12" y2="13"></line>
                      <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    Insufficient stock: only {{ getItemAvailableStock(item) }} available
                  </p>
                </div>
                <div class="w-full md:w-32">
                  <label class="text-xs text-gray-500 font-medium mb-1 block">Qty</label>
                  <input type="number" v-model="item.qty" class="form-input" min="1" @input="validateQuantity(idx)">
                  <div v-if="item.selectedPart" class="text-xs text-gray-500 mt-1">
                    {{ normalizeUnitOfMeasure(item.selectedPart.unit_of_measure) }}
                  </div>
                </div>
                <div class="w-full md:w-56" v-if="item.selectedPart">
                  <label class="text-xs text-gray-500 font-medium mb-1 block">From Location</label>
                  <select v-model="item.locationKey" class="form-input" @change="validateQuantity(idx)">
                    <option value="">Auto (any location)</option>
                    <option v-for="loc in item.locationOptions" :key="loc.location_key" :value="loc.location_key">
                      {{ loc.location_display }} ({{ loc.quantity }})
                    </option>
                  </select>
                  <div v-if="item.locationLoading" class="text-xs text-gray-500 mt-1">Loading locations...</div>
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
            <button @click="processCheckout" :disabled="processing || !canCheckout" class="btn-primary w-full md:w-auto justify-center">
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

    <!-- Create Work Order Confirmation Modal -->
    <div v-if="allowUntrackedReturns && showCreateConfirmation" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 text-center">
        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-yellow-100 inline-flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-yellow-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
              <line x1="12" y1="9" x2="12" y2="13"></line>
              <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Confirm New Work Order</h3>
            <p class="text-gray-600 mb-4">
              Are you sure this is the correct work order?
              <br>
              <strong class="text-gray-800">{{ pendingWorkOrderNumber }}</strong>
              <br>
              Check for typo before you proceed.
            </p>
            <div class="flex gap-3 justify-center">
              <button
                @click="cancelCreateWorkOrder"
                class="btn-secondary"
              >
                Cancel
              </button>
              <button
                @click="confirmCreateWorkOrder"
                class="btn-primary"
              >
                Yes, Create
              </button>
            </div>
          </div>
      </div>
    </div>

    <!-- Create Unit/Tech Confirmation Modal -->
    <div v-if="allowUntrackedReturns && showCreateReferenceConfirmation" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 text-center">
        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-yellow-100 inline-flex items-center justify-center mx-auto mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-yellow-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
          </svg>
        </div>
        <div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">
            Confirm New {{ pendingReferenceType === 'unit' ? 'Unit' : 'Technician' }}
          </h3>
          <p class="text-gray-600 mb-4">
            Are you sure this is correct?
            <br>
            <strong class="text-gray-800">{{ pendingReferenceName }}</strong>
            <br>
            Check for typo before you proceed.
          </p>
          <div class="flex gap-3 justify-center">
            <button
              @click="cancelCreateReference"
              class="btn-secondary"
            >
              Cancel
            </button>
            <button
              @click="confirmCreateReference"
              class="btn-primary"
            >
              Yes, Create
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Unit/Tech Modal -->
    <Transition name="fade">
      <div v-if="allowUntrackedReturns && showCreateReferenceModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-xl shadow-2xl flex flex-col max-h-[90vh] slide-up">
          <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-900">
              Create New {{ createReferenceType === 'unit' ? 'Unit' : 'Technician' }}
            </h3>
            <button @click="closeCreateReferenceModal" class="text-gray-400 hover:text-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          
          <div class="p-6 overflow-y-auto space-y-4">
            <div v-if="createReferenceType === 'unit'">
              <div class="form-group">
                <label class="form-label">Unit ID *</label>
                <input v-model="newUnitForm.name" class="form-input" placeholder="e.g., UNIT-12" required>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                  <label class="form-label">Make</label>
                  <input v-model="newUnitForm.make" class="form-input" placeholder="e.g., Ford">
                </div>
                <div class="form-group">
                  <label class="form-label">Model</label>
                  <input v-model="newUnitForm.model" class="form-input" placeholder="e.g., F-150">
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Year</label>
                <input v-model="newUnitForm.year" class="form-input" placeholder="e.g., 2020">
              </div>
              <div class="form-group">
                <label class="form-label">VIN</label>
                <input v-model="newUnitForm.vin" class="form-input">
              </div>
              <div class="form-group">
                <label class="form-label">Plate</label>
                <input v-model="newUnitForm.plate" class="form-input">
              </div>
            </div>
            
            <div v-else>
              <div class="form-group">
                <label class="form-label">Name *</label>
                <input v-model="newTechForm.name" class="form-input" required>
              </div>
              <div class="form-group">
                <label class="form-label">Employee Number</label>
                <input v-model="newTechForm.emp_id" class="form-input" placeholder="e.g., T-101">
                <div class="text-xs text-gray-500 mt-1">
                  Leave blank to auto-assign a temporary unique 10-digit number.
                </div>
              </div>
            </div>
          </div>

          <div class="p-6 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 rounded-b-xl">
            <button @click="closeCreateReferenceModal" class="btn-outline">Cancel</button>
            <button @click="saveReference" :disabled="savingReference" class="btn-primary">
              <span v-if="savingReference" class="spinner w-4 h-4"></span>
              Create
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { partsApi, workOrdersApi, unitsApi, techniciansApi, assetApi } from '@/services/api'
import { useToast } from '@/composables/useToast'
import { useSettings } from '@/composables/useSettings'

const route = useRoute()
const { showToast } = useToast()
const { allowUntrackedReturns } = useSettings()

const parts = ref([])
const workOrders = ref([])
const units = ref([])
const technicians = ref([])
const processing = ref(false)

const OutgoingDests = [
  { val: 'wo', label: 'To Work Order' },
  { val: 'unit', label: 'To Unit' },
  { val: 'tech', label: 'To Tech' }
]

const form = ref({
  dest: 'wo',
  refId: ''
})

const resetDestination = () => {
  form.value.refId = ''
  workOrderSearch.value = ''
  selectedWorkOrder.value = null
  showWorkOrderDropdown.value = false
  showCreateConfirmation.value = false
  pendingWorkOrderNumber.value = ''

  referenceSearch.value = ''
  selectedReference.value = null
  showReferenceDropdown.value = false
  showCreateReferenceConfirmation.value = false
  pendingReferenceName.value = ''
  pendingReferenceType.value = ''
  showCreateReferenceModal.value = false
  createReferenceType.value = ''
  resetNewUnitForm()
  resetNewTechForm()
}

const setDest = (dest) => {
  if (form.value.dest === dest) return
  form.value.dest = dest
  resetDestination()
}

const createBasketItem = () => ({
  partId: '',
  qty: 1,
  isDuplicate: false,
  outOfStock: false,
  partSearch: '',
  showDropdown: false,
  selectedPart: null,
  locationKey: '',
  locationOptions: [],
  locationLoading: false,
})

const basket = ref([createBasketItem()])

// Work Order Autocomplete
const workOrderSearch = ref('')
const selectedWorkOrder = ref(null)
const showWorkOrderDropdown = ref(false)
const showCreateConfirmation = ref(false)
const pendingWorkOrderNumber = ref('')

// Unit/Tech Autocomplete
const referenceSearch = ref('')
const selectedReference = ref(null)
const showReferenceDropdown = ref(false)
const showCreateReferenceConfirmation = ref(false)
const pendingReferenceName = ref('')
const pendingReferenceType = ref('')
const showCreateReferenceModal = ref(false)
const createReferenceType = ref('')
const savingReference = ref(false)
const newUnitForm = ref({
  name: '',
  make: '',
  model: '',
  year: '',
  vin: '',
  plate: ''
})
const newTechForm = ref({
  name: '',
  emp_id: ''
})

const destLabel = computed(() => {
  const dest = OutgoingDests.find(d => d.val === form.value.dest)
  return dest ? dest.label.replace('To ', '') : ''
})

const normalizeUnitOfMeasure = (value) => {
  const unit = String(value ?? '').replace(/\s+/g, ' ').trim().toLowerCase()
  return unit || 'each'
}

// Filter work orders based on search
const filteredWorkOrders = computed(() => {
  if (!workOrderSearch.value.trim()) {
    return workOrders.value
  }
  
  const search = workOrderSearch.value.toLowerCase()
  return workOrders.value.filter(wo => 
    wo.wo_number.toLowerCase().includes(search)
  )
})

// Filter units/techs based on search
const filteredReferences = computed(() => {
  const search = referenceSearch.value.toLowerCase().trim()
  
  let items = []
  if (form.value.dest === 'unit') {
    items = units.value
  } else if (form.value.dest === 'tech') {
    items = technicians.value
  }
  
  if (!search) {
    return items
  }
  
  return items.filter(item => 
    item.name.toLowerCase().includes(search)
  )
})

const resetNewUnitForm = () => {
  newUnitForm.value = {
    name: '',
    make: '',
    model: '',
    year: '',
    vin: '',
    plate: ''
  }
}

const resetNewTechForm = () => {
  newTechForm.value = {
    name: '',
    emp_id: ''
  }
}

const findReferenceByName = (name, type) => {
  const search = name.toLowerCase().trim()
  const list = type === 'unit' ? units.value : technicians.value
  return list.find(item => item.name?.toLowerCase().trim() === search) || null
}

// Get parts with stock > 0
const partsInStock = computed(() => {
  return parts.value.filter(p => (p.stock || 0) > 0)
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

// Get filtered parts for a specific basket item
const getFilteredPartsForItem = (currentIndex) => {
  const item = basket.value[currentIndex]
  const searchTerm = (item.partSearch || '').toLowerCase().trim()
  
  // Filter parts: exclude already selected, only show items with stock > 0
  let filtered = partsInStock.value.filter(part => {
    const isAlreadySelected = basket.value.some((basketItem, idx) => 
      idx !== currentIndex && basketItem.partId === part.id
    )
    return !isAlreadySelected
  })
  
  // Apply search filter
  if (searchTerm) {
    filtered = filtered.filter(part => 
      part.fowler_part_number.toLowerCase().includes(searchTerm) ||
      part.name.toLowerCase().includes(searchTerm) ||
      (part.supplier_part_number && part.supplier_part_number.toLowerCase().includes(searchTerm))
    )
  }
  
  return filtered
}

const getItemAvailableStock = (item) => {
  if (!item?.selectedPart) return 0
  if (!item.locationKey) return Number(item.selectedPart.stock || 0)

  const selectedLocation = (item.locationOptions || []).find(loc => loc.location_key === item.locationKey)
  return Number(selectedLocation?.quantity || 0)
}

const loadPartLocations = async (idx, partId) => {
  const item = basket.value[idx]
  if (!item) return

  item.locationLoading = true
  item.locationOptions = []
  item.locationKey = ''

  try {
    const response = await partsApi.getLocations(partId)
    item.locationOptions = (response.data?.locations || [])
      .filter(loc => Number(loc.quantity || 0) > 0)
      .map(loc => ({
        location_key: loc.location_key,
        location_aisle: loc.location_aisle || '',
        location_shelf: loc.location_shelf || '',
        location_bay: loc.location_bay || '',
        location_alt: loc.location_alt || '',
        location_display: loc.location_display || 'unassigned',
        quantity: Number(loc.quantity || 0),
      }))

    if (item.locationOptions.length === 1) {
      item.locationKey = item.locationOptions[0].location_key
    }
  } catch (error) {
    showToast('Warning', 'Could not load part locations, using auto allocation', 'warning')
  } finally {
    item.locationLoading = false
  }
}

// Check if there are any unselected parts available
const hasAvailableParts = computed(() => {
  const selectedIds = basket.value
    .map(item => item.partId)
    .filter(id => id !== '' && id !== null)
  
  return partsInStock.value.length > selectedIds.length
})

// Check if checkout is possible
const canCheckout = computed(() => {
  // A destination is considered valid if a refId is set, or if the user is typing a new WO number.
  const hasDestination = form.value.refId || 
    (allowUntrackedReturns.value && form.value.dest === 'wo' && workOrderSearch.value.trim() !== '') ||
    (allowUntrackedReturns.value && (form.value.dest === 'unit' || form.value.dest === 'tech') && referenceSearch.value.trim() !== '');
  if (!hasDestination) return false;
  
  const validItems = basket.value.filter(item => 
    item.partId && 
    item.qty > 0 && 
    !item.isDuplicate && 
    !item.outOfStock &&
    item.selectedPart &&
    getItemAvailableStock(item) >= item.qty
  );
  
  return validItems.length > 0;
});

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
      const part = parts.value.find(p => p.id === parseInt(route.query.partId))
      if (part && part.stock > 0) {
        const preselected = createBasketItem()
        preselected.partId = part.id
        preselected.qty = 1
        preselected.partSearch = `${part.fowler_part_number} - ${part.name}`
        preselected.showDropdown = false
        preselected.selectedPart = part
        basket.value = [preselected]
        loadPartLocations(0, part.id)
      }
    }
  } catch (error) {
    showToast('Error', 'Failed to load data', 'error')
  }
}

const onPartInput = (idx) => {
  const item = basket.value[idx]
  if (!item.selectedPart) {
    item.showDropdown = true
  }
}

const hidePartDropdown = (idx) => {
  setTimeout(() => {
    basket.value[idx].showDropdown = false
  }, 200)
}

const selectPart = (idx, part) => {
  const item = basket.value[idx]
  item.partId = part.id
  item.selectedPart = part
  item.partSearch = `${part.fowler_part_number} - ${part.name}`
  item.showDropdown = false
  item.outOfStock = (part.stock || 0) === 0
  item.isDuplicate = isPartSelected(part.id, idx)
  item.locationKey = ''
  item.locationOptions = []
  loadPartLocations(idx, part.id)
  
  if (item.isDuplicate) {
    showToast('Warning', 'This part is already in the basket', 'warning')
  } else if (item.outOfStock) {
    showToast('Warning', 'This part is out of stock', 'warning')
  }
}

const clearPart = (idx) => {
  basket.value[idx] = createBasketItem()
}

const validateQuantity = (idx) => {
  const item = basket.value[idx]
  const available = getItemAvailableStock(item)
  if (item.selectedPart && item.qty > available) {
    showToast('Warning', `Only ${available} units available`, 'warning')
  }
}

const addToBasket = () => {
  basket.value.push(createBasketItem())
}

const removeFromBasket = (idx) => {
  if (basket.value.length > 1) {
    basket.value.splice(idx, 1)
  }
}

const processCheckout = async () => {
  // If work order destination, check if it needs to be created or auto-selected.
  if (form.value.dest === 'wo' && !selectedWorkOrder.value && workOrderSearch.value.trim()) {
    const searchTerm = workOrderSearch.value.trim();
    const existingWo = workOrders.value.find(wo => wo.wo_number.toLowerCase() === searchTerm.toLowerCase());

    if (!existingWo) {
      if (!allowUntrackedReturns.value) {
        showToast('Error', 'Work order creation is disabled in Settings', 'error');
        return;
      }
      // Work order is new, so show confirmation dialog and pause.
      // The user's confirmation will trigger the creation and then re-call this function.
      pendingWorkOrderNumber.value = searchTerm;
      showCreateConfirmation.value = true;
      return; 
    } else {
      // The user typed an existing WO number but didn't select it. Let's select it for them.
      selectWorkOrder(existingWo);
    }
  }

  if ((form.value.dest === 'unit' || form.value.dest === 'tech') && !selectedReference.value && referenceSearch.value.trim()) {
    const searchTerm = referenceSearch.value.trim();
    const existingRef = findReferenceByName(searchTerm, form.value.dest);

    if (!existingRef) {
      if (!allowUntrackedReturns.value) {
        showToast('Error', 'Unit/technician creation is disabled in Settings', 'error');
        return;
      }
      pendingReferenceName.value = searchTerm;
      pendingReferenceType.value = form.value.dest;
      showCreateReferenceConfirmation.value = true;
      return;
    } else {
      selectReference(existingRef);
    }
  }
  
  if (!form.value.refId) {
    showToast('Error', 'Please select a destination', 'error');
    return;
  }
  
  const validItems = basket.value.filter(item => 
    item.partId && 
    item.qty > 0 && 
    !item.isDuplicate && 
    !item.outOfStock &&
    item.selectedPart &&
    getItemAvailableStock(item) >= item.qty
  );
  
  if (validItems.length === 0) {
    showToast('Error', 'Please add at least one valid part to checkout', 'error');
    return;
  }
  
  try {
    processing.value = true;
    
    const refType = form.value.dest === 'wo' ? 'work_order' : form.value.dest === 'unit' ? 'unit' : 'technician';
    
    await assetApi.processCheckout({
      items: validItems.map(item => {
        const selectedLocation = item.locationOptions.find(loc => loc.location_key === item.locationKey)
        return {
          part_id: parseInt(item.partId),
          quantity: parseInt(item.qty),
          location_key: item.locationKey || undefined,
          location_aisle: selectedLocation?.location_aisle || undefined,
          location_shelf: selectedLocation?.location_shelf || undefined,
          location_bay: selectedLocation?.location_bay || undefined,
          location_alt: selectedLocation?.location_alt || undefined
        }
      }),
      reference_type: refType,
      reference_id: parseInt(form.value.refId)
    });
    
    showToast('Success', 'Checkout completed successfully');
    
    // Reset basket and references
    basket.value = [createBasketItem()];
    form.value.refId = '';
    clearWorkOrder();
    clearReference();
    
    // Refresh parts to get updated stock
    const partsRes = await partsApi.getAll();
    parts.value = partsRes.data || [];
    
  } catch (error) {
    showToast('Error', error.response?.data?.message || 'Failed to process checkout', 'error');
  } finally {
    processing.value = false;
  }
}

// Work Order Autocomplete Functions
const onWorkOrderInput = () => {
  if (!selectedWorkOrder.value) {
    showWorkOrderDropdown.value = true
  }
}

const hideWorkOrderDropdown = () => {
  setTimeout(() => {
    showWorkOrderDropdown.value = false
  }, 200)
}

const selectWorkOrder = (wo) => {
  selectedWorkOrder.value = wo
  workOrderSearch.value = wo.wo_number
  form.value.refId = wo.id
  showWorkOrderDropdown.value = false
}

const clearWorkOrder = () => {
  selectedWorkOrder.value = null
  workOrderSearch.value = ''
  form.value.refId = ''
}

const onReferenceInput = () => {
  if (!selectedReference.value) {
    showReferenceDropdown.value = true
  }
}

const hideReferenceDropdown = () => {
  setTimeout(() => {
    showReferenceDropdown.value = false
  }, 200)
}

const selectReference = (ref) => {
  selectedReference.value = ref
  referenceSearch.value = ref.name
  form.value.refId = ref.id
  showReferenceDropdown.value = false
}

const clearReference = () => {
  selectedReference.value = null
  referenceSearch.value = ''
  form.value.refId = ''
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

// Handle when user presses Enter or clicks checkout with non-existent work order
const cancelCreateWorkOrder = () => {
  showCreateConfirmation.value = false;
  pendingWorkOrderNumber.value = '';
  // Don't clear the search, allow the user to correct a typo
};

const confirmCreateWorkOrder = async () => {
  const woNumberToCreate = pendingWorkOrderNumber.value;
  showCreateConfirmation.value = false;
  pendingWorkOrderNumber.value = '';

  try {
    processing.value = true; // Show spinner during creation + checkout
    
    // Create new work order
    const response = await workOrdersApi.create({
      wo_number: woNumberToCreate
    });
    
    // Add to work orders list and select it
    workOrders.value.push(response.data);
    selectWorkOrder(response.data);
    
    showToast('Success', `Work order ${woNumberToCreate} created`);
    
    // Now that the WO exists and is selected, re-run checkout
    await processCheckout();
    
  } catch (error) {
    showToast('Error', error.response?.data?.message || 'Failed to create work order', 'error');
    clearWorkOrder(); // Clear selection on failure
    processing.value = false; // Hide spinner
  }
};

const cancelCreateReference = () => {
  showCreateReferenceConfirmation.value = false;
  pendingReferenceName.value = '';
  pendingReferenceType.value = '';
};

const openCreateReferenceModal = () => {
  createReferenceType.value = pendingReferenceType.value;
  showCreateReferenceModal.value = true;

  if (createReferenceType.value === 'unit') {
    resetNewUnitForm();
    newUnitForm.value.name = pendingReferenceName.value;
  } else if (createReferenceType.value === 'tech') {
    resetNewTechForm();
    newTechForm.value.name = pendingReferenceName.value;
  }

  pendingReferenceName.value = '';
  pendingReferenceType.value = '';
};

const closeCreateReferenceModal = () => {
  showCreateReferenceModal.value = false;
  createReferenceType.value = '';
  resetNewUnitForm();
  resetNewTechForm();
};

const confirmCreateReference = () => {
  showCreateReferenceConfirmation.value = false;
  openCreateReferenceModal();
};

const saveReference = async () => {
  if (createReferenceType.value === 'unit' && !newUnitForm.value.name) {
    showToast('Error', 'Unit ID is required', 'error');
    return;
  }

  if (createReferenceType.value === 'tech' && !newTechForm.value.name) {
    showToast('Error', 'Technician name is required', 'error');
    return;
  }

  try {
    savingReference.value = true;

    if (createReferenceType.value === 'unit') {
      const response = await unitsApi.create(newUnitForm.value);
      units.value.push(response.data);
      selectReference(response.data);
      showToast('Success', 'Unit created successfully');
    } else if (createReferenceType.value === 'tech') {
      const response = await techniciansApi.create(newTechForm.value);
      technicians.value.push(response.data);
      selectReference(response.data);
      showToast('Success', 'Technician created successfully');
    }

    closeCreateReferenceModal();
    await processCheckout();
  } catch (error) {
    showToast('Error', error.response?.data?.message || 'Failed to create reference', 'error');
  } finally {
    savingReference.value = false;
  }
};

onMounted(fetchData)
</script>


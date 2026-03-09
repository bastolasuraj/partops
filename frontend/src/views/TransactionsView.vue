<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8 slide-up">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Stock Transactions</h1>
          <p class="text-gray-600">View all asset movements grouped by transaction</p>
        </div>
      </div>
    </div>

    <!-- Filters Card -->
    <div class="card slide-up mb-6 overflow-visible">
      <div class="card-body">
        <div 
          class="grid grid-cols-1 md:grid-cols-2 gap-4 transition-all duration-300 ease-in-out"
          :class="hasFilters ? 'lg:grid-cols-5' : 'lg:grid-cols-4'"
        >
          <div class="form-group">
            <label class="form-label">Part Number</label>
            <div class="relative">
              <input 
                v-model="filters.partNumber" 
                @input="handleSearch"
                @focus="onPartFocus"
                @blur="hidePartDropdown"
                type="text" 
                placeholder="Search part #..." 
                class="form-input !pl-10 pr-10"
              >
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="11" cy="11" r="8"></circle>
                  <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
              </div>

              <!-- Clear button -->
              <button 
                v-if="filters.partNumber"
                @click="clearPartNumber"
                type="button"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                title="Clear part number"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="18" y1="6" x2="6" y2="18"></line>
                  <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
              </button>
              
              <!-- Part Suggestions Dropdown -->
              <div 
                v-if="showPartDropdown && partSuggestions.length > 0"
                class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
              >
                <button
                  v-for="p in partSuggestions"
                  :key="p.id"
                  @mousedown.prevent="selectPart(p)"
                  type="button"
                  class="w-full px-4 py-2 text-left hover:bg-blue-50 transition-colors border-b border-gray-100 last:border-b-0"
                >
                  <div class="flex-1 min-w-0 mr-2">
                    <div class="font-bold text-sm text-blue-600">{{ p.fowler_part_number }}</div>
                    <div v-if="p.supplier_part_number" class="text-xs text-gray-500 font-mono">
                      {{ p.supplier_part_number }}
                    </div>
                    <div class="text-xs text-gray-700 truncate" :title="p.name">{{ p.name }}</div>
                  </div>
                </button>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Transaction Type</label>
            <select v-model="filters.type" @change="applyFilters" class="form-input">
              <option value="">All Types</option>
              <option value="incoming">Incoming</option>
              <option value="outgoing">Outgoing</option>
              <option value="return">Return</option>
            </select>
          </div>
          
          <div class="form-group">
            <label class="form-label">Reference Type</label>
            <select v-model="filters.refType" @change="applyFilters" class="form-input">
              <option value="">All References</option>
              <option value="work_order">Work Order</option>
              <option value="unit">Unit</option>
              <option value="technician">Technician</option>
              <option value="vendor">Vendor</option>
            </select>
          </div>
          
          <div class="form-group">
            <label class="form-label">Date Range</label>
            <DateRangePicker 
              :model-value="{ start: filters.dateFrom, end: filters.dateTo }"
              @update:model-value="(val) => { filters.dateFrom = val.start; filters.dateTo = val.end; applyFilters(); }"
            />
          </div>

          <div v-if="hasFilters" class="form-group flex items-end">
            <button @click="clearFilters" class="btn-outline text-sm w-full justify-center bg-gray-50 hover:bg-gray-100 text-gray-700 h-[42px]">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
              Clear
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6 slide-up">
      <div class="card">
        <div class="card-body">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500 font-medium">Total Transactions</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">{{ groupedTransactions.length }}</p>
            </div>
            <div class="p-3 bg-blue-50 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500 font-medium">Total Items</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">{{ totalItems }}</p>
            </div>
            <div class="p-3 bg-indigo-50 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 7h-9"></path>
                <path d="M14 17H5"></path>
                <circle cx="17" cy="17" r="3"></circle>
                <circle cx="7" cy="7" r="3"></circle>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500 font-medium">Incoming</p>
              <p class="text-2xl font-bold text-green-600 mt-1">{{ incomingCount }}</p>
            </div>
            <div class="p-3 bg-green-50 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="19" x2="12" y2="5"></line>
                <polyline points="5 12 12 5 19 12"></polyline>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500 font-medium">Outgoings</p>
              <p class="text-2xl font-bold text-orange-600 mt-1">{{ outgoingCount }}</p>
            </div>
            <div class="p-3 bg-orange-50 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-orange-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <polyline points="19 12 12 19 5 12"></polyline>
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Transactions Table -->
    <div class="card slide-up">
      <div class="card-header">
        <h2 class="text-xl font-semibold text-gray-800">Transaction History</h2>
        <div class="text-sm text-gray-500">
          Showing {{ groupedTransactions.length }} transactions
        </div>
      </div>
      
      <div v-if="loading" class="p-8 text-center">
        <div class="spinner mx-auto mb-4"></div>
        <p class="text-gray-500">Loading transactions...</p>
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="data-table table-fixed">
          <thead>
            <tr>
              <th class="w-12"></th>
              <th class="w-[16%]">Date & Time</th>
              <th class="w-[12%]">Type</th>
              <th class="w-[14%]">Reference</th>
              <th class="w-[10%]">Items</th>
              <th class="w-[10%] !text-center">Total Qty</th>
              <th class="w-[12%]">Movement Cost</th>
              <th class="w-[12%]">User</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="group in paginatedTransactions" :key="group.id">
              <!-- Main Transaction Row -->
              <tr 
                class="cursor-pointer hover:bg-gray-50 transition-colors"
                :class="{ 'bg-blue-50': expandedRows.includes(group.id) }"
                @click="toggleRow(group.id)"
              >
                <td class="text-center">
                  <button class="p-1 hover:bg-gray-200 rounded transition-colors">
                    <svg 
                      xmlns="http://www.w3.org/2000/svg" 
                      class="w-4 h-4 transition-transform"
                      :class="{ 'rotate-90': expandedRows.includes(group.id) }"
                      viewBox="0 0 24 24" 
                      fill="none" 
                      stroke="currentColor" 
                      stroke-width="2"
                    >
                      <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                  </button>
                </td>
                <td class="font-mono text-xs">
                  <div>{{ formatDate(group.created_at) }}</div>
                  <div class="text-gray-500">{{ formatTime(group.created_at) }}</div>
                </td>
                <td>
                  <span 
                    class="badge"
                    :class="{
                      'badge-success': group.transaction_type === 'incoming',
                      'badge-warning': group.transaction_type === 'outgoing',
                      'badge-info': group.transaction_type === 'return'
                    }"
                  >
                    {{ formatType(group.transaction_type, group.reference_type) }}
                  </span>
                </td>
                <td>
                  <div v-if="group.reference_type" class="text-sm">
                    <div class="font-medium">{{ formatRefType(group.reference_type) }}</div>
                    <div class="text-gray-500">{{ group.reference_number || '-' }}</div>
                  </div>
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td>
                  <span class="badge badge-secondary">
                    {{ group.items.length }} {{ group.items.length === 1 ? 'item' : 'items' }}
                  </span>
                </td>
                <td class="!text-center">
                  <span 
                    class="font-semibold"
                    :class="{
                      'text-green-600': group.transaction_type === 'incoming',
                      'text-orange-600': group.transaction_type === 'outgoing',
                      'text-purple-600': group.transaction_type === 'return'
                    }"
                  >
                    {{ group.transaction_type === 'outgoing' ? '-' : '+' }}{{ Math.abs(group.total_quantity) }}
                  </span>
                </td>
                <td class="text-sm font-medium text-gray-900">
                  {{ formatPurchaseCost(group) }}
                </td>
                <td class="text-sm text-gray-600">{{ group.user_name || 'System' }}</td>
              </tr>

              <!-- Expanded Details Row -->
              <tr v-if="expandedRows.includes(group.id)" class="bg-gray-50">
                <td class="w-12"></td>
                <td colspan="6" class="p-0">
                  <div class="py-4 border-t border-gray-200">
                    <div class="overflow-hidden">
                      <table class="w-full table-fixed">
                        <thead class="bg-gray-50 border-b border-gray-200">
                          <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 w-1/6">Supplier PN</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 w-1/6">Fowler PN</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 w-1/6">Part Name</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 w-1/6">Location</th>
                            <th class="px-4 py-2 !text-center w-[12%] text-xs font-semibold text-gray-600">Quantity</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 w-[12%]">Unit Cost</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 w-[12%]">Total</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 w-[16%]">Notes</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr 
                            v-for="(item, idx) in group.items" 
                            :key="idx"
                            class="border-b border-gray-100 last:border-0 hover:bg-gray-50"
                          >
                            <td class="px-4 py-3 w-1/6 text-xs">
                              <span class="font-mono text-gray-600">
                                {{ item.supplier_part_number || '-' }}
                              </span>
                            </td>
                            <td class="px-4 py-3 w-1/6 text-xs">
                              <span class="font-mono font-medium text-blue-600">
                                {{ item.part_number }}
                              </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-900 w-1/6 truncate">
                              {{ item.part_name }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 w-1/6">
                              {{ item.location || '-' }}
                            </td>
                            <td class="px-4 py-3 !text-center w-[12%]">
                              <span 
                                class="font-semibold text-xs"
                                :class="{
                                  'text-green-600': group.transaction_type === 'incoming',
                                  'text-orange-600': group.transaction_type === 'outgoing',
                                  'text-purple-600': group.transaction_type === 'return'
                                }"
                              >
                                {{ group.transaction_type === 'outgoing' ? '-' : '+' }}{{ Math.abs(item.quantity) }}
                              </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-700 w-[12%]">
                              {{ item.unit_price ? formatCurrency(item.unit_price) : '-' }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-700 w-[12%]">
                              {{ formatLineCost(group, item) }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 w-[16%]">
                              {{ item.notes || '-' }}
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>

                    <!-- Transaction Notes -->
                    <div v-if="group.notes" class="mt-3 px-4 py-3 bg-yellow-50 border border-yellow-200 rounded-lg mx-4">
                      <div class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-yellow-600 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                          <polyline points="14 2 14 8 20 8"></polyline>
                          <line x1="16" y1="13" x2="8" y2="13"></line>
                          <line x1="16" y1="17" x2="8" y2="17"></line>
                          <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        <div>
                          <div class="text-xs font-semibold text-yellow-800 mb-1">Transaction Notes:</div>
                          <div class="text-sm text-yellow-900">{{ group.notes }}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            </template>

            <tr v-if="groupedTransactions.length === 0">
              <td colspan="8" class="text-center py-8 text-gray-500">
                No transactions found
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="card-footer flex items-center justify-between">
        <div class="text-sm text-gray-600">
          Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, groupedTransactions.length) }} of {{ groupedTransactions.length }}
        </div>
        <div class="flex gap-2">
          <button 
            @click="currentPage--" 
            :disabled="currentPage === 1"
            class="btn-outline text-sm"
            :class="{ 'opacity-50 cursor-not-allowed': currentPage === 1 }"
          >
            Previous
          </button>
          <button 
            v-for="page in visiblePages" 
            :key="page"
            @click="currentPage = page"
            class="px-3 py-1 text-sm rounded-lg border transition-colors"
            :class="page === currentPage ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
          >
            {{ page }}
          </button>
          <button 
            @click="currentPage++" 
            :disabled="currentPage === totalPages"
            class="btn-outline text-sm"
            :class="{ 'opacity-50 cursor-not-allowed': currentPage === totalPages }"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { assetApi, partsApi } from '@/services/api'
import { useToast } from '@/composables/useToast'
import DateRangePicker from '@/components/DateRangePicker.vue'

const { showToast } = useToast()

const transactions = ref([])
const loading = ref(true)
const currentPage = ref(1)
const perPage = ref(25)
const expandedRows = ref([])
let searchTimeout = null

// Part Autocomplete
const partSuggestions = ref([])
const showPartDropdown = ref(false)
const isLoadingSuggestions = ref(false)

const filters = ref({
  partNumber: '',
  type: '',
  refType: '',
  dateFrom: '',
  dateTo: ''
})

const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchData()
    fetchPartSuggestions()
  }, 500)
}

const fetchPartSuggestions = async () => {
  try {
    isLoadingSuggestions.value = true
    const params = {}
    if (filters.value.partNumber) {
      params.search = filters.value.partNumber
    }
    const response = await partsApi.getAll(params)
    partSuggestions.value = response.data || []
    showPartDropdown.value = true
  } catch (error) {
    console.error('Failed to fetch part suggestions', error)
  } finally {
    isLoadingSuggestions.value = false
  }
}

const onPartFocus = () => {
  showPartDropdown.value = true
  fetchPartSuggestions()
}

const selectPart = (part) => {
  filters.value.partNumber = part.fowler_part_number
  showPartDropdown.value = false
  fetchData()
}

const clearPartNumber = () => {
  filters.value.partNumber = ''
  partSuggestions.value = []
  showPartDropdown.value = false
  fetchData()
}

const hidePartDropdown = () => {
  setTimeout(() => {
    showPartDropdown.value = false
  }, 200)
}

const fetchData = async () => {
  try {
    loading.value = true
    const params = { limit: 0 }
    if (filters.value.partNumber) {
      params.part_number = filters.value.partNumber
    }
    const response = await assetApi.getTransactions(params)
    transactions.value = response.data || []
  } catch (error) {
    showToast('Error', 'Failed to load transactions', 'error')
  } finally {
    loading.value = false
  }
}

// Group transactions by transaction_group_id or create unique groups
const groupedTransactions = computed(() => {
  let filtered = [...transactions.value]
  
  // Apply filters
  if (filters.value.type) {
    filtered = filtered.filter(t => t.transaction_type === filters.value.type)
  }
  
  if (filters.value.refType) {
    filtered = filtered.filter(t => t.reference_type === filters.value.refType)
  }
  
  if (filters.value.dateFrom) {
    const fromDate = new Date(filters.value.dateFrom)
    filtered = filtered.filter(t => new Date(t.created_at) >= fromDate)
  }
  
  if (filters.value.dateTo) {
    const toDate = new Date(filters.value.dateTo)
    toDate.setHours(23, 59, 59, 999)
    filtered = filtered.filter(t => new Date(t.created_at) <= toDate)
  }
  
  // Group by transaction_group_id, reference, and timestamp
  const groups = {}
  
  filtered.forEach(txn => {
    const groupKey = txn.transaction_group_id || 
                     `${txn.transaction_type}_${txn.reference_type}_${txn.reference_id}_${txn.created_at}`
    
    if (!groups[groupKey]) {
      groups[groupKey] = {
        id: groupKey,
        transaction_type: txn.transaction_type,
        reference_type: txn.reference_type,
        reference_id: txn.reference_id,
        reference_number: txn.reference_number,
        created_at: txn.created_at,
        user_name: txn.user_name,
        notes: txn.notes,
        items: [],
        total_quantity: 0,
        total_cost: 0
      }
    }
    
    const unitPrice = Number(txn.unit_price || 0)
    const lineCost = unitPrice > 0 ? Math.abs(txn.quantity) * unitPrice : 0
    groups[groupKey].items.push({
      transaction_id: Number(txn.id || 0),
      part_id: txn.part_id,
      part_number: txn.part_number,
      supplier_part_number: txn.supplier_part_number,
      part_name: txn.part_name,
      location: txn.location,
      quantity: txn.quantity,
      notes: txn.notes,
      unit_price: unitPrice,
      line_cost: lineCost
    })
    
    groups[groupKey].total_quantity += txn.quantity
    groups[groupKey].total_cost += lineCost
  })

  const grouped = Object.values(groups).map(group => {
    group.items.sort((a, b) => a.transaction_id - b.transaction_id)
    return group
  })

  return grouped.sort((a, b) =>
    new Date(b.created_at) - new Date(a.created_at)
  )
})

const hasFilters = computed(() => {
  return !!(filters.value.partNumber || 
         filters.value.type || 
         filters.value.refType || 
         filters.value.dateFrom || 
         filters.value.dateTo)
})

watch(groupedTransactions, (newGroups) => {
  if (hasFilters.value) {
    expandedRows.value = newGroups.map(group => group.id);
  } else {
    expandedRows.value = [];
  }
})

const paginatedTransactions = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return groupedTransactions.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(groupedTransactions.value.length / perPage.value)
})

const visiblePages = computed(() => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
  let end = Math.min(totalPages.value, start + maxVisible - 1)
  
  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1)
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

const totalItems = computed(() => {
  return groupedTransactions.value.reduce((sum, group) => sum + group.items.length, 0)
})

const incomingCount = computed(() => {
  return groupedTransactions.value.filter(t => t.transaction_type === 'incoming').length
})

const outgoingCount = computed(() => {
  return groupedTransactions.value.filter(t => t.transaction_type === 'outgoing').length
})

const toggleRow = (groupId) => {
  const index = expandedRows.value.indexOf(groupId)
  if (index > -1) {
    expandedRows.value.splice(index, 1)
  } else {
    expandedRows.value.push(groupId)
  }
}

const applyFilters = () => {
  currentPage.value = 1
}

const clearFilters = () => {
  filters.value = {
    partNumber: '',
    type: '',
    refType: '',
    dateFrom: '',
    dateTo: ''
  }
  currentPage.value = 1
  expandedRows.value = [] // Also clear here to be explicit
  fetchData()
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  })
}

const formatTime = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleTimeString('en-US', { 
    hour: '2-digit', 
    minute: '2-digit'
  })
}

const formatType = (type, referenceType) => {
  if (type === 'outgoing') {
    if (referenceType === 'supplier' || referenceType === 'vendor') {
      return 'Return to supplier'
    }
    return 'Checkout'
  }
  if (type === 'return') {
    return 'Back to the shelf'
  }
  if (type === 'incoming') {
    return 'New/added stuff'
  }
  return type
}

const formatCurrency = (value) => {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

const formatPurchaseCost = (group) => {
  if (!group.total_cost) {
    return '-'
  }
  const signedCost = group.transaction_type === 'outgoing'
    ? -group.total_cost
    : group.total_cost
  return formatCurrency(signedCost)
}

const formatLineCost = (group, item) => {
  if (!item.line_cost) {
    return '-'
  }
  const signedCost = group.transaction_type === 'outgoing'
    ? -item.line_cost
    : item.line_cost
  return formatCurrency(signedCost)
}

const formatRefType = (type) => {
  const types = {
    'work_order': 'Work Order',
    'unit': 'Unit',
    'technician': 'Technician',
    'vendor': 'Vendor',
    'purchase_order': 'Purchase Order'
  }
  return types[type] || type
}

onMounted(fetchData)
</script>

<style scoped>
.rotate-90 {
  transform: rotate(90deg);
}
</style>

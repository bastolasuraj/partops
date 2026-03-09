<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8 slide-up">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Fowler PN Mapping</h1>
          <p class="text-gray-600">View supplier parts grouped by Fowler PN</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="badge badge-info flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
            Live Data
          </span>
        </div>
      </div>
    </div>

    <!-- Search Card -->
    <div class="card slide-up mb-6">
      <div class="card-body">
        <div class="flex gap-4">
          <div class="flex-1">
            <label class="form-label">Search Fowler PN</label>
            <input 
              v-model="searchFowlerPn" 
              @keyup.enter="searchMapping"
              type="text" 
              class="form-input" 
              placeholder="Enter Fowler PN (e.g., FW-1001)"
            >
          </div>
          <div class="flex items-end">
            <button @click="searchMapping" :disabled="!searchFowlerPn || searching" class="btn-primary">
              <span v-if="searching" class="spinner w-4 h-4"></span>
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
              </svg>
              Search
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Results Card -->
    <div v-if="hasSearched" class="card slide-up">
      <div class="card-header">
        <h2 class="text-xl font-semibold text-gray-800">
          Mapping Results for: <span class="text-blue-600">{{ searchFowlerPn }}</span>
        </h2>
        <span class="badge badge-info">{{ mappings.length }} supplier(s)</span>
      </div>
      
      <!-- Loading State -->
      <div v-if="searching" class="p-8 text-center">
        <div class="spinner mx-auto mb-4"></div>
        <p class="text-gray-500">Searching mappings...</p>
      </div>
      
      <!-- No Results -->
      <div v-else-if="mappings.length === 0" class="p-8 text-center text-gray-500">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 mb-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"></circle>
          <path d="m21 21-4.35-4.35"></path>
        </svg>
        <p class="font-medium">No mappings found for "{{ searchFowlerPn }}"</p>
        <p class="text-sm mt-2">This Fowler PN doesn't exist or has no supplier parts mapped to it.</p>
      </div>
      
      <!-- Mappings Grid -->
      <div v-else class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="mapping in mappings" :key="mapping.id" 
               class="border rounded-lg p-4 hover:border-blue-300 transition-colors"
               :class="mapping.is_primary ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white'">
            
            <!-- Primary Badge -->
            <div v-if="mapping.is_primary" class="mb-3">
              <span class="badge badge-success text-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                </svg>
                Primary Supplier
              </span>
            </div>

            <!-- Supplier Info -->
            <div class="mb-3">
              <div class="text-xs text-gray-500 uppercase font-bold mb-1">Supplier</div>
              <div class="font-bold text-lg text-gray-900">{{ mapping.supplier_name || 'Unknown' }}</div>
            </div>

            <!-- Part Info -->
            <div class="mb-3">
              <div class="text-xs text-gray-500 uppercase font-bold mb-1">Part Name</div>
              <div class="font-medium text-gray-900">{{ mapping.part_name || 'N/A' }}</div>
            </div>

            <!-- Supplier PN -->
            <div class="mb-3">
              <div class="text-xs text-gray-500 uppercase font-bold mb-1">Supplier Part #</div>
              <div class="font-mono text-sm text-blue-600">{{ mapping.supplier_part_number }}</div>
            </div>

            <!-- Stock -->
            <div class="mb-3">
              <div class="text-xs text-gray-500 uppercase font-bold mb-1">Current Stock</div>
              <div class="text-2xl font-bold" :class="mapping.stock > 0 ? 'text-green-600' : 'text-gray-400'">
                {{ mapping.stock || 0 }}
              </div>
            </div>

            <!-- Dates -->
            <div class="text-xs text-gray-500 pt-3 border-t border-gray-200">
              <div>Added: {{ formatDate(mapping.created_at) }}</div>
            </div>
          </div>
        </div>

        <!-- Summary Stats -->
        <div class="mt-6 pt-6 border-t border-gray-200">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
              <div class="text-xs text-gray-500 uppercase font-bold mb-1">Total Suppliers</div>
              <div class="text-2xl font-bold text-gray-900">{{ mappings.length }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
              <div class="text-xs text-gray-500 uppercase font-bold mb-1">Total Stock</div>
              <div class="text-2xl font-bold text-green-600">{{ totalStock }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
              <div class="text-xs text-gray-500 uppercase font-bold mb-1">Primary Supplier</div>
              <div class="text-lg font-bold text-blue-600">{{ primarySupplier }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- All Fowler PNs Overview -->
    <div class="card slide-up mt-6">
      <div class="card-header">
        <h2 class="text-xl font-semibold text-gray-800">All Fowler PNs</h2>
        <span class="badge badge-info">{{ uniqueFowlerPns.length }} unique</span>
      </div>
      
      <div v-if="loadingParts" class="p-8 text-center">
        <div class="spinner mx-auto mb-4"></div>
        <p class="text-gray-500">Loading parts...</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="data-table">
          <thead>
            <tr>
              <th>Fowler PN</th>
              <th>Supplier Count</th>
              <th>Total Stock</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="fpn in uniqueFowlerPns" :key="fpn.fowler_part_number">
              <td>
                <div class="font-mono font-bold text-blue-600">{{ fpn.fowler_part_number }}</div>
              </td>
              <td>
                <span class="badge badge-info">{{ fpn.count }} supplier(s)</span>
              </td>
              <td>
                <div class="font-semibold text-gray-900">{{ fpn.total_stock }}</div>
              </td>
              <td>
                <button @click="quickSearch(fpn.fowler_part_number)" 
                        class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                  View Details
                </button>
              </td>
            </tr>
            <tr v-if="uniqueFowlerPns.length === 0">
              <td colspan="4" class="text-center py-8 text-gray-500">No parts found</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { partsApi } from '@/services/api'
import { useToast } from '@/composables/useToast'

const { showToast } = useToast()

const searchFowlerPn = ref('')
const mappings = ref([])
const searching = ref(false)
const hasSearched = ref(false)
const parts = ref([])
const loadingParts = ref(true)

const totalStock = computed(() => {
  return mappings.value.reduce((sum, m) => sum + (m.stock || 0), 0)
})

const primarySupplier = computed(() => {
  const primary = mappings.value.find(m => m.is_primary)
  return primary?.supplier_name || 'None'
})

const uniqueFowlerPns = computed(() => {
  const grouped = {}
  parts.value.forEach(part => {
    if (!grouped[part.fowler_part_number]) {
      grouped[part.fowler_part_number] = {
        fowler_part_number: part.fowler_part_number,
        count: 0,
        total_stock: 0
      }
    }
    grouped[part.fowler_part_number].count++
    grouped[part.fowler_part_number].total_stock += part.stock || 0
  })
  return Object.values(grouped).sort((a, b) => b.count - a.count)
})

const searchMapping = async () => {
  if (!searchFowlerPn.value) return
  
  try {
    searching.value = true
    hasSearched.value = true
    
    // Try the mapping API first
    const response = await partsApi.getFowlerMapping(searchFowlerPn.value)
    let results = response.data || []
    
    // If no results from mapping table, search directly in parts table
    if (results.length === 0) {
      const matchingParts = parts.value.filter(part => 
        part.fowler_part_number && 
        part.fowler_part_number.toLowerCase() === searchFowlerPn.value.toLowerCase()
      )
      
      // Transform parts data to match mapping structure
      results = matchingParts.map(part => ({
        id: part.id,
        fowler_part_number: part.fowler_part_number,
        part_id: part.id,
        supplier_id: part.supplier_id,
        supplier_part_number: part.supplier_part_number,
        part_name: part.name,
        stock: part.stock,
        supplier_name: part.supplier_name,
        is_primary: false,
        created_at: part.created_at
      }))
      
      // Mark first one as primary if exists
      if (results.length > 0) {
        results[0].is_primary = true
      }
    }
    
    mappings.value = results
  } catch (error) {
    showToast('Error', 'Failed to search mappings', 'error')
    mappings.value = []
  } finally {
    searching.value = false
  }
}

const quickSearch = (fowlerPn) => {
  searchFowlerPn.value = fowlerPn
  searchMapping()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const fetchParts = async () => {
  try {
    loadingParts.value = true
    const response = await partsApi.getAll()
    parts.value = response.data || []
  } catch (error) {
    showToast('Error', 'Failed to load parts', 'error')
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
    day: 'numeric'
  })
}

onMounted(fetchParts)
</script>

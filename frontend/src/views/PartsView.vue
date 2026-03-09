<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8 slide-up">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Parts Master</h1>
          <p class="text-gray-600">Manage your parts assets</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="badge badge-info flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
            Live Data
          </span>
        </div>
      </div>
    </div>

    <!-- Parts Card -->
    <div class="card slide-up">
      <div class="card-header flex-col md:flex-row gap-4 !items-start md:!items-center h-auto md:h-20">
        <h2 class="text-xl font-semibold text-gray-800">Parts Master List</h2>
        <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
          <div class="relative w-full md:w-64">
            <input 
              v-model="searchQuery" 
              @input="handleSearch"
              type="text" 
              placeholder="Search parts..." 
              class="form-input !pl-10"
            >
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
            </div>
          </div>
          <button @click="openImportModal" class="btn-outline whitespace-nowrap">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
              <polyline points="17 8 12 3 7 8"></polyline>
              <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
            Bulk Import
          </button>
          <button @click="openAddModal" class="btn-primary whitespace-nowrap">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Add New Part
          </button>
        </div>
      </div>
      
      <!-- Loading State -->
      <div v-if="loading" class="p-8 text-center">
        <div class="spinner mx-auto mb-4"></div>
        <p class="text-gray-500">Loading parts...</p>
      </div>
      
      <!-- Parts Table -->
      <div v-else class="overflow-x-auto">
        <table class="data-table">
          <thead>
            <tr>
              <th>Supplier PN</th>
              <th>Fowler PN</th>
              <th>Part Name</th>
              <th>Location</th>
              <th>Available</th>
              <th>Availability</th>
              <th>Total Cost</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="part in paginatedParts" :key="part.id">
              <td class="font-mono font-medium text-gray-600">
                <div class="flex items-center gap-3">
                  <div v-if="part.supplier_part_number" class="shrink-0">
                    <button
                      v-if="getQrSrc(part, 'supplier')"
                      type="button"
                      class="h-10 w-10 rounded-md border border-gray-200 bg-white hover:border-blue-400 transition-colors"
                      :title="`Supplier PN: ${part.supplier_part_number}`"
                      @click="openQrLightbox('Supplier Part #', part.supplier_part_number, getQrSrc(part, 'supplier'))"
                    >
                      <img
                        :src="getQrSrc(part, 'supplier')"
                        :alt="`Supplier QR for ${part.supplier_part_number}`"
                        class="h-10 w-10 rounded-md"
                      />
                    </button>
                    <div v-else class="h-10 w-10 rounded-md border border-gray-200 bg-gray-50 flex items-center justify-center text-[10px] text-gray-400">
                      QR
                    </div>
                  </div>
                  <span>{{ part.supplier_part_number }}</span>
                </div>
              </td>
              <td class="font-mono font-medium text-blue-600">
                <div class="flex items-center gap-3">
                  <div v-if="part.fowler_part_number" class="shrink-0">
                    <button
                      v-if="getQrSrc(part, 'fowler')"
                      type="button"
                      class="h-10 w-10 rounded-md border border-gray-200 bg-white hover:border-blue-400 transition-colors"
                      :title="`Fowler PN: ${part.fowler_part_number}`"
                      @click="openQrLightbox('Fowler Part #', part.fowler_part_number, getQrSrc(part, 'fowler'))"
                    >
                      <img
                        :src="getQrSrc(part, 'fowler')"
                        :alt="`Fowler QR for ${part.fowler_part_number}`"
                        class="h-10 w-10 rounded-md"
                      />
                    </button>
                    <div v-else class="h-10 w-10 rounded-md border border-gray-200 bg-gray-50 flex items-center justify-center text-[10px] text-gray-400">
                      QR
                    </div>
                  </div>
                  <span>{{ part.fowler_part_number }}</span>
                </div>
              </td>
              <td>
                <div class="font-medium text-gray-900">{{ part.name }}</div>
                <div class="text-xs text-gray-500">{{ part.supplier_name || 'N/A' }}</div>
              </td>
              <td class="text-sm max-w-xs">
                <div class="truncate" :title="formatLocation(part)">{{ formatLocation(part) }}</div>
              </td>
                <td class="text-sm text-gray-600">{{ part.stock || 0 }}</td>
                <td>
                  <div class="inline-flex items-center" :title="availabilityLabel(part)">
                    <svg
                      v-if="availabilityStatus(part) === 'in'"
                      xmlns="http://www.w3.org/2000/svg"
                      class="w-4 h-4 text-green-600"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                    >
                      <circle cx="12" cy="12" r="10"></circle>
                      <path d="M9 12l2 2 4-4"></path>
                    </svg>
                    <svg
                      v-else-if="availabilityStatus(part) === 'low'"
                      xmlns="http://www.w3.org/2000/svg"
                      class="w-4 h-4 text-amber-600"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                    >
                      <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                      <line x1="12" y1="9" x2="12" y2="13"></line>
                      <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <svg
                      v-else
                      xmlns="http://www.w3.org/2000/svg"
                      class="w-4 h-4 text-red-600"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                    >
                      <circle cx="12" cy="12" r="10"></circle>
                      <line x1="15" y1="9" x2="9" y2="15"></line>
                      <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    <span class="sr-only">{{ availabilityLabel(part) }}</span>
                  </div>
                </td>
              <td class="text-sm font-medium text-gray-700">{{ formatCurrency(part.total_cost) }}</td>
              <td>
                <div class="flex gap-2">
                  <button @click="openDetailModal(part)" class="text-gray-500 hover:text-blue-600" title="Details">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                  </button>
                  <button @click="openEditModal(part)" class="text-gray-500 hover:text-blue-600" title="Edit">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                  </button>
                  <button @click="quickCheckout(part)" class="text-gray-500 hover:text-green-600" title="Checkout">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                  </button>
                  <button @click="deletePart(part)" class="text-gray-500 hover:text-red-600" title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="parts.length === 0">
              <td colspan="7" class="text-center py-8 text-gray-500">No parts found</td>
            </tr>
          </tbody>
        </table>
      </div>
      <!-- Pagination -->
      <div v-if="totalPages > 1" class="card-footer flex items-center justify-between">
        <div class="text-sm text-gray-600">
          Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, parts.length) }} of {{ parts.length }} parts
        </div>
        <div class="flex gap-2">
          <button @click="currentPage--" :disabled="currentPage === 1" class="btn-outline text-sm" :class="{ 'opacity-50 cursor-not-allowed': currentPage === 1 }">
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
          <button @click="currentPage++" :disabled="currentPage === totalPages" class="btn-outline text-sm" :class="{ 'opacity-50 cursor-not-allowed': currentPage === totalPages }">
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <Transition name="fade">
      <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-2xl rounded-xl shadow-2xl flex flex-col max-h-[90vh] slide-up">
          <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-900">{{ modalTitle }}</h3>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          
          <div class="p-6 overflow-y-auto">
            <!-- View Mode -->
            <div v-if="modalMode === 'view'" class="space-y-6">
              <div class="grid grid-cols-2 gap-6">
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                  <div class="text-xs text-gray-500 uppercase font-bold">Part Name</div>
                  <div class="text-lg font-bold text-gray-900">{{ selectedPart?.name }}</div>
                </div>
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                  <div class="text-xs text-gray-500 uppercase font-bold">Current Stock</div>
                  <div class="text-2xl font-mono font-bold" :class="isLowStock(selectedPart) ? 'text-red-500' : 'text-green-500'">
                    {{ selectedPart?.stock || 0 }}
                  </div>
                </div>
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 col-span-2">
                  <div class="text-xs text-gray-500 uppercase font-bold">Unit of Measure</div>
                  <div class="text-lg font-bold text-gray-900">{{ selectedPart?.unit_of_measure || 'each' }}</div>
                </div>
              </div>
              <div class="grid grid-cols-3 gap-4">
                <div><label class="form-label">Aisle</label><div class="text-gray-700">{{ selectedPart?.location_aisle || '-' }}</div></div>
                <div><label class="form-label">Shelf</label><div class="text-gray-700">{{ selectedPart?.location_shelf || '-' }}</div></div>
                <div><label class="form-label">Bay</label><div class="text-gray-700">{{ selectedPart?.location_bay || '-' }}</div></div>
              </div>
              <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                <div class="text-xs text-gray-500 uppercase font-bold">Alternate Location</div>
                <div class="text-gray-700">{{ selectedPart?.location_alt || '-' }}</div>
              </div>
              <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                <div class="text-xs text-gray-500 uppercase font-bold">Stock by Location</div>
                <div v-if="selectedPartLocationsLoading" class="text-sm text-gray-500 mt-2">
                  Loading location breakdown...
                </div>
                <div v-else-if="selectedPartLocations.length === 0" class="text-sm text-gray-500 mt-2">
                  No location-level stock rows found.
                </div>
                <div v-else class="mt-3 space-y-2">
                  <div
                    v-for="loc in selectedPartLocations"
                    :key="loc.location_key"
                    class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm"
                  >
                    <span class="font-medium text-gray-700">{{ loc.location_display || 'unassigned' }}</span>
                    <span class="font-mono text-gray-900">{{ loc.quantity || 0 }} {{ selectedPart?.unit_of_measure || 'each' }}</span>
                  </div>
                </div>
              </div>

            </div>

            <!-- Add/Edit Form -->
            <div v-else class="space-y-4">
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="text-blue-500 w-5 h-5 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="12" y1="16" x2="12" y2="12"></line>
                  <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <div class="text-sm text-blue-700">
                  <p class="font-bold">Form Purpose:</p>
                  <p>Supplier Part # determines uniqueness. If it exists, create an alias instead.</p>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                  <label class="form-label">Supplier</label>
                  <select v-model="form.supplier_id" class="form-input">
                    <option value="">Select Supplier</option>
                    <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Supplier Part #</label>
                  <input v-model="form.supplier_part_number" class="form-input" placeholder="Check DB..." @blur="checkDuplicate">
                  <div v-if="duplicateExists" class="text-xs mt-1 text-red-500 font-bold">🚫 Exists: Linked to existing part</div>
                </div>
                
                <div class="form-group relative">
                  <label class="form-label">Fowler Part #</label>
                  <input 
                    v-model="fowlerPnSearch" 
                    @input="onFowlerPnInput"
                    @focus="showFowlerPnDropdown = true"
                    @blur="hideFowlerPnDropdown"
                    class="form-input" 
                    :class="{ 'border-green-300 focus:border-green-500 focus:ring-green-500': selectedFowlerPn }"
                    :disabled="duplicateExists" 
                    placeholder="Type to search or create new..."
                  >
                  
                  <!-- Dropdown for existing Fowler PNs -->
                  <div 
                    v-if="showFowlerPnDropdown && filteredFowlerPns.length > 0"
                    class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-48 overflow-y-auto"
                  >
                    <button
                      v-for="fpn in filteredFowlerPns"
                      :key="fpn"
                      @mousedown.prevent="selectFowlerPn(fpn)"
                      type="button"
                      class="w-full px-4 py-2 text-left hover:bg-blue-50 transition-colors border-b border-gray-100 last:border-b-0"
                    >
                      <div class="font-medium text-gray-900">{{ fpn }}</div>
                      <div class="text-xs text-gray-500">{{ getPartCountForFowlerPn(fpn) }} supplier part(s)</div>
                    </button>
                  </div>
                  
                  <!-- Selected Fowler PN indicator -->
                  <div v-if="selectedFowlerPn && !showFowlerPnDropdown" class="mt-1 text-xs text-green-600 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Using existing Fowler PN
                  </div>
                  <div v-else-if="fowlerPnSearch && !showFowlerPnDropdown && !selectedFowlerPn" class="mt-1 text-xs text-blue-600 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <line x1="12" y1="5" x2="12" y2="19"></line>
                      <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Creating new Fowler PN
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Part Name</label>
                  <input v-model="form.name" class="form-input" :disabled="duplicateExists || isNameLockedByFowler" required>
                  <div v-if="isNameLockedByFowler" class="mt-1 text-xs text-green-600">
                    Name auto-filled from existing Fowler PN.
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Unit of Measure</label>
                  <input v-model="form.unit_of_measure" class="form-input" :disabled="duplicateExists" placeholder="each, feet, box">
                </div>
                
                <div class="md:col-span-2 grid grid-cols-3 gap-4">
                  <div><label class="form-label">Aisle</label><input v-model="form.location_aisle" class="form-input" :disabled="duplicateExists"></div>
                  <div><label class="form-label">Shelf</label><input v-model="form.location_shelf" class="form-input" :disabled="duplicateExists"></div>
                  <div><label class="form-label">Bay</label><input v-model="form.location_bay" class="form-input" :disabled="duplicateExists"></div>
                </div>

                <div class="form-group md:col-span-2">
                  <label class="form-label">Alternate Location</label>
                  <input v-model="form.location_alt" class="form-input" :disabled="duplicateExists" placeholder="Use for non-aisle/shelf/bay locations">
                </div>
                
                <div class="form-group md:col-span-2">
                  <label class="form-label">Low Stock Threshold</label>
                  <input type="number" v-model="form.low_stock_threshold" class="form-input" :disabled="duplicateExists">
                </div>
              </div>
            </div>
          </div>

          <div class="p-6 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 rounded-b-xl">
            <button @click="closeModal" class="btn-outline">Cancel</button>
            <button v-if="modalMode !== 'view'" @click="savePart" :disabled="duplicateExists || saving" class="btn-primary">
              <span v-if="saving" class="spinner w-4 h-4"></span>
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
              </svg>
              Save
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Bulk Import Modal -->
    <Transition name="fade">
      <div v-if="showImportModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-3xl rounded-xl shadow-2xl flex flex-col max-h-[90vh] slide-up">
          <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-900">Bulk Import Parts</h3>
            <button @click="closeImportModal" class="text-gray-400 hover:text-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>

          <div class="p-6 overflow-y-auto space-y-4">

            <div>
              <label class="form-label">Spreadsheet (.xlsx, .xls, .csv)</label>
              <input
                type="file"
                accept=".xlsx,.xls,.csv"
                @change="handleImportFile"
                class="form-input"
              >
              <p v-if="importFileName" class="text-xs text-gray-500 mt-1">Selected: {{ importFileName }}</p>
              <p v-if="importParseError" class="text-xs text-red-600 mt-1">{{ importParseError }}</p>
            </div>

            <div v-if="importParsing" class="flex items-center gap-2 text-sm text-gray-600">
              <span class="spinner w-4 h-4"></span>
              Parsing spreadsheet...
            </div>
            <div v-if="importLoading" class="space-y-2 text-sm text-gray-600">
              <div class="flex items-center justify-between">
                <span>Uploading {{ importProgress.processed }} / {{ importProgress.total }}</span>
                <span>{{ importProgress.percent }}%</span>
              </div>
              <div class="h-2 bg-gray-200 rounded">
                <div
                  class="h-2 bg-blue-600 rounded transition-all duration-300"
                  :style="{ width: `${importProgress.percent}%` }"
                ></div>
              </div>
              <div class="text-xs text-gray-500">
                Remaining: {{ Math.max(importProgress.total - importProgress.processed, 0) }}
              </div>
            </div>

            <div v-if="importItems.length" class="space-y-3">
              <div class="text-sm text-gray-700">
                Ready to import <span class="font-semibold">{{ importItems.length }}</span> part(s).
              </div>
              <div class="border border-gray-200 rounded-lg overflow-hidden">
                <table class="data-table text-sm">
                  <thead>
                    <tr>
                      <th>Supplier PN</th>
                      <th>Name</th>
                      <th>Supplier</th>
                      <th>Location</th>
                      <th>Unit</th>
                      <th>Qty</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in importPreview" :key="item.supplier_part_number">
                      <td class="font-mono text-gray-600">{{ item.supplier_part_number }}</td>
                      <td>{{ item.name }}</td>
                      <td>{{ item.supplier_name || 'N/A' }}</td>
                      <td>{{ item.location_raw || formatImportLocation(item) }}</td>
                      <td>{{ item.unit_of_measure || 'each' }}</td>
                      <td>{{ item.quantity }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <p class="text-xs text-gray-500">Preview shows the first 5 rows.</p>
            </div>

            <div v-if="importStats" class="bg-green-50 border border-green-200 rounded-lg p-4 text-sm text-green-800 space-y-1">
              <div>
                Imported: {{ importStats.created }}
                | Duplicates skipped (existing): {{ importStats.duplicates }}
                | File duplicate rows flagged: {{ importFileDuplicates.length }}
                | Empty rows skipped: {{ importStats.skipped }}
                | Suppliers created: {{ importStats.suppliers_created }}
                | Errors: {{ importStats.errors }}
              </div>
              <div v-if="importDuplicateItems.length || importFileDuplicates.length" class="text-xs text-green-700">
                Duplicate report is available to download below.
              </div>
              <div v-if="importDuplicateItems.length || importFileDuplicates.length" class="text-xs text-green-700">
                Report includes two sheets: `Existing Duplicates` (already in database) and `File Duplicates` (repeated rows in uploaded file, including unit checks).
              </div>
              <div v-if="importMissingItems.length || importDuplicateItems.length || importFileDuplicates.length" class="pt-2 flex flex-wrap gap-2">
                <button
                  v-if="importMissingItems.length"
                  type="button"
                  class="btn-outline text-xs"
                  @click="downloadMissingReport"
                >
                  Download Missing Info
                </button>
                <button
                  v-if="importDuplicateItems.length || importFileDuplicates.length"
                  type="button"
                  class="btn-outline text-xs"
                  @click="downloadDuplicatesReport"
                >
                  Download Duplicates
                </button>
              </div>
            </div>

            <div v-if="importDuplicateItems.length" class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
              <div class="font-semibold mb-2">Existing duplicates (sample)</div>
              <ul class="space-y-1 text-xs text-amber-900">
                <li v-for="(item, index) in importDuplicateItems.slice(0, 5)" :key="`dup-${index}`">
                  Supplier PN {{ item.supplier_part_number }} already exists (Fowler {{ item.existing_fowler_part_number || 'N/A' }}, {{ item.existing_name || 'N/A' }})
                  <span v-if="(item.incoming_unit_of_measure || 'each') !== (item.existing_unit_of_measure || 'each')">
                    | unit mismatch: incoming {{ item.incoming_unit_of_measure || 'each' }}, existing {{ item.existing_unit_of_measure || 'each' }}
                  </span>
                </li>
              </ul>
              <div v-if="importDuplicateItems.length > 5" class="text-xs text-amber-700 mt-2">
                Showing 5 of {{ importDuplicateItems.length }}. See duplicates report for full list.
              </div>
            </div>

            <div v-if="importFileDuplicates.length" class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
              <div class="font-semibold mb-2">File duplicate rows (sample)</div>
              <ul class="space-y-1 text-xs text-amber-900">
                <li v-for="(item, index) in importFileDuplicates.slice(0, 5)" :key="`file-dup-${index}`">
                  Supplier PN {{ item.supplier_part_number }} row {{ item.source_row || '-' }}: {{ item.remarks || 'duplicate in file (same supplier + unit + location; quantity merged)' }}
                </li>
              </ul>
              <div v-if="importFileDuplicates.length > 5" class="text-xs text-amber-700 mt-2">
                Showing 5 of {{ importFileDuplicates.length }}. See duplicates report for full list.
              </div>
            </div>

            <div v-if="importErrors.length" class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700">
              <div class="font-semibold mb-2">Sample errors</div>
              <ul class="space-y-1">
                <li v-for="(error, index) in importErrors" :key="index">
                  Row {{ error.row }} ({{ error.supplier_part_number }}): {{ error.message }}
                </li>
              </ul>
            </div>
          </div>

          <div class="p-6 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 rounded-b-xl">
            <button @click="closeImportModal" class="btn-outline">Close</button>
            <button @click="submitBulkImport" :disabled="importItems.length === 0 || importLoading" class="btn-primary">
              <span v-if="importLoading" class="spinner w-4 h-4"></span>
              <span v-else>Import</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- QR Lightbox -->
    <Transition name="fade">
      <div
        v-if="qrLightbox.open"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click="closeQrLightbox"
      >
        <div
          class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 flex flex-col items-center gap-4"
          @click.stop
        >
          <div class="w-full flex items-start justify-between">
            <div>
              <div class="text-sm text-gray-500">{{ qrLightbox.label }}</div>
              <div class="text-lg font-semibold text-gray-900 break-all">{{ qrLightbox.value }}</div>
            </div>
            <button @click="closeQrLightbox" class="text-gray-400 hover:text-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <img
            :src="qrLightbox.src"
            :alt="`${qrLightbox.label} QR`"
            class="w-64 h-64 border border-gray-200 rounded-xl bg-white"
          />
          <button
            class="btn-outline"
            @click="printQr(qrLightbox)"
          >
            Print QR
          </button>
          <div class="text-xs text-gray-500">Click outside to close</div>
        </div>
      </div>
    </Transition>

    <!-- Create Part QR Modal -->
    <Transition name="fade">
      <div
        v-if="createQrModal.open"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click="closeCreateQrModal"
      >
        <div
          class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full p-6 flex flex-col gap-6"
          @click.stop
        >
          <div class="flex items-start justify-between">
            <div>
              <h3 class="text-xl font-semibold text-gray-900">Print QR Codes</h3>
              <p class="text-sm text-gray-500">Print labels for the new part</p>
            </div>
            <button @click="closeCreateQrModal" class="text-gray-400 hover:text-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="border border-gray-200 rounded-xl p-4 flex flex-col items-center gap-3">
              <div class="text-xs uppercase text-gray-500 font-semibold">Fowler Part #</div>
              <div class="font-mono text-gray-900 text-sm break-all text-center">{{ createQrModal.fowler.value || '-' }}</div>
              <div class="w-40 h-40 border border-gray-200 rounded-xl bg-white flex items-center justify-center">
                <img
                  v-if="getQrSrcByValue('fowler', createQrModal.fowler.value)"
                  :src="getQrSrcByValue('fowler', createQrModal.fowler.value)"
                  :alt="`Fowler QR for ${createQrModal.fowler.value}`"
                  class="w-40 h-40"
                />
                <div v-else class="text-xs text-gray-400">Generating...</div>
              </div>
            </div>

            <div class="border border-gray-200 rounded-xl p-4 flex flex-col items-center gap-3">
              <div class="text-xs uppercase text-gray-500 font-semibold">Supplier Part #</div>
              <div class="font-mono text-gray-900 text-sm break-all text-center">{{ createQrModal.supplier.value || '-' }}</div>
              <div class="w-40 h-40 border border-gray-200 rounded-xl bg-white flex items-center justify-center">
                <img
                  v-if="getQrSrcByValue('supplier', createQrModal.supplier.value)"
                  :src="getQrSrcByValue('supplier', createQrModal.supplier.value)"
                  :alt="`Supplier QR for ${createQrModal.supplier.value}`"
                  class="w-40 h-40"
                />
                <div v-else class="text-xs text-gray-400">Generating...</div>
              </div>
            </div>
          </div>

          <div class="flex justify-end">
            <button
              class="btn-primary"
              :disabled="!createQrModal.fowler.value || !createQrModal.supplier.value"
              @click="printCreateQrPair"
            >
              Print Both QRs
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { partsApi, suppliersApi } from '@/services/api'
import { useToast } from '@/composables/useToast'
import * as XLSX from 'xlsx'
import QRCode from 'qrcode'

const router = useRouter()
const { showToast } = useToast()

const parts = ref([])
const suppliers = ref([])
const loading = ref(true)
const saving = ref(false)
const showModal = ref(false)
const modalMode = ref('add')
const selectedPart = ref(null)
const selectedPartLocations = ref([])
const selectedPartLocationsLoading = ref(false)
const duplicateExists = ref(false)
const showImportModal = ref(false)
const importFileName = ref('')
const importItems = ref([])
const importParsing = ref(false)
const importLoading = ref(false)
const importParseError = ref('')
const importStats = ref(null)
const importErrors = ref([])
const importMissingItems = ref([])
const importDuplicateItems = ref([])
const importFileDuplicates = ref([])
const importProgress = ref({
  total: 0,
  processed: 0,
  percent: 0
})
const qrCache = ref({})
const pendingQr = new Set()
const qrLightbox = ref({
  open: false,
  src: '',
  label: '',
  value: ''
})
const createQrModal = ref({
  open: false,
  fowler: { value: '' },
  supplier: { value: '' }
})

const qrOptions = {
  width: 256,
  margin: 1,
  errorCorrectionLevel: 'M'
}

// Pagination
const currentPage = ref(1)
const perPage = ref(25)

// Search
const searchQuery = ref('')
let searchTimeout = null

// Fowler PN dropdown
const fowlerPnSearch = ref('')
const selectedFowlerPn = ref(false)
const showFowlerPnDropdown = ref(false)
const fowlerMatchedPart = ref(null)
let fowlerLookupTimeout = null
let fowlerLookupRequestId = 0

const form = ref({
  fowler_part_number: '',
  name: '',
  supplier_id: '',
  supplier_part_number: '',
  unit_of_measure: 'each',
  location_aisle: '',
  location_shelf: '',
  location_bay: '',
  location_alt: '',
  low_stock_threshold: 5,
  unit_price: 0
})

const modalTitle = ref('Add New Part')

const paginatedParts = computed(() => {
  const source = Array.isArray(parts.value) ? parts.value : []
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return source.slice(start, end)
})

const totalPages = computed(() => {
  const count = Array.isArray(parts.value) ? parts.value.length : 0
  return Math.ceil(count / perPage.value)
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

const importPreview = computed(() => {
  return importItems.value.slice(0, 5)
})

// Get unique Fowler PNs
const uniqueFowlerPns = computed(() => {
  const pns = new Set()
  parts.value.forEach(part => {
    if (part.fowler_part_number) {
      pns.add(part.fowler_part_number)
    }
  })
  return Array.from(pns).sort()
})

// Filter Fowler PNs based on search
const filteredFowlerPns = computed(() => {
  if (!fowlerPnSearch.value.trim()) {
    return uniqueFowlerPns.value
  }
  
  const search = fowlerPnSearch.value.toLowerCase()
  return uniqueFowlerPns.value.filter(fpn => 
    fpn.toLowerCase().includes(search)
  )
})

// Get count of parts for a Fowler PN
const getPartCountForFowlerPn = (fowlerPn) => {
  return parts.value.filter(p => p.fowler_part_number === fowlerPn).length
}

const importHeaderMap = {
  'part number': 'supplier_part_number',
  'part#': 'supplier_part_number',
  'part #': 'supplier_part_number',
  'part no': 'supplier_part_number',
  'part pn': 'supplier_part_number',
  'pn': 'supplier_part_number',
  'supplier part number': 'supplier_part_number',
  'supplier part #': 'supplier_part_number',
  'supplier part no': 'supplier_part_number',
  'supplier pn': 'supplier_part_number',
  'description': 'name',
  'part name': 'name',
  'item description': 'name',
  'manufacturer (if known)': 'supplier_name',
  'manufacturer': 'supplier_name',
  'supplier': 'supplier_name',
  'vendor': 'supplier_name',
  'location': 'location_raw',
  'alternate location': 'location_alt',
  'alt location': 'location_alt',
  'other location': 'location_alt',
  'location other': 'location_alt',
  'bin location': 'location_alt',
  'bin': 'location_alt',
  'qty': 'quantity',
  'quantity': 'quantity',
  'unit': 'unit_of_measure',
  'units': 'unit_of_measure',
  'uom': 'unit_of_measure',
  'u/m': 'unit_of_measure',
  'unit of measure': 'unit_of_measure',
  'unit type': 'unit_of_measure',
  'measure': 'unit_of_measure',
  'cost/unit': 'unit_price',
  'cost per unit': 'unit_price',
  'unit cost': 'unit_price',
  'unit price': 'unit_price',
  'price': 'unit_price',
  'fowler part number': 'fowler_part_number',
  'fowler pn': 'fowler_part_number',
  'fowler part #': 'fowler_part_number'
}

const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchParts()
  }, 300)
}

const fetchParts = async () => {
  try {
    loading.value = true
    const params = {}
    if (searchQuery.value) {
      params.search = searchQuery.value
    }
    const response = await partsApi.getAll(params)
    const payload = response?.data ?? response
    const rows = Array.isArray(payload) ? payload : (Array.isArray(payload?.data) ? payload.data : [])
    parts.value = rows
    if (currentPage.value > totalPages.value && totalPages.value > 0) {
      currentPage.value = totalPages.value
    }
    if (totalPages.value === 0) {
      currentPage.value = 1
    }
  } catch (error) {
    parts.value = []
    showToast('Error', error.response?.data?.message || 'Failed to load parts', 'error')
  } finally {
    loading.value = false
  }
}

const fetchSuppliers = async () => {
  try {
    const response = await suppliersApi.getAll()
    const payload = response?.data ?? response
    suppliers.value = Array.isArray(payload) ? payload : (Array.isArray(payload?.data) ? payload.data : [])
  } catch (error) {
    console.error('Failed to load suppliers:', error)
  }
}

const loadSelectedPartLocations = async (partId) => {
  if (!partId) {
    selectedPartLocations.value = []
    return
  }

  selectedPartLocationsLoading.value = true
  try {
    const response = await partsApi.getLocations(partId)
    selectedPartLocations.value = Array.isArray(response?.data?.locations)
      ? response.data.locations
      : []
  } catch (error) {
    selectedPartLocations.value = []
  } finally {
    selectedPartLocationsLoading.value = false
  }
}

const isLowStock = (part) => {
  return (part?.stock || 0) <= (part?.low_stock_threshold || 0)
}

const availabilityStatus = (part) => {
  const stock = Number(part?.stock || 0)
  const threshold = Number(part?.low_stock_threshold || 0)
  if (stock <= 0) return 'out'
  if (stock <= threshold) return 'low'
  return 'in'
}

const availabilityLabel = (part) => {
  const status = availabilityStatus(part)
  if (status === 'out') return 'Out of Stock'
  if (status === 'low') return 'Low Stock'
  return 'In Stock'
}

const currencyFormatter = new Intl.NumberFormat('en-US', {
  style: 'currency',
  currency: 'USD'
})

const formatCurrency = (value) => {
  const numeric = Number(value || 0)
  return currencyFormatter.format(Number.isFinite(numeric) ? numeric : 0)
}

const formatLocation = (part) => {
  if (part?.location_stock_summary) {
    return part.location_stock_summary
  }
  if (part?.location_alt) {
    return part.location_alt
  }
  const loc = [part.location_aisle, part.location_shelf, part.location_bay].filter(Boolean)
  return loc.length > 0 ? loc.join('-') : '-'
}

const formatImportLocation = (item) => {
  if (item?.location_alt) {
    return item.location_alt
  }
  const loc = [item.location_aisle, item.location_shelf, item.location_bay].filter(Boolean)
  return loc.length > 0 ? loc.join('-') : '-'
}

const buildQrPayload = (type, value) => {
  if (!value) return ''
  const raw = `PAM:${type.toUpperCase()}:${value}`
  if (typeof window === 'undefined') {
    return raw
  }
  const url = new URL('/scanner', window.location.origin)
  url.searchParams.set('qr', raw)
  url.searchParams.set('src', 'pam')
  return url.toString()
}

const getQrKey = (type, value) => {
  return `${type}:${value}`
}

const ensureQr = async (type, value) => {
  if (!value) return
  const key = getQrKey(type, value)
  if (qrCache.value[key]) return qrCache.value[key]
  if (pendingQr.has(key)) return ''

  pendingQr.add(key)
  try {
    const payload = buildQrPayload(type, value)
    const svg = await QRCode.toString(payload, { type: 'svg', ...qrOptions })
    const dataUrl = `data:image/svg+xml;base64,${btoa(svg)}`
    qrCache.value[key] = dataUrl
    return dataUrl
  } catch (error) {
    console.error('Failed to generate QR code:', error)
    return ''
  } finally {
    pendingQr.delete(key)
  }
}

const getQrSrc = (part, type) => {
  const value = type === 'supplier' ? part.supplier_part_number : part.fowler_part_number
  if (!value) return ''
  const key = getQrKey(type, value)
  return qrCache.value[key] || ''
}

const getQrSrcByValue = (type, value) => {
  if (!value) return ''
  const key = getQrKey(type, value)
  return qrCache.value[key] || ''
}

const openQrLightbox = (label, value, src) => {
  if (!src) return
  qrLightbox.value = {
    open: true,
    src,
    label,
    value
  }
}

const closeQrLightbox = () => {
  qrLightbox.value.open = false
}

const openCreateQrModal = async (part) => {
  createQrModal.value = {
    open: true,
    fowler: { value: part?.fowler_part_number || '' },
    supplier: { value: part?.supplier_part_number || '' }
  }

  if (part?.fowler_part_number) {
    ensureQr('fowler', part.fowler_part_number)
  }
  if (part?.supplier_part_number) {
    ensureQr('supplier', part.supplier_part_number)
  }
}

const closeCreateQrModal = () => {
  createQrModal.value.open = false
}

const printQr = (payload) => {
  if (!payload?.src) return
  const printWindow = window.open('', '_blank', 'width=520,height=620')
  if (!printWindow) return

  const safeLabel = payload.label || 'QR Code'
  const safeValue = payload.value || ''
  const html = `
    <!doctype html>
    <html>
      <head>
        <meta charset="utf-8" />
        <title>${safeLabel}</title>
        <style>
          @page { margin: 12mm; }
          body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            gap: 12px;
            padding: 16px;
          }
          .label {
            font-size: 14px;
            color: #555;
          }
          .value {
            font-size: 18px;
            font-weight: 600;
            color: #111;
            word-break: break-all;
            text-align: center;
          }
          .qr {
            width: 256px;
            height: 256px;
          }
          img {
            width: 256px;
            height: 256px;
            image-rendering: pixelated;
          }
        </style>
      </head>
      <body>
        <div class="label">${safeLabel}</div>
        <div class="value">${safeValue}</div>
        <div class="qr">
          <img src="${payload.src}" alt="${safeLabel}" />
        </div>
      </body>
    </html>
  `

  printWindow.document.open()
  printWindow.document.write(html)
  printWindow.document.close()

  let printTriggered = false

  const triggerPrint = () => {
    if (printTriggered) return
    printTriggered = true
    printWindow.focus()
    printWindow.print()
  }

  const closeAfterPrint = () => {
    if (!printWindow.closed) {
      printWindow.close()
    }
  }

  printWindow.addEventListener('load', triggerPrint, { once: true })
  printWindow.addEventListener('afterprint', closeAfterPrint)

  // Fallbacks for browsers that don't fire load/afterprint reliably
  setTimeout(triggerPrint, 500)
  setTimeout(closeAfterPrint, 2500)
}

const printQrByValue = async (type, label, value) => {
  if (!value) return
  const src = (await ensureQr(type, value)) || getQrSrcByValue(type, value)
  if (!src) {
    showToast('Error', 'QR still generating, please try again', 'error')
    return
  }
  printQr({ src, label, value })
}

const printCreateQrPair = async () => {
  const fowlerValue = createQrModal.value.fowler.value
  const supplierValue = createQrModal.value.supplier.value
  if (!fowlerValue || !supplierValue) return

  const [fowlerSrc, supplierSrc] = await Promise.all([
    ensureQr('fowler', fowlerValue),
    ensureQr('supplier', supplierValue)
  ])

  const fowlerQr = fowlerSrc || getQrSrcByValue('fowler', fowlerValue)
  const supplierQr = supplierSrc || getQrSrcByValue('supplier', supplierValue)

  if (!fowlerQr || !supplierQr) {
    showToast('Error', 'QR still generating, please try again', 'error')
    return
  }

  const printWindow = window.open('', '_blank', 'width=620,height=720')
  if (!printWindow) return

  const html = `
    <!doctype html>
    <html>
      <head>
        <meta charset="utf-8" />
        <title>Print QR Codes</title>
        <style>
          @page { margin: 12mm; }
          body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 16px;
          }
          .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
          }
          .card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
          }
          .label {
            font-size: 12px;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.05em;
          }
          .value {
            font-size: 14px;
            font-weight: 600;
            color: #111;
            word-break: break-all;
            text-align: center;
          }
          .qr {
            width: 256px;
            height: 256px;
          }
          img {
            width: 256px;
            height: 256px;
            image-rendering: pixelated;
          }
        </style>
      </head>
      <body>
        <div class="row">
          <div class="card">
            <div class="label">Fowler Part #</div>
            <div class="value">${fowlerValue}</div>
            <div class="qr">
              <img src="${fowlerQr}" alt="Fowler QR" />
            </div>
          </div>
          <div class="card">
            <div class="label">Supplier Part #</div>
            <div class="value">${supplierValue}</div>
            <div class="qr">
              <img src="${supplierQr}" alt="Supplier QR" />
            </div>
          </div>
        </div>
      </body>
    </html>
  `

  printWindow.document.open()
  printWindow.document.write(html)
  printWindow.document.close()

  let printTriggered = false
  const triggerPrint = () => {
    if (printTriggered) return
    printTriggered = true
    printWindow.focus()
    printWindow.print()
  }

  const closeAfterPrint = () => {
    if (!printWindow.closed) {
      printWindow.close()
    }
  }

  printWindow.addEventListener('load', triggerPrint, { once: true })
  printWindow.addEventListener('afterprint', closeAfterPrint)
  setTimeout(triggerPrint, 500)
  setTimeout(closeAfterPrint, 2500)
}

const resetImportState = () => {
  importFileName.value = ''
  importItems.value = []
  importParsing.value = false
  importLoading.value = false
  importParseError.value = ''
  importStats.value = null
  importErrors.value = []
  importMissingItems.value = []
  importDuplicateItems.value = []
  importFileDuplicates.value = []
  importProgress.value = { total: 0, processed: 0, percent: 0 }
}

const openImportModal = () => {
  resetImportState()
  showImportModal.value = true
}

const closeImportModal = () => {
  showImportModal.value = false
  resetImportState()
}

const handleImportFile = async (event) => {
  const file = event.target.files?.[0]
  if (!file) return

  resetImportState()
  importFileName.value = file.name
  importParsing.value = true

  try {
    const data = await file.arrayBuffer()
    const workbook = XLSX.read(data, { type: 'array' })
    const { items, missingItems } = parseWorkbook(workbook)
    const { items: dedupedItems, duplicates } = dedupeImportItems(items)
    importItems.value = dedupedItems
    importFileDuplicates.value = duplicates
    importMissingItems.value = missingItems

    if (importItems.value.length === 0) {
      importParseError.value = 'No usable rows found. Check headers and try again.'
    }
  } catch (error) {
    console.error('Import parse failed:', error)
    importParseError.value = 'Could not read the file. Try saving as .xlsx or .csv.'
  } finally {
    importParsing.value = false
  }
}

const submitBulkImport = async () => {
  if (importItems.value.length === 0) return

  try {
    importLoading.value = true
    importProgress.value = {
      total: importItems.value.length,
      processed: 0,
      percent: 0
    }
    const aggregateStats = {
      processed: 0,
      created: 0,
      duplicates: 0,
      skipped: 0,
      errors: 0,
      suppliers_created: 0
    }
    const aggregateErrors = []
    const aggregateDuplicateItems = []
    const batchSize = 100

    for (let i = 0; i < importItems.value.length; i += batchSize) {
      const batch = importItems.value.slice(i, i + batchSize)
      const response = await partsApi.bulkImport(batch)
      const stats = response.data?.stats || {}

      aggregateStats.processed += stats.processed || 0
      aggregateStats.created += stats.created || 0
      aggregateStats.duplicates += stats.duplicates || 0
      aggregateStats.skipped += stats.skipped || 0
      aggregateStats.errors += stats.errors || 0
      aggregateStats.suppliers_created += stats.suppliers_created || 0

      if (Array.isArray(response.data?.errors)) {
        aggregateErrors.push(...response.data.errors)
      }
      if (Array.isArray(response.data?.duplicate_items)) {
        aggregateDuplicateItems.push(...response.data.duplicate_items)
      }

      const processed = Math.min(i + batch.length, importProgress.value.total)
      importProgress.value = {
        total: importProgress.value.total,
        processed,
        percent: Math.round((processed / importProgress.value.total) * 100)
      }
    }

    importStats.value = aggregateStats
    importErrors.value = aggregateErrors.slice(0, 25)
    importDuplicateItems.value = aggregateDuplicateItems
    showToast('Success', 'Bulk import completed')
    fetchParts()
  } catch (error) {
    showToast('Error', error.response?.data?.message || 'Bulk import failed', 'error')
  } finally {
    importLoading.value = false
  }
}

const downloadMissingReport = () => {
  if (importMissingItems.value.length === 0) return

  const headers = [
    'Sheet',
    'Row',
    'Part Number',
    'Description',
    'Manufacturer',
    'Location',
    'Unit',
    'Quantity',
    'Cost/Unit',
    'Remarks'
  ]

  const rows = importMissingItems.value.map(item => [
    item.sheet,
    item.row,
    item.part_number,
    item.description,
    item.manufacturer,
    item.location,
    item.unit_of_measure || 'each',
    item.quantity,
    item.unit_price,
    item.remarks
  ])

  const ws = XLSX.utils.aoa_to_sheet([headers, ...rows])
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Missing Items')

  const today = new Date().toISOString().slice(0, 10)
  XLSX.writeFile(wb, `missing_items_${today}.xlsx`)
}

const downloadDuplicatesReport = () => {
  if (importDuplicateItems.value.length === 0 && importFileDuplicates.value.length === 0) return

  const wb = XLSX.utils.book_new()
  const guideRows = [
    ['Sheet', 'Purpose', 'Why it matters'],
    [
      'Existing Duplicates',
      'Rows skipped because Supplier Part Number already exists in the database.',
      'Prevents creating a second master record for the same supplier part number and preserves data integrity.'
    ],
    [
      'File Duplicates',
      'Rows where Supplier Part Number appears more than once in the uploaded file.',
      'Only exact duplicates (same supplier PN + unit + location) are merged; this sheet also flags unit mismatches for manual review.'
    ]
  ]
  XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(guideRows), 'How to Use')

  if (importFileDuplicates.value.length > 0) {
    const headers = [
      'Source',
      'Sheet',
      'Row',
      'Supplier Part Number',
      'Name',
      'Manufacturer',
      'Location',
      'Unit',
      'Merged Unit (Import)',
      'Quantity',
      'Cost/Unit',
      'Remarks'
    ]
    const rows = importFileDuplicates.value.map(item => [
      'file',
      item.source_sheet || '',
      item.source_row || '',
      item.supplier_part_number || '',
      item.name || item.description || '',
      item.supplier_name || '',
      item.location_raw || item.location_alt || '',
      item.unit_of_measure || 'each',
      item.merged_unit_of_measure || item.unit_of_measure || 'each',
      item.quantity ?? '',
      item.unit_price ?? '',
      item.remarks || 'duplicate in file (same supplier + unit + location; quantity merged)'
    ])
    const ws = XLSX.utils.aoa_to_sheet([headers, ...rows])
    XLSX.utils.book_append_sheet(wb, ws, 'File Duplicates')
  }

  if (importDuplicateItems.value.length > 0) {
    const headers = [
      'Source',
      'Sheet',
      'Row',
      'Supplier Part Number',
      'Incoming Name',
      'Incoming Unit',
      'Existing Part ID',
      'Existing Fowler PN',
      'Existing Name',
      'Existing Unit',
      'Remarks'
    ]
    const rows = importDuplicateItems.value.map(item => [
      'existing',
      item.sheet || '',
      item.row || '',
      item.supplier_part_number || '',
      item.incoming_name || '',
      item.incoming_unit_of_measure || 'each',
      item.existing_part_id || '',
      item.existing_fowler_part_number || '',
      item.existing_name || '',
      item.existing_unit_of_measure || 'each',
      (item.incoming_unit_of_measure || 'each') !== (item.existing_unit_of_measure || 'each')
        ? `already in database; unit mismatch: incoming=${item.incoming_unit_of_measure || 'each'}, existing=${item.existing_unit_of_measure || 'each'}`
        : 'already in database'
    ])
    const ws = XLSX.utils.aoa_to_sheet([headers, ...rows])
    XLSX.utils.book_append_sheet(wb, ws, 'Existing Duplicates')
  }

  const today = new Date().toISOString().slice(0, 10)
  XLSX.writeFile(wb, `duplicates_skipped_${today}.xlsx`)
}

const parseWorkbook = (workbook) => {
  const items = []
  const missingItems = []

  workbook.SheetNames.forEach((sheetName) => {
    const sheet = workbook.Sheets[sheetName]
    const rows = XLSX.utils.sheet_to_json(sheet, {
      header: 1,
      raw: false,
      defval: ''
    })

    const headerInfo = findHeaderRow(rows)
    if (!headerInfo) {
      return
    }

    for (let rowIndex = headerInfo.rowIndex + 1; rowIndex < rows.length; rowIndex++) {
      const row = rows[rowIndex]
      if (!row || row.length === 0) continue

      const rowData = {}
      Object.entries(headerInfo.columnMap).forEach(([index, field]) => {
        rowData[field] = row[index] ?? ''
      })

      if (isRowEmpty(rowData)) {
        continue
      }

      const { item, missingInfo } = buildImportItem(rowData, {
        sheetName,
        rowNumber: rowIndex + 1
      })
      if (missingInfo) {
        missingItems.push(missingInfo)
      }
      if (item) {
        items.push(item)
      }
    }
  })

  return { items, missingItems }
}

const findHeaderRow = (rows) => {
  const maxScan = Math.min(rows.length, 15)
  let bestMatch = null

  for (let i = 0; i < maxScan; i++) {
    const row = rows[i] || []
    const columnMap = {}

    row.forEach((cell, index) => {
      const key = normalizeHeader(cell)
      if (importHeaderMap[key]) {
        columnMap[index] = importHeaderMap[key]
      }
    })

    const fields = Object.values(columnMap)
    const hasSupplier = fields.includes('supplier_part_number')
    const hasName = fields.includes('name')

    if (hasSupplier && hasName) {
      return { rowIndex: i, columnMap }
    }

    if (fields.length >= 2 && hasSupplier) {
      if (!bestMatch || fields.length > bestMatch.score) {
        bestMatch = { rowIndex: i, columnMap, score: fields.length }
      }
    }
  }

  return bestMatch ? { rowIndex: bestMatch.rowIndex, columnMap: bestMatch.columnMap } : null
}

const buildImportItem = (rowData, meta) => {
  const missing = []
  const supplierPartNumber = normalizeString(rowData.supplier_part_number)
  const supplierName = normalizeString(rowData.supplier_name)
  const unitOfMeasure = normalizeUnitOfMeasure(rowData.unit_of_measure)
  const rawDescription = normalizeString(rowData.description || rowData.name)
  let description = rawDescription

  if (!supplierPartNumber) {
    missing.push('missing part number')
  }

  if (!description) {
    if (supplierName) {
      description = `manufacturer: ${supplierName}`
    }
    missing.push('missing description')
  }

  if (!supplierName) {
    missing.push('missing manufacturer')
  }

  const quantity = parseQuantity(rowData.quantity)
  const rawUnitPrice = normalizeString(rowData.unit_price)
  const unitPrice = parseUnitPrice(rowData.unit_price)
  const missingCost = rawUnitPrice === '' || unitPrice <= 0
  if (missingCost) {
    missing.push('missing cost/unit')
  }

  const locationData = resolveLocation(rowData)
  if (locationData.missing) {
    missing.push('missing location')
  }

  const missingInfo = missing.length
    ? {
        sheet: meta.sheetName,
        row: meta.rowNumber,
        part_number: supplierPartNumber,
        description: description || rawDescription,
        manufacturer: supplierName,
        location: locationData.location_raw || locationData.location_alt,
        unit_of_measure: unitOfMeasure,
        quantity: normalizeString(rowData.quantity),
        unit_price: rawUnitPrice,
        remarks: missing.join(', ')
      }
    : null

  if (missing.length > 0) {
    return { item: null, missingInfo }
  }

  return {
    item: {
      supplier_part_number: supplierPartNumber,
      name: description,
      description,
      supplier_name: supplierName,
      unit_of_measure: unitOfMeasure,
      location_raw: locationData.location_raw,
      location_aisle: locationData.location_aisle,
      location_shelf: locationData.location_shelf,
      location_bay: locationData.location_bay,
      location_alt: locationData.location_alt,
      quantity,
      unit_price: unitPrice,
      source_sheet: meta.sheetName,
      source_row: meta.rowNumber
    },
    missingInfo
  }
}

const resolveLocation = (rowData) => {
  const locationRaw = normalizeString(rowData.location_raw)
  const locationAlt = normalizeString(rowData.location_alt)

  if (!locationRaw) {
    if (locationAlt) {
      return {
        location_raw: '',
        location_aisle: '',
        location_shelf: '',
        location_bay: '',
        location_alt: locationAlt,
        missing: false
      }
    }

    return {
      location_raw: '',
      location_aisle: '',
      location_shelf: '',
      location_bay: '',
      location_alt: 'location undefined',
      missing: true
    }
  }

  const dashMatch = locationRaw.match(/^[^\\-\\.]+-[^\\-\\.]+-[^\\-\\.]+$/)
  if (dashMatch) {
    const [aisle, shelf, bay] = locationRaw.split('-').map(part => part.trim())
    return {
      location_raw: locationRaw,
      location_aisle: aisle || '',
      location_shelf: shelf || '',
      location_bay: bay || '',
      location_alt: '',
      missing: false
    }
  }

  const dotMatch = locationRaw.match(/^[^\\.]+\\.[^\\.]+\\.[^\\.]+$/)
  if (dotMatch) {
    const [aisle, shelf, bay] = locationRaw.split('.').map(part => part.trim())
    return {
      location_raw: locationRaw,
      location_aisle: aisle || '',
      location_shelf: shelf || '',
      location_bay: bay || '',
      location_alt: '',
      missing: false
    }
  }

  return {
    location_raw: locationRaw,
    location_aisle: '',
    location_shelf: '',
    location_bay: '',
    location_alt: locationRaw,
    missing: false
  }
}

const parseLocationTokens = (value) => {
  const text = normalizeString(value)
  if (!text) return []
  return text
    .split('|')
    .map(part => normalizeString(part))
    .filter(Boolean)
}

const collectItemLocations = (item) => {
  const locations = new Set()
  const raw = normalizeString(item.location_raw)
  const alt = normalizeString(item.location_alt)

  parseLocationTokens(raw).forEach(location => locations.add(location))
  parseLocationTokens(alt).forEach(location => locations.add(location))

  if (!raw && !alt) {
    parseLocationTokens(formatImportLocation(item)).forEach(location => locations.add(location))
  }

  return Array.from(locations)
}

const toMergedLocationText = (locations) => {
  const merged = Array.from(new Set(locations.map(location => normalizeString(location)).filter(Boolean))).join(' | ')
  return merged.length > 255 ? merged.slice(0, 255) : merged
}

const dedupeImportItems = (items) => {
  const mergedByRowKey = new Map()
  const supplierUnits = new Map()
  const duplicates = []

  items.forEach((rawItem) => {
    if (!rawItem?.supplier_part_number) return

    const supplierPartNumber = normalizeString(rawItem.supplier_part_number)
    if (!supplierPartNumber) return

    const item = {
      ...rawItem,
      supplier_part_number: supplierPartNumber,
      unit_of_measure: normalizeUnitOfMeasure(rawItem.unit_of_measure),
      quantity: parseQuantity(rawItem.quantity),
      location_raw: normalizeString(rawItem.location_raw),
      location_aisle: normalizeString(rawItem.location_aisle),
      location_shelf: normalizeString(rawItem.location_shelf),
      location_bay: normalizeString(rawItem.location_bay),
      location_alt: normalizeString(rawItem.location_alt)
    }

    const rowUnit = item.unit_of_measure || 'each'
    if (!supplierUnits.has(supplierPartNumber)) {
      supplierUnits.set(supplierPartNumber, new Set())
    }
    supplierUnits.get(supplierPartNumber).add(rowUnit)

    const rowLocations = collectItemLocations(item).map(location => normalizeString(location)).filter(Boolean)
    const locationSignature = rowLocations.length ? rowLocations.slice().sort().join('|') : 'unassigned'
    const mergeKey = `${supplierPartNumber}::${rowUnit}::${locationSignature}`

    const existing = mergedByRowKey.get(mergeKey)
    if (!existing) {
      mergedByRowKey.set(mergeKey, {
        item: { ...item },
        locationSet: new Set(rowLocations)
      })
      return
    }

    collectItemLocations(item).forEach(location => existing.locationSet.add(location))
    existing.item.quantity = parseQuantity(existing.item.quantity) + parseQuantity(item.quantity)

    duplicates.push({
      ...item,
      merged_unit_of_measure: rowUnit,
      remarks: 'duplicate in file (same supplier + unit + location; quantity merged)'
    })
  })

  const mergedItems = Array.from(mergedByRowKey.values()).map((entry) => {
    const mergedItem = { ...entry.item }
    const locations = Array.from(entry.locationSet).filter(Boolean)

    if (locations.length > 1) {
      mergedItem.location_raw = ''
      mergedItem.location_aisle = ''
      mergedItem.location_shelf = ''
      mergedItem.location_bay = ''
      mergedItem.location_alt = toMergedLocationText(locations)
    } else if (locations.length === 1 && !normalizeString(mergedItem.location_raw) && !normalizeString(mergedItem.location_alt)) {
      mergedItem.location_alt = locations[0]
    }

    return mergedItem
  })

  supplierUnits.forEach((unitSet, supplierPartNumber) => {
    if (unitSet.size <= 1) return
    const units = Array.from(unitSet)
    mergedItems
      .filter(item => normalizeString(item.supplier_part_number) === supplierPartNumber)
      .forEach((mergedItem) => {
        duplicates.push({
          source_sheet: mergedItem.source_sheet || '',
          source_row: mergedItem.source_row || '',
          supplier_part_number: mergedItem.supplier_part_number,
          name: mergedItem.name || mergedItem.description || '',
          supplier_name: mergedItem.supplier_name || '',
          location_raw: mergedItem.location_raw || mergedItem.location_alt || '',
          unit_of_measure: mergedItem.unit_of_measure || 'each',
          merged_unit_of_measure: mergedItem.unit_of_measure || 'each',
          quantity: mergedItem.quantity,
          unit_price: mergedItem.unit_price,
          remarks: `unit mismatch across file rows (${units.join(', ')})`
        })
      })
  })

  return { items: mergedItems, duplicates }
}

const isRowEmpty = (rowData) => {
  return Object.values(rowData).every(value => normalizeString(value) === '')
}

const normalizeHeader = (value) => {
  return normalizeString(value).toLowerCase().replace(/\s*\/\s*/g, '/')
}

const normalizeString = (value) => {
  if (value === null || value === undefined) {
    return ''
  }
  return String(value).replace(/\s+/g, ' ').trim()
}

const normalizeUnitOfMeasure = (value) => {
  const unit = normalizeString(value).toLowerCase()
  if (!unit) return 'each'
  return unit.slice(0, 30)
}

const normalizeFowlerPartNumber = (value) => {
  return normalizeString(value).toUpperCase()
}

const parseQuantity = (value) => {
  const num = normalizeNumber(value)
  return Math.round(num)
}

const parseUnitPrice = (value) => {
  const num = normalizeNumber(value)
  return Number(num.toFixed(2))
}

const normalizeNumber = (value) => {
  if (value === null || value === undefined || value === '') {
    return 0
  }
  if (typeof value === 'number') {
    return value
  }
  const cleaned = String(value).replace(/[^0-9.\-]/g, '')
  if (cleaned === '' || cleaned === '-' || cleaned === '.') {
    return 0
  }
  return Number(cleaned)
}

const buildPartPayload = (part = {}) => ({
  fowler_part_number: normalizeFowlerPartNumber(part.fowler_part_number),
  name: normalizeString(part.name),
  supplier_id: part.supplier_id ?? '',
  supplier_part_number: normalizeString(part.supplier_part_number),
  unit_of_measure: normalizeUnitOfMeasure(part.unit_of_measure),
  location_aisle: normalizeString(part.location_aisle),
  location_shelf: normalizeString(part.location_shelf),
  location_bay: normalizeString(part.location_bay),
  location_alt: normalizeString(part.location_alt),
  low_stock_threshold: Math.max(0, Math.round(normalizeNumber(part.low_stock_threshold ?? 5))),
  unit_price: parseUnitPrice(part.unit_price ?? 0)
})

const isNameLockedByFowler = computed(() => {
  return modalMode.value === 'add' && !!fowlerMatchedPart.value
})

const getNextFowlerPartNumberFromParts = () => {
  let maxSequence = 0
  parts.value.forEach((part) => {
    const match = normalizeFowlerPartNumber(part?.fowler_part_number).match(/^FC-P(\d+)$/)
    if (!match) return
    const value = Number(match[1])
    if (Number.isFinite(value) && value > maxSequence) {
      maxSequence = value
    }
  })
  return `FC-P${String(maxSequence + 1).padStart(6, '0')}`
}

const fetchNextFowlerPartNumber = async () => {
  try {
    const response = await partsApi.getNextFowler()
    const fromApi = normalizeFowlerPartNumber(response?.data?.fowler_part_number)
    if (fromApi) return fromApi
  } catch (error) {
    // Fallback to local max sequence if API endpoint is unavailable
  }

  return getNextFowlerPartNumberFromParts()
}

const populateNextFowlerPartNumber = async () => {
  if (modalMode.value !== 'add') return
  const next = await fetchNextFowlerPartNumber()
  if (!next) return
  form.value.fowler_part_number = next
  fowlerPnSearch.value = next
}


const resetForm = () => {
  form.value = buildPartPayload()
  fowlerPnSearch.value = ''
  selectedFowlerPn.value = false
  fowlerMatchedPart.value = null
  showFowlerPnDropdown.value = false
  if (fowlerLookupTimeout) {
    clearTimeout(fowlerLookupTimeout)
    fowlerLookupTimeout = null
  }
  duplicateExists.value = false
}

const openAddModal = () => {
  resetForm()
  selectedPartLocations.value = []
  selectedPartLocationsLoading.value = false
  modalMode.value = 'add'
  modalTitle.value = 'Add New Part'
  showModal.value = true
  populateNextFowlerPartNumber()
}

const openEditModal = (part) => {
  selectedPart.value = part
  selectedPartLocations.value = []
  selectedPartLocationsLoading.value = false
  form.value = buildPartPayload(part)
  fowlerPnSearch.value = form.value.fowler_part_number
  selectedFowlerPn.value = true
  fowlerMatchedPart.value = null
  showFowlerPnDropdown.value = false
  modalMode.value = 'edit'
  modalTitle.value = `Edit: ${part.fowler_part_number}`
  showModal.value = true
}

const openDetailModal = (part) => {
  selectedPart.value = part
  selectedPartLocations.value = []
  selectedPartLocationsLoading.value = false
  modalMode.value = 'view'
  modalTitle.value = `Details: ${part.fowler_part_number}`
  showModal.value = true
  loadSelectedPartLocations(part.id)
}

const closeModal = () => {
  showModal.value = false
  selectedPart.value = null
  selectedPartLocations.value = []
  selectedPartLocationsLoading.value = false
  resetForm()
}

const checkDuplicate = async () => {
  if (!form.value.supplier_part_number || modalMode.value === 'edit') {
    duplicateExists.value = false
    return
  }
  
  try {
    const response = await partsApi.checkDuplicate(form.value.supplier_part_number)
    duplicateExists.value = response.data?.exists || false
  } catch (error) {
    duplicateExists.value = false
  }
}

const savePart = async () => {
  try {
    saving.value = true
    let fowlerPartNumber = normalizeFowlerPartNumber(form.value.fowler_part_number || fowlerPnSearch.value)
    if (!fowlerPartNumber) {
      fowlerPartNumber = await fetchNextFowlerPartNumber()
      form.value.fowler_part_number = fowlerPartNumber
      fowlerPnSearch.value = fowlerPartNumber
    }

    if (!fowlerPartNumber || !form.value.name) {
      showToast('Error', 'Fowler Part # and Name are required', 'error')
      return
    }

    const payload = buildPartPayload(form.value)
    
    if (modalMode.value === 'edit') {
      await partsApi.update(selectedPart.value.id, payload)
      showToast('Success', 'Part updated successfully')
      closeModal()
      fetchParts()
      return
    }

    const response = await partsApi.create(payload)
    showToast('Success', 'Part created successfully')
    closeModal()

    const createdPart = response?.data || {
      fowler_part_number: form.value.fowler_part_number,
      supplier_part_number: form.value.supplier_part_number
    }
    openCreateQrModal(createdPart)
    fetchParts()
  } catch (error) {
    showToast('Error', error.response?.data?.message || 'Failed to save part', 'error')
  } finally {
    saving.value = false
  }
}

const deletePart = async (part) => {
  if (!confirm(`Delete part ${part.name} (${part.supplier_part_number})? This will only work if stock is 0.`)) return
  
  try {
    await partsApi.delete(part.id)
    showToast('Success', 'Part deleted successfully')
    fetchParts()
  } catch (error) {
    showToast('Error', error.response?.data?.message || 'Failed to delete part', 'error')
  }
}

const quickCheckout = (part) => {
  router.push({ path: '/outgoing', query: { partId: part.id } })
}

// Fowler PN dropdown handlers
const findPartByFowlerPartNumber = (fowlerPn) => {
  const normalized = normalizeFowlerPartNumber(fowlerPn)
  if (!normalized) return null
  return parts.value.find(part => normalizeFowlerPartNumber(part.fowler_part_number) === normalized) || null
}

const applyFowlerPartMatch = (part) => {
  if (!part) return
  fowlerMatchedPart.value = part
  selectedFowlerPn.value = true
  form.value.fowler_part_number = normalizeFowlerPartNumber(part.fowler_part_number)
  fowlerPnSearch.value = form.value.fowler_part_number
  form.value.name = normalizeString(part.name)
  if (part.unit_of_measure) {
    form.value.unit_of_measure = normalizeUnitOfMeasure(part.unit_of_measure)
  }
}

const lookupFowlerPartNumber = async (fowlerPn) => {
  if (modalMode.value !== 'add') return
  const normalized = normalizeFowlerPartNumber(fowlerPn)
  if (!normalized) {
    fowlerMatchedPart.value = null
    selectedFowlerPn.value = false
    return
  }

  const localMatch = findPartByFowlerPartNumber(normalized)
  if (localMatch) {
    applyFowlerPartMatch(localMatch)
    return
  }

  const requestId = ++fowlerLookupRequestId
  try {
    const response = await partsApi.checkFowler(normalized)
    if (requestId !== fowlerLookupRequestId) return
    if (normalizeFowlerPartNumber(fowlerPnSearch.value) !== normalized) return

    if (response?.data?.exists && response?.data?.part) {
      applyFowlerPartMatch(response.data.part)
      return
    }

    fowlerMatchedPart.value = null
    selectedFowlerPn.value = false
  } catch (error) {
    fowlerMatchedPart.value = null
    selectedFowlerPn.value = false
  }
}

const scheduleFowlerLookup = (fowlerPn) => {
  if (fowlerLookupTimeout) {
    clearTimeout(fowlerLookupTimeout)
  }
  fowlerLookupTimeout = setTimeout(() => {
    lookupFowlerPartNumber(fowlerPn)
  }, 180)
}

const onFowlerPnInput = () => {
  const normalized = normalizeFowlerPartNumber(fowlerPnSearch.value)
  fowlerPnSearch.value = normalized
  form.value.fowler_part_number = normalized
  fowlerMatchedPart.value = null
  selectedFowlerPn.value = false
  if (normalized) {
    showFowlerPnDropdown.value = true
  } else {
    showFowlerPnDropdown.value = false
  }
  scheduleFowlerLookup(normalized)
}

const hideFowlerPnDropdown = () => {
  setTimeout(() => {
    showFowlerPnDropdown.value = false
  }, 200)
}

const selectFowlerPn = (fpn) => {
  const normalized = normalizeFowlerPartNumber(fpn)
  fowlerPnSearch.value = normalized
  form.value.fowler_part_number = normalized
  const matched = findPartByFowlerPartNumber(normalized)
  if (matched) {
    applyFowlerPartMatch(matched)
  } else {
    selectedFowlerPn.value = true
  }
  showFowlerPnDropdown.value = false
}

onMounted(() => {
  fetchParts()
  fetchSuppliers()
})

watch(paginatedParts, (list) => {
  list.forEach((part) => {
    ensureQr('supplier', part.supplier_part_number)
    ensureQr('fowler', part.fowler_part_number)
  })
}, { immediate: true })
</script>

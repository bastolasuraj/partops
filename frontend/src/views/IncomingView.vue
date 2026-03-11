<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8 slide-up">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Incoming Assets</h1>
          <p class="text-gray-600">Record incoming stock</p>
        </div>
      </div>
    </div>

    <!-- Incoming Card -->
    <div class="card slide-up overflow-visible">
      <div class="card-header flex-col md:flex-row items-start md:items-center gap-4">
        <h2 class="text-xl font-semibold text-gray-800">Process Incoming Stock</h2>
        <div class="flex p-1 bg-gray-100 rounded-lg">
          <button 
            v-for="type in incomingTypes" 
            :key="type.val"
            @click="changeType(type.val)"
            class="px-4 py-1.5 rounded-md text-xs font-medium transition-all"
            :class="form.type === type.val ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
          >
            {{ type.label }}
          </button>
        </div>
      </div>
      
      <div class="card-body">
        <!-- New Stock from Vendor -->
        <form v-if="form.type === 'new'" @submit.prevent="processIncoming" class="space-y-6">
          <!-- Part Search Dropdown -->
          <div class="form-group relative">
            <label class="form-label">Select Part *</label>
            <input
              v-model="partSearch"
              @input="onPartInput"
              @focus="showPartDropdown = true"
              @blur="handlePartBlur"
              type="text"
              class="form-input"
              :class="{ 
                'border-red-300 focus:border-red-500 focus:ring-red-500': partValidationMessage,
                'border-green-300 focus:border-green-500 focus:ring-green-500': selectedPart && !partValidationMessage
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
                    <div class="flex items-center gap-2 mb-1">
                      <span class="font-bold text-blue-600">{{ p.fowler_part_number }}</span>
                      <span class="text-gray-400">→</span>
                      <span class="font-mono text-sm font-semibold text-gray-900">{{ p.supplier_part_number || 'N/A' }}</span>
                    </div>
                    <div class="text-sm text-gray-700 mb-1">{{ p.name }}</div>
                    <div class="text-xs text-gray-500">
                      <span class="font-medium">{{ p.supplier_name || 'No Supplier' }}</span>
                      <span class="mx-1">|</span>
                      <span>Unit: {{ normalizeUnitOfMeasure(p.unit_of_measure) }}</span>
                    </div>
                  </div>
                  <div class="ml-3 text-right">
                    <span 
                      class="px-2 py-1 text-xs font-medium rounded block mb-1"
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
              
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="bg-white rounded-lg p-3 border border-blue-100">
                  <div class="text-xs text-gray-500 uppercase font-medium mb-1">Supplier</div>
                  <div class="font-semibold text-gray-900">{{ selectedPart.supplier_name || 'N/A' }}</div>
                  <div class="text-xs text-gray-600 mt-1">{{ selectedPart.supplier_part_number || 'N/A' }}</div>
                </div>
                <div class="bg-white rounded-lg p-3 border border-blue-100">
                  <div class="text-xs text-gray-500 uppercase font-medium mb-1">Unit</div>
                  <div class="font-semibold text-gray-900">{{ selectedPartUnit }}</div>
                </div>
                <div class="bg-white rounded-lg p-3 border border-blue-100">
                  <div class="text-xs text-gray-500 uppercase font-medium mb-1">Current Stock</div>
                  <div class="text-2xl font-bold" :class="(selectedPart.stock || 0) > 0 ? 'text-green-600' : 'text-gray-400'">
                    {{ selectedPart.stock || 0 }}
                  </div>
                </div>
              </div>

              <div v-if="loadingPartLocations" class="mt-3 text-xs text-gray-500">
                Loading current locations...
              </div>
              <div v-else-if="partLocationOptions.length > 0" class="mt-3 text-xs text-gray-600">
                Current locations:
                <span class="font-medium">{{ partLocationOptions.map(loc => `${loc.location_display} (${loc.quantity})`).join(', ') }}</span>
              </div>
               

            </div>

            <div v-if="partValidationMessage" class="mt-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {{ partValidationMessage }}
            </div>
          </div>

          <div class="form-group relative">
            <label class="form-label">Vendor</label>
            <input
              v-model="vendorSearch"
              @input="onVendorInput"
              @focus="showVendorDropdown = true"
              @blur="handleVendorBlur"
              type="text"
              class="form-input"
              :class="{ 
                'border-red-300 focus:border-red-500 focus:ring-red-500': vendorValidationMessage,
                'border-green-300 focus:border-green-500 focus:ring-green-500': selectedVendor && !vendorValidationMessage
              }"
              placeholder="Type to search or create vendor..."
            />
            
            <!-- Dropdown for vendors -->
            <div 
              v-if="showVendorDropdown && filteredVendors.length > 0"
              class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
            >
              <button
                v-for="vendor in filteredVendors"
                :key="vendor.id"
                @mousedown.prevent="selectVendor(vendor)"
                type="button"
                class="w-full px-4 py-3 text-left hover:bg-blue-50 transition-colors border-b border-gray-100 last:border-b-0"
              >
                <div class="font-medium text-gray-900">{{ vendor.name }}</div>
              </button>
            </div>

            <div v-if="allowUntrackedReturns && showCreateVendorAction" class="mt-2 text-sm text-gray-600">
              <button type="button" class="text-blue-600 hover:text-blue-700 font-medium" @click="promptCreateVendor">
                Create new vendor "{{ vendorSearchTerm }}"
              </button>
            </div>
            
            <!-- Selected vendor display -->
            <div v-if="selectedVendor && !showVendorDropdown" class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
              <div class="flex items-center justify-between">
                <div>
                  <div class="font-medium text-blue-900">{{ selectedVendor.name }}</div>
                </div>
                <button
                  @click="clearVendor"
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

            <div v-if="vendorValidationMessage" class="mt-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {{ vendorValidationMessage }}
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="form-group">
              <label class="form-label">Quantity Received *</label>
              <input type="number" v-model="form.qty" class="form-input" min="1" required>
              <div class="text-xs text-gray-500 mt-1">
                Unit: {{ selectedPartUnit }}
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Unit Price *</label>
              <input type="number" step="0.01" min="0.01" v-model="form.price" class="form-input" placeholder="0.00" required>
            </div>

            <div class="form-group md:col-span-2">
              <label class="form-label">Storage Location</label>
              <input
                type="text"
                v-model="form.locationRaw"
                class="form-input"
                list="incoming-location-options"
                placeholder="A1-02-03 or back shelf"
              >
              <datalist id="incoming-location-options">
                <option v-for="loc in partLocationOptions" :key="loc.location_key" :value="loc.location_display"></option>
              </datalist>
              <div class="text-xs text-gray-500 mt-1">
                Leave as-is to use the part default location. This controls location-level stock.
              </div>
            </div>
          </div>
          
          <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
            <label class="flex items-center gap-2 cursor-pointer mb-4">
              <input type="checkbox" v-model="form.hasCore" class="rounded text-blue-600 focus:ring-blue-500 w-5 h-5">
              <span class="font-semibold text-gray-700">This item has a Core Charge</span>
            </label>
            <div v-if="form.hasCore" class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="form-label">Core Cost Paid</label>
                  <input type="number" step="0.01" v-model="form.coreCost" class="form-input" placeholder="0.00">
                  <p class="text-xs text-gray-500 mt-1">Amount paid for core deposit</p>
                </div>
                <div>
                  <label class="form-label">Expected Rebate</label>
                  <input type="number" step="0.01" v-model="form.coreRebateExpected" class="form-input" placeholder="0.00">
                  <p class="text-xs text-gray-500 mt-1">Expected return when core is returned</p>
                </div>
              </div>
            </div>
          </div>

          <div class="pt-4 border-t border-gray-100 flex justify-end">
            <button type="submit" :disabled="processing || !canSubmitIncoming" :class="incomingSubmitButtonClass">
              <span v-if="processing" class="spinner w-4 h-4"></span>
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
              </svg>
              Process Incoming Stock
            </button>
          </div>
        </form>

        <!-- Returns from WO/Unit/Tech -->
        <div v-else>
          <!-- Reference Selection -->
          <div class="mb-6 relative">
            <label class="form-label">
              Select {{ form.type === 'wo' ? 'Work Order' : form.type === 'unit' ? 'Unit' : 'Technician' }}
            </label>
            <input
              v-model="referenceSearch"
              @input="onReferenceInput"
              @focus="showReferenceDropdown = true"
              @blur="handleReferenceBlur"
              type="text"
              class="form-input"
              :class="{ 
                'border-red-300 focus:border-red-500 focus:ring-red-500': referenceValidationMessage,
                'border-green-300 focus:border-green-500 focus:ring-green-500': selectedReference && !referenceValidationMessage
              }"
              :placeholder="`Type to search ${form.type === 'wo' ? 'work orders' : form.type === 'unit' ? 'units' : 'technicians'}...`"
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
                <div class="font-medium text-gray-900">
                  {{ form.type === 'wo' ? ref.wo_number : ref.name }}
                </div>
                <div v-if="form.type === 'wo'" class="text-xs text-gray-500">
                  Items: {{ ref.item_count }}
                </div>
              </button>
            </div>

            <div v-if="allowUntrackedReturns && showCreateReferenceAction" class="mt-2 text-sm text-gray-600">
              <button type="button" class="text-blue-600 hover:text-blue-700 font-medium" @click="promptCreateReference">
                Create new {{ referenceTypeLabel }} "{{ referenceSearchTerm }}"
              </button>
            </div>
            
            <!-- Selected reference display -->
            <div v-if="selectedReference && !showReferenceDropdown" class="mt-3 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg">
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <div class="text-sm font-medium text-gray-500 mb-1">
                    Selected {{ form.type === 'wo' ? 'Work Order' : form.type === 'unit' ? 'Unit' : 'Technician' }}
                  </div>
                  <div class="font-bold text-lg text-gray-900">
                    {{ selectedReferenceDisplay }}
                  </div>
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

            <div v-if="referenceValidationMessage" class="mt-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {{ referenceValidationMessage }}
            </div>
          </div>

          <div v-if="allowUntrackedReturns" class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" v-model="manualReturnMode" class="rounded text-yellow-600 focus:ring-yellow-500 w-5 h-5">
              <span class="font-semibold text-yellow-800">Manual return mode</span>
            </label>
          </div>

          <!-- Loading State -->
          <div v-if="loadingItems && !manualReturnMode" class="p-8 text-center text-gray-500">
            <div class="spinner w-8 h-8 mx-auto mb-2"></div>
            <p>Loading returnable items...</p>
          </div>

          <!-- No Items -->
          <div v-else-if="!selectedReference && !manualReturnMode" class="p-8 border-2 border-dashed border-gray-200 rounded-lg text-center text-gray-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-8 w-8 mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
              <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
            </svg>
            <p>Select a {{ form.type === 'wo' ? 'work order' : form.type === 'unit' ? 'unit' : 'technician' }} to see returnable items.</p>
          </div>

          <!-- No Returnable Items -->
          <div v-else-if="returnableItems.length === 0 && !manualReturnMode" class="p-8 border-2 border-dashed border-gray-200 rounded-lg text-center text-gray-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-8 w-8 mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <p>No returnable items found for this {{ form.type === 'wo' ? 'work order' : form.type === 'unit' ? 'unit' : 'technician' }}.</p>
          </div>

          <!-- Returnable Items List -->
          <div v-else-if="!manualReturnMode">
            <div class="mb-4 flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-800">Returnable Items</h3>
              <button 
                @click="selectAllItems" 
                class="text-sm text-blue-600 hover:text-blue-700 font-medium"
              >
                {{ allSelected ? 'Deselect All' : 'Select All' }}
              </button>
            </div>

            <div class="space-y-3 mb-6">
              <div 
                v-for="item in returnableItems" 
                :key="item.part_id"
                class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors"
                :class="{
                  'bg-blue-50 border-blue-300': item.returnQty > 0 && !item.validationMessage,
                  'bg-red-50 border-red-300': item.validationMessage
                }"
              >
                <div class="flex items-start justify-between gap-4">
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                      <span class="font-semibold text-gray-900">{{ item.fowler_part_number }}</span>
                      <span class="text-gray-600">{{ item.part_name }}</span>
                    </div>
                    <div class="text-sm text-gray-500">
                      Taken: <span class="font-medium">{{ item.total_taken }}</span> | 
                      Returned: <span class="font-medium">{{ item.total_returned }}</span> | 
                      Available: <span class="font-medium text-green-600">{{ item.available_to_return }}</span>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <input 
                      type="number" 
                      v-model.number="item.returnQty"
                      @input="validateReturnableItem(item)"
                      @blur="validateReturnableItem(item, true)"
                      :max="item.available_to_return"
                      min="0"
                      class="form-input w-24 text-center"
                      :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': item.validationMessage }"
                      placeholder="0"
                    >
                    <span class="text-gray-500 text-sm">qty</span>
                  </div>
                </div>
                <div v-if="item.validationMessage" class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                  {{ item.validationMessage }}
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 border-t border-gray-100 flex justify-between items-center">
              <div class="text-sm text-gray-600">
                <span class="font-medium">{{ selectedItemsCount }}</span> item(s) selected for return
              </div>
              <button 
                @click="processMultipleReturns" 
                :disabled="processing || !canProcessSelectedReturns"
                :class="processReturnsButtonClass"
              >
                <span v-if="processing" class="spinner w-4 h-4"></span>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                  <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                Process Returns
              </button>
            </div>
          </div>

          <!-- Manual Return Items -->
          <div v-else class="space-y-4">
            <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
              <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-yellow-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="9" cy="21" r="1"></circle>
                  <circle cx="20" cy="21" r="1"></circle>
                  <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                Manual Return List
              </h3>
              <div class="space-y-3">
                <div 
                  v-for="(item, idx) in manualReturnBasket" 
                  :key="idx" 
                  class="flex flex-col md:flex-row gap-3 items-end bg-white p-3 rounded-lg border border-gray-200 shadow-sm"
                >
                  <div class="flex-1 w-full relative">
                    <label class="text-xs text-gray-500 font-medium mb-1 block">Part</label>
                    <input
                      v-model="item.partSearch"
                      @input="onManualPartInput(idx)"
                      @focus="item.showDropdown = true"
                      @blur="handleManualPartBlur(idx)"
                      type="text"
                      class="form-input"
                      :class="{ 
                        'border-red-300 focus:border-red-500 focus:ring-red-500': item.isDuplicate || item.validationMessage,
                        'border-green-300 focus:border-green-500 focus:ring-green-500': item.partId && !item.isDuplicate && !item.validationMessage
                      }"
                      placeholder="Type to search parts..."
                    />
                    
                    <div 
                      v-if="item.showDropdown && getFilteredPartsForManualItem(idx).length > 0"
                      class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                    >
                      <button
                        v-for="part in getFilteredPartsForManualItem(idx)"
                        :key="part.id"
                        @mousedown.prevent="selectManualPart(idx, part)"
                        type="button"
                        class="w-full px-4 py-3 text-left hover:bg-blue-50 transition-colors border-b border-gray-100 last:border-b-0"
                      >
                        <div class="font-medium text-gray-900">
                          {{ part.fowler_part_number }} - {{ part.name }}
                        </div>
                        <div class="text-xs text-gray-500">
                          {{ part.supplier_part_number || 'N/A' }}
                        </div>
                      </button>
                    </div>

                    <div v-if="item.validationMessage" class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                      {{ item.validationMessage }}
                    </div>
                  </div>
                  
                  <div class="w-full md:w-32">
                    <label class="text-xs text-gray-500 font-medium mb-1 block">Qty</label>
                    <input type="number" v-model.number="item.qty" class="form-input text-center" min="1">
                  </div>
                  
                  <div class="flex items-center gap-2">
                    <button @click="clearManualPart(idx)" type="button" class="btn-outline text-xs">
                      Clear
                    </button>
                    <button @click="removeManualReturnItem(idx)" type="button" class="btn-outline text-xs" :disabled="manualReturnBasket.length === 1">
                      Remove
                    </button>
                  </div>
                </div>
              </div>
              
              <button @click="addManualReturnItem" class="mt-4 btn-outline text-sm w-full md:w-auto">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Another Part
              </button>
            </div>

            <div class="pt-4 border-t border-gray-100 flex justify-end">
              <button 
                @click="processManualReturns"
                :disabled="processing || !canProcessManualReturns"
                :class="manualReturnsButtonClass"
              >
                <span v-if="processing" class="spinner w-4 h-4"></span>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                  <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                Process Manual Returns
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Reference Confirmation Modal -->
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
            Confirm New {{ referenceTypeLabel }}
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

    <!-- Create Reference Modal -->
    <Transition name="fade">
      <div v-if="allowUntrackedReturns && showCreateReferenceModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-xl shadow-2xl flex flex-col max-h-[90vh] slide-up">
          <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-900">
              Create New {{ createReferenceType === 'wo' ? 'Work Order' : (createReferenceType === 'unit' ? 'Unit' : 'Technician') }}
            </h3>
            <button @click="closeCreateReferenceModal" class="text-gray-400 hover:text-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          
          <div class="p-6 overflow-y-auto space-y-4">
            <div v-if="createReferenceType === 'wo'">
              <div class="form-group">
                <label class="form-label">Work Order Number *</label>
                <input v-model="newWorkOrderForm.wo_number" class="form-input" required>
              </div>
            </div>

            <div v-else-if="createReferenceType === 'unit'">
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
                  Leave blank to auto-assign a random temporary 10-digit employee number.
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

    <!-- Create Vendor Confirmation Modal -->
    <div v-if="showCreateVendorConfirmation" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 text-center">
        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-yellow-100 inline-flex items-center justify-center mx-auto mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-yellow-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
          </svg>
        </div>
        <div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Confirm New Vendor</h3>
          <p class="text-gray-600 mb-4">
            Are you sure this is correct?
            <br>
            <strong class="text-gray-800">{{ pendingVendorName }}</strong>
            <br>
            Check for typo before you proceed.
          </p>
          <div class="flex gap-3 justify-center">
            <button
              @click="cancelCreateVendor"
              class="btn-secondary"
            >
              Cancel
            </button>
            <button
              @click="confirmCreateVendor"
              class="btn-primary"
            >
              Yes, Create
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Vendor Modal -->
    <Transition name="fade">
      <div v-if="showVendorModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-xl shadow-2xl flex flex-col max-h-[90vh] slide-up">
          <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-900">Create New Vendor</h3>
            <button @click="closeVendorModal" class="text-gray-400 hover:text-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          
          <div class="p-6 overflow-y-auto space-y-4">
            <div class="form-group">
              <label class="form-label">Name *</label>
              <input v-model="vendorForm.name" class="form-input" required>
            </div>
            <div class="form-group">
              <label class="form-label">Contact Person</label>
              <input v-model="vendorForm.contact" class="form-input">
            </div>
            <div class="form-group">
              <label class="form-label">Phone</label>
              <input v-model="vendorForm.phone" class="form-input">
            </div>
            <div class="form-group">
              <label class="form-label">Email</label>
              <input v-model="vendorForm.email" type="email" class="form-input">
            </div>
            <div class="form-group">
              <label class="form-label">Website URL</label>
              <input v-model="vendorForm.url" type="url" class="form-input" placeholder="https://example.com">
            </div>
            <div class="form-group">
              <label class="form-label">Address</label>
              <textarea v-model="vendorForm.address" class="form-input" rows="2"></textarea>
            </div>
          </div>

          <div class="p-6 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 rounded-b-xl">
            <button @click="closeVendorModal" class="btn-outline">Cancel</button>
            <button @click="saveVendor" :disabled="savingVendor" class="btn-primary">
              <span v-if="savingVendor" class="spinner w-4 h-4"></span>
              Create
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { partsApi, workOrdersApi, unitsApi, techniciansApi, assetApi, suppliersApi } from '@/services/api'
import { useToast } from '@/composables/useToast'
import { useSettings } from '@/composables/useSettings'

const route = useRoute()
const { showToast } = useToast()
const { allowUntrackedReturns } = useSettings()

const parts = ref([])
const workOrders = ref([])
const units = ref([])
const technicians = ref([])
const suppliers = ref([])
const processing = ref(false)
const loadingItems = ref(false)
const returnableItems = ref([])
const selectedReference = ref('')
const manualReturnMode = ref(false)
const createManualReturnItem = () => ({
  partId: '',
  qty: 1,
  partSearch: '',
  showDropdown: false,
  selectedPart: null,
  isDuplicate: false,
  validationMessage: ''
})
const manualReturnBasket = ref([createManualReturnItem()])

// Part search
const partSearch = ref('')
const selectedPart = ref(null)
const showPartDropdown = ref(false)
const partLocationOptions = ref([])
const loadingPartLocations = ref(false)
const partValidationMessage = ref('')

// Vendor search (for new stock)
const vendorSearch = ref('')
const selectedVendor = ref(null)
const showVendorDropdown = ref(false)
const vendorValidationMessage = ref('')

// Reference search (for WO/Unit/Tech returns)
const referenceSearch = ref('')
const selectedReferenceObj = ref(null)
const showReferenceDropdown = ref(false)
const referenceValidationMessage = ref('')
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
const newWorkOrderForm = ref({
  wo_number: ''
})
const showCreateVendorConfirmation = ref(false)
const pendingVendorName = ref('')
const showVendorModal = ref(false)
const savingVendor = ref(false)
const vendorForm = ref({
  name: '',
  contact: '',
  phone: '',
  email: '',
  url: '',
  address: ''
})

const incomingTypes = [
  { val: 'new', label: 'Vendor (New)' },
  { val: 'wo', label: 'WO Return' },
  { val: 'unit', label: 'Unit Return' },
  { val: 'tech', label: 'Tech Return' }
]

const form = ref({
  type: 'new',
  qty: 1,
  price: 0,
  locationRaw: '',
  hasCore: false,
  coreCost: 0,
  coreRebateExpected: 0,
  coreRebateReceived: 0
})

const normalizeUnitOfMeasure = (value) => {
  const unit = String(value ?? '').replace(/\s+/g, ' ').trim().toLowerCase()
  return unit || 'each'
}

const normalizeLookupText = (value) => String(value ?? '').replace(/\s+/g, ' ').trim().toLowerCase()

const selectedPartUnit = computed(() => normalizeUnitOfMeasure(selectedPart.value?.unit_of_measure))
const hasTypedVendorSearch = computed(() => normalizeLookupText(vendorSearch.value) !== '')
const canSubmitIncoming = computed(() =>
  !!selectedPart.value &&
  (!hasTypedVendorSearch.value || !!selectedVendor.value) &&
  Number(form.value.qty) > 0 &&
  Number(form.value.price) > 0
)
const incomingSubmitButtonClass = computed(() =>
  canSubmitIncoming.value
    ? 'btn-primary'
    : 'btn-danger opacity-70 cursor-not-allowed'
)

const formatPartDefaultLocation = (part) => {
  if (!part) return ''
  const aisle = String(part.location_aisle || '').trim()
  const shelf = String(part.location_shelf || '').trim()
  const bay = String(part.location_bay || '').trim()
  const alt = String(part.location_alt || '').trim()
  const rack = [aisle, shelf, bay].filter(Boolean)
  if (rack.length > 0) return rack.join('-')
  return alt
}

const buildPartDisplayText = (part) => `${part.fowler_part_number} - ${part.name}`

const clearPartSelectionState = ({ clearSearch = false, clearVendorSelection = false } = {}) => {
  selectedPart.value = null
  partLocationOptions.value = []
  form.value.locationRaw = ''
  loadingPartLocations.value = false
  if (clearSearch) {
    partSearch.value = ''
  }
  if (clearVendorSelection) {
    clearVendor()
  }
}

const clearVendorSelectionState = ({ clearSearch = false } = {}) => {
  selectedVendor.value = null
  if (clearSearch) {
    vendorSearch.value = ''
  }
}

const loadPartLocations = async (partId) => {
  loadingPartLocations.value = true
  partLocationOptions.value = []

  try {
    const response = await partsApi.getLocations(partId)
    partLocationOptions.value = (response.data?.locations || [])
      .filter(loc => Number(loc.quantity || 0) > 0)
      .map(loc => ({
        location_key: loc.location_key,
        location_display: loc.location_display || 'unassigned',
        quantity: Number(loc.quantity || 0)
      }))
  } catch (error) {
    partLocationOptions.value = []
  } finally {
    loadingPartLocations.value = false
  }
}

// Filter parts based on search
const filteredParts = computed(() => {
  if (!partSearch.value.trim()) {
    return parts.value
  }
  
  const search = partSearch.value.toLowerCase()
  return parts.value.filter(part => 
    part.fowler_part_number.toLowerCase().includes(search) ||
    part.name.toLowerCase().includes(search) ||
    (part.supplier_part_number && part.supplier_part_number.toLowerCase().includes(search)) ||
    (part.supplier_name && part.supplier_name.toLowerCase().includes(search))
  )
})

const manualSelectedPartIds = computed(() => {
  return manualReturnBasket.value
    .map(item => item.partId)
    .filter(id => id !== '' && id !== null)
})

const isManualPartSelected = (partId, currentIndex) => {
  return manualReturnBasket.value.some((item, idx) =>
    idx !== currentIndex && item.partId === partId
  )
}

const getFilteredPartsForManualItem = (currentIndex) => {
  const item = manualReturnBasket.value[currentIndex]
  const searchTerm = (item.partSearch || '').toLowerCase().trim()
  
  let filtered = parts.value.filter(part => {
    const isAlreadySelected = manualReturnBasket.value.some((basketItem, idx) =>
      idx !== currentIndex && basketItem.partId === part.id
    )
    return !isAlreadySelected
  })
  
  if (searchTerm) {
    filtered = filtered.filter(part =>
      part.fowler_part_number.toLowerCase().includes(searchTerm) ||
      part.name.toLowerCase().includes(searchTerm) ||
      (part.supplier_part_number && part.supplier_part_number.toLowerCase().includes(searchTerm))
    )
  }
  
  return filtered
}

const manualValidItems = computed(() => {
  return manualReturnBasket.value.filter(item =>
    item.partId &&
    item.qty > 0 &&
    !item.isDuplicate &&
    !item.validationMessage &&
    item.selectedPart
  )
})

const canProcessManualReturns = computed(() => {
  if (!selectedReference.value) return false
  return manualValidItems.value.length > 0
})
const invalidSelectedReturnItems = computed(() =>
  returnableItems.value.filter(item => item.returnQty > 0 && !!item.validationMessage)
)
const canProcessSelectedReturns = computed(() =>
  !!selectedReference.value &&
  selectedItemsCount.value > 0 &&
  invalidSelectedReturnItems.value.length === 0
)
const processReturnsButtonClass = computed(() =>
  canProcessSelectedReturns.value
    ? 'btn-primary'
    : 'btn-danger opacity-70 cursor-not-allowed'
)
const manualReturnsButtonClass = computed(() =>
  canProcessManualReturns.value
    ? 'btn-primary'
    : 'btn-danger opacity-70 cursor-not-allowed'
)

const filteredVendors = computed(() => {
  if (!vendorSearch.value.trim()) {
    return suppliers.value
  }
  
  const search = vendorSearch.value.toLowerCase().trim()
  return suppliers.value.filter(supplier =>
    supplier.name?.toLowerCase().includes(search)
  )
})

const vendorSearchTerm = computed(() => vendorSearch.value.trim())
const hasExactVendorMatch = computed(() => {
  if (!vendorSearchTerm.value) return false
  return suppliers.value.some(supplier =>
    supplier.name?.toLowerCase().trim() === vendorSearchTerm.value.toLowerCase()
  )
})
const showCreateVendorAction = computed(() =>
  vendorSearchTerm.value && !selectedVendor.value && !hasExactVendorMatch.value
)

const hasReturnableItems = (item) => {
  if (typeof item?.item_count === 'number') return item.item_count > 0
  if (typeof item?.items === 'number') return item.items > 0
  if (typeof item?.returnable_count === 'number') return item.returnable_count > 0
  return true
}

// Filter references based on search and type
const filteredReferences = computed(() => {
  const search = referenceSearch.value.toLowerCase().trim()
  
  let items = []
  if (form.value.type === 'wo') {
    items = workOrders.value
  } else if (form.value.type === 'unit') {
    items = units.value
  } else if (form.value.type === 'tech') {
    items = technicians.value
  }

  if (!allowUntrackedReturns.value) {
    items = items.filter(hasReturnableItems)
  }
  
  if (!search) {
    return items
  }
  
  return items.filter(item => {
    if (form.value.type === 'wo') {
      return item.wo_number.toLowerCase().includes(search)
    } else {
      return item.name.toLowerCase().includes(search)
    }
  })
})

const referenceSearchTerm = computed(() => referenceSearch.value.trim())
const hasExactReferenceMatch = computed(() => {
  if (!referenceSearchTerm.value) return false
  
  if (form.value.type === 'wo') {
    return workOrders.value.some(wo =>
      wo.wo_number?.toLowerCase().trim() === referenceSearchTerm.value.toLowerCase()
    )
  }
  
  const list = form.value.type === 'unit' ? units.value : technicians.value
  return list.some(item =>
    item.name?.toLowerCase().trim() === referenceSearchTerm.value.toLowerCase()
  )
})

const showCreateReferenceAction = computed(() =>
  referenceSearchTerm.value && !selectedReferenceObj.value && !hasExactReferenceMatch.value
)

const referenceTypeLabel = computed(() => {
  if (form.value.type === 'wo') return 'Work Order'
  if (form.value.type === 'unit') return 'Unit'
  return 'Technician'
})

const buildReferenceValidationMessage = () => {
  const label = referenceTypeLabel.value
  const lowerLabel = label.toLowerCase()

  if (hasExactReferenceMatch.value) {
    return `Select the correct ${lowerLabel} from the list.`
  }

  return allowUntrackedReturns.value
    ? `${label} is not registered. Select an existing ${lowerLabel} or create it first.`
    : `${label} is not registered. Select an existing ${lowerLabel}.`
}

const getReturnableItemValidationMessage = (item) => {
  const qty = Number(item?.returnQty || 0)
  const available = Number(item?.available_to_return || 0)

  if (qty <= 0) {
    return ''
  }

  if (qty > available) {
    return `Return quantity cannot exceed available quantity (${available}).`
  }

  return ''
}

const validateReturnableItem = (item, notify = false) => {
  if (!item) return

  item.validationMessage = getReturnableItemValidationMessage(item)
  if (notify && item.validationMessage) {
    showToast('Error', item.validationMessage, 'error')
  }
}

// Get display text for selected reference
const selectedReferenceDisplay = computed(() => {
  if (!selectedReferenceObj.value) return ''
  
  if (form.value.type === 'wo') {
    return selectedReferenceObj.value.wo_number
  } else {
    return selectedReferenceObj.value.name
  }
})

const selectedItemsCount = computed(() => {
  return returnableItems.value.filter(item => item.returnQty > 0).length
})

const allSelected = computed(() => {
  if (returnableItems.value.length === 0) return false
  return returnableItems.value.every(item => item.returnQty === item.available_to_return)
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

const resetNewWorkOrderForm = () => {
  newWorkOrderForm.value = {
    wo_number: ''
  }
}

const resetVendorForm = () => {
  vendorForm.value = {
    name: '',
    contact: '',
    phone: '',
    email: '',
    url: '',
    address: ''
  }
}

const changeType = (type) => {
  form.value.type = type
  referenceValidationMessage.value = ''
  if (type !== 'new') {
    form.value.locationRaw = ''
  } else if (selectedPart.value) {
    form.value.locationRaw = formatPartDefaultLocation(selectedPart.value)
  }
  selectedReference.value = ''
  selectedReferenceObj.value = null
  referenceSearch.value = ''
  returnableItems.value = []
  manualReturnMode.value = false
  manualReturnBasket.value = [createManualReturnItem()]
}

const selectAllItems = () => {
  if (allSelected.value) {
    // Deselect all
    returnableItems.value.forEach(item => {
      item.returnQty = 0
      item.validationMessage = ''
    })
  } else {
    // Select all with max available
    returnableItems.value.forEach(item => {
      item.returnQty = item.available_to_return
      item.validationMessage = ''
    })
  }
}

const onPartInput = () => {
  const typed = normalizeLookupText(partSearch.value)
  partValidationMessage.value = ''

  if (selectedPart.value && typed !== normalizeLookupText(buildPartDisplayText(selectedPart.value))) {
    clearPartSelectionState()
  }

  showPartDropdown.value = true
}

const findExactPartMatch = (value) => {
  const search = normalizeLookupText(value)
  if (!search) return null

  const exactIdentifierMatch = parts.value.find(part =>
    [
      part.fowler_part_number,
      part.supplier_part_number,
      buildPartDisplayText(part)
    ].some(candidate => normalizeLookupText(candidate) === search)
  )

  if (exactIdentifierMatch) {
    return exactIdentifierMatch
  }

  const exactNameMatches = parts.value.filter(part =>
    normalizeLookupText(part.name) === search
  )

  return exactNameMatches.length === 1 ? exactNameMatches[0] : null
}

const handlePartBlur = () => {
  setTimeout(() => {
    showPartDropdown.value = false

    const typed = normalizeLookupText(partSearch.value)
    if (!typed) {
      partValidationMessage.value = ''
      clearPartSelectionState()
      return
    }

    if (selectedPart.value) {
      partValidationMessage.value = ''
      return
    }

    const match = findExactPartMatch(partSearch.value)
    if (match) {
      selectPart(match)
      return
    }

    clearPartSelectionState()
    partValidationMessage.value = 'Item is not registered in Parts Master.'
    showToast('Error', partValidationMessage.value, 'error')
  }, 200)
}

const selectPart = (part) => {
  selectedPart.value = part
  partSearch.value = buildPartDisplayText(part)
  partValidationMessage.value = ''
  showPartDropdown.value = false
  form.value.locationRaw = formatPartDefaultLocation(part)
  loadPartLocations(part.id)
  syncVendorToPart(part)
}

const clearPart = () => {
  partValidationMessage.value = ''
  clearPartSelectionState({ clearSearch: true, clearVendorSelection: true })
}

const onManualPartInput = (idx) => {
  const item = manualReturnBasket.value[idx]
  const typed = normalizeLookupText(item.partSearch)
  item.validationMessage = ''

  if (item.selectedPart && typed !== normalizeLookupText(buildPartDisplayText(item.selectedPart))) {
    item.partId = ''
    item.selectedPart = null
    item.isDuplicate = false
  }

  item.showDropdown = true
}

const handleManualPartBlur = (idx) => {
  setTimeout(() => {
    const item = manualReturnBasket.value[idx]
    if (!item) return

    item.showDropdown = false

    const typed = normalizeLookupText(item.partSearch)
    if (!typed) {
      item.validationMessage = ''
      item.partId = ''
      item.selectedPart = null
      item.isDuplicate = false
      return
    }

    if (item.selectedPart) {
      item.validationMessage = ''
      return
    }

    const match = findExactPartMatch(item.partSearch)
    if (match) {
      selectManualPart(idx, match)
      return
    }

    item.partId = ''
    item.selectedPart = null
    item.isDuplicate = false
    item.validationMessage = 'Item is not registered in Parts Master.'
    showToast('Error', item.validationMessage, 'error')
  }, 200)
}

const selectManualPart = (idx, part) => {
  const item = manualReturnBasket.value[idx]
  item.partId = part.id
  item.selectedPart = part
  item.partSearch = buildPartDisplayText(part)
  item.showDropdown = false
  item.validationMessage = ''
  item.isDuplicate = isManualPartSelected(part.id, idx)
  
  if (item.isDuplicate) {
    showToast('Warning', 'This part is already in the list', 'warning')
  }
}

const clearManualPart = (idx) => {
  manualReturnBasket.value[idx] = createManualReturnItem()
}

const addManualReturnItem = () => {
  manualReturnBasket.value.push(createManualReturnItem())
}

const removeManualReturnItem = (idx) => {
  if (manualReturnBasket.value.length > 1) {
    manualReturnBasket.value.splice(idx, 1)
  }
}

const onVendorInput = () => {
  const typed = normalizeLookupText(vendorSearch.value)
  vendorValidationMessage.value = ''

  if (selectedVendor.value && typed !== normalizeLookupText(selectedVendor.value.name)) {
    clearVendorSelectionState()
  }

  showVendorDropdown.value = true
}

const findExactVendorMatch = (value) => {
  const search = normalizeLookupText(value)
  if (!search) return null

  return suppliers.value.find(supplier =>
    normalizeLookupText(supplier.name) === search
  ) || null
}

const handleVendorBlur = () => {
  setTimeout(() => {
    showVendorDropdown.value = false

    const typed = normalizeLookupText(vendorSearch.value)
    if (!typed) {
      vendorValidationMessage.value = ''
      clearVendorSelectionState()
      return
    }

    if (selectedVendor.value) {
      vendorValidationMessage.value = ''
      return
    }

    const match = findExactVendorMatch(vendorSearch.value)
    if (match) {
      selectVendor(match)
      return
    }

    clearVendorSelectionState()
    vendorValidationMessage.value = 'Vendor is not registered. Select an existing vendor or create it first.'
    showToast('Error', vendorValidationMessage.value, 'error')
  }, 200)
}

const selectVendor = (vendor) => {
  selectedVendor.value = vendor
  vendorSearch.value = vendor.name
  vendorValidationMessage.value = ''
  showVendorDropdown.value = false
}

const clearVendor = () => {
  vendorValidationMessage.value = ''
  clearVendorSelectionState({ clearSearch: true })
}

const syncVendorToPart = (part) => {
  if (!part?.supplier_name && !part?.supplier_id) return
  
  const matchById = suppliers.value.find(supplier => String(supplier.id) === String(part.supplier_id))
  const matchByName = suppliers.value.find(supplier =>
    supplier.name?.toLowerCase().trim() === (part.supplier_name || '').toLowerCase().trim()
  )
  
  const match = matchById || matchByName
  if (match) {
    selectVendor(match)
  } else if (part.supplier_name) {
    selectedVendor.value = null
    vendorSearch.value = part.supplier_name
    vendorValidationMessage.value = 'Vendor is not registered. Select an existing vendor or create it first.'
  }
}

const clearReferenceSelectionState = ({ clearSearch = false, clearItems = true } = {}) => {
  selectedReferenceObj.value = null
  selectedReference.value = ''
  if (clearSearch) {
    referenceSearch.value = ''
  }
  if (clearItems) {
    returnableItems.value = []
  }
}

const onReferenceInput = () => {
  const typed = normalizeLookupText(referenceSearch.value)
  referenceValidationMessage.value = ''

  if (selectedReferenceObj.value) {
    const selectedLabel = form.value.type === 'wo'
      ? selectedReferenceObj.value.wo_number
      : selectedReferenceObj.value.name

    if (typed !== normalizeLookupText(selectedLabel)) {
      clearReferenceSelectionState()
    }
  }

  showReferenceDropdown.value = true
}

const findExactReferenceMatch = (value) => {
  const search = normalizeLookupText(value)
  if (!search) return null

  if (form.value.type === 'wo') {
    return workOrders.value.find(wo =>
      normalizeLookupText(wo.wo_number) === search
    ) || null
  }

  const list = form.value.type === 'unit' ? units.value : technicians.value
  const matches = list.filter(item =>
    normalizeLookupText(item.name) === search
  )

  return matches.length === 1 ? matches[0] : null
}

const handleReferenceBlur = () => {
  setTimeout(() => {
    showReferenceDropdown.value = false

    const typed = normalizeLookupText(referenceSearch.value)
    if (!typed) {
      referenceValidationMessage.value = ''
      clearReferenceSelectionState()
      return
    }

    if (selectedReferenceObj.value) {
      referenceValidationMessage.value = ''
      return
    }

    const match = findExactReferenceMatch(referenceSearch.value)
    if (match) {
      selectReference(match)
      return
    }

    clearReferenceSelectionState()
    referenceValidationMessage.value = buildReferenceValidationMessage()
    showToast('Error', referenceValidationMessage.value, 'error')
  }, 200)
}

const selectReference = (ref) => {
  selectedReferenceObj.value = ref
  selectedReference.value = ref.id
  referenceValidationMessage.value = ''
  
  if (form.value.type === 'wo') {
    referenceSearch.value = ref.wo_number
  } else {
    referenceSearch.value = ref.name
  }
  
  showReferenceDropdown.value = false
  loadReturnableItems()
}

const clearReference = () => {
  referenceValidationMessage.value = ''
  clearReferenceSelectionState({ clearSearch: true })
}

const promptCreateReference = () => {
  pendingReferenceName.value = referenceSearchTerm.value
  pendingReferenceType.value = form.value.type
  showCreateReferenceConfirmation.value = true
}

const cancelCreateReference = () => {
  showCreateReferenceConfirmation.value = false
  pendingReferenceName.value = ''
  pendingReferenceType.value = ''
}

const openCreateReferenceModal = () => {
  createReferenceType.value = pendingReferenceType.value
  showCreateReferenceModal.value = true

  if (createReferenceType.value === 'wo') {
    resetNewWorkOrderForm()
    newWorkOrderForm.value.wo_number = pendingReferenceName.value
  } else if (createReferenceType.value === 'unit') {
    resetNewUnitForm()
    newUnitForm.value.name = pendingReferenceName.value
  } else {
    resetNewTechForm()
    newTechForm.value.name = pendingReferenceName.value
  }

  pendingReferenceName.value = ''
  pendingReferenceType.value = ''
}

const confirmCreateReference = () => {
  showCreateReferenceConfirmation.value = false
  openCreateReferenceModal()
}

const closeCreateReferenceModal = () => {
  showCreateReferenceModal.value = false
  createReferenceType.value = ''
  resetNewUnitForm()
  resetNewTechForm()
  resetNewWorkOrderForm()
}

const saveReference = async () => {
  if (createReferenceType.value === 'wo' && !newWorkOrderForm.value.wo_number) {
    showToast('Error', 'Work order number is required', 'error')
    return
  }
  if (createReferenceType.value === 'unit' && !newUnitForm.value.name) {
    showToast('Error', 'Unit ID is required', 'error')
    return
  }
  if (createReferenceType.value === 'tech' && !newTechForm.value.name) {
    showToast('Error', 'Technician name is required', 'error')
    return
  }

  try {
    savingReference.value = true
    if (createReferenceType.value === 'wo') {
      const response = await workOrdersApi.create({ wo_number: newWorkOrderForm.value.wo_number })
      workOrders.value.push(response.data)
      selectReference(response.data)
      showToast('Success', `Work order ${newWorkOrderForm.value.wo_number} created`)
    } else if (createReferenceType.value === 'unit') {
      const response = await unitsApi.create(newUnitForm.value)
      units.value.push(response.data)
      selectReference(response.data)
      showToast('Success', 'Unit created successfully')
    } else {
      const response = await techniciansApi.create(newTechForm.value)
      technicians.value.push(response.data)
      selectReference(response.data)
      showToast('Success', 'Technician created successfully')
    }
    
    closeCreateReferenceModal()
  } catch (error) {
    showToast('Error', error.response?.data?.message || 'Failed to create reference', 'error')
  } finally {
    savingReference.value = false
  }
}

const promptCreateVendor = () => {
  pendingVendorName.value = vendorSearchTerm.value
  showCreateVendorConfirmation.value = true
}

const cancelCreateVendor = () => {
  showCreateVendorConfirmation.value = false
  pendingVendorName.value = ''
}

const openVendorModal = () => {
  resetVendorForm()
  vendorForm.value.name = pendingVendorName.value
  showVendorModal.value = true
  pendingVendorName.value = ''
}

const confirmCreateVendor = () => {
  showCreateVendorConfirmation.value = false
  openVendorModal()
}

const closeVendorModal = () => {
  showVendorModal.value = false
  resetVendorForm()
}

const saveVendor = async () => {
  if (!vendorForm.value.name) {
    showToast('Error', 'Name is required', 'error')
    return
  }
  
  try {
    savingVendor.value = true
    const response = await suppliersApi.create(vendorForm.value)
    suppliers.value.push(response.data)
    selectVendor(response.data)
    showToast('Success', 'Vendor created successfully')
    closeVendorModal()
  } catch (error) {
    showToast('Error', error.response?.data?.message || 'Failed to create vendor', 'error')
  } finally {
    savingVendor.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

const fetchData = async () => {
  try {
    const [partsRes, woRes, unitsRes, techsRes, suppliersRes] = await Promise.all([
      partsApi.getAll(),
      workOrdersApi.getAll(),
      unitsApi.getAll(),
      techniciansApi.getAll(),
      suppliersApi.getAll()
    ])
    
    parts.value = partsRes.data || []
    workOrders.value = woRes.data || []
    units.value = unitsRes.data || []
    technicians.value = techsRes.data || []
    suppliers.value = suppliersRes.data || []

    // Check for pre-selected part from query
    if (route.query.partId) {
      const part = parts.value.find(p => p.id === parseInt(route.query.partId))
      if (part) {
        // Switch to 'new' tab if not already there
        form.value.type = 'new'
        // Select the part
        selectPart(part)
      }
    }
  } catch (error) {
    showToast('Error', 'Failed to load data', 'error')
  }
}

const loadReturnableItems = async () => {
  if (!selectedReference.value) {
    returnableItems.value = []
    return
  }

  try {
    loadingItems.value = true
    // Convert form type to proper reference_type
    let refType = form.value.type
    if (refType === 'wo') refType = 'work_order'
    else if (refType === 'tech') refType = 'technician'
    else if (refType === 'unit') refType = 'unit'
    
    const response = await assetApi.getReturnableItems(refType, selectedReference.value)
    
    // Add returnQty property to each item
    returnableItems.value = (response.data || []).map(item => ({
      ...item,
      returnQty: 0,
      validationMessage: ''
    }))
  } catch (error) {
    showToast('Error', 'Failed to load returnable items', 'error')
    returnableItems.value = []
  } finally {
    loadingItems.value = false
  }
}

const processIncoming = async () => {
  if (!selectedPart.value) {
    showToast('Error', partValidationMessage.value || 'Please select a registered part from Parts Master', 'error')
    return
  }

  if (hasTypedVendorSearch.value && !selectedVendor.value) {
    showToast('Error', vendorValidationMessage.value || 'Vendor is not registered. Select an existing vendor or create it first.', 'error')
    return
  }
  
  if (!form.value.qty || form.value.qty < 1) {
    showToast('Error', 'Quantity must be at least 1', 'error')
    return
  }

  if (form.value.type === 'new' && (!form.value.price || form.value.price <= 0)) {
    showToast('Error', 'Unit price is required for vendor incoming', 'error')
    return
  }
  
  try {
    processing.value = true
    const locationRaw = (form.value.locationRaw || '').trim() || formatPartDefaultLocation(selectedPart.value)
    
    await assetApi.processIncoming({
      part_id: parseInt(selectedPart.value.id),
      quantity: parseInt(form.value.qty),
      supplier_id: selectedVendor.value?.id 
        ? parseInt(selectedVendor.value.id) 
        : (selectedPart.value.supplier_id ? parseInt(selectedPart.value.supplier_id) : null),
      unit_price: parseFloat(form.value.price) || 0,
      unit_of_measure: selectedPartUnit.value,
      location_raw: locationRaw || undefined,
      has_core: form.value.hasCore,
      core_cost: parseFloat(form.value.coreCost) || 0,
      core_rebate_expected: parseFloat(form.value.coreRebateExpected) || 0,
      core_rebate_received: parseFloat(form.value.coreRebateReceived) || 0
    })
    
    showToast('Success', `Stock updated successfully (${form.value.qty} ${selectedPartUnit.value})`)
    
    // Reset form
    clearPart()
    form.value.qty = 1
    form.value.price = 0
    form.value.locationRaw = ''
    form.value.hasCore = false
    form.value.coreCost = 0
    form.value.coreRebateExpected = 0
    form.value.coreRebateReceived = 0
    
    // Refresh parts to get updated stock
    const partsRes = await partsApi.getAll()
    parts.value = partsRes.data || []
    
  } catch (error) {
    showToast('Error', error.response?.data?.message || 'Failed to process incoming', 'error')
  } finally {
    processing.value = false
  }
}

const processMultipleReturns = async () => {
  if (!selectedReference.value) {
    showToast('Error', referenceValidationMessage.value || `Please select a valid ${referenceTypeLabel.value.toLowerCase()}`, 'error')
    return
  }

  const itemsToReturn = returnableItems.value.filter(item => item.returnQty > 0)
  
  if (itemsToReturn.length === 0) {
    showToast('Error', 'Please select at least one item to return', 'error')
    return
  }

  // Validate quantities
  const invalidItems = itemsToReturn.filter(item => item.returnQty > item.available_to_return)
  if (invalidItems.length > 0) {
    showToast('Error', 'Some items have invalid return quantities', 'error')
    return
  }

  try {
    processing.value = true
    // Convert form type to proper reference_type
    let refType = form.value.type
    if (refType === 'wo') refType = 'work_order'
    else if (refType === 'tech') refType = 'technician'
    else if (refType === 'unit') refType = 'unit'
    
    // Process each return
    let successCount = 0
    let errorCount = 0
    
    for (const item of itemsToReturn) {
      try {
        await assetApi.processIncoming({
          part_id: parseInt(item.part_id),
          quantity: parseInt(item.returnQty),
          reference_type: refType,
          reference_id: parseInt(selectedReference.value)
        })
        successCount++
      } catch (error) {
        errorCount++
        console.error(`Failed to return ${item.fowler_part_number}:`, error)
      }
    }
    
    if (successCount > 0) {
      showToast('Success', `${successCount} item(s) returned successfully`)
      
      // Reload returnable items
      await loadReturnableItems()
      
      // Refresh parts
      const partsRes = await partsApi.getAll()
      parts.value = partsRes.data || []
    }
    
    if (errorCount > 0) {
      showToast('Warning', `${errorCount} item(s) failed to return`, 'warning')
    }
    
  } catch (error) {
    showToast('Error', error.response?.data?.message || 'Failed to process returns', 'error')
  } finally {
    processing.value = false
  }
}

const processManualReturns = async () => {
  if (!selectedReference.value) {
    showToast('Error', referenceValidationMessage.value || `Please select a valid ${referenceTypeLabel.value.toLowerCase()}`, 'error')
    return
  }
  
  const itemsToReturn = manualValidItems.value
  if (itemsToReturn.length === 0) {
    showToast('Error', 'Please add at least one part to return', 'error')
    return
  }
  
  try {
    processing.value = true
    let refType = form.value.type
    if (refType === 'wo') refType = 'work_order'
    else if (refType === 'tech') refType = 'technician'
    else if (refType === 'unit') refType = 'unit'
    
    let successCount = 0
    let errorCount = 0
    
    for (const item of itemsToReturn) {
      try {
        await assetApi.processIncoming({
          part_id: parseInt(item.partId),
          quantity: parseInt(item.qty),
          reference_type: refType,
          reference_id: parseInt(selectedReference.value)
        })
        successCount++
      } catch (error) {
        errorCount++
        console.error('Failed to return item:', error)
      }
    }
    
    if (successCount > 0) {
      showToast('Success', `${successCount} item(s) returned successfully`)
      manualReturnBasket.value = [createManualReturnItem()]
      
      const partsRes = await partsApi.getAll()
      parts.value = partsRes.data || []
      await loadReturnableItems()
    }
    
    if (errorCount > 0) {
      showToast('Warning', `${errorCount} item(s) failed to return`, 'warning')
    }
  } catch (error) {
    showToast('Error', error.response?.data?.message || 'Failed to process manual returns', 'error')
  } finally {
    processing.value = false
  }
}

watch(allowUntrackedReturns, (value) => {
  if (!value) {
    manualReturnMode.value = false
  }
})

onMounted(fetchData)
</script>

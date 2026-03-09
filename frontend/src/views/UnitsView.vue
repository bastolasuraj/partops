<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8 slide-up">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Units</h1>
          <p class="text-gray-600">Vehicles and equipment assets</p>
        </div>
      </div>
    </div>

    <!-- Units Card -->
    <div class="card slide-up">
      <div class="card-header">
        <h2 class="text-xl font-semibold text-gray-800">Units Directory</h2>
        <button @click="openAddModal" class="btn-primary">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Add New
        </button>
      </div>
      
      <div v-if="loading" class="p-8 text-center">
        <div class="spinner mx-auto mb-4"></div>
        <p class="text-gray-500">Loading units...</p>
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="data-table">
          <thead>
            <tr>
              <th>Unit ID</th>
              <th>Make/Model</th>
              <th>Year</th>
              <th>VIN</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="unit in units" :key="unit.id">
              <td class="font-mono font-medium text-blue-600">{{ unit.name }}</td>
              <td>{{ unit.make }} {{ unit.model }}</td>
              <td>{{ unit.year || '-' }}</td>
              <td class="font-mono text-xs text-gray-500">{{ unit.vin || '-' }}</td>
              <td>
                <div class="flex gap-2">
                  <button @click="openEditModal(unit)" class="text-gray-500 hover:text-blue-600" title="Edit">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                    </svg>
                  </button>
                  <button @click="deleteUnit(unit)" class="text-gray-500 hover:text-red-600" title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="3 6 5 6 21 6"></polyline>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="units.length === 0">
              <td colspan="5" class="text-center py-8 text-gray-500">No units found</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <Transition name="fade">
      <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-xl shadow-2xl flex flex-col max-h-[90vh] slide-up">
          <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-900">{{ modalTitle }}</h3>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          
          <div class="p-6 overflow-y-auto space-y-4">
            <div class="form-group">
              <label class="form-label">Unit ID *</label>
              <input v-model="form.name" class="form-input" placeholder="e.g., UNIT-12" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div class="form-group">
                <label class="form-label">Make</label>
                <input v-model="form.make" class="form-input" placeholder="e.g., Ford">
              </div>
              <div class="form-group">
                <label class="form-label">Model</label>
                <input v-model="form.model" class="form-input" placeholder="e.g., F-150">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Year</label>
              <input v-model="form.year" class="form-input" placeholder="e.g., 2020">
            </div>
            <div class="form-group">
              <label class="form-label">VIN</label>
              <input v-model="form.vin" class="form-input">
            </div>
            <div class="form-group">
              <label class="form-label">Plate</label>
              <input v-model="form.plate" class="form-input">
            </div>
          </div>

          <div class="p-6 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 rounded-b-xl">
            <button @click="closeModal" class="btn-outline">Cancel</button>
            <button @click="saveUnit" :disabled="saving" class="btn-primary">
              <span v-if="saving" class="spinner w-4 h-4"></span>
              Save
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { unitsApi } from '@/services/api'
import { useToast } from '@/composables/useToast'

const { showToast } = useToast()

const units = ref([])
const loading = ref(true)
const saving = ref(false)
const showModal = ref(false)
const modalMode = ref('add')
const selectedUnit = ref(null)

const form = ref({
  name: '',
  make: '',
  model: '',
  year: '',
  vin: '',
  plate: ''
})

const modalTitle = ref('Add Unit')

const fetchUnits = async () => {
  try {
    loading.value = true
    const response = await unitsApi.getAll()
    units.value = response.data || []
  } catch (error) {
    showToast('Error', 'Failed to load units', 'error')
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.value = { name: '', make: '', model: '', year: '', vin: '', plate: '' }
}

const openAddModal = () => {
  resetForm()
  modalMode.value = 'add'
  modalTitle.value = 'Add Unit'
  showModal.value = true
}

const openEditModal = (unit) => {
  selectedUnit.value = unit
  form.value = { ...unit }
  modalMode.value = 'edit'
  modalTitle.value = `Edit: ${unit.name}`
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedUnit.value = null
  resetForm()
}

const saveUnit = async () => {
  if (!form.value.name) {
    showToast('Error', 'Unit ID is required', 'error')
    return
  }
  
  try {
    saving.value = true
    
    if (modalMode.value === 'edit') {
      await unitsApi.update(selectedUnit.value.id, form.value)
      showToast('Success', 'Unit updated successfully')
    } else {
      await unitsApi.create(form.value)
      showToast('Success', 'Unit created successfully')
    }
    
    closeModal()
    fetchUnits()
  } catch (error) {
    showToast('Error', error.response?.data?.message || 'Failed to save unit', 'error')
  } finally {
    saving.value = false
  }
}

const deleteUnit = async (unit) => {
  if (!confirm(`Delete unit ${unit.name}?`)) return
  
  try {
    await unitsApi.delete(unit.id)
    showToast('Deleted', 'Unit removed successfully')
    fetchUnits()
  } catch (error) {
    showToast('Error', 'Failed to delete unit', 'error')
  }
}

onMounted(fetchUnits)
</script>

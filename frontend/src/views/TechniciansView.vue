<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8 slide-up">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Technicians</h1>
          <p class="text-gray-600">Manage staff and access</p>
        </div>
      </div>
    </div>

    <!-- Technicians Card -->
    <div class="card slide-up">
      <div class="card-header">
        <h2 class="text-xl font-semibold text-gray-800">Technicians Directory</h2>
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
        <p class="text-gray-500">Loading technicians...</p>
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="data-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Employee Number</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="tech in technicians" :key="tech.id">
              <td class="font-medium text-gray-900">{{ tech.name }}</td>
              <td class="font-mono text-xs">{{ tech.emp_id || '-' }}</td>
              <td>
                <div class="flex gap-2">
                  <button @click="openEditModal(tech)" class="text-gray-500 hover:text-blue-600" title="Edit">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                    </svg>
                  </button>
                  <button @click="deleteTechnician(tech)" class="text-gray-500 hover:text-red-600" title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="3 6 5 6 21 6"></polyline>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="technicians.length === 0">
              <td colspan="3" class="text-center py-8 text-gray-500">No technicians found</td>
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
              <label class="form-label">Name *</label>
              <input v-model="form.name" class="form-input" required>
            </div>
            <div class="form-group">
              <label class="form-label">Employee Number</label>
              <input v-model="form.emp_id" class="form-input" placeholder="e.g., T-101">
              <div class="text-xs text-gray-500 mt-1">
                Leave blank to auto-assign a random temporary 10-digit employee number.
              </div>
            </div>
          </div>

          <div class="p-6 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 rounded-b-xl">
            <button @click="closeModal" class="btn-outline">Cancel</button>
            <button @click="saveTechnician" :disabled="saving" class="btn-primary">
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
import { techniciansApi } from '@/services/api'
import { useToast } from '@/composables/useToast'

const { showToast } = useToast()

const technicians = ref([])
const loading = ref(true)
const saving = ref(false)
const showModal = ref(false)
const modalMode = ref('add')
const selectedTechnician = ref(null)

const form = ref({
  name: '',
  emp_id: ''
})

const modalTitle = ref('Add Technician')

const fetchTechnicians = async () => {
  try {
    loading.value = true
    const response = await techniciansApi.getAll()
    technicians.value = response.data || []
  } catch (error) {
    showToast('Error', 'Failed to load technicians', 'error')
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.value = { name: '', emp_id: '' }
}

const openAddModal = () => {
  resetForm()
  modalMode.value = 'add'
  modalTitle.value = 'Add Technician'
  showModal.value = true
}

const openEditModal = (tech) => {
  selectedTechnician.value = tech
  form.value = {
    name: tech.name || '',
    emp_id: tech.emp_id || ''
  }
  modalMode.value = 'edit'
  modalTitle.value = `Edit: ${tech.name}`
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedTechnician.value = null
  resetForm()
}

const saveTechnician = async () => {
  if (!form.value.name) {
    showToast('Error', 'Name is required', 'error')
    return
  }
  
  try {
    saving.value = true
    const payload = {
      name: String(form.value.name || '').trim(),
      emp_id: String(form.value.emp_id || '').trim()
    }
    
    if (modalMode.value === 'edit') {
      await techniciansApi.update(selectedTechnician.value.id, payload)
      showToast('Success', 'Technician updated successfully')
    } else {
      await techniciansApi.create(payload)
      showToast('Success', 'Technician created successfully')
    }
    
    closeModal()
    fetchTechnicians()
  } catch (error) {
    showToast('Error', error.response?.data?.message || 'Failed to save technician', 'error')
  } finally {
    saving.value = false
  }
}

const deleteTechnician = async (tech) => {
  if (!confirm(`Delete technician ${tech.name}?`)) return
  
  try {
    await techniciansApi.delete(tech.id)
    showToast('Deleted', 'Technician removed successfully')
    fetchTechnicians()
  } catch (error) {
    showToast('Error', 'Failed to delete technician', 'error')
  }
}

onMounted(fetchTechnicians)
</script>

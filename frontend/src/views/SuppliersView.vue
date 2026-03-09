<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8 slide-up">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Suppliers</h1>
          <p class="text-gray-600">Manage vendor information</p>
        </div>
      </div>
    </div>

    <!-- Suppliers Card -->
    <div class="card slide-up">
      <div class="card-header">
        <h2 class="text-xl font-semibold text-gray-800">Suppliers Directory</h2>
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
        <p class="text-gray-500">Loading suppliers...</p>
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="data-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Phone</th>
              <th>Email</th>
              <th>Contact</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="supplier in suppliers" :key="supplier.id">
              <td class="font-medium text-gray-900">
                <a v-if="supplier.url" :href="supplier.url" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 hover:underline flex items-center gap-1">
                  {{ supplier.name }}
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                  </svg>
                </a>
                <span v-else>{{ supplier.name }}</span>
              </td>
              <td>
                <a v-if="supplier.phone" :href="`tel:${supplier.phone}`" class="font-mono text-sm text-blue-600 hover:text-blue-800 hover:underline flex items-center gap-1">
                  {{ formatPhone(supplier.phone) }}
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                  </svg>
                </a>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td>
                <a v-if="supplier.email" :href="`mailto:${supplier.email}`" class="text-blue-600 hover:text-blue-800 hover:underline flex items-center gap-1">
                  {{ supplier.email }}
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                  </svg>
                </a>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td>
                <span v-if="supplier.contact" class="flex items-center gap-1">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                  </svg>
                  {{ supplier.contact }}
                </span>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td>
                <div class="flex gap-2">
                  <button @click="openEditModal(supplier)" class="text-gray-500 hover:text-blue-600" title="Edit">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                    </svg>
                  </button>
                  <button @click="deleteSupplier(supplier)" class="text-gray-500 hover:text-red-600" title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="3 6 5 6 21 6"></polyline>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="suppliers.length === 0">
              <td colspan="5" class="text-center py-8 text-gray-500">No suppliers found</td>
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
              <label class="form-label">Contact Person</label>
              <input v-model="form.contact" class="form-input">
            </div>
            <div class="form-group">
              <label class="form-label">Phone</label>
              <input v-model="form.phone" class="form-input">
            </div>
            <div class="form-group">
              <label class="form-label">Email</label>
              <input v-model="form.email" type="email" class="form-input">
            </div>
            <div class="form-group">
              <label class="form-label">Website URL</label>
              <input v-model="form.url" type="url" class="form-input" placeholder="https://example.com">
            </div>
            <div class="form-group">
              <label class="form-label">Address</label>
              <textarea v-model="form.address" class="form-input" rows="2"></textarea>
            </div>
          </div>

          <div class="p-6 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 rounded-b-xl">
            <button @click="closeModal" class="btn-outline">Cancel</button>
            <button @click="saveSupplier" :disabled="saving" class="btn-primary">
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
import { suppliersApi } from '@/services/api'
import { useToast } from '@/composables/useToast'
import { usePhoneFormat } from '@/composables/usePhoneFormat'

const { showToast } = useToast()
const { formatPhone } = usePhoneFormat()

const suppliers = ref([])
const loading = ref(true)
const saving = ref(false)
const showModal = ref(false)
const modalMode = ref('add')
const selectedSupplier = ref(null)

const form = ref({
  name: '',
  contact: '',
  phone: '',
  email: '',
  url: '',
  address: ''
})

const modalTitle = ref('Add Supplier')

const fetchSuppliers = async () => {
  try {
    loading.value = true
    const response = await suppliersApi.getAll()
    suppliers.value = response.data || []
  } catch (error) {
    showToast('Error', 'Failed to load suppliers', 'error')
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.value = { name: '', contact: '', phone: '', email: '', url: '', address: '' }
}

const openAddModal = () => {
  resetForm()
  modalMode.value = 'add'
  modalTitle.value = 'Add Supplier'
  showModal.value = true
}

const openEditModal = (supplier) => {
  selectedSupplier.value = supplier
  form.value = { ...supplier }
  modalMode.value = 'edit'
  modalTitle.value = `Edit: ${supplier.name}`
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedSupplier.value = null
  resetForm()
}

const saveSupplier = async () => {
  if (!form.value.name) {
    showToast('Error', 'Name is required', 'error')
    return
  }
  
  try {
    saving.value = true
    
    if (modalMode.value === 'edit') {
      await suppliersApi.update(selectedSupplier.value.id, form.value)
      showToast('Success', 'Supplier updated successfully')
    } else {
      await suppliersApi.create(form.value)
      showToast('Success', 'Supplier created successfully')
    }
    
    closeModal()
    fetchSuppliers()
  } catch (error) {
    showToast('Error', error.response?.data?.message || 'Failed to save supplier', 'error')
  } finally {
    saving.value = false
  }
}

const deleteSupplier = async (supplier) => {
  if (!confirm(`Delete supplier ${supplier.name}?`)) return
  
  try {
    await suppliersApi.delete(supplier.id)
    showToast('Deleted', 'Supplier removed successfully')
    fetchSuppliers()
  } catch (error) {
    showToast('Error', 'Failed to delete supplier', 'error')
  }
}

onMounted(fetchSuppliers)
</script>

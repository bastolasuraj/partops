<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8 slide-up">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Settings</h1>
          <p class="text-gray-600">Control optional behavior and contingency modes.</p>
        </div>
      </div>
    </div>

    <div class="card slide-up">
      <div class="card-header">
        <h2 class="text-xl font-semibold text-gray-800">Contingency Mode</h2>
      </div>
      <div class="p-6">
        <div class="flex items-start gap-4">
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              type="checkbox"
              v-model="allowUntrackedReturns"
              class="sr-only peer"
            >
            <div class="w-11 h-6 bg-red-500 rounded-full peer peer-focus:ring-2 peer-focus:ring-green-500 peer-checked:bg-green-600 transition-colors"></div>
            <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-5"></div>
          </label>
          <div class="flex-1">
            <div class="font-semibold text-gray-900 flex items-center gap-2">
              Enable untracked returns and quick creation
              <span class="text-xs px-2 py-0.5 rounded-full" :class="allowUntrackedReturns ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                {{ allowUntrackedReturns ? 'Enabled' : 'Disabled' }}
              </span>
            </div>
            <p class="text-sm text-gray-600 mt-1">
              Allows creating work orders, units, and technicians during checkout/returns.
              Enables manual returns for items not yet recorded in the system, with multi-part selection.
            </p>
          </div>
        </div>

<!--        <div class="mt-4 text-xs text-gray-500">-->
<!--          Only enable this when you need to reconcile paper records or fill gaps in historical data.-->
<!--        </div>-->
        <div v-if="settingsError" class="mt-3 text-xs text-red-600">
          {{ settingsError }}
        </div>
      </div>
    </div>

    <div class="card slide-up mt-6">
      <div class="card-header">
        <h2 class="text-xl font-semibold text-gray-800">Audit Logs</h2>
      </div>
      <div class="p-6 space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <div>
            <label class="form-label">Default entries per page</label>
            <select v-model.number="auditLogsPageSizeDraft" class="form-input">
              <option
                v-for="size in auditLogsPageSizeOptions"
                :key="size"
                :value="size"
              >
                {{ size }}
              </option>
            </select>
            <p class="mt-2 text-sm text-gray-600">
              Controls the default page size on <span class="font-mono">/logs</span>.
            </p>
          </div>

          <div>
            <label class="form-label">Active log entries kept in the database</label>
            <input
              v-model.number="auditLogsRetentionLimitDraft"
              type="number"
              min="1"
              max="100000"
              step="1"
              class="form-input"
            >
            <p class="mt-2 text-sm text-gray-600">
              When the active log table exceeds this count, the oldest rows are moved to
              <span class="font-mono">{{ auditLogsArchiveFile }}</span>.
            </p>
          </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
          Archive file: <span class="font-mono">{{ auditLogsArchiveFile }}</span>
        </div>

        <div class="flex flex-wrap gap-3">
          <button
            class="btn-primary"
            :disabled="savingAuditLogSettings || !auditLogSettingsValid || !auditLogSettingsDirty"
            @click="saveAuditLogSettings"
          >
            {{ savingAuditLogSettings ? 'Saving Log Settings...' : 'Save Log Settings' }}
          </button>

          <button
            class="btn-outline"
            :disabled="savingAuditLogSettings || !auditLogSettingsDirty"
            @click="resetAuditLogSettings"
          >
            Reset
          </button>
        </div>

        <div class="text-xs text-gray-500">
          Lowering the active log limit archives excess oldest rows immediately.
        </div>
      </div>
    </div>

    <div class="card slide-up mt-6 border border-red-200">
      <div class="card-header bg-red-50">
        <h2 class="text-xl font-semibold text-red-800">Danger Zone</h2>
      </div>
      <div class="p-6 space-y-8">
        <div>
          <h3 class="font-semibold text-red-900">Selective Table Truncation</h3>
          <p class="text-sm text-red-700 mt-1">
            Choose one or more tables to truncate (data only, structure preserved).
          </p>

          <div v-if="tablesLoading" class="text-sm text-gray-600 mt-4">
            Loading table list...
          </div>
          <div v-else class="mt-4 space-y-4">
            <div class="flex flex-wrap gap-2">
              <button class="btn-outline text-sm" @click="selectDefaultTables">Select Recommended</button>
              <button class="btn-outline text-sm" @click="selectAllTables">Select All</button>
              <button class="btn-outline text-sm" @click="clearSelectedTables">Clear</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
              <label
                v-for="table in availableTables"
                :key="table"
                class="flex items-center gap-2 text-sm text-gray-800 border border-gray-200 rounded-md px-3 py-2 bg-white"
              >
                <input
                  type="checkbox"
                  :value="table"
                  v-model="selectedTables"
                  class="rounded border-gray-300 text-red-600 focus:ring-red-500"
                >
                <span class="font-mono">{{ table }}</span>
              </label>
            </div>

            <div>
              <label class="form-label text-red-800">
                Type <span class="font-mono font-bold">{{ selectiveConfirmation }}</span> to confirm
              </label>
              <input
                v-model="selectiveConfirmText"
                type="text"
                class="form-input border-red-300 focus:border-red-500 focus:ring-red-500"
                :placeholder="selectiveConfirmation"
              >
            </div>

            <button
              class="btn-primary bg-red-600 hover:bg-red-700 disabled:bg-red-300 disabled:cursor-not-allowed"
              :disabled="truncatingSelective || selectedTables.length === 0 || selectiveConfirmText !== selectiveConfirmation"
              @click="truncateSelectedTables"
            >
              {{ truncatingSelective ? 'Truncating Selected...' : `Truncate Selected Tables (${selectedTables.length})` }}
            </button>
          </div>
        </div>

        <div class="pt-6 border-t border-red-200">
          <h3 class="font-semibold text-red-900">Complete Database Data Wipe</h3>
          <p class="text-sm text-red-700 mt-1">
            Truncates all tables in the current database (data only). This includes users and app settings.
          </p>

          <div class="mt-4">
            <label class="form-label text-red-800">
              Type <span class="font-mono font-bold">{{ wipeConfirmation }}</span> to confirm
            </label>
            <input
              v-model="wipeConfirmText"
              type="text"
              class="form-input border-red-300 focus:border-red-500 focus:ring-red-500"
              :placeholder="wipeConfirmation"
            >
          </div>

          <button
            class="btn-primary mt-4 bg-red-800 hover:bg-red-900 disabled:bg-red-300 disabled:cursor-not-allowed"
            :disabled="wipingAll || wipeConfirmText !== wipeConfirmation"
            @click="wipeAllData"
          >
            {{ wipingAll ? 'Wiping All Data...' : 'Wipe All Table Data' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { settingsApi } from '@/services/api'
import { useSettings } from '@/composables/useSettings'
import { useToast } from '@/composables/useToast'

const {
  allowUntrackedReturns,
  auditLogsPageSize,
  auditLogsRetentionLimit,
  auditLogsPageSizeOptions,
  auditLogsArchiveFile,
  loadSettings,
  saveSettings,
  settingsError
} = useSettings()
const { showToast } = useToast()
const isReady = ref(false)
const tablesLoading = ref(false)
const availableTables = ref([])
const defaultOperationalTables = ref([])
const selectedTables = ref([])
const selectiveConfirmation = 'DELETE SELECTED DATA'
const wipeConfirmation = 'WIPE ALL DATA'
const selectiveConfirmText = ref('')
const wipeConfirmText = ref('')
const truncatingSelective = ref(false)
const wipingAll = ref(false)
const savingAuditLogSettings = ref(false)
const auditLogsPageSizeDraft = ref(25)
const auditLogsRetentionLimitDraft = ref(20)
const fallbackOperationalTables = [
  'vendor_returns',
  'inventory_transactions',
  'inventory_location_levels',
  'inventory_levels',
  'fowler_supplier_mapping',
  'work_orders',
  'parts',
  'technicians',
  'units',
  'suppliers'
]
const fallbackAllTables = [
  ...fallbackOperationalTables,
  'users',
  'app_settings'
]

const auditLogSettingsValid = computed(() => {
  return auditLogsPageSizeOptions.value.includes(Number(auditLogsPageSizeDraft.value))
    && Number.isInteger(Number(auditLogsRetentionLimitDraft.value))
    && Number(auditLogsRetentionLimitDraft.value) >= 1
    && Number(auditLogsRetentionLimitDraft.value) <= 100000
})

const auditLogSettingsDirty = computed(() => {
  return Number(auditLogsPageSizeDraft.value) !== Number(auditLogsPageSize.value)
    || Number(auditLogsRetentionLimitDraft.value) !== Number(auditLogsRetentionLimit.value)
})

onMounted(async () => {
  await loadSettings(true)
  resetAuditLogSettings()
  await loadDatabaseTables()
  isReady.value = true
})

watch(allowUntrackedReturns, async (value, previous) => {
  if (!isReady.value) return
  try {
    await saveSettings({
      allow_untracked_returns: !!value
    })
    showToast('Settings saved', 'Changes applied')
  } catch (error) {
    allowUntrackedReturns.value = previous
    showToast('Failed to save settings', error?.response?.data?.message || 'Please try again', 'error')
  }
})

const resetAuditLogSettings = () => {
  auditLogsPageSizeDraft.value = Number(auditLogsPageSize.value) || 25
  auditLogsRetentionLimitDraft.value = Number(auditLogsRetentionLimit.value) || 20
}

const saveAuditLogSettings = async () => {
  if (!auditLogSettingsValid.value) {
    showToast('Invalid log settings', 'Choose a valid page size and retention limit', 'warning')
    return
  }

  try {
    savingAuditLogSettings.value = true
    await saveSettings({
      audit_logs_page_size: Number(auditLogsPageSizeDraft.value),
      audit_logs_retention_limit: Number(auditLogsRetentionLimitDraft.value)
    })
    resetAuditLogSettings()
    showToast('Log settings saved', 'Audit log preferences updated')
  } catch (error) {
    showToast('Failed to save log settings', error?.response?.data?.message || 'Please try again', 'error')
  } finally {
    savingAuditLogSettings.value = false
  }
}

const loadDatabaseTables = async () => {
  tablesLoading.value = true
  try {
    const response = await settingsApi.getDatabaseTables()
    availableTables.value = Array.isArray(response?.data?.tables) ? response.data.tables : []
    defaultOperationalTables.value = Array.isArray(response?.data?.default_operational_tables)
      ? response.data.default_operational_tables
      : []

    if (defaultOperationalTables.value.length === 0) {
      defaultOperationalTables.value = [...fallbackOperationalTables]
    }
    if (availableTables.value.length === 0) {
      availableTables.value = [...fallbackAllTables]
    }

    if (selectedTables.value.length === 0 && defaultOperationalTables.value.length > 0) {
      selectedTables.value = [...defaultOperationalTables.value]
    }
  } catch (error) {
    showToast('Failed to load tables', error?.response?.data?.message || 'Please refresh', 'error')
    availableTables.value = [...fallbackAllTables]
    defaultOperationalTables.value = [...fallbackOperationalTables]
    if (selectedTables.value.length === 0) {
      selectedTables.value = [...defaultOperationalTables.value]
    }
  } finally {
    tablesLoading.value = false
  }
}

const selectDefaultTables = () => {
  selectedTables.value = [...defaultOperationalTables.value]
}

const selectAllTables = () => {
  selectedTables.value = [...availableTables.value]
}

const clearSelectedTables = () => {
  selectedTables.value = []
}

const truncateSelectedTables = async () => {
  if (selectedTables.value.length === 0) {
    showToast('No tables selected', 'Choose at least one table', 'warning')
    return
  }

  if (selectiveConfirmText.value !== selectiveConfirmation) {
    showToast('Confirmation required', `Type ${selectiveConfirmation} to continue`, 'warning')
    return
  }

  const confirmed = window.confirm(
    `This will truncate ${selectedTables.value.length} selected table(s). Continue?`
  )
  if (!confirmed) return

  try {
    truncatingSelective.value = true
    const response = await settingsApi.truncateData({
      mode: 'selective',
      tables: selectedTables.value,
      confirm_text: selectiveConfirmText.value
    })

    selectiveConfirmText.value = ''
    const truncated = Array.isArray(response?.data?.truncated_tables) ? response.data.truncated_tables : []
    showToast('Selected tables truncated', `${truncated.length} table(s) truncated`)

    if (truncated.includes('users')) {
      window.location.replace('/login')
      return
    }
  } catch (error) {
    showToast('Failed to truncate selected tables', error?.response?.data?.message || 'Please try again', 'error')
  } finally {
    truncatingSelective.value = false
  }
}

const wipeAllData = async () => {
  if (wipeConfirmText.value !== wipeConfirmation) {
    showToast('Confirmation required', `Type ${wipeConfirmation} to continue`, 'warning')
    return
  }

  const confirmed = window.confirm(
    'This will truncate ALL tables (data only), including users and app settings. Continue?'
  )
  if (!confirmed) return

  try {
    wipingAll.value = true
    await settingsApi.truncateData({
      mode: 'wipe_all',
      confirm_text: wipeConfirmText.value
    })
    wipeConfirmText.value = ''
    showToast('Database data wiped', 'All table data has been truncated')
    window.location.replace('/login')
  } catch (error) {
    showToast('Failed to wipe database data', error?.response?.data?.message || 'Please try again', 'error')
  } finally {
    wipingAll.value = false
  }
}
</script>

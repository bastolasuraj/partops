import { ref } from 'vue'
import { settingsApi } from '@/services/api'

const allowUntrackedReturns = ref(false)
const auditLogsPageSize = ref(25)
const auditLogsRetentionLimit = ref(20)
const auditLogsPageSizeOptions = ref([10, 25, 50, 100])
const auditLogsArchiveFile = ref('old_logs.json')
const settingsLoaded = ref(false)
const settingsLoading = ref(false)
const settingsError = ref('')

const loadSettings = async (force = false) => {
  if (settingsLoading.value || (settingsLoaded.value && !force)) return
  settingsLoading.value = true
  settingsError.value = ''

  try {
    const response = await settingsApi.get()
    if (response?.data) {
      if (typeof response.data.allow_untracked_returns !== 'undefined') {
        allowUntrackedReturns.value = !!response.data.allow_untracked_returns
      }

      if (typeof response.data.audit_logs_page_size !== 'undefined') {
        auditLogsPageSize.value = Number(response.data.audit_logs_page_size) || 25
      }

      if (typeof response.data.audit_logs_retention_limit !== 'undefined') {
        auditLogsRetentionLimit.value = Number(response.data.audit_logs_retention_limit) || 20
      }

      if (Array.isArray(response.data.audit_logs_page_size_options) && response.data.audit_logs_page_size_options.length > 0) {
        auditLogsPageSizeOptions.value = response.data.audit_logs_page_size_options
      }

      if (response.data.audit_logs_archive_file) {
        auditLogsArchiveFile.value = response.data.audit_logs_archive_file
      }
    }
    settingsLoaded.value = true
  } catch (error) {
    settingsError.value = error?.response?.data?.message || error?.message || 'Failed to load settings'
  } finally {
    settingsLoading.value = false
  }
}

const applySettingsResponse = (response) => {
  if (!response?.data) {
    return response
  }

  if (typeof response.data.allow_untracked_returns !== 'undefined') {
    allowUntrackedReturns.value = !!response.data.allow_untracked_returns
  }

  if (typeof response.data.audit_logs_page_size !== 'undefined') {
    auditLogsPageSize.value = Number(response.data.audit_logs_page_size) || auditLogsPageSize.value
  }

  if (typeof response.data.audit_logs_retention_limit !== 'undefined') {
    auditLogsRetentionLimit.value = Number(response.data.audit_logs_retention_limit) || auditLogsRetentionLimit.value
  }

  if (Array.isArray(response.data.audit_logs_page_size_options) && response.data.audit_logs_page_size_options.length > 0) {
    auditLogsPageSizeOptions.value = response.data.audit_logs_page_size_options
  }

  if (response.data.audit_logs_archive_file) {
    auditLogsArchiveFile.value = response.data.audit_logs_archive_file
  }

  return response
}

const saveSettings = async (overrides = null) => {
  const payload = overrides || {
    allow_untracked_returns: !!allowUntrackedReturns.value,
    audit_logs_page_size: Number(auditLogsPageSize.value) || 25,
    audit_logs_retention_limit: Number(auditLogsRetentionLimit.value) || 20
  }

  const response = await settingsApi.update(payload)
  return applySettingsResponse(response)
}

export function useSettings() {
  return {
    allowUntrackedReturns,
    auditLogsPageSize,
    auditLogsRetentionLimit,
    auditLogsPageSizeOptions,
    auditLogsArchiveFile,
    settingsLoaded,
    settingsLoading,
    settingsError,
    loadSettings,
    saveSettings
  }
}

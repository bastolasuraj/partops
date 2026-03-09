import { ref } from 'vue'
import { settingsApi } from '@/services/api'

const allowUntrackedReturns = ref(false)
const settingsLoaded = ref(false)
const settingsLoading = ref(false)
const settingsError = ref('')

const loadSettings = async (force = false) => {
  if (settingsLoading.value || (settingsLoaded.value && !force)) return
  settingsLoading.value = true
  settingsError.value = ''

  try {
    const response = await settingsApi.get()
    if (response?.data && typeof response.data.allow_untracked_returns !== 'undefined') {
      allowUntrackedReturns.value = !!response.data.allow_untracked_returns
    }
    settingsLoaded.value = true
  } catch (error) {
    settingsError.value = error?.response?.data?.message || error?.message || 'Failed to load settings'
  } finally {
    settingsLoading.value = false
  }
}

const saveSettings = async () => {
  return settingsApi.update({
    allow_untracked_returns: !!allowUntrackedReturns.value
  })
}

export function useSettings() {
  return {
    allowUntrackedReturns,
    settingsLoaded,
    settingsLoading,
    settingsError,
    loadSettings,
    saveSettings
  }
}

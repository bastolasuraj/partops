<template>
  <div>
    <div class="mb-8 slide-up">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Logs</h1>
          <p class="text-gray-600">Admin-only audit trail for login activity and system actions.</p>
        </div>
        <button
          v-if="verified"
          class="btn-outline"
          :disabled="logsLoading"
          @click="loadLogs"
        >
          {{ logsLoading ? 'Refreshing...' : 'Refresh Logs' }}
        </button>
      </div>
    </div>

    <div v-if="accessLoading" class="card slide-up">
      <div class="card-body py-10 text-center text-gray-600">
        Checking admin access...
      </div>
    </div>

    <div v-else-if="!verified" class="card slide-up max-w-2xl">
      <div class="card-header">
        <h2 class="text-xl font-semibold text-gray-800">Confirm Admin Password</h2>
      </div>
      <div class="p-6 space-y-4">
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
          This page is hidden from navigation and requires the current admin password before audit records can be viewed.
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <input
            v-model="password"
            type="password"
            class="form-input"
            autocomplete="current-password"
            @keyup.enter="confirmAccess"
          >
        </div>

        <button
          class="btn-primary"
          :disabled="confirming"
          @click="confirmAccess"
        >
          {{ confirming ? 'Confirming...' : 'Unlock Logs' }}
        </button>
      </div>
    </div>

    <template v-else>
      <div class="card slide-up mb-6">
        <div class="card-body flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div class="inline-flex rounded-xl border border-gray-200 bg-gray-50 p-1">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              type="button"
              class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors"
              :class="activeTab === tab.key ? tab.activeClass : 'text-gray-600 hover:text-gray-900'"
              @click="activeTab = tab.key"
            >
              {{ tab.label }} ({{ tab.count }})
            </button>
          </div>

          <div class="text-sm text-gray-600">
            <span class="font-semibold text-gray-800">Access window:</span>
            {{ verifiedUntil ? `verified until ${formatDateTime(verifiedUntil)}` : 'Password confirmation required' }}
          </div>
        </div>
      </div>

      <div class="card slide-up">
        <div class="card-header">
          <h2 class="text-xl font-semibold text-gray-800">{{ activeTabLabel }}</h2>
        </div>
        <div class="p-6">
          <div v-if="logsLoading && currentLogs.length === 0" class="text-sm text-gray-600">
            Loading {{ activeTabLabel.toLowerCase() }}...
          </div>
          <div v-else-if="currentLogs.length === 0" class="text-sm text-gray-500">
            No {{ activeTabLabel.toLowerCase() }} recorded yet.
          </div>
          <div v-else class="space-y-3">
            <details
              v-for="entry in currentLogs"
              :key="`${activeTab}-${entry.id}`"
              class="rounded-xl border border-gray-200 bg-white"
            >
              <summary class="cursor-pointer list-none px-4 py-3 transition-colors hover:bg-gray-50">
                <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                  <div class="min-w-0 text-sm text-gray-800">
                    <span class="font-medium">{{ formatSummary(entry) }}</span>
                  </div>
                  <span class="self-start rounded-full px-2.5 py-1 text-xs font-semibold" :class="outcomeClass(entry.outcome)">
                    {{ formatOutcome(entry.outcome) }}
                  </span>
                </div>
              </summary>

              <div class="border-t border-gray-100 px-4 py-4">
                <div class="grid grid-cols-1 gap-3 text-sm text-gray-700 md:grid-cols-2">
                  <div><span class="font-semibold text-gray-900">Who:</span> {{ formatWho(entry) }}</div>
                  <div><span class="font-semibold text-gray-900">When:</span> {{ formatDateTime(entry.created_at) }}</div>
                  <div><span class="font-semibold text-gray-900">Where:</span> {{ formatWhere(entry) }}</div>
                  <div><span class="font-semibold text-gray-900">How:</span> {{ formatHow(entry) }}</div>
                  <div class="md:col-span-2"><span class="font-semibold text-gray-900">What:</span> {{ formatWhat(entry) }}</div>
                  <div v-if="formatResult(entry)" class="md:col-span-2"><span class="font-semibold text-gray-900">Result:</span> {{ formatResult(entry) }}</div>
                </div>
              </div>
            </details>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { auditLogsApi } from '@/services/api'
import { useToast } from '@/composables/useToast'

const { showToast } = useToast()

const accessLoading = ref(true)
const logsLoading = ref(false)
const confirming = ref(false)
const verified = ref(false)
const verifiedUntil = ref('')
const password = ref('')
const userLogs = ref([])
const actionLogs = ref([])
const activeTab = ref('user')
const limit = ref(100)

const tabs = computed(() => ([
  {
    key: 'user',
    label: 'User',
    count: userLogs.value.length,
    activeClass: 'bg-blue-600 text-white shadow-sm'
  },
  {
    key: 'logs',
    label: 'Logs',
    count: actionLogs.value.length,
    activeClass: 'bg-green-600 text-white shadow-sm'
  }
]))

const currentLogs = computed(() => activeTab.value === 'user' ? userLogs.value : actionLogs.value)
const activeTabLabel = computed(() => activeTab.value === 'user' ? 'User Activity' : 'Logs')

const loadAccessStatus = async () => {
  accessLoading.value = true

  try {
    const response = await auditLogsApi.getAccessStatus()
    verified.value = !!response?.data?.verified
    verifiedUntil.value = response?.data?.verified_until || ''

    if (verified.value) {
      await loadLogs()
    }
  } catch (error) {
    showToast('Failed to check logs access', error?.response?.data?.message || 'Please try again', 'error')
  } finally {
    accessLoading.value = false
  }
}

const loadLogs = async () => {
  logsLoading.value = true

  try {
    const response = await auditLogsApi.getAll({ limit: limit.value })
    userLogs.value = Array.isArray(response?.data?.user_logs) ? response.data.user_logs : []
    actionLogs.value = Array.isArray(response?.data?.action_logs) ? response.data.action_logs : []
    verified.value = true
    verifiedUntil.value = response?.data?.verified_until || verifiedUntil.value
  } catch (error) {
    if (error?.response?.status === 423) {
      verified.value = false
      verifiedUntil.value = ''
      showToast('Password required', 'Enter your admin password again to view logs', 'warning')
      return
    }

    showToast('Failed to load logs', error?.response?.data?.message || 'Please try again', 'error')
  } finally {
    logsLoading.value = false
  }
}

const confirmAccess = async () => {
  if (!password.value.trim()) {
    showToast('Password required', 'Enter your current admin password', 'warning')
    return
  }

  confirming.value = true

  try {
    const response = await auditLogsApi.confirmAccess(password.value)
    verified.value = !!response?.data?.verified
    verifiedUntil.value = response?.data?.verified_until || ''
    password.value = ''
    showToast('Access granted', 'Logs unlocked', 'success')
    await loadLogs()
  } catch (error) {
    showToast('Access denied', error?.response?.data?.message || 'Password confirmation failed', 'error')
  } finally {
    confirming.value = false
  }
}

const formatDateTime = (value) => {
  if (!value) return '-'

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return value
  }

  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    timeZoneName: 'short'
  })
}

const formatDateForSentence = (value) => {
  if (!value) return '-'

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return value
  }

  return date.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    timeZoneName: 'short'
  })
}

const formatOutcome = (value) => value === 'failure' ? 'Failure' : 'Success'

const outcomeClass = (value) => {
  return value === 'failure'
    ? 'bg-red-100 text-red-700'
    : 'bg-green-100 text-green-700'
}

const formatWho = (entry) => {
  const name = entry?.display_name || entry?.username || 'Unknown user'
  const role = entry?.user_role ? ` (${entry.user_role})` : ''
  return `${name}${role}`
}

const formatWhere = (entry) => {
  return [entry?.route_path, entry?.ip_address].filter(Boolean).join(' | ') || '-'
}

const formatHow = (entry) => {
  return [entry?.http_method, entry?.auth_type, entry?.origin].filter(Boolean).join(' | ') || '-'
}

const formatWhat = (entry) => {
  const parts = []

  if (entry?.resource_type) {
    const label = entry.resource_type.replace(/[-_]/g, ' ')
    parts.push(label)
  }

  if (entry?.resource_id) {
    parts.push(`#${entry.resource_id}`)
  }

  if (entry?.action) {
    parts.push(`(${entry.action})`)
  }

  return parts.join(' ') || entry?.description || '-'
}

const formatActionPhrase = (entry) => {
  const description = String(entry?.description || 'did something')
  return description.charAt(0).toLowerCase() + description.slice(1)
}

const formatSummary = (entry) => {
  return `${formatWho(entry)} ${formatActionPhrase(entry)} on ${formatDateForSentence(entry.created_at)}.`
}

const formatResult = (entry) => {
  const metadata = entry?.metadata
  if (!metadata || typeof metadata !== 'object') {
    return ''
  }

  const parts = []

  if (metadata.response_message) {
    parts.push(metadata.response_message)
  }

  if (metadata.status_code) {
    parts.push(`HTTP ${metadata.status_code}`)
  }

  return parts.join(' | ')
}

onMounted(loadAccessStatus)
</script>

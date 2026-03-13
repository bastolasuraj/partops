import axios from 'axios'

const envApiUrl = import.meta.env.VITE_API_URL

const normalizeUrl = (url) => url?.replace(/\/+$/, '')

const resolveApiBaseUrl = () => {
  if (!envApiUrl) {
    return '/api'
  }

  if (envApiUrl.startsWith('/')) {
    return normalizeUrl(envApiUrl)
  }

  try {
    return normalizeUrl(new URL(envApiUrl).toString())
  } catch (error) {
    return '/api'
  }
}

const API_BASE_URL = resolveApiBaseUrl()
const ERROR_REPORT_PATH = '/audit-logs/errors'

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  timeout: 10000, // 10 second timeout
  withCredentials: true, // Enable sending cookies/session
})

let authRedirecting = false
let clientErrorLoggingInstalled = false

// CSRF token — stored in memory only (never localStorage).
let _csrfToken = ''
export const setCsrfToken = (token) => { _csrfToken = token || '' }
export const getCsrfToken = () => _csrfToken

const resolveErrorReportUrl = () => {
  const normalizedBaseUrl = normalizeUrl(API_BASE_URL) || '/api'
  return normalizedBaseUrl.endsWith(ERROR_REPORT_PATH)
    ? normalizedBaseUrl
    : `${normalizedBaseUrl}${ERROR_REPORT_PATH}`
}

const currentRoutePath = () => {
  if (typeof window === 'undefined') {
    return '/'
  }

  return `${window.location.pathname || '/'}${window.location.search || ''}`
}

const cleanObject = (value) => {
  if (!value || typeof value !== 'object' || Array.isArray(value)) {
    return {}
  }

  return Object.fromEntries(
    Object.entries(value).filter(([, entryValue]) => entryValue !== null && entryValue !== undefined && entryValue !== '')
  )
}

const normalizeErrorMessage = (value) => {
  if (!value) {
    return ''
  }

  if (typeof value === 'string') {
    return value
  }

  if (value instanceof Error) {
    return value.message || value.name || 'Error'
  }

  if (typeof value === 'object' && typeof value.message === 'string') {
    return value.message
  }

  try {
    return JSON.stringify(value)
  } catch (error) {
    return String(value)
  }
}

const normalizeErrorStack = (value) => {
  if (!value) {
    return null
  }

  if (value instanceof Error) {
    return value.stack || null
  }

  if (typeof value === 'object' && typeof value.stack === 'string') {
    return value.stack
  }

  return null
}

export const reportClientError = async (payload = {}) => {
  if (typeof window === 'undefined') {
    return
  }

  const message = normalizeErrorMessage(payload.message || payload.error || payload.reason)
  if (!message) {
    return
  }

  const metadata = cleanObject(payload.metadata)
  const body = cleanObject({
    source: payload.source || 'frontend',
    error_kind: payload.error_kind || 'client_error',
    message,
    stack_trace: typeof payload.stack_trace === 'string'
      ? payload.stack_trace
      : normalizeErrorStack(payload.error || payload.reason || payload.message),
    status_code: Number.isFinite(Number(payload.status_code)) ? Number(payload.status_code) : undefined,
    http_method: payload.http_method || 'CLIENT',
    route_path: payload.route_path || currentRoutePath(),
    ip_address: payload.ip_address,
    user_agent: payload.user_agent || (typeof navigator !== 'undefined' ? navigator.userAgent : undefined),
    file_name: payload.file_name,
    line_number: payload.line_number,
    column_number: payload.column_number,
    status_text: payload.status_text,
    current_url: payload.current_url || window.location.href,
    request_url: payload.request_url,
    api_message: payload.api_message,
    endpoint: payload.endpoint,
    reason_type: payload.reason_type,
    metadata: Object.keys(metadata).length > 0 ? metadata : undefined
  })

  try {
    const errorHeaders = { 'Content-Type': 'application/json' }
    if (_csrfToken) errorHeaders['X-CSRF-Token'] = _csrfToken
    await fetch(resolveErrorReportUrl(), {
      method: 'POST',
      headers: errorHeaders,
      credentials: 'include',
      keepalive: true,
      body: JSON.stringify(body)
    })
  } catch (reportError) {
    console.error('[Client Error Report Failed]', reportError)
  }
}

export const installGlobalErrorLogging = () => {
  if (clientErrorLoggingInstalled || typeof window === 'undefined') {
    return
  }

  clientErrorLoggingInstalled = true

  window.addEventListener('error', (event) => {
    const target = event?.target
    const resourceUrl = target?.currentSrc || target?.src || target?.href || null
    const isResourceError = !!(target && target !== window && resourceUrl)

    void reportClientError({
      source: 'frontend',
      error_kind: isResourceError ? 'resource_error' : 'runtime_error',
      message: isResourceError
        ? `Failed to load resource: ${resourceUrl}`
        : normalizeErrorMessage(event.error || event.message) || 'Unhandled frontend error',
      stack_trace: event.error?.stack,
      route_path: currentRoutePath(),
      file_name: event.filename || undefined,
      line_number: Number.isFinite(event.lineno) ? event.lineno : undefined,
      column_number: Number.isFinite(event.colno) ? event.colno : undefined
    })
  }, true)

  window.addEventListener('unhandledrejection', (event) => {
    if (event?.reason?.__pamReported) {
      return
    }

    void reportClientError({
      source: 'frontend',
      error_kind: 'unhandled_rejection',
      message: normalizeErrorMessage(event.reason) || 'Unhandled promise rejection',
      stack_trace: normalizeErrorStack(event.reason),
      route_path: currentRoutePath(),
      reason_type: event?.reason?.name || typeof event?.reason
    })
  })
}

const getValidationMessage = (payload) => {
  const errors = payload?.errors
  if (!errors || typeof errors !== 'object') {
    return null
  }

  for (const value of Object.values(errors)) {
    if (Array.isArray(value) && value.length > 0 && value[0]) {
      return value[0]
    }
  }

  return null
}

// Request interceptor — attach CSRF token for mutating requests.
api.interceptors.request.use(
  (config) => {
    if (typeof FormData !== 'undefined' && config.data instanceof FormData) {
      // Let the browser set multipart boundaries for file uploads.
      if (typeof config.headers?.delete === 'function') {
        config.headers.delete('Content-Type')
        config.headers.delete('content-type')
      } else if (config.headers) {
        delete config.headers['Content-Type']
        delete config.headers['content-type']
      }
    }

    const method = (config.method || '').toLowerCase()
    if (['post', 'put', 'patch', 'delete'].includes(method) && _csrfToken) {
      config.headers['X-CSRF-Token'] = _csrfToken
    }

    return config
  },
  (error) => {
    console.error('[API Request Error]', error)
    return Promise.reject(error)
  }
)

// Response interceptor for error handling
api.interceptors.response.use(
  (response) => {
    return response.data
  },
  (error) => {
    const requestUrl = error.config?.url || ''
    const validationMessage = getValidationMessage(error.response?.data)
    const message = validationMessage || error.response?.data?.message || error.message || 'An error occurred'

    if (error.response?.data && validationMessage) {
      error.response.data.message = validationMessage
    }

    error.__pamReported = true

    if (!requestUrl.includes(ERROR_REPORT_PATH)) {
      void reportClientError({
        source: 'frontend_api',
        error_kind: error.code === 'ECONNABORTED'
          ? 'timeout'
          : (error.response?.status ? 'api_error' : 'network_error'),
        message,
        stack_trace: error.stack,
        status_code: error.response?.status,
        status_text: error.response?.statusText,
        http_method: error.config?.method?.toUpperCase() || 'REQUEST',
        route_path: currentRoutePath(),
        request_url: requestUrl,
        endpoint: requestUrl,
        api_message: error.response?.data?.message
      })
    }
    
    // Redirect to login on 401 (except for auth endpoints)
    if (error.response?.status === 401 && !error.config?.url?.includes('/auth/')) {
      const currentPath = window.location.pathname || '/'
      if (!authRedirecting && currentPath !== '/login') {
        authRedirecting = true
        const redirectTarget = `${currentPath}${window.location.search || ''}`
        window.location.replace(`/login?redirect=${encodeURIComponent(redirectTarget)}`)
      }
    }
    
    return Promise.reject(error)
  }
)

// Auth API
export const authApi = {
  login: (username, password) => api.post('/auth/login', { username, password }),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
  check: () => api.get('/auth/check'),
}

// Suppliers API
export const suppliersApi = {
  getAll: () => api.get('/suppliers'),
  getById: (id) => api.get(`/suppliers/${id}`),
  create: (data) => api.post('/suppliers', data),
  update: (id, data) => api.put(`/suppliers/${id}`, data),
  delete: (id) => api.delete(`/suppliers/${id}`),
}

// Technicians API
export const techniciansApi = {
  getAll: () => api.get('/technicians'),
  getById: (id) => api.get(`/technicians/${id}`),
  create: (data) => api.post('/technicians', data),
  update: (id, data) => api.put(`/technicians/${id}`, data),
  delete: (id) => api.delete(`/technicians/${id}`),
}

// Units API
export const unitsApi = {
  getAll: () => api.get('/units'),
  getById: (id) => api.get(`/units/${id}`),
  create: (data) => api.post('/units', data),
  update: (id, data) => api.put(`/units/${id}`, data),
  delete: (id) => api.delete(`/units/${id}`),
}

// Parts API
export const partsApi = {
  getAll: (params) => api.get('/parts', { params }),
  getById: (id) => api.get(`/parts/${id}`),
  create: (data) => api.post('/parts', data),
  update: (id, data) => api.put(`/parts/${id}`, data),
  delete: (id) => api.delete(`/parts/${id}`),
  analyzeBulkImport: (items, config = {}) => api.post('/parts/bulk-import/analyze', { items }, {
    timeout: 120000,
    ...config
  }),
  bulkImport: (items, config = {}) => api.post('/parts/bulk-import', { items }, {
    timeout: 180000,
    ...config
  }),
  getFowlerMapping: (fowlerPartNumber) => api.get('/parts/fowler-mapping', {
    params: { fowler_pn: fowlerPartNumber }
  }),
  checkFowler: (fowlerPartNumber) => api.get('/parts/check-fowler', {
    params: { fowler_part_number: fowlerPartNumber }
  }),
  getNextFowler: () => api.get('/parts/next-fowler'),
  checkDuplicate: (supplierPartNumber) => api.get('/parts/check-duplicate', { params: { supplier_part_number: supplierPartNumber } }),
  getLowStock: () => api.get('/parts/low-stock'),
  getStock: (id) => api.get(`/parts/${id}/stock`),
  getLocations: (id) => api.get(`/parts/${id}/locations`),
}

// Work Orders API
export const workOrdersApi = {
  getAll: () => api.get('/work-orders'),
  getById: (id) => api.get(`/work-orders/${id}`),
  create: (data) => api.post('/work-orders', data),
  update: (id, data) => api.put(`/work-orders/${id}`, data),
  delete: (id) => api.delete(`/work-orders/${id}`),
  getOpen: () => api.get('/work-orders/open'),
}

// Asset API
export const assetApi = {
  getStockLevels: () => api.get('/inventory/stock-levels'),
  getTransactions: (params = {}) => {
    // Handle legacy calls where first arg might be partId
    if (typeof params !== 'object' && params !== null) {
      params = { part_id: params }
    }
    // Default limit only when not provided (allow 0 or 'all')
    if (params.limit === undefined || params.limit === null) params.limit = 50
    
    return api.get('/inventory/transactions', { params })
  },
  getReturnableItems: (referenceType, referenceId) => {
    return api.get('/inventory/returnable-items', { 
      params: { reference_type: referenceType, reference_id: referenceId } 
    })
  },
  processIncoming: (data) => api.post('/inventory/incoming', data),
  processCheckout: (data) => api.post('/inventory/checkout', data),
  processReturn: (data) => api.post('/inventory/return', data),
}

// Vendor Returns API
export const vendorReturnsApi = {
  getAll: () => api.get('/vendor-returns'),
  getById: (id) => api.get(`/vendor-returns/${id}`),
  create: (data) => api.post('/vendor-returns', data),
  update: (id, data) => api.put(`/vendor-returns/${id}`, data),
  delete: (id) => api.delete(`/vendor-returns/${id}`),
}

// Settings API
export const settingsApi = {
  get: () => api.get('/settings'),
  update: (data) => api.put('/settings', data),
  getDatabaseTables: () => api.get('/settings/database-tables'),
  truncateData: (payload) => api.post('/settings/truncate-data', payload)
}

// Reports API
export const reportsApi = {
  getSummary: (params = {}) => api.get('/reports/summary', { params }),
  getArchiveList: (params = {}) => api.get('/reports/archive', { params }),
  downloadArchive: (params) => api.get('/reports/archive/download', {
    params,
    responseType: 'blob',
    timeout: 60000
  }),
  archiveDownload: (formData) => api.post('/reports/archive', formData, {
    timeout: 60000
  })
}

// Audit Logs API
export const auditLogsApi = {
  getAll: (params = {}) => api.get('/audit-logs', { params }),
  getAccessStatus: () => api.get('/audit-logs/access-status'),
  confirmAccess: (password) => api.post('/audit-logs/confirm-access', { password }),
}

export default api

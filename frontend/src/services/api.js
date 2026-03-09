import axios from 'axios'

const envApiUrl = import.meta.env.VITE_API_URL

const normalizeUrl = (url) => url?.replace(/\/+$/, '')

const resolveApiBaseUrl = () => {
  if (typeof window === 'undefined') {
    return normalizeUrl(envApiUrl) || '/api'
  }

  if (!envApiUrl) {
    return '/api'
  }

  if (envApiUrl.startsWith('/')) {
    return normalizeUrl(envApiUrl)
  }

  try {
    const envUrl = new URL(envApiUrl)

    if (envUrl.hostname !== window.location.hostname) {
      envUrl.hostname = window.location.hostname
    }

    if (!envUrl.port && window.location.port) {
      envUrl.port = window.location.port
    }

    return normalizeUrl(envUrl.toString())
  } catch (error) {
    return '/api'
  }
}

const API_BASE_URL = resolveApiBaseUrl()

console.log('API Base URL:', API_BASE_URL)

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  timeout: 10000, // 10 second timeout
  withCredentials: true, // Enable sending cookies/session
})

let authRedirecting = false

// Request interceptor for logging
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

    console.log(`[API Request] ${config.method?.toUpperCase()} ${config.url}`)
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
    console.log(`[API Response] ${response.config.url}`, response.data)
    return response.data
  },
  (error) => {
    const message = error.response?.data?.message || error.message || 'An error occurred'
    const details = {
      url: error.config?.url,
      method: error.config?.method,
      status: error.response?.status,
      statusText: error.response?.statusText,
      message: message,
      data: error.response?.data
    }
    
    console.error('[API Error]', details)
    
    // Log to localStorage for debugging
    const errorLog = JSON.parse(localStorage.getItem('api_errors') || '[]')
    errorLog.push({
      timestamp: new Date().toISOString(),
      ...details
    })
    // Keep only last 50 errors
    if (errorLog.length > 50) errorLog.shift()
    localStorage.setItem('api_errors', JSON.stringify(errorLog))
    
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
  bulkImport: (items, config = {}) => api.post('/parts/bulk-import', { items }, {
    timeout: 60000,
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

export default api

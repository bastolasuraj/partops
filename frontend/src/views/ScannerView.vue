<template>
  <div class="max-w-3xl mx-auto">
    <div class="mb-8 slide-up">
      <h1 class="text-3xl font-bold text-gray-900">Scanner</h1>
      <p class="text-gray-600">Scan a part QR code using your phone camera</p>
    </div>

    <div class="card slide-up">
      <div class="card-header">
        <h2 class="text-xl font-semibold text-gray-800">Camera Scanner</h2>
      </div>

      <div class="p-6 flex flex-col items-center gap-6">
        <div class="w-64 h-64 md:w-72 md:h-72 rounded-2xl border-2 border-dashed border-blue-300 bg-blue-50 flex items-center justify-center overflow-hidden">
          <video
            ref="videoRef"
            class="w-full h-full object-cover"
            autoplay
            muted
            playsinline
          ></video>
        </div>

        <div class="flex items-center gap-3">
          <button
            v-if="!scanning"
            @click="startScanner"
            class="btn-primary"
          >
            Start Camera
          </button>
          <button
            v-else
            @click="stopScanner"
            class="btn-outline"
          >
            Stop Camera
          </button>
        </div>

        <div v-if="errorMessage" class="w-full bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-600">
          {{ errorMessage }}
        </div>

        <div class="w-full bg-gray-50 border border-gray-200 rounded-lg p-4">
          <div class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Scanned Value</div>
          <div class="mt-2 text-lg font-mono text-gray-900 break-all">
            {{ scannedText || '—' }}
          </div>
        </div>

        <div v-if="scanDetails.loading" class="w-full flex items-center gap-2 text-sm text-gray-600">
          <span class="spinner w-4 h-4"></span>
          Looking up part details...
        </div>

        <div v-if="scanDetails.error" class="w-full bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-600">
          {{ scanDetails.error }}
        </div>

        <div v-if="scanDetails.type !== 'idle' && !scanDetails.loading && !scanDetails.error" class="w-full">
          <div class="text-xs uppercase tracking-wide text-gray-500 font-semibold mb-2">
            {{ scanDetails.type === 'fowler' ? 'Fowler Part Results' : scanDetails.type === 'supplier' ? 'Supplier Part Result' : 'Scan Result' }}
          </div>

          <div v-if="scanDetails.parts.length === 0" class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-700">
            No matching parts found for <span class="font-mono">{{ scanDetails.value }}</span>.
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="part in scanDetails.parts"
              :key="part.id"
              class="border border-gray-200 rounded-xl p-4 bg-white"
            >
              <div class="flex items-start justify-between gap-4">
                <div>
                  <div class="text-lg font-semibold text-gray-900">{{ part.name }}</div>
                  <div class="text-sm text-gray-500">{{ part.supplier_name || 'N/A' }}</div>
                </div>
                <div class="text-sm font-mono text-blue-600">
                  {{ part.fowler_part_number }}
                </div>
              </div>
              <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                <div>
                  <div class="text-xs text-gray-500">Supplier PN</div>
                  <div class="font-mono text-gray-800">{{ part.supplier_part_number }}</div>
                </div>
                <div>
                  <div class="text-xs text-gray-500">Stock</div>
                  <div class="font-semibold text-gray-800">{{ part.stock }}</div>
                </div>
                <div>
                  <div class="text-xs text-gray-500">Location</div>
                  <div class="text-gray-800">{{ formatLocation(part) }}</div>
                </div>
                <div>
                  <div class="text-xs text-gray-500">Low Stock Threshold</div>
                  <div class="text-gray-800">{{ part.low_stock_threshold }}</div>
                </div>
              </div>
              <div v-if="Number(part.stock) > 0" class="mt-4">
                <form
                  class="inline-flex"
                  action="/outgoing"
                  method="get"
                  @submit="handleCheckoutSubmit"
                >
                  <input type="hidden" name="partId" :value="part.id" />
                  <button
                    type="submit"
                    class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700 cursor-pointer"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <line x1="12" y1="19" x2="12" y2="5"></line>
                      <polyline points="5 12 12 5 19 12"></polyline>
                    </svg>
                    Checkout this item
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>

        <p class="text-xs text-gray-500 text-center max-w-md">
          Align the QR code inside the window. The scanned text will appear above.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { BrowserQRCodeReader } from '@zxing/browser'
import { partsApi } from '@/services/api'

const route = useRoute()
const videoRef = ref(null)
const scannedText = ref('')
const errorMessage = ref('')
const scanning = ref(false)
const controlsRef = ref(null)
const readerRef = ref(null)
const lastValue = ref('')
const lastScanAt = ref(0)
const scanDetails = ref({
  type: 'idle',
  value: '',
  parts: [],
  loading: false,
  error: ''
})

const formatLocation = (part) => {
  if (part?.location_alt) {
    return part.location_alt
  }
  const loc = [part.location_aisle, part.location_shelf, part.location_bay].filter(Boolean)
  return loc.length > 0 ? loc.join('-') : '-'
}

const parsePamPayload = (text) => {
  const match = text.match(/^PAM:(SUPPLIER|FOWLER):(.+)$/i)
  if (match) {
    return {
      type: match[1].toLowerCase(),
      value: match[2].trim()
    }
  }
  return { type: 'unknown', value: text }
}

const parseScanPayload = (raw) => {
  const text = (raw || '').trim()
  if (!text) return { type: 'unknown', value: '', source: 'unknown', display: '' }

  if (/^https?:\/\//i.test(text)) {
    try {
      const url = new URL(text)
      const qrParam = url.searchParams.get('qr')
      const isScannerLink = url.pathname.endsWith('/scanner') && qrParam
      if (isScannerLink) {
        const parsed = parsePamPayload(qrParam)
        const sameOrigin = url.origin === window.location.origin
        return {
          ...parsed,
          source: sameOrigin ? 'app' : 'foreign',
          foreignOrigin: sameOrigin ? '' : url.origin,
          display: parsed.value || qrParam
        }
      }
    } catch (error) {
      // Fall through to treat as foreign
    }
  }

  const parsed = parsePamPayload(text)
  if (parsed.type !== 'unknown') {
    return { ...parsed, source: 'app', display: parsed.value }
  }

  return { type: 'foreign', value: text, source: 'foreign', display: text }
}

const lookupBySupplierPn = async (supplierPn) => {
  const check = await partsApi.checkDuplicate(supplierPn)
  const part = check.data?.part
  if (!part?.id) {
    return []
  }
  const detail = await partsApi.getById(part.id)
  return detail.data ? [detail.data] : []
}

const lookupByFowlerPn = async (fowlerPn) => {
  const response = await partsApi.getAll({ search: fowlerPn })
  const allParts = response.data || []
  return allParts.filter(part => part.fowler_part_number === fowlerPn)
}

const runLookup = async (payload) => {
  if (!payload?.value) {
    scanDetails.value = {
      type: payload?.type || 'unknown',
      value: '',
      parts: [],
      loading: false,
      error: 'Invalid scan value'
    }
    return
  }

  if (payload.source === 'foreign' || payload.type === 'foreign') {
    scanDetails.value = {
      type: 'foreign',
      value: payload.value,
      parts: [],
      loading: false,
      error: payload.foreignOrigin
        ? `This QR belongs to a different PAM site (${payload.foreignOrigin}).`
        : 'This QR code does not belong to PAM.'
    }
    return
  }

  scanDetails.value = {
    type: payload.type,
    value: payload.value,
    parts: [],
    loading: true,
    error: ''
  }

  try {
    if (payload.type === 'supplier') {
      scanDetails.value.parts = await lookupBySupplierPn(payload.value)
    } else if (payload.type === 'fowler') {
      scanDetails.value.parts = await lookupByFowlerPn(payload.value)
    } else {
      const supplierParts = await lookupBySupplierPn(payload.value)
      if (supplierParts.length) {
        scanDetails.value.type = 'supplier'
        scanDetails.value.parts = supplierParts
      } else {
        const fowlerParts = await lookupByFowlerPn(payload.value)
        scanDetails.value.type = fowlerParts.length ? 'fowler' : 'unknown'
        scanDetails.value.parts = fowlerParts
      }
    }
  } catch (error) {
    scanDetails.value.error = error?.response?.data?.message || error?.message || 'Failed to load part details'
  } finally {
    scanDetails.value.loading = false
  }
}

const handleScanText = (text) => {
  const payload = parseScanPayload(text)
  scannedText.value = payload.display || text
  runLookup(payload)
}

const startScanner = async () => {
  if (!videoRef.value) return
  errorMessage.value = ''

  try {
    if (!readerRef.value) {
      readerRef.value = new BrowserQRCodeReader()
    }

    if (controlsRef.value) {
      controlsRef.value.stop()
      controlsRef.value = null
    }

    scanning.value = true
    controlsRef.value = await readerRef.value.decodeFromVideoDevice(
      null,
      videoRef.value,
      (result, error) => {
        if (result) {
          const text = result.getText()
          const now = Date.now()
          if (text !== lastValue.value || now - lastScanAt.value > 1500) {
            lastValue.value = text
            lastScanAt.value = now
            handleScanText(text)
            stopScanner()
          }
        } else if (error && error.name !== 'NotFoundException') {
          // Ignore not-found while scanning
          console.error('Scanner error:', error)
        }
      }
    )
  } catch (error) {
    scanning.value = false
    errorMessage.value = error?.message || 'Unable to access camera'
  }
}

const stopScanner = () => {
  scanning.value = false
  if (controlsRef.value) {
    controlsRef.value.stop()
    controlsRef.value = null
  }
  if (readerRef.value) {
    readerRef.value.reset()
    readerRef.value = null
  }
}

const handleCheckoutSubmit = () => {
  stopScanner()
}

onMounted(() => {
  const qrParam = route.query.qr
  const qrValue = Array.isArray(qrParam) ? qrParam[0] : qrParam

  if (qrValue) {
    handleScanText(String(qrValue))
  } else {
    startScanner()
  }
})

onBeforeUnmount(() => {
  stopScanner()
})
</script>

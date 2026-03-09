import { ref } from 'vue'

const TOAST_TYPES = ['success', 'error', 'warning', 'info']

const toast = ref({
  show: false,
  title: '',
  message: '',
  type: 'success'
})

let timeoutId = null

export function useToast() {
  const normalizeType = (type) => {
    const normalized = String(type || '').toLowerCase()
    return TOAST_TYPES.includes(normalized) ? normalized : 'success'
  }

  // Backward compatible signatures:
  // 1) showToast(title, message, type)
  // 2) showToast(title, type)
  // 3) showToast(title, message)
  const showToast = (title, messageOrType = '', typeMaybe = 'success') => {
    if (timeoutId) {
      clearTimeout(timeoutId)
    }

    let message = ''
    let type = 'success'

    const secondArgLooksLikeType = TOAST_TYPES.includes(String(messageOrType || '').toLowerCase())
    const thirdArgLooksLikeType = TOAST_TYPES.includes(String(typeMaybe || '').toLowerCase())

    if (thirdArgLooksLikeType) {
      message = String(messageOrType || '')
      type = normalizeType(typeMaybe)
    } else if (secondArgLooksLikeType) {
      message = ''
      type = normalizeType(messageOrType)
    } else {
      message = String(messageOrType || '')
      type = normalizeType(typeMaybe)
    }
    
    toast.value = {
      show: true,
      title: String(title || ''),
      message,
      type
    }
    
    timeoutId = setTimeout(() => {
      toast.value.show = false
    }, 3000)
  }
  
  const hideToast = () => {
    toast.value.show = false
  }
  
  return {
    toast,
    showToast,
    hideToast
  }
}

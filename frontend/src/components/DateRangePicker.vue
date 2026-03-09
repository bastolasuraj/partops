<template>
  <div class="relative" ref="pickerRef">
    <label v-if="label" class="form-label">{{ label }}</label>
    
    <!-- Trigger Input -->
    <div 
      @click="togglePicker"
      class="form-input cursor-pointer flex items-center justify-between hover:border-blue-400 transition-colors"
      :class="{ 'border-blue-500 ring-2 ring-blue-100': isOpen }"
    >
      <div class="flex items-center gap-2 text-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="16" y1="2" x2="16" y2="6"></line>
          <line x1="8" y1="2" x2="8" y2="6"></line>
          <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
        <span v-if="formattedRange" class="text-gray-900 font-medium">{{ formattedRange }}</span>
        <span v-else class="text-gray-400">Select date range</span>
      </div>
      <button 
        v-if="modelValue.start || modelValue.end" 
        @click.stop="clearRange" 
        class="text-gray-400 hover:text-gray-600 p-1"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <!-- Calendar Popover -->
    <Transition name="fade">
      <div 
        v-if="isOpen" 
        class="absolute z-50 mt-2 bg-white rounded-xl shadow-xl border border-gray-200 p-4 w-[340px] right-0 sm:left-0 origin-top-left"
      >
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
          <button @click="prevMonth" class="p-1 hover:bg-gray-100 rounded-full transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
          </button>
          <span class="font-bold text-gray-800">{{ currentMonthName }} {{ currentYear }}</span>
          <button @click="nextMonth" class="p-1 hover:bg-gray-100 rounded-full transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </button>
        </div>

        <!-- Days Header -->
        <div class="grid grid-cols-7 mb-2">
          <div v-for="day in ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']" :key="day" class="text-center text-xs font-medium text-gray-400 py-1">
            {{ day }}
          </div>
        </div>

        <!-- Calendar Grid -->
        <div class="grid grid-cols-7 gap-y-1">
          <!-- Empty cells for start of month -->
          <div v-for="n in startDayOfWeek" :key="`empty-${n}`" class="h-10"></div>
          
          <!-- Days -->
          <button
            v-for="day in daysInMonth"
            :key="day"
            @click="selectDay(day)"
            class="h-10 w-full relative group"
            :class="getDayClass(day)"
            :disabled="isDateDisabled(day)"
          >
            <!-- Range Selection Background -->
            <div 
              v-if="isInRange(day)"
              class="absolute inset-y-0 bg-blue-50 z-0"
              :class="{
                'left-0 right-0': !isStart(day) && !isEnd(day),
                'left-1/2 right-0 rounded-l-full': isStart(day) && hasRange,
                'left-0 right-1/2 rounded-r-full': isEnd(day) && hasRange,
              }"
            ></div>

            <!-- Selection Circle -->
            <div 
              class="relative z-10 w-8 h-8 mx-auto flex items-center justify-center rounded-full text-sm transition-all"
              :class="getDayContentClass(day)"
            >
              {{ day }}
            </div>
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: Object, // { start: 'YYYY-MM-DD', end: 'YYYY-MM-DD' }
    default: () => ({ start: '', end: '' })
  },
  label: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue', 'change'])

const isOpen = ref(false)
const pickerRef = ref(null)
const viewingDate = ref(new Date())
// Used to track temporary selection state before emitting
const selection = ref({ start: null, end: null })

// Helper to parse 'YYYY-MM-DD' to Date object (local time)
const parseDate = (dateStr) => {
  if (!dateStr) return null
  const [y, m, d] = dateStr.split('-').map(Number)
  return new Date(y, m - 1, d)
}

// Helper to format Date object to 'YYYY-MM-DD'
const formatDateStr = (date) => {
  if (!date) return ''
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

// Sync internal state with props
watch(() => props.modelValue, (newVal) => {
  selection.value = {
    start: parseDate(newVal.start),
    end: parseDate(newVal.end)
  }
  // If start date exists, make sure we view that month
  if (selection.value.start && !isOpen.value) {
    viewingDate.value = new Date(selection.value.start)
  }
}, { immediate: true })

const currentMonthName = computed(() => {
  return viewingDate.value.toLocaleString('default', { month: 'long' })
})

const currentYear = computed(() => {
  return viewingDate.value.getFullYear()
})

const daysInMonth = computed(() => {
  const year = viewingDate.value.getFullYear()
  const month = viewingDate.value.getMonth()
  return new Date(year, month + 1, 0).getDate()
})

const startDayOfWeek = computed(() => {
  const year = viewingDate.value.getFullYear()
  const month = viewingDate.value.getMonth()
  return new Date(year, month, 1).getDay()
})

const formattedRange = computed(() => {
  const start = selection.value.start
  const end = selection.value.end
  
  if (!start && !end) return ''
  
  const options = { month: 'short', day: 'numeric' }
  const startStr = start ? start.toLocaleDateString('en-US', options) : '...'
  
  if (!end) return startStr
  if (start.getTime() === end.getTime()) return startStr
  
  const endStr = end.toLocaleDateString('en-US', options)
  return `${startStr} - ${endStr}`
})

const hasRange = computed(() => {
  return selection.value.start && selection.value.end && selection.value.start.getTime() !== selection.value.end.getTime()
})

const togglePicker = () => {
  isOpen.value = !isOpen.value
}

const prevMonth = () => {
  viewingDate.value = new Date(viewingDate.value.getFullYear(), viewingDate.value.getMonth() - 1, 1)
}

const nextMonth = () => {
  viewingDate.value = new Date(viewingDate.value.getFullYear(), viewingDate.value.getMonth() + 1, 1)
}

const isSameDay = (d1, d2) => {
  if (!d1 || !d2) return false
  return d1.getFullYear() === d2.getFullYear() && 
         d1.getMonth() === d2.getMonth() && 
         d1.getDate() === d2.getDate()
}

const getDateObj = (day) => {
  return new Date(viewingDate.value.getFullYear(), viewingDate.value.getMonth(), day)
}

const isStart = (day) => {
  const date = getDateObj(day)
  return isSameDay(date, selection.value.start)
}

const isEnd = (day) => {
  const date = getDateObj(day)
  return isSameDay(date, selection.value.end)
}

const isInRange = (day) => {
  if (!selection.value.start || !selection.value.end) return false
  const date = getDateObj(day)
  return date > selection.value.start && date < selection.value.end
}

const isDateDisabled = (day) => {
  const date = getDateObj(day)
  // Example: prevent future dates if needed, or implement min/max props
  // For now, no restrictions
  return false
}

const getDayClass = (day) => {
  // Base classes for the button wrapper
  return 'flex items-center justify-center'
}

const getDayContentClass = (day) => {
  if (isStart(day) || isEnd(day)) {
    return 'bg-blue-600 text-white font-bold shadow-md'
  }
  if (isInRange(day)) {
    return 'text-blue-700 font-medium'
  }
  const date = getDateObj(day)
  const today = new Date()
  if (isSameDay(date, today)) {
    return 'bg-gray-100 text-blue-600 font-bold border border-blue-200'
  }
  return 'text-gray-700 hover:bg-gray-100'
}

const selectDay = (day) => {
  const date = getDateObj(day)
  
  if (!selection.value.start || (selection.value.start && selection.value.end)) {
    // Start new selection
    selection.value.start = date
    selection.value.end = null
  } else {
    // Complete selection
    if (date < selection.value.start) {
      selection.value.end = selection.value.start
      selection.value.start = date
    } else {
      selection.value.end = date
    }
    // Auto close on range completion? Optional. Let's keep it open for review.
    // isOpen.value = false 
  }
  
  emitUpdate()
}

const clearRange = () => {
  selection.value = { start: null, end: null }
  emitUpdate()
}

const emitUpdate = () => {
  emit('update:modelValue', {
    start: formatDateStr(selection.value.start),
    end: formatDateStr(selection.value.end)
  })
  emit('change')
}

// Click outside to close
const handleClickOutside = (event) => {
  if (pickerRef.value && !pickerRef.value.contains(event.target)) {
    isOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>

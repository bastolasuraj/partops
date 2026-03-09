<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100">
    <div class="max-w-md w-full mx-4">
      <!-- Logo/Title -->
      <div class="text-center mb-8">
        <img src="@/assets/logo.png" alt="PAM" class="h-[140px] w-auto mx-auto mb-3 drop-shadow-sm" />
        <p class="text-lg text-gray-600 mb-4">Parts Asset Management</p>
      </div>

      <!-- Login Card -->
      <div class="bg-white rounded-lg shadow-xl p-8">
        <h2 class="text-2xl font-semibold text-gray-900 mb-6">Sign In</h2>

        <!-- Error Message -->
        <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
          <p class="text-sm text-red-600">{{ error }}</p>
        </div>

        <!-- Login Form -->
        <form @submit.prevent="handleLogin">
          <!-- Username -->
          <div class="mb-4">
            <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
              Username
            </label>
            <div class="username-group relative flex rounded-lg border border-gray-300 overflow-hidden focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-transparent">
              <input
                id="username"
                v-model="username"
                type="text"
                required
                autocomplete="username"
                class="flex-1 min-w-0 px-4 py-2 border-0 rounded-none focus:outline-none focus:ring-0"
                placeholder="Enter your username"
                :disabled="loading"
              />
              <span class="username-suffix inline-flex items-center bg-gray-50 px-3 text-gray-500 text-sm border-l border-gray-300">
                @fowler.ca
              </span>
            </div>
          </div>

          <!-- Password -->
          <div class="mb-6">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
              Password
            </label>
            <div class="relative">
              <input
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="current-password"
                class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Enter your password"
                :disabled="loading"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none"
                :disabled="loading"
              >
                <!-- Eye Icon (show password) -->
                <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <!-- Eye Off Icon (hide password) -->
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                  <line x1="1" y1="1" x2="23" y2="23"></line>
                </svg>
              </button>
            </div>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="loading"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="loading">Signing in...</span>
            <span v-else>Sign In</span>
          </button>
        </form>

        <!-- Info -->
        <div class="mt-6 text-center text-sm text-gray-600">
          <p>Use your LDAP credentials to sign in</p>
        </div>
      </div>

      <!-- Footer -->
      <div class="text-center mt-6 text-sm text-gray-600">
        <p>&copy; 2024 PAM - Parts Asset Management. All rights reserved.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { authApi } from '../services/api'
import { useToast } from '../composables/useToast'

const router = useRouter()
const route = useRoute()
const { showToast } = useToast()

const username = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)
const error = ref('')

const handleLogin = async () => {
  error.value = ''
  loading.value = true

  try {
    const response = await authApi.login(username.value, password.value)
    
    // Only show success if we actually got a successful response
    if (response?.data?.user) {
      showToast('Login successful!', 'success')
      const redirectTarget = route.query.redirect
      if (redirectTarget) {
        router.push(redirectTarget)
      } else {
        router.push('/')
      }
    } else {
      error.value = 'Login failed. Please try again.'
    }
  } catch (err) {
    console.error('Login error:', err)
    error.value = err.response?.data?.message || 'Invalid username or password'
    // Don't show toast for errors, just display in the error div
  } finally {
    loading.value = false
  }
}
</script>

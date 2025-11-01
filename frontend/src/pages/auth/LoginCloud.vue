<script setup>
import { LoaderCircle, Eye, EyeOff } from 'lucide-vue-next'
import { ref } from 'vue'
import { useAuthStore } from '../../composable/useAuth'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'

const router = useRouter()
const auth = useAuthStore()

const { login } = auth

const {
  errors,
  processing,
  remember
} = storeToRefs(auth)

const email = ref('')
const password = ref('')
const showPassword = ref(false)

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}

const handleLogin = async () => {
  try {
    // Set the auth store values
    auth.email = email.value
    auth.password = password.value
    await login()
  } catch (error) {
    console.error('Login failed:', error)
  }
}

const goToRequestAccess = () => {
  router.push('/request-account')
}
</script>

<template>
  <div class="h-screen flex bg-white overflow-hidden">
    <!-- Left Side - Login Form -->
    <div class="w-full lg:w-1/2 flex flex-col">
      <div class="flex-1 overflow-y-auto px-6 sm:px-12 lg:px-16 xl:px-20 py-12">
        <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="mb-10">
          <div class="flex items-center gap-2 mb-8">
            <img
              src="/src/assets/lb5.png"
              alt="LabTrack"
              class="h-17"
            />
          </div>
          <h1 class="text-[18px] font-semibold text-gray-900 mb-2">Sign in</h1>
          <p class="text-gray-600 text-sm">Sign in to use LabTrack</p>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleLogin" class="space-y-6">
          <!-- Email -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-900 mb-2">
              Email
            </label>
            <input
              id="email"
              type="email"
              required
              autofocus
              v-model="email"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent text-sm transition-all"
              :class="{ 'border-red-500 focus:ring-red-500': errors.email }"
            />
            <div v-if="errors.email" class="text-red-600 text-sm mt-1.5">
              {{ errors.email }}
            </div>
          </div>

          <!-- Password -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label for="password" class="block text-sm font-medium text-gray-900">
                Password
              </label>
              <a href="#" class="text-sm text-blue-600 hover:text-blue-700 transition-colors">
                Forgot password?
              </a>
            </div>
            <div class="relative">
              <input
                id="password"
                :type="showPassword ? 'text' : 'password'"
                required
                v-model="password"
                class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent text-sm transition-all pr-10"
                :class="{ 'border-red-500 focus:ring-red-500': errors.password }"
              />
              <button
                type="button"
                @click="togglePasswordVisibility"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
              >
                <EyeOff v-if="showPassword" class="h-5 w-5" />
                <Eye v-else class="h-5 w-5" />
              </button>
            </div>
            <div v-if="errors.password" class="text-red-600 text-sm mt-1.5">
              {{ errors.password }}
            </div>
          </div>

          <!-- Remember me -->
          <div class="flex items-center">
            <input
              type="checkbox"
              id="remember"
              v-model="remember"
              class="h-4 w-4 text-gray-900 border-gray-300 rounded focus:ring-2 focus:ring-gray-900"
            />
            <label for="remember" class="ml-2 text-sm text-gray-700">
              Remember me
            </label>
          </div>

          <!-- Submit Button -->
          <div>
            <button
              type="submit"
              class="w-full bg-gray-900 hover:bg-gray-800 text-white px-4 py-2.5 rounded-md font-medium text-sm transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              :disabled="processing"
            >
              <LoaderCircle
                v-if="processing"
                class="h-4 w-4 animate-spin"
              />
              <span v-else>Continue →</span>
            </button>
          </div>

          <!-- Footer Link -->
          <div class="text-center">
            <span class="text-sm text-gray-600">Don't have an account? </span>
            <button
              type="button"
              @click="goToRequestAccess"
              class="text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors"
            >
              Create account
            </button>
          </div>
        </form>
        </div>
      </div>
    </div>

    <!-- Right Side - Decorative (hidden on mobile) -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-gray-50 to-gray-100 items-center justify-center p-12">
      <div class="max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl p-8 border border-gray-200">
          <div class="space-y-6">
            <!-- Mock Dashboard Element -->
            <div class="flex items-center gap-3 pb-4 border-b border-gray-200">
              <div class="w-12 h-12 bg-gradient-to-br from-gray-800 to-gray-900 rounded-lg flex items-center justify-center">
                <img
                  src="/src/assets/LABTrackv2.png"
                  alt="LabTrack"
                  class="h-7 w-7"
                />
              </div>
              <div>
                <div class="text-sm">
                  <span class="font-semibold text-gray-900">Lab</span><span class="font-light text-gray-400">Track</span>
                </div>
                <div class="text-xs text-gray-500">Computer Laboratory Management</div>
              </div>
            </div>

            <!-- Mock Stats -->
            <div class="grid grid-cols-2 gap-4">
              <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-xs text-gray-500 mb-1">Active Computers</div>
                <div class="text-2xl font-semibold text-gray-900">24</div>
              </div>
              <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-xs text-gray-500 mb-1">Students Online</div>
                <div class="text-2xl font-semibold text-gray-900">18</div>
              </div>
            </div>

            <!-- Mock Activity -->
            <div class="space-y-3">
              <div class="text-xs font-medium text-gray-700 uppercase tracking-wider">
                Recent Activity
              </div>
              <div class="space-y-2">
                <div class="flex items-center gap-3 text-sm">
                  <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                  <span class="text-gray-600">Computer Lab 1 - Online</span>
                </div>
                <div class="flex items-center gap-3 text-sm">
                  <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                  <span class="text-gray-600">Computer Lab 2 - Active</span>
                </div>
                <div class="flex items-center gap-3 text-sm">
                  <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                  <span class="text-gray-600">Computer Lab 3 - Idle</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

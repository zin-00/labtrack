<script setup>
import { ref, computed } from 'vue'
import { storeToRefs } from 'pinia';
import { useAuthStore } from '../../composable/useAuth'
import { Eye, EyeOff, CheckCircle, AlertCircle } from 'lucide-vue-next'

const auth = useAuthStore()
const {
  requestData,
  isLoading,
  isSuccess,
  errorMessage,
} = storeToRefs(auth)

const { submitRequest, resetForm } = auth

const showPassword = ref(false)
const passwordTouched = ref(false)

const passwordStrength = computed(() => {
  const password = requestData.value.password || ''
  let score = 0
  let feedback = []

  if (password.length >= 8) score += 1
  else feedback.push('At least 8 characters')
  
  if (/[a-z]/.test(password)) score += 1
  else feedback.push('One lowercase letter')
  
  if (/[A-Z]/.test(password)) score += 1
  else feedback.push('One uppercase letter')
  
  if (/\d/.test(password)) score += 1
  else feedback.push('One number')

  const strength = score === 0 ? '' : score <= 1 ? 'weak' : score <= 2 ? 'fair' : score <= 3 ? 'good' : 'strong'
  
  return { score, strength, feedback }
})

// Form validation
const isValidEmail = computed(() => {
  const email = requestData.value.email || ''
  return email === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
})

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}

const onPasswordFocus = () => {
  passwordTouched.value = true
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-white p-4">
    <div class="bg-white w-full max-w-4xl border border-gray-200 rounded-lg shadow-sm">
      
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-900">Request Access</h2>
        <p class="text-gray-600 text-sm mt-1">Fill out the form below to request access to LabTrack</p>
      </div>

      <div class="p-6">
        <!-- Success State -->
        <div v-if="isSuccess" class="text-center py-8">
          <div class="mb-4">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto">
              <CheckCircle class="w-6 h-6 text-green-600" />
            </div>
          </div>
          <h3 class="text-lg font-semibold text-gray-800 mb-2">Request Submitted Successfully!</h3>
          <p class="text-gray-600 text-sm mb-6 max-w-md mx-auto">
            Your access request has been submitted and is now under review. 
            You'll receive an email notification once your request is processed.
          </p>
          <button 
            @click="resetForm"
            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md text-sm font-medium"
          >
            Submit Another Request
          </button>
        </div>

        <!-- Request Form -->
        <div v-else>
          <form @submit.prevent="submitRequest" class="space-y-5">
            
            <!-- Personal Information Section -->
            <div>
              <h3 class="text-base font-medium text-gray-900 mb-3 pb-1 border-b border-gray-200">
                Personal Information
              </h3>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Full Name -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Full Name *
                  </label>
                  <input 
                    v-model="requestData.fullname"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                    placeholder="Enter your full name"
                    :disabled="isLoading"
                    required
                  />
                </div>

                <!-- ID Number -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    ID Number *
                  </label>
                  <input 
                    v-model="requestData.id_number"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                    placeholder="Student or Employee ID"
                    :disabled="isLoading"
                    required
                  />
                </div>
              </div>
            </div>

            <!-- Account Information Section -->
            <div>
              <h3 class="text-base font-medium text-gray-900 mb-3 pb-1 border-b border-gray-200">
                Account Information
              </h3>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Email -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Email Address *
                  </label>
                  <input 
                    v-model="requestData.email"
                    type="email"
                    class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                    :class="{
                      'border-gray-300': isValidEmail,
                      'border-red-300': !isValidEmail
                    }"
                    placeholder="your.email@domain.com"
                    :disabled="isLoading"
                    required
                  />
                  <p v-if="!isValidEmail && requestData.email" class="text-xs text-red-600 mt-1">
                    Please enter a valid email address
                  </p>
                </div>

                <!-- Role -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Role *
                  </label>
                  <select 
                    v-model="requestData.role"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-sm"
                    :disabled="isLoading"
                    required
                  >
                    <option value="" disabled>Select your role</option>
                    <option value="student">Student</option>
                    <option value="faculty">Faculty Member</option>
                    <option value="staff">IT Staff</option>
                    <option value="admin">Administrator</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Security Section -->
            <div>
              <h3 class="text-base font-medium text-gray-900 mb-3 pb-1 border-b border-gray-200">
                Security
              </h3>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
                <!-- Password -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Password *
                  </label>
                  <div class="relative">
                    <input 
                      v-model="requestData.password"
                      :type="showPassword ? 'text' : 'password'"
                      class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                      placeholder="Create a password"
                      :disabled="isLoading"
                      required
                      minlength="8"
                      @focus="onPasswordFocus"
                    />
                    <button
                      type="button"
                      @click="togglePasswordVisibility"
                      class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                    >
                      <Eye v-if="!showPassword" class="w-4 h-4" />
                      <EyeOff v-else class="w-4 h-4" />
                    </button>
                  </div>
                </div>

                <!-- Confirm Password -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Confirm Password *
                  </label>
                  <input 
                    v-model="requestData.password_confirmation"
                    type="password"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                    placeholder="Confirm your password"
                    :disabled="isLoading"
                    required
                  />
                </div>
              </div>
              
              <!-- Password Strength Indicator -->
              <div v-if="passwordTouched && requestData.password" class="mt-3 max-w-md">
                <div class="flex items-center space-x-2 mb-2">
                  <div class="flex-1 bg-gray-200 rounded-full h-1.5">
                    <div 
                      class="h-1.5 rounded-full transition-all duration-300"
                      :class="{
                        'bg-red-500 w-1/4': passwordStrength.strength === 'weak',
                        'bg-orange-500 w-2/4': passwordStrength.strength === 'fair',
                        'bg-yellow-500 w-3/4': passwordStrength.strength === 'good',
                        'bg-green-500 w-full': passwordStrength.strength === 'strong'
                      }"
                    ></div>
                  </div>
                  <span class="text-xs font-medium capitalize"
                    :class="{
                      'text-red-600': passwordStrength.strength === 'weak',
                      'text-orange-600': passwordStrength.strength === 'fair',
                      'text-yellow-600': passwordStrength.strength === 'good',
                      'text-green-600': passwordStrength.strength === 'strong'
                    }"
                  >
                    {{ passwordStrength.strength }}
                  </span>
                </div>
                
                <div v-if="passwordStrength.feedback.length > 0" class="text-xs text-gray-600">
                  <p class="mb-1">Password should include:</p>
                  <ul class="list-disc list-inside space-y-0.5">
                    <li v-for="item in passwordStrength.feedback" :key="item">{{ item }}</li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Error Message -->
            <div v-if="errorMessage" class="bg-red-50 border border-red-200 rounded-md p-3 flex items-start space-x-2">
              <AlertCircle class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" />
              <div>
                <p class="text-sm text-red-700">{{ errorMessage }}</p>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
              <button 
                type="button"
                @click="$router.push('/login')"
                class="text-gray-600 hover:text-gray-800 text-sm font-medium"
                :disabled="isLoading"
              >
                ← Back to Sign In
              </button>
              
              <div class="flex space-x-3">
                <button 
                  type="reset"
                  class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm font-medium"
                  :disabled="isLoading"
                >
                  Clear Form
                </button>
                <button 
                  type="submit"
                  class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium flex items-center disabled:opacity-50 disabled:cursor-not-allowed"
                  :disabled="isLoading || !isValidEmail"
                >
                  <svg v-if="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                  </svg>
                  {{ isLoading ? 'Submitting Request...' : 'Submit Request' }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
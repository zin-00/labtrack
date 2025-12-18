<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { LoaderCircle, Mail, Key, Lock, Eye, EyeOff, ArrowLeft, CheckCircle } from 'lucide-vue-next'
import axios from 'axios'
import { useApiUrl } from '../../api/api'

const router = useRouter()
const { api } = useApiUrl()

// Steps: 1 = Enter Email, 2 = Enter OTP, 3 = New Password, 4 = Success
const currentStep = ref(1)
const processing = ref(false)
const error = ref('')
const success = ref('')

// Form data
const email = ref('')
const otp = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)
const showConfirmPassword = ref(false)

// OTP inputs for individual boxes
const otpDigits = ref(['', '', '', '', '', ''])

const stepTitle = computed(() => {
  switch (currentStep.value) {
    case 1: return 'Forgot Password'
    case 2: return 'Verify OTP'
    case 3: return 'Reset Password'
    case 4: return 'Password Reset'
    default: return 'Forgot Password'
  }
})

const stepDescription = computed(() => {
  switch (currentStep.value) {
    case 1: return 'Enter your email address and we\'ll send you an OTP to reset your password.'
    case 2: return `We've sent a 6-digit OTP to ${email.value}. Please enter it below.`
    case 3: return 'Create a new password for your account.'
    case 4: return 'Your password has been successfully reset.'
    default: return ''
  }
})

// Handle OTP input
const handleOtpInput = (index, event) => {
  const value = event.target.value
  
  // Only allow digits
  if (!/^\d*$/.test(value)) {
    otpDigits.value[index] = ''
    return
  }
  
  otpDigits.value[index] = value.slice(-1) // Only keep last digit
  
  // Move to next input if digit entered
  if (value && index < 5) {
    const nextInput = document.querySelector(`input[data-otp-index="${index + 1}"]`)
    if (nextInput) nextInput.focus()
  }
  
  // Update combined OTP
  otp.value = otpDigits.value.join('')
}

const handleOtpKeydown = (index, event) => {
  // Handle backspace
  if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
    const prevInput = document.querySelector(`input[data-otp-index="${index - 1}"]`)
    if (prevInput) {
      prevInput.focus()
      otpDigits.value[index - 1] = ''
    }
  }
}

const handleOtpPaste = (event) => {
  event.preventDefault()
  const pastedData = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6)
  
  for (let i = 0; i < 6; i++) {
    otpDigits.value[i] = pastedData[i] || ''
  }
  otp.value = otpDigits.value.join('')
  
  // Focus last filled input or first empty
  const lastIndex = Math.min(pastedData.length, 5)
  const input = document.querySelector(`input[data-otp-index="${lastIndex}"]`)
  if (input) input.focus()
}

// API calls
const sendOtp = async () => {
  if (!email.value) {
    error.value = 'Please enter your email address.'
    return
  }
  
  processing.value = true
  error.value = ''
  
  try {
    await axios.post(`${api}/auth/forgot-password/send-otp`, {
      email: email.value
    })
    success.value = 'OTP sent successfully!'
    currentStep.value = 2
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to send OTP. Please try again.'
  } finally {
    processing.value = false
  }
}

const verifyOtp = async () => {
  if (otp.value.length !== 6) {
    error.value = 'Please enter the complete 6-digit OTP.'
    return
  }
  
  processing.value = true
  error.value = ''
  
  try {
    await axios.post(`${api}/auth/forgot-password/verify-otp`, {
      email: email.value,
      otp: otp.value
    })
    success.value = 'OTP verified successfully!'
    currentStep.value = 3
  } catch (err) {
    error.value = err.response?.data?.message || 'Invalid OTP. Please try again.'
  } finally {
    processing.value = false
  }
}

const resetPassword = async () => {
  if (!password.value || !passwordConfirmation.value) {
    error.value = 'Please fill in all fields.'
    return
  }
  
  if (password.value.length < 8) {
    error.value = 'Password must be at least 8 characters long.'
    return
  }
  
  if (password.value !== passwordConfirmation.value) {
    error.value = 'Passwords do not match.'
    return
  }
  
  processing.value = true
  error.value = ''
  
  try {
    await axios.post(`${api}/auth/forgot-password/reset`, {
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value
    })
    currentStep.value = 4
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to reset password. Please try again.'
  } finally {
    processing.value = false
  }
}

const resendOtp = async () => {
  processing.value = true
  error.value = ''
  success.value = ''
  
  try {
    await axios.post(`${api}/auth/forgot-password/resend-otp`, {
      email: email.value
    })
    success.value = 'New OTP sent successfully!'
    // Clear OTP inputs
    otpDigits.value = ['', '', '', '', '', '']
    otp.value = ''
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to resend OTP. Please try again.'
  } finally {
    processing.value = false
  }
}

const goBack = () => {
  if (currentStep.value === 1) {
    router.push('/login')
  } else if (currentStep.value === 4) {
    router.push('/login')
  } else {
    error.value = ''
    success.value = ''
    currentStep.value--
  }
}

const goToLogin = () => {
  router.push('/login')
}
</script>

<template>
  <div class="h-screen flex bg-white overflow-hidden">
    <!-- Left Side - Form -->
    <div class="w-full lg:w-1/2 flex flex-col">
      <div class="flex-1 overflow-y-auto px-6 sm:px-12 lg:px-16 xl:px-20 py-12">
        <div class="w-full max-w-md">
          <!-- Logo -->
          <div class="mb-10">
            <div class="flex items-center gap-2 mb-8">
              <img
                src="/src/assets/sfxclogov2.png"
                alt="LabTrack"
                class="h-17"
              />
            </div>
            <h1 class="text-[18px] font-semibold text-gray-900 mb-2">{{ stepTitle }}</h1>
            <p class="text-gray-600 text-sm">{{ stepDescription }}</p>
          </div>

          <!-- Progress Steps -->
          <div class="flex items-center justify-between mb-8" v-if="currentStep < 4">
            <div 
              v-for="step in 3" 
              :key="step"
              class="flex items-center"
              :class="{ 'flex-1': step < 3 }"
            >
              <div 
                class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-all"
                :class="{
                  'bg-green-900 text-white': currentStep >= step,
                  'bg-gray-200 text-gray-500': currentStep < step
                }"
              >
                {{ step }}
              </div>
              <div 
                v-if="step < 3"
                class="flex-1 h-1 mx-2 rounded transition-all"
                :class="{
                  'bg-green-900': currentStep > step,
                  'bg-gray-200': currentStep <= step
                }"
              />
            </div>
          </div>

          <!-- Error Message -->
          <div 
            v-if="error" 
            class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mb-6 text-sm"
          >
            {{ error }}
          </div>

          <!-- Success Message -->
          <div 
            v-if="success && currentStep !== 4" 
            class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md mb-6 text-sm"
          >
            {{ success }}
          </div>

          <!-- Step 1: Enter Email -->
          <form v-if="currentStep === 1" @submit.prevent="sendOtp" class="space-y-6">
            <div>
              <label for="email" class="block text-sm font-medium text-gray-900 mb-2">
                Email Address
              </label>
              <div class="relative">
                <Mail class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                <input
                  id="email"
                  type="email"
                  required
                  autofocus
                  v-model="email"
                  placeholder="Enter your email address"
                  class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-700 focus:border-transparent text-sm transition-all"
                />
              </div>
            </div>

            <div class="space-y-3">
              <button
                type="submit"
                class="w-full bg-green-900 hover:bg-green-800 text-white px-4 py-2.5 rounded-md font-medium text-sm transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                :disabled="processing"
              >
                <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                <span v-else>Send OTP</span>
              </button>
              
              <button
                type="button"
                @click="goBack"
                class="w-full border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2.5 rounded-md font-medium text-sm transition-all duration-200 flex items-center justify-center gap-2"
              >
                <ArrowLeft class="h-4 w-4" />
                Back to Login
              </button>
            </div>
          </form>

          <!-- Step 2: Enter OTP -->
          <form v-if="currentStep === 2" @submit.prevent="verifyOtp" class="space-y-6">
            <div>
              <label class="block text-sm font-medium text-gray-900 mb-4">
                Enter OTP Code
              </label>
              <div class="flex gap-2 justify-center" @paste="handleOtpPaste">
                <input
                  v-for="(digit, index) in otpDigits"
                  :key="index"
                  type="text"
                  inputmode="numeric"
                  maxlength="1"
                  :data-otp-index="index"
                  :value="digit"
                  @input="handleOtpInput(index, $event)"
                  @keydown="handleOtpKeydown(index, $event)"
                  class="w-12 h-14 text-center text-xl font-semibold border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-700 focus:border-transparent transition-all"
                  :class="{ 'border-green-700': digit }"
                />
              </div>
            </div>

            <div class="text-center">
              <span class="text-sm text-gray-600">Didn't receive the code? </span>
              <button
                type="button"
                @click="resendOtp"
                :disabled="processing"
                class="text-sm text-green-700 hover:text-green-800 font-medium transition-colors disabled:opacity-50"
              >
                Resend OTP
              </button>
            </div>

            <div class="space-y-3">
              <button
                type="submit"
                class="w-full bg-green-900 hover:bg-green-800 text-white px-4 py-2.5 rounded-md font-medium text-sm transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                :disabled="processing || otp.length !== 6"
              >
                <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                <span v-else>Verify OTP</span>
              </button>
              
              <button
                type="button"
                @click="goBack"
                class="w-full border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2.5 rounded-md font-medium text-sm transition-all duration-200 flex items-center justify-center gap-2"
              >
                <ArrowLeft class="h-4 w-4" />
                Back
              </button>
            </div>
          </form>

          <!-- Step 3: New Password -->
          <form v-if="currentStep === 3" @submit.prevent="resetPassword" class="space-y-6">
            <div>
              <label for="password" class="block text-sm font-medium text-gray-900 mb-2">
                New Password
              </label>
              <div class="relative">
                <Lock class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                <input
                  id="password"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  v-model="password"
                  placeholder="Enter new password"
                  class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-700 focus:border-transparent text-sm transition-all"
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                >
                  <EyeOff v-if="showPassword" class="h-5 w-5" />
                  <Eye v-else class="h-5 w-5" />
                </button>
              </div>
              <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters</p>
            </div>

            <div>
              <label for="password_confirmation" class="block text-sm font-medium text-gray-900 mb-2">
                Confirm Password
              </label>
              <div class="relative">
                <Lock class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                <input
                  id="password_confirmation"
                  :type="showConfirmPassword ? 'text' : 'password'"
                  required
                  v-model="passwordConfirmation"
                  placeholder="Confirm new password"
                  class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-700 focus:border-transparent text-sm transition-all"
                />
                <button
                  type="button"
                  @click="showConfirmPassword = !showConfirmPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                >
                  <EyeOff v-if="showConfirmPassword" class="h-5 w-5" />
                  <Eye v-else class="h-5 w-5" />
                </button>
              </div>
            </div>

            <div class="space-y-3">
              <button
                type="submit"
                class="w-full bg-green-900 hover:bg-green-800 text-white px-4 py-2.5 rounded-md font-medium text-sm transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                :disabled="processing"
              >
                <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                <span v-else>Reset Password</span>
              </button>
              
              <button
                type="button"
                @click="goBack"
                class="w-full border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2.5 rounded-md font-medium text-sm transition-all duration-200 flex items-center justify-center gap-2"
              >
                <ArrowLeft class="h-4 w-4" />
                Back
              </button>
            </div>
          </form>

          <!-- Step 4: Success -->
          <div v-if="currentStep === 4" class="text-center space-y-6">
            <div class="flex justify-center">
              <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
                <CheckCircle class="h-10 w-10 text-green-600" />
              </div>
            </div>
            
            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Password Reset Successful!</h3>
              <p class="text-gray-600 text-sm">
                Your password has been successfully reset. You can now login with your new password.
              </p>
            </div>

            <button
              type="button"
              @click="goToLogin"
              class="w-full bg-green-900 hover:bg-green-800 text-white px-4 py-2.5 rounded-md font-medium text-sm transition-all duration-200 flex items-center justify-center gap-2"
            >
              Go to Login
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Side - 3D RFID Card (hidden on mobile) -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-gray-50 to-gray-100 items-center justify-center p-12">
      <div class="rfid-card-container">
        <!-- 3D RFID Card -->
        <div class="rfid-card">
          <!-- Card Front -->
          <div class="card-front">
            <!-- Chip -->
            <div class="chip">
              <div class="chip-line"></div>
              <div class="chip-line"></div>
              <div class="chip-line"></div>
              <div class="chip-line"></div>
              <div class="chip-main"></div>
            </div>
            
            <!-- RFID Waves -->
            <div class="rfid-waves">
              <div class="wave wave-1"></div>
              <div class="wave wave-2"></div>
              <div class="wave wave-3"></div>
              <div class="wave wave-4"></div>
            </div>
            
            <!-- Card Content -->
            <div class="card-content">
              <div class="card-logo">
                <span class="font-bold">Lab</span><span class="font-light">Track</span>
              </div>
              <div class="card-id">ID: XXXX-XXXX-XXXX</div>
            </div>
            
            <!-- Card Footer -->
            <div class="card-footer">
              <div class="card-label">ACCESS CARD</div>
              <div class="card-type">RFID</div>
            </div>
          </div>
        </div>
        
        <!-- Floating text -->
        <div class="mt-8 text-center">
          <p class="text-gray-500 text-sm">Tap your RFID card to access</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.rfid-card-container {
  perspective: 1000px;
}

.rfid-card {
  width: 320px;
  height: 200px;
  position: relative;
  transform-style: preserve-3d;
  animation: float 6s ease-in-out infinite;
}

.card-front {
  width: 100%;
  height: 100%;
  background: linear-gradient(145deg, #166534 0%, #14532d 50%, #166534 100%);
  border-radius: 16px;
  padding: 24px;
  position: relative;
  box-shadow: 
    0 25px 50px -12px rgba(0, 0, 0, 0.4),
    0 0 0 1px rgba(255, 255, 255, 0.1),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
  transform: rotateY(-15deg) rotateX(5deg);
  transition: transform 0.3s ease;
}

.rfid-card:hover .card-front {
  transform: rotateY(-5deg) rotateX(2deg);
}

/* Chip styles */
.chip {
  width: 45px;
  height: 35px;
  background: linear-gradient(135deg, #e8e8e8 0%, #c0c0c0 50%, #e8e8e8 100%);
  border-radius: 6px;
  position: relative;
  overflow: hidden;
  box-shadow: 
    inset 0 1px 2px rgba(255, 255, 255, 0.8),
    0 2px 4px rgba(0, 0, 0, 0.3);
}

.chip-line {
  position: absolute;
  background: #a0a0a0;
}

.chip-line:nth-child(1) {
  width: 100%;
  height: 1px;
  top: 33%;
}

.chip-line:nth-child(2) {
  width: 100%;
  height: 1px;
  top: 66%;
}

.chip-line:nth-child(3) {
  width: 1px;
  height: 100%;
  left: 33%;
}

.chip-line:nth-child(4) {
  width: 1px;
  height: 100%;
  left: 66%;
}

.chip-main {
  position: absolute;
  width: 33%;
  height: 33%;
  background: linear-gradient(135deg, #d0d0d0 0%, #b0b0b0 100%);
  top: 33%;
  left: 33%;
  border-radius: 2px;
}

/* RFID Waves */
.rfid-waves {
  position: absolute;
  top: 20px;
  right: 24px;
  width: 50px;
  height: 50px;
}

.wave {
  position: absolute;
  border: 2px solid rgba(250, 204, 21, 0.5);
  border-radius: 50%;
  animation: pulse 2s ease-in-out infinite;
}

.wave-1 {
  width: 15px;
  height: 15px;
  top: 17px;
  left: 17px;
  animation-delay: 0s;
}

.wave-2 {
  width: 25px;
  height: 25px;
  top: 12px;
  left: 12px;
  animation-delay: 0.2s;
}

.wave-3 {
  width: 35px;
  height: 35px;
  top: 7px;
  left: 7px;
  animation-delay: 0.4s;
}

.wave-4 {
  width: 45px;
  height: 45px;
  top: 2px;
  left: 2px;
  animation-delay: 0.6s;
}

/* Card Content */
.card-content {
  position: absolute;
  bottom: 60px;
  left: 24px;
}

.card-logo {
  font-size: 24px;
  color: #facc15;
  letter-spacing: 1px;
}

.card-logo .font-bold {
  font-weight: 700;
}

.card-logo .font-light {
  font-weight: 300;
  color: white;
}

.card-id {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.7);
  font-family: 'Courier New', monospace;
  letter-spacing: 2px;
  margin-top: 4px;
}

/* Card Footer */
.card-footer {
  position: absolute;
  bottom: 24px;
  left: 24px;
  right: 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-label {
  font-size: 10px;
  color: white;
  letter-spacing: 3px;
  font-weight: 500;
}

.card-type {
  font-size: 10px;
  color: #facc15;
  letter-spacing: 2px;
  padding: 4px 8px;
  border: 1px solid #facc15;
  border-radius: 4px;
}

/* Animations */
@keyframes float {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-15px);
  }
}

@keyframes pulse {
  0%, 100% {
    opacity: 0.3;
    transform: scale(1);
  }
  50% {
    opacity: 0.6;
    transform: scale(1.05);
  }
}
</style>

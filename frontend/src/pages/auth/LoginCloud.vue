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

const goToForgotPassword = () => {
  router.push('/forgot-password')
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
              <button 
                type="button"
                @click="goToForgotPassword"
                class="text-sm text-blue-600 hover:text-blue-700 transition-colors"
              >
                Forgot password?
              </button>
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
  background: linear-gradient(145deg, #1a1a1a 0%, #0a0a0a 50%, #1a1a1a 100%);
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
  border: 2px solid rgba(255, 255, 255, 0.3);
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
  color: white;
  letter-spacing: 1px;
}

.card-logo .font-bold {
  font-weight: 700;
}

.card-logo .font-light {
  font-weight: 300;
  color: rgba(255, 255, 255, 0.6);
}

.card-id {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.4);
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
  color: rgba(255, 255, 255, 0.5);
  letter-spacing: 3px;
  font-weight: 500;
}

.card-type {
  font-size: 10px;
  color: rgba(255, 255, 255, 0.5);
  letter-spacing: 2px;
  padding: 4px 8px;
  border: 1px solid rgba(255, 255, 255, 0.2);
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

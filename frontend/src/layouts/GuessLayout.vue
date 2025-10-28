<script setup>

</script>

<template>
  <div class="min-h-screen bg-white">
    <!-- Header -->
    <header class="fixed w-full top-0 z-50 bg-white/95 backdrop-blur-sm border-b border-gray-100">
      <nav class="max-w-6xl mx-auto px-6 py-4">
        <div class="flex items-center justify-between">
          <!-- Logo -->
          <div class="flex items-center space-x-2">
            <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
              </svg>
            </div>
            <span class="text-xl font-semibold text-gray-900">Lab<span class="text-green-600">Track</span></span>
          </div>

          <!-- Navigation -->
          <ul class="hidden md:flex items-center space-x-8">
            <!-- <li>
              <a href="/" class="text-gray-700 hover:text-green-600 transition-colors duration-200 font-medium">
                Home
              </a>
            </li>
            <li>
              <a href="/about" class="text-gray-700 hover:text-green-600 transition-colors duration-200 font-medium">
                About
              </a>
            </li>
            <li>
              <a href="/contact" class="text-gray-700 hover:text-green-600 transition-colors duration-200 font-medium">
                Contact
              </a>
            </li>
            <li>
              <a href="/login" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200 font-medium">
                Login
              </a>
            </li> -->

            <router-link to="/" class="text-gray-700">Home</router-link>
            <router-link to="/about" class="text-gray-700">About</router-link>
            <router-link to="/contact" class="text-gray-700">Contact</router-link>
            <router-link to="/login" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200 font-medium">
              Login
            </router-link>
          </ul>

          <!-- Mobile Menu Button -->
          <button class="md:hidden p-2 text-gray-700 hover:text-green-600 transition-colors duration-200">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
        </div>

        <!-- Mobile Menu (hidden by default) -->
        <div class="md:hidden mt-4 pt-4 border-t border-gray-100 hidden">
          <ul class="space-y-4">
            <li>
              <a href="/" class="block text-gray-700 hover:text-green-600 transition-colors duration-200 font-medium">
                Home
              </a>
            </li>
            <li>
              <a href="/about" class="block text-gray-700 hover:text-green-600 transition-colors duration-200 font-medium">
                About
              </a>
            </li>
            <li>
              <a href="/contact" class="block text-gray-700 hover:text-green-600 transition-colors duration-200 font-medium">
                Contact
              </a>
            </li>
            <li>
              <a href="/login" class="inline-block bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200 font-medium">
                Login
              </a>
            </li>
          </ul>
        </div>
      </nav>
    </header>

    <!-- Main Content -->
    <main class="pt-20">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-gray-50 border-t border-gray-100">
      <div class="max-w-6xl mx-auto px-6 py-8">
        <div class="flex flex-col md:flex-row justify-between items-center">
          <div class="flex items-center space-x-2 mb-4 md:mb-0">
            <div class="w-6 h-6 bg-green-600 rounded-full flex items-center justify-center">
              <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
              </svg>
            </div>
            <span class="text-gray-700 font-medium">Lab<span class="text-green-600">Track</span></span>
          </div>

          <div class="text-center md:text-right">
            <p class="text-sm text-gray-500 mb-1">
              Saint Francis Xavier College
            </p>
            <p class="text-xs text-gray-400">
              © 2024 LabTrack. All rights reserved.
            </p>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
/* Smooth scrolling */
html {
  scroll-behavior: smooth;
}

/* Focus states for accessibility */
a:focus, button:focus {
  outline: 2px solid #16a34a;
  outline-offset: 2px;
}

/* Header backdrop blur effect */
.backdrop-blur-sm {
  backdrop-filter: blur(8px);
}

/* Ensure proper spacing for fixed header */
.pt-20 {
  padding-top: 5rem;
}

/* Mobile menu animation (can be enhanced with Vue transitions) */
.mobile-menu-enter-active,
.mobile-menu-leave-active {
  transition: all 0.3s ease;
}

.mobile-menu-enter-from,
.mobile-menu-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
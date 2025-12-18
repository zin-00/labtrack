<script setup>
import { watch } from 'vue';

const props = defineProps({
  isLoading: {
    type: Boolean,
    default: false
  },
  subMessage: {
    type: String,
    default: 'Please wait while we fetch your data'
  }
});

// Hide overflow when loading
watch(() => props.isLoading, (newVal) => {
  if (newVal) {
    document.body.style.overflowX = 'hidden';
    document.body.style.overflowY = 'hidden';
  } else {
    document.body.style.overflowX = '';
    document.body.style.overflowY = '';
  }
});
</script>

<template>
  <Transition
    enter-active-class="transition-opacity duration-300"
    leave-active-class="transition-opacity duration-300"
    enter-from-class="opacity-0"
    leave-to-class="opacity-0"
  >
    <div 
      v-if="isLoading"
      class="fixed inset-0 bg-white/40 z-50 flex items-center justify-center backdrop-blur-sm"
    >
      <div class="text-center">
        <!-- Animated Circular Spinner -->
        <div class="relative mx-auto mb-8 flex flex-col items-center">
          <!-- Outer rotating ring -->
          <div class="relative w-16 h-16">
            <div class="absolute inset-0 rounded-full border-2 border-gray-200"></div>
            <div class="absolute inset-0 rounded-full border-2 border-transparent border-t-gray-800 border-r-gray-600 animate-spin"></div>
          </div>
          
          <!-- Inner pulse circle -->
          <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
            <div class="w-8 h-8 bg-gray-800 rounded-full opacity-20 animate-ping"></div>
            <div class="absolute inset-0 w-8 h-8 bg-gray-700 rounded-full opacity-40"></div>
          </div>
        </div>
        
        <!-- Loading Text with Animation -->
        <div class="space-y-3">
          <div class="flex items-center justify-center gap-1.5">
            <span class="w-2 h-2 bg-gray-800 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
            <span class="w-2 h-2 bg-gray-600 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
          </div>
          <p class="text-gray-600 text-sm font-medium tracking-wide">{{ subMessage }}</p>
        </div>
        
        <!-- Progress Bar with Shimmer Effect -->
        <div class="w-72 h-1 bg-gray-200 rounded-full overflow-hidden mt-8 mx-auto relative">
          <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/50 to-transparent animate-[shimmer_2s_ease-in-out_infinite]"></div>
          <div class="h-full bg-gradient-to-r from-gray-500 via-gray-800 to-gray-500 rounded-full animate-[loading_1.8s_ease-in-out_infinite]"></div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
@keyframes loading {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

/* Smooth spin animation */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
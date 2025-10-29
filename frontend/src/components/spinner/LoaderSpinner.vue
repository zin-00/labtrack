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
      class="absolute inset-0 bg-gradient-to-br from-gray-50 via-white to-gray-100 z-50 flex items-center justify-center backdrop-blur-sm"
    >
      <div class="text-center">
        <!-- Animated Container -->
        <div class="relative mx-auto mb-6 flex flex-col items-center">
          <!-- Pulsing Background Circle -->
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="w-32 h-32 bg-gray-200 rounded-full animate-ping opacity-20"></div>
          </div>
          
          <!-- Logo with Shadow -->
          <div class="relative mb-4 transform transition-transform duration-700 hover:scale-110">
            <img 
              src="../../assets/LABTrackv2.png" 
              alt="LABTrack Logo" 
              class="h-24 w-24 object-contain drop-shadow-lg animate-pulse"
            />
          </div>
        </div>
        
        <!-- Loading Text with Animation -->
        <div class="space-y-2 mt-0">
          <div class="flex items-center justify-center gap-1">
            <span class="w-2 h-2 bg-gray-800 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
            <span class="w-2 h-2 bg-gray-600 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
          </div>
          <p class="text-gray-500 text-xs mt-2">{{ subMessage }}</p>
        </div>
        
        <!-- Progress Bar -->
        <div class="w-64 h-1 bg-gray-200 rounded-full overflow-hidden mt-6 mx-auto">
          <div class="h-full bg-gradient-to-r from-gray-600 via-gray-800 to-gray-600 rounded-full animate-[loading_1.5s_ease-in-out_infinite]"></div>
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
</style>
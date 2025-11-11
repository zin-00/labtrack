<template>
  <div
    v-if="toast.title || toast.description"
    :class="[
      'bg-white border border-gray-200 rounded-lg shadow-lg p-4 mb-3 w-[356px]',
      'flex items-start gap-3 transition-all duration-300 ease-in-out',
      isExiting 
        ? 'opacity-0 translate-x-full scale-95' 
        : 'opacity-100 translate-x-0 scale-100 animate-slide-in'
    ]"
    role="alert"
  >
    <!-- Icon -->
    <div v-if="variantClasses.icon" class="flex-shrink-0 mt-0.5">
      <component :is="variantClasses.icon" :class="['w-5 h-5', variantClasses.iconColor]" />
    </div>
    
    <!-- Content -->
    <div class="flex-1 min-w-0">
      <div v-if="toast.title" :class="['font-semibold text-sm mb-1', variantClasses.titleColor]">
        {{ toast.title }}
      </div>
      <div v-if="toast.description" class="text-sm text-gray-600">
        {{ toast.description }}
      </div>
    </div>

    <!-- Close Button -->
    <button
      @click="handleClose"
      class="text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0 -mr-1 -mt-1"
      aria-label="Close"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  toast: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close']);

const isExiting = ref(false);
let timer = null;

// Icons as SVG components
const CheckCircle = {
  template: `
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
  `
};

const AlertCircle = {
  template: `
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
  `
};

const AlertTriangle = {
  template: `
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
    </svg>
  `
};

const InfoCircle = {
  template: `
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
  `
};

// Variant configurations - shadcn style with white background
const variants = {
  default: {
    icon: null,
    iconColor: '',
    titleColor: 'text-gray-900'
  },
  success: {
    icon: CheckCircle,
    iconColor: 'text-green-600',
    titleColor: 'text-gray-900'
  },
  error: {
    icon: AlertCircle,
    iconColor: 'text-red-600',
    titleColor: 'text-gray-900'
  },
  warning: {
    icon: AlertTriangle,
    iconColor: 'text-yellow-600',
    titleColor: 'text-gray-900'
  },
  info: {
    icon: InfoCircle,
    iconColor: 'text-blue-600',
    titleColor: 'text-gray-900'
  }
};

const variantClasses = computed(() => variants[props.toast.variant || 'default']);

const handleClose = () => {
  if (timer) {
    clearTimeout(timer);
    timer = null;
  }
  isExiting.value = true;
  setTimeout(() => {
    emit('close', props.toast.id);
  }, 300);
};

onMounted(() => {
  // Duration is handled in useToast.js now
  // This prevents double auto-close
});

onUnmounted(() => {
  if (timer) {
    clearTimeout(timer);
    timer = null;
  }
});
</script>

<style scoped>
@keyframes slide-in {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.animate-slide-in {
  animation: slide-in 0.3s ease-out;
}
</style>
<script setup>
import { computed } from 'vue'

defineOptions({ inheritAttrs: false }) // Prevents Vue from auto-binding attrs

const props = defineProps({
  type: {
    type: String,
    default: 'submit',
    validator: value => ['submit', 'button', 'reset'].includes(value)
  },
  variant: {
    type: String,
    default: 'primary',
    validator: value => [
      'primary', 'secondary', 'danger',
      'success', 'warning', 'info',
      'light', 'dark', 'transparent'
    ].includes(value),
  },
  disabled: Boolean,
  loading: Boolean
})

const variantClasses = computed(() => {
  switch (props.variant) {
    case 'primary': return 'bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600'
    case 'secondary': return 'bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600'
    case 'danger': return 'bg-red-600 text-white hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600'
    case 'success': return 'bg-green-600 text-white hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600'
    case 'warning': return 'bg-yellow-500 text-white hover:bg-yellow-600 dark:bg-yellow-400 dark:hover:bg-yellow-500'
    case 'info': return 'bg-cyan-600 text-white hover:bg-cyan-700 dark:bg-cyan-500 dark:hover:bg-cyan-600'
    case 'light': return 'bg-white text-gray-800 hover:bg-gray-100 dark:bg-gray-200 dark:text-black'
    case 'dark': return 'bg-black text-white hover:bg-gray-800 dark:bg-gray-900 dark:hover:bg-gray-800'
    case 'transparent': return 'bg-transparent text-gray-800 hover:bg-gray-100 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600'
    default: return 'bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600'
  }
})
</script>

<template>
  <button
    v-bind="$attrs"
    :type="type"
    :disabled="disabled || loading"
    :class="[
      'inline-flex items-center justify-center text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2',
      variantClasses,
      disabled || loading ? 'opacity-50 cursor-not-allowed' : '',
      $attrs.class // allow parent to pass extra classes
    ]"
  >
    <svg
      v-if="loading"
      class="animate-spin mr-2 h-4 w-4 text-white"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
    </svg>
    <slot />
  </button>
</template>

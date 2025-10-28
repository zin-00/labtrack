<template>
  <div class="bg-white p-4 rounded-lg shadow">
    <h4 class="text-sm font-medium text-gray-600 mb-1">{{ title }}</h4>
    <div class="flex items-end justify-between">
      <span class="text-xl font-bold text-gray-900">{{ value }}</span>
      <span
        :class="[
          'text-xs font-medium',
          change.includes('+') ? 'text-green-500' : 'text-red-500'
        ]"
      >
        {{ change }}
      </span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: String,
  value: [Number, String],
  change: String
})
</script>

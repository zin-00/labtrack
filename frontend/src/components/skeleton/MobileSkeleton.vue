<script setup>
import { computed } from 'vue'

const props = defineProps({
  rows: {
    type: Number,
    default: 8
  }
})

const skeletonRows = computed(() => {
  return Array.from({ length: props.rows }, (_, i) => ({ id: `mobile-skeleton-${i}` }))
})
</script>

<template>
  <div class="space-y-3">
    <div
      v-for="row in skeletonRows"
      :key="row.id"
      class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm animate-pulse"
    >
      <div class="flex items-start justify-between mb-3">
        <div class="space-y-2">
          <div class="h-5 bg-gray-200 rounded w-32"></div>
          <div class="h-4 bg-gray-200 rounded w-20"></div>
        </div>
        <div class="w-5 h-5 bg-gray-200 rounded"></div>
      </div>
      
      <div class="space-y-3">
        <div v-for="i in 4" :key="i" class="flex items-center gap-2">
          <div class="h-3 bg-gray-200 rounded w-16"></div>
          <div class="h-4 bg-gray-200 rounded flex-1 max-w-40"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Skeleton shimmer animation */
@keyframes skeleton-shimmer {
  0% { background-position: -200px 0; }
  100% { background-position: calc(200px + 100%) 0; }
}

.animate-pulse .bg-gray-200 {
  background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
  background-size: 200px 100%;
  animation: skeleton-shimmer 1.5s infinite;
}
</style>
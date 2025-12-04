<script setup>
import { computed } from 'vue'

const props = defineProps({
  rows: {
    type: Number,
    default: 8
  },
  columns: {
    type: Array,
    default: () => [
      { key: 'student_id', width: 'w-20' },
      { key: 'first_name', width: 'w-24' },
      { key: 'middle_name', width: 'w-24' },
      { key: 'last_name', width: 'w-24' },
      { key: 'email', width: 'w-32' },
      { key: 'year_level_id', width: 'w-16' },
      { key: 'section_id', width: 'w-16' },
      { key: 'program', width: 'w-20', rounded: true },
      { key: 'status', width: 'w-16', rounded: true },
      { key: 'actions', width: 'w-20', type: 'actions' }
    ]
  }
})

const skeletonRows = computed(() => {
  return Array.from({ length: props.rows }, (_, i) => ({ id: `skeleton-${i}` }))
})
</script>

<template>
  <!-- Desktop Skeleton - Just the rows (will be inside tbody) -->
  <tr v-for="row in skeletonRows" :key="row.id" class="animate-pulse">
    <td 
      v-for="column in columns" 
      :key="column.key" 
      :class="[
        'px-6 py-4',
        column.key === 'ip_address' ? 'whitespace-nowrap' : '',
        column.key === 'computer_id' ? 'whitespace-nowrap' : '',
        column.key === 'browser_name' ? 'whitespace-nowrap' : '',
        column.key === 'title' ? '' : '',
        column.key === 'url' ? '' : '',
        column.key === 'duration' ? 'whitespace-nowrap' : '',
        column.key === 'created_at' ? 'whitespace-nowrap' : '',
        column.key === 'actions' ? 'text-center' : ''
      ]"
    >
      <!-- Actions column -->
      <div v-if="column.type === 'actions'" class="flex items-center justify-center gap-1">
        <div class="w-6 h-6 bg-gray-200 rounded"></div>
      </div>
      <!-- URL column - longer skeleton -->
      <div v-else-if="column.key === 'url'" class="h-4 bg-gray-200 rounded w-48 max-w-full"></div>
      <!-- Title column - medium skeleton -->
      <div v-else-if="column.key === 'title'" class="h-4 bg-gray-200 rounded w-32 max-w-full"></div>
      <!-- Date column -->
      <div v-else-if="column.key === 'created_at'" class="h-4 bg-gray-200 rounded w-28"></div>
      <!-- Duration column -->
      <div v-else-if="column.key === 'duration'" class="h-4 bg-gray-200 rounded w-16"></div>
      <!-- Regular columns with rounded badges -->
      <div 
        v-else
        :class="[
          'h-4 bg-gray-200',
          column.rounded ? 'rounded-full h-6 w-16' : 'rounded w-20'
        ]"
      ></div>
    </td>
  </tr>
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
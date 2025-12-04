<script setup>
import { ref, computed, defineEmits, useSlots } from 'vue'
import {
  PencilIcon,
  TrashIcon,
  EyeIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  UserCircleIcon,
  EllipsisVerticalIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  users: {
    type: Array,
    default: () => []
  },
  items: {
    type: Array,
    default: () => []
  },
  data: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  pagination: {
    type: Object,
    default: () => ({
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 8,
      from: null,
      to: null
    })
  },
  mobileFields: {
    type: Array,
    default: () => ['name', 'id', 'status', 'description']
  },
  titleField: {
    type: String,
    default: 'name'
  }
})

const emit = defineEmits(['edit', 'delete', 'view', 'page-change'])

const sortField = ref('')
const sortDirection = ref('asc')
const showActions = ref({})

const slots = useSlots()

const resolvedItems = computed(() => {
  if (props.users?.length) return props.users
  if (props.items?.length) return props.items
  if (props.data?.length) return props.data
  return []
})

const sortedItems = computed(() => {
  if (!sortField.value) return resolvedItems.value
  return [...resolvedItems.value].sort((a, b) => {
    let aValue = a[sortField.value] ?? ''
    let bValue = b[sortField.value] ?? ''
    aValue = aValue.toString().toLowerCase()
    bValue = bValue.toString().toLowerCase()
    return sortDirection.value === 'asc'
      ? aValue.localeCompare(bValue)
      : bValue.localeCompare(aValue)
  })
})

const sort = (field) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDirection.value = 'asc'
  }
}

const getSortIcon = (field) => {
  if (sortField.value !== field) return null
  return sortDirection.value === 'asc' ? ChevronUpIcon : ChevronDownIcon
}

const headerLabels = {
  id: 'ID',
  student_id: 'Student ID',
  first_name: 'First Name',
  middle_name: 'Middle Name',
  last_name: 'Last Name',
  name: 'Name',
  level: 'Level',
  email: 'Email',
  year_level_id: 'Year Level',
  section_id: 'Section',
  program: 'Program',
  status: 'Status',
  description: 'Description',
  created_at: 'Created At',
  workstation: 'Workstation',
  ip_address: 'IP Address'
}

const formatProgram = (program) => {
  const map = {
    bsit: 'BSIT',
    bsais: 'BSAIS',
    bsa: 'BSA',
    bscs: 'BSCS'
  }
  return map[program] || (program ? program.toUpperCase() : '—')
}

const formatName = (item) => {
  if (item.first_name || item.last_name) {
    const name = [item.first_name, item.last_name].filter(Boolean).join(' ')
    return name || '—'
  }
  return item.name || item.level || item.title || item[props.titleField] || '—'
}

const getDisplayValue = (item, field) => {
  if (field.includes('.')) {
    const parts = field.split('.')
    let value = item
    for (const part of parts) {
      value = value?.[part]
      if (value === null || value === undefined) return '—'
    }
    return value
  }
  
  const value = item[field]
  if (value === null || value === undefined) return '—'
  if (typeof value === 'string' || typeof value === 'number') return value
  if (typeof value === 'object') return JSON.stringify(value)
  return String(value)
}

const getMobileFieldLabel = (field) => {
  // Remove dots for nested fields (e.g., "student.first_name" -> "first_name")
  const cleanField = field.includes('.') ? field.split('.').pop() : field
  return headerLabels[cleanField] || cleanField.charAt(0).toUpperCase() + cleanField.slice(1).replace(/_/g, ' ')
}

const toggleActions = (itemId) => {
  showActions.value = { [itemId]: !showActions.value[itemId] }
}

const handleEdit = (item) => emit('edit', item)
const handleDelete = (item) => emit('delete', item)
const handleView = (item) => emit('view', item)

const changePage = (page) => {
  if (page >= 1 && page <= props.pagination.last_page) {
    emit('page-change', page)
  }
}

const getVisiblePages = () => {
  const current = props.pagination.current_page
  const total = props.pagination.last_page
  const pages = []
  
  if (total <= 5) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    pages.push(1)
    if (current > 3) pages.push('...')
    
    const start = Math.max(2, current - 1)
    const end = Math.min(total - 1, current + 1)
    
    for (let i = start; i <= end; i++) {
      if (i !== 1 && i !== total) pages.push(i)
    }
    
    if (current < total - 2) pages.push('...')
    if (total > 1) pages.push(total)
  }
  
  return pages
}
</script>

<template>
  <div class="w-full">
    <!-- Desktop Table -->
    <div class="hidden md:block overflow-x-auto">
      <table class="w-full text-sm bg-white border border-gray-200 rounded-lg overflow-hidden">
        <thead v-if="!slots.header" class="bg-gray-50">
          <tr>
            <th
              v-for="field in ['student_id', 'first_name','middle_name', 'last_name','email', 'year_level_id', 'section_id','program', 'status']"
              :key="field"
              class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors"
              @click="sort(field)"
            >
              <div class="flex items-center gap-1">
                {{ headerLabels[field] }}
                <component
                  :is="getSortIcon(field)"
                  v-if="getSortIcon(field)"
                  class="w-3 h-3"
                />
              </div>
            </th>
            <th class="px-3 py-2 text-xs font-medium text-gray-600 text-center w-20">Actions</th>
          </tr>
        </thead>
        <slot name="header" v-else />

        <tbody class="divide-y divide-gray-100">
          <template v-if="!slots.default">
            <tr v-if="loading">
              <td colspan="10" class="px-3 py-8 text-center text-gray-500">
                <div class="flex justify-center items-center gap-2">
                  <div class="animate-spin rounded-full h-4 w-4 border-2 border-gray-300 border-t-blue-500"></div>
                  Loading…
                </div>
              </td>
            </tr>

            <tr v-else-if="!resolvedItems.length">
              <td colspan="10" class="px-3 py-8 text-center text-gray-500">
                <div class="flex flex-col items-center gap-2">
                  <UserCircleIcon class="w-8 h-8 text-gray-300" />
                  <span class="text-sm">No records found</span>
                </div>
              </td>
            </tr>

            <tr
              v-for="item in sortedItems"
              :key="item.id"
              class="hover:bg-gray-50 transition-colors"
            >
              <td class="px-3 py-1 text-gray-900 font-medium">{{ item.student_id }}</td>
              <td class="px-3 py-1 text-gray-900 text-xs">{{ item.first_name }}</td>
              <td class="px-3 py-1 text-gray-900 text-xs">{{ item.middle_name }}</td>
              <td class="px-3 py-1 text-gray-900 text-xs">{{ item.last_name }}</td>
              <td class="px-3 py-1">
                <a
                  v-if="item.email"
                  :href="`mailto:${item.email}`"
                  class="text-blue-600 hover:underline text-sm"
                >
                  {{ item.email }}
                </a>
                <span v-else class="text-gray-400">—</span>
              </td>
              <td class="px-3 py-1">{{ item.year_level_id }}</td>
              <td class="px-3 py-1">{{ item.section_id }}</td>
              <td class="px-3 py-1">
                <span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded-full">
                  {{ formatProgram(item.program?.program_code) }}
                </span>
              </td>
              <td class="px-3 py-1">
                <span
                  v-if="item.status"
                  :class="item.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                >
                  {{ item.status }}
                </span>
                <span v-else class="text-gray-400">—</span>
              </td>
              <td class="px-3 py-2">
                <div class="flex items-center justify-center gap-1">
                  <button @click="handleView(item)" class="p-1.5 hover:text-blue-600 hover:bg-blue-50 rounded-md transition-colors">
                    <EyeIcon class="w-3.5 h-3.5" />
                  </button>
                  <button @click="handleEdit(item)" class="p-1.5 hover:text-green-600 hover:bg-green-50 rounded-md transition-colors">
                    <PencilIcon class="w-3.5 h-3.5" />
                  </button>
                  <button @click="handleDelete(item)" class="p-1.5 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors">
                    <TrashIcon class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>
          </template>
          <template v-else>
            <slot />
          </template>
        </tbody>
      </table>
    </div>

    <!-- ✨ Minimal & Clean Mobile Cards -->
    <div class="md:hidden space-y-2">
      <div v-if="loading" class="flex justify-center py-12">
        <div class="flex items-center gap-2 text-gray-400 text-sm">
          <div class="animate-spin rounded-full h-4 w-4 border-2 border-gray-200 border-t-gray-400"></div>
          Loading
        </div>
      </div>

      <div v-else-if="!resolvedItems.length" class="flex flex-col items-center py-12 text-gray-400">
        <UserCircleIcon class="w-10 h-10 mb-2 opacity-50" />
        <span class="text-sm">No records found</span>
      </div>

      <!-- Minimal Card Design -->
      <div
        v-for="item in sortedItems"
        :key="item.id"
        class="bg-white border border-gray-200 rounded-lg p-3 relative group"
      >
        <!-- Header: Name + Actions -->
        <div class="flex items-start justify-between gap-3 mb-2.5">
          <div class="flex-1 min-w-0">
            <h3 class="font-medium text-gray-900 text-sm truncate">{{ formatName(item) }}</h3>
            <p class="text-xs text-gray-400 mt-0.5">{{ item.student_id || item.id }}</p>
          </div>
          
          <!-- Minimal Action Menu -->
          <div class="relative flex-shrink-0">
            <button 
              @click="toggleActions(item.id)"
              class="p-1.5 hover:bg-gray-100 rounded-md transition-colors"
            >
              <EllipsisVerticalIcon class="w-4 h-4 text-gray-400" />
            </button>
            
            <!-- Dropdown Menu -->
            <div 
              v-if="showActions[item.id]"
              class="absolute right-0 top-8 bg-white border border-gray-200 rounded-lg shadow-lg z-20 min-w-[100px] py-1"
            >
              <button 
                @click="handleView(item); showActions[item.id] = false" 
                class="w-full px-3 py-1.5 text-left text-xs hover:bg-gray-50 flex items-center gap-2 text-gray-700"
              >
                <EyeIcon class="w-3.5 h-3.5" />
                View
              </button>
              <button 
                @click="handleEdit(item); showActions[item.id] = false" 
                class="w-full px-3 py-1.5 text-left text-xs hover:bg-gray-50 flex items-center gap-2 text-gray-700"
              >
                <PencilIcon class="w-3.5 h-3.5" />
                Edit
              </button>
              <button 
                @click="handleDelete(item); showActions[item.id] = false" 
                class="w-full px-3 py-1.5 text-left text-xs hover:bg-red-50 flex items-center gap-2 text-red-600"
              >
                <TrashIcon class="w-3.5 h-3.5" />
                Delete
              </button>
            </div>
          </div>
        </div>
        
        <!-- Compact Info Grid -->
        <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-xs">
          <div v-for="field in mobileFields" :key="field" class="flex flex-col">
            <span class="text-gray-400 text-[10px] uppercase tracking-wide mb-0.5">
              {{ getMobileFieldLabel(field) }}
            </span>
            <span class="text-gray-900 font-medium truncate">
              {{ getDisplayValue(item, field) }}
            </span>
          </div>
        </div>

        <!-- Status Badge (if exists) -->
        <div v-if="item.status" class="mt-2.5 pt-2.5 border-t border-gray-100">
          <span
            :class="[
              'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium uppercase tracking-wide',
              item.status === 'active' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'
            ]"
          >
            {{ item.status }}
          </span>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div
      v-if="pagination.last_page > 1"
      class="flex flex-col sm:flex-row items-center justify-between gap-4 px-4 py-4 border-t border-gray-100 bg-white"
    >
      <div class="text-sm text-gray-500 order-2 sm:order-1">
        {{ pagination.from ?? ((pagination.current_page - 1) * pagination.per_page + 1) }}-{{ pagination.to ?? (pagination.current_page * pagination.per_page > pagination.total ? pagination.total : pagination.current_page * pagination.per_page) }} of {{ pagination.total }}
      </div>

      <div class="flex items-center gap-2 order-1 sm:order-2">
        <button
          @click="changePage(pagination.current_page - 1)"
          :disabled="pagination.current_page === 1"
          class="px-3 py-1.5 text-sm bg-white border border-gray-200 rounded-md text-gray-600 hover:bg-gray-50 hover:border-gray-300 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        >
          Prev
        </button>

        <template v-for="page in getVisiblePages()" :key="page">
          <button
            v-if="page !== '...'"
            @click="changePage(page)"
            :class="[
              'min-w-[36px] px-3 py-1.5 text-sm rounded-md border transition-colors',
              page === pagination.current_page
                ? 'border-gray-300 bg-gray-100 text-gray-800 font-medium'
                : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300'
            ]"
          >
            {{ page }}
          </button>
          <span v-else class="px-2 text-gray-400 text-sm">...</span>
        </template>

        <button
          @click="changePage(pagination.current_page + 1)"
          :disabled="pagination.current_page === pagination.last_page"
          class="px-3 py-1.5 text-sm bg-white border border-gray-200 rounded-md text-gray-600 hover:bg-gray-50 hover:border-gray-300 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
@media (max-width: 768px) {
  .relative button + div {
    animation: slideDown 0.15s ease-out;
  }
  
  @keyframes slideDown {
    from { 
      opacity: 0; 
      transform: translateY(-8px) scale(0.95); 
    }
    to { 
      opacity: 1; 
      transform: translateY(0) scale(1); 
    }
  }
}
</style>
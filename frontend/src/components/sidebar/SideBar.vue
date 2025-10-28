<template>
  <div>
    <!-- Toggle Button -->
    <button 
      @click="toggleSidebar" 
      class="fixed top-1/2 z-40 p-2 rounded-lg text-gray-700 dark:text-gray-300 focus:outline-none transition-all duration-300 transform -translate-y-1/2 bg-white dark:bg-gray-800 shadow-lg hover:shadow-xl"
      :class="{
        'left-[0px]': sidebarState === 'closed',
        'left-[40px]': sidebarState === 'icon',
        'left-[210px]': sidebarState === 'full'
      }"
    >
      <ChevronLeftIcon v-if="sidebarState === 'full'" class="h-5 w-5" />
      <ChevronRightIcon v-else-if="sidebarState === 'icon'" class="h-5 w-5" />
      <ChevronRightIcon v-else class="h-5 w-5" />
    </button>
    
    <!-- Overlay for mobile/small screens -->
    <div 
      v-if="sidebarState !== 'closed' && screenWidth < 1024" 
      @click="closeSidebar" 
      class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden"
    />
    
    <!-- Sidebar -->
    <div 
      class="fixed inset-y-0 left-0 z-30 pt-16 transition-all duration-300 ease-in-out bg-white dark:bg-gray-800 shadow-lg flex flex-col"
      :class="{
        'w-60': sidebarState === 'full',
        'w-[60px]': sidebarState === 'icon',
        'w-0 overflow-hidden': sidebarState === 'closed',
        'translate-x-0': sidebarState !== 'closed',
        '-translate-x-full': sidebarState === 'closed'
      }"
    >
      <!-- Scrollable Menu Section -->
      <div class="flex-1 py-4 overflow-y-auto overflow-x-visible">
        <ul class="space-y-2 font-medium px-2">
          <li v-for="item in menuItems" :key="item.id" class="relative">
            <!-- Regular menu item -->
            <router-link 
              v-if="!item.children" 
              :to="item.to" 
              @mouseenter="(e) => showTooltip(e, item.label)"
              @mouseleave="hideTooltip"
              class="flex items-center p-2 rounded-lg transition-colors duration-200 relative"
              :class="{
                'bg-gray-900 dark:bg-gray-700 text-white': isActiveRoute(item.to),
                'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700': !isActiveRoute(item.to),
                'opacity-50 pointer-events-none': sidebarState === 'closed'
              }"
            >
              <component :is="item.icon" class="h-5 w-5 flex-shrink-0" />
              <span 
                class="ms-3 transition-all duration-200 overflow-hidden whitespace-nowrap text-sm"
                :class="{ 'opacity-0 w-0': sidebarState === 'icon', 'opacity-100': sidebarState === 'full' }"
              >
                {{ item.label }}
              </span>
            </router-link>
            
            <!-- Dropdown menu item -->
            <div v-else class="relative">
              <button 
                @click="toggleDropdown(item.id)"
                @mouseenter="(e) => showTooltip(e, item.label)"
                @mouseleave="hideTooltip"
                class="flex items-center w-full p-2 rounded-lg transition-colors duration-200 text-sm"
                :class="{ 
                  'bg-gray-900 dark:bg-gray-700 text-white': isActiveParent(item.children),
                  'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700': !isActiveParent(item.children)
                }"
              >
                <component :is="item.icon" class="h-5 w-5 flex-shrink-0" />
                <span 
                  class="ms-3 transition-all duration-200 overflow-hidden whitespace-nowrap"
                  :class="{ 'opacity-0 w-0': sidebarState === 'icon', 'opacity-100': sidebarState === 'full' }"
                >
                  {{ item.label }}
                </span>
                <ChevronDownIcon 
                  v-if="sidebarState === 'full'"
                  class="ml-auto h-4 w-4 transition-transform duration-200"
                  :class="{ 'rotate-180': openDropdowns.includes(item.id) }"
                />
              </button>
              
              <!-- Dropdown items -->
              <div 
                v-if="sidebarState === 'full'"
                class="overflow-hidden transition-all duration-300"
                :class="{ 'max-h-0': !openDropdowns.includes(item.id), 'max-h-96': openDropdowns.includes(item.id) }"
              >
                <ul class="pl-6 mt-2 space-y-1">
                  <li v-for="child in item.children" :key="child.id" class="relative">
                    <router-link 
                      :to="child.to"
                      class="flex items-center p-2 rounded-lg transition-colors duration-200 text-sm"
                      :class="{ 
                        'bg-gray-800 dark:bg-gray-600 text-white': isActiveRoute(child.to),
                        'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700': !isActiveRoute(child.to)
                      }"
                    >
                      <component :is="child.icon" class="h-5 w-5 flex-shrink-0" />
                      <span class="ms-3">{{ child.label }}</span>
                    </router-link>
                  </li>
                </ul>
              </div>
            </div>
          </li>
        </ul>
      </div>
      
      <!-- Sticky Bottom Section - User Info, Settings & Logout -->
      <div 
        class="border-t border-gray-200 dark:border-gray-700 p-2 bg-white dark:bg-gray-800 transition-all duration-300"
        :class="{ 'opacity-0 pointer-events-none': sidebarState === 'closed' }"
      >
        <!-- User Info (when expanded) -->
        <div 
          v-if="sidebarState === 'full' && user"
          class="mb-2 px-2 py-1"
        >
          <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
            {{ user.name }}
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
            {{ user.email }}
          </div>
        </div>
        
        <!-- Settings Button -->
        <router-link 
          to="/settings"
          @mouseenter="(e) => showTooltip(e, 'Settings')"
          @mouseleave="hideTooltip"
          class="flex items-center w-full p-2 rounded-lg transition-colors duration-200 text-sm font-medium mb-2"
          :class="{
            'bg-gray-900 dark:bg-gray-700 text-white': isActiveRoute('/settings'),
            'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700': !isActiveRoute('/settings')
          }"
        >
          <CogIcon class="h-5 w-5 flex-shrink-0" />
          <span 
            class="ms-3 transition-all duration-200 overflow-hidden whitespace-nowrap"
            :class="{ 'opacity-0 w-0': sidebarState === 'icon', 'opacity-100': sidebarState === 'full' }"
          >
            Settings
          </span>
        </router-link>
        
        <!-- Logout Button -->
        <button 
          @click="$emit('logout')"
          @mouseenter="(e) => showTooltip(e, 'Log Out')"
          @mouseleave="hideTooltip"
          class="flex items-center w-full p-2 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 text-sm font-medium"
        >
          <ArrowRightOnRectangleIcon class="h-5 w-5 flex-shrink-0" />
          <span 
            class="ms-3 transition-all duration-200 overflow-hidden whitespace-nowrap"
            :class="{ 'opacity-0 w-0': sidebarState === 'icon', 'opacity-100': sidebarState === 'full' }"
          >
            Log Out
          </span>
        </button>
      </div>
    </div>
    
    <!-- External Tooltip - positioned outside sidebar container -->
    <Teleport to="body">
      <div
        v-if="tooltipState.visible && sidebarState === 'icon'"
        class="fixed left-20 z-[9999] pointer-events-none transition-opacity duration-200"
        :style="{ top: tooltipState.top - 16 + 'px' }"
      >
        <div class="px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded-lg shadow-lg whitespace-nowrap">
          {{ tooltipState.text }}
          <div class="absolute left-0 top-1/2 transform -translate-y-1/2 -translate-x-1 w-2 h-2 bg-gray-900 dark:bg-gray-700 rotate-45"></div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import { 
  ChevronRightIcon, 
  ChevronLeftIcon, 
  ChevronDownIcon,
  CogIcon,
  ArrowRightOnRectangleIcon
} from '@heroicons/vue/24/outline';

// Props for customization
const props = defineProps({
  customMenuItems: {
    type: Array,
    default: () => []
  },
  user: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['sidebarStateChange', 'logout']);

// Reactive state
const sidebarState = ref(localStorage.getItem('sidebarState') || 'full');
const screenWidth = ref(window.innerWidth);
const openDropdowns = ref([]);
const route = useRoute();

// Tooltip state
const tooltipState = ref({
  visible: false,
  text: '',
  top: 0
});

const menuItems = computed(() => {
  return props.customMenuItems;
});

watch(sidebarState, (newState) => {
  localStorage.setItem('sidebarState', newState);
  emit('sidebarStateChange', newState);
  if (newState === 'icon' || newState === 'closed') {
    openDropdowns.value = [];
  }
  // Hide tooltip when sidebar state changes
  tooltipState.value.visible = false;
});

// Methods
const toggleSidebar = () => {
  if (sidebarState.value === 'closed') {
    sidebarState.value = 'icon';
  } else if (sidebarState.value === 'icon') {
    sidebarState.value = 'full';
  } else {
    sidebarState.value = 'icon';
  }
};

const closeSidebar = () => {
  sidebarState.value = 'closed';
};

const toggleDropdown = (itemId) => {
  if (sidebarState.value !== 'full') return;
  
  const index = openDropdowns.value.indexOf(itemId);
  if (index > -1) {
    openDropdowns.value.splice(index, 1);
  } else {
    openDropdowns.value.push(itemId);
  }
};

const isActiveRoute = (routePath) => {
  return route.path === routePath;
};

const isActiveParent = (children) => {
  return children.some(child => route.path === child.to);
};

const updateScreenWidth = () => {
  screenWidth.value = window.innerWidth;
};

// Tooltip methods
const showTooltip = (event, label) => {
  if (sidebarState.value !== 'icon') return;
  
  const rect = event.currentTarget.getBoundingClientRect();
  tooltipState.value = {
    visible: true,
    text: label,
    top: rect.top + rect.height / 2
  };
};

const hideTooltip = () => {
  tooltipState.value.visible = false;
};

// Lifecycle hooks
onMounted(() => {
  window.addEventListener('resize', updateScreenWidth);
  
  if (screenWidth.value < 1024) {
    sidebarState.value = 'closed';
  }
  
  // Auto-open dropdown if current route is a child
  menuItems.value.forEach(item => {
    if (item.children && isActiveParent(item.children)) {
      if (!openDropdowns.value.includes(item.id)) {
        openDropdowns.value.push(item.id);
      }
    }
  });
});

onUnmounted(() => {
  window.removeEventListener('resize', updateScreenWidth);
});
</script>

<style scoped>
/* Custom scrollbar for sidebar */
::-webkit-scrollbar {
  width: 4px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 2px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.dark ::-webkit-scrollbar-thumb {
  background: #4b5563;
}

.dark ::-webkit-scrollbar-thumb:hover {
  background: #6b7280;
}
</style>
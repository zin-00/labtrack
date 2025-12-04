<template>
  <div>
    <!-- Toggle Button -->
    <button 
      @click="toggleSidebar" 
      class="fixed top-1/2 z-40 p-2 rounded-lg text-gray-700 focus:outline-none transition-all duration-300 transform -translate-y-1/2 bg-white shadow-md hover:shadow-lg border border-gray-200"
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
      class="fixed inset-0  bg-white/50 backdrop-blur-sm bg-opacity-50 z-30 lg:hidden"
    />
    
    <!-- Sidebar -->
    <div 
      class="fixed inset-y-0 left-0 z-30 pt-16 transition-all duration-300 ease-in-out bg-white shadow-sm border-r border-gray-200 flex flex-col"
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
        <ul class="space-y-1 font-medium px-3">
          <li v-for="item in menuItems" :key="item.id" class="relative">
            <!-- Regular menu item -->
            <router-link 
              v-if="!item.children" 
              :to="item.to" 
              @mouseenter="(e) => showTooltip(e, item.label)"
              @mouseleave="hideTooltip"
              class="flex items-center p-2.5 rounded-lg transition-all duration-200 relative group"
              :class="{
                'bg-gray-100 text-gray-800': isActiveRoute(item.to),
                'text-gray-600 hover:bg-gray-50 hover:text-gray-800': !isActiveRoute(item.to),
                'opacity-50 pointer-events-none': sidebarState === 'closed'
              }"
            >
              <component :is="item.icon" class="h-5 w-5 flex-shrink-0" />
              <span 
                class="ms-3 transition-all duration-200 overflow-hidden whitespace-nowrap text-sm font-medium"
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
                class="flex items-center w-full p-2.5 rounded-lg transition-all duration-200 text-sm font-medium group"
                :class="{ 
                  'bg-gray-100 text-gray-800': isActiveParent(item.children),
                  'text-gray-600 hover:bg-gray-50 hover:text-gray-800': !isActiveParent(item.children)
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
                <ul class="pl-8 mt-1 space-y-1">
                  <li v-for="child in item.children" :key="child.id" class="relative">
                    <router-link 
                      :to="child.to"
                      class="flex items-center p-2 rounded-lg transition-all duration-200 text-sm group"
                      :class="{ 
                        'bg-gray-200 text-gray-800': isActiveRoute(child.to),
                        'text-gray-500 hover:bg-gray-50 hover:text-gray-700': !isActiveRoute(child.to)
                      }"
                    >
                      <component :is="child.icon" class="h-4 w-4 flex-shrink-0" />
                      <span class="ms-2">{{ child.label }}</span>
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
        class="border-t border-gray-200 p-3 bg-white transition-all duration-300"
        :class="{ 'opacity-0 pointer-events-none': sidebarState === 'closed' }"
      >
        <!-- User Info (when expanded) -->
        <div 
          v-if="sidebarState === 'full' && user"
          class="mb-3 px-2 py-2 bg-gray-50 rounded-lg border border-gray-100"
        >
          <div class="text-sm font-semibold text-gray-800 truncate">
            {{ user.name }}
          </div>
          <div class="text-xs text-gray-500 truncate mt-0.5">
            {{ user.email }}
          </div>
        </div>
        
        <!-- Profile Button -->
        <router-link 
          to="/profile"
          @mouseenter="(e) => showTooltip(e, 'Profile')"
          @mouseleave="hideTooltip"
          class="flex items-center w-full p-2.5 rounded-lg transition-all duration-200 text-sm font-medium mb-1 group"
          :class="{
            'bg-gray-100 text-gray-800': isActiveRoute('/profile'),
            'text-gray-600 hover:bg-gray-50 hover:text-gray-800': !isActiveRoute('/profile')
          }"
        >
          <UserCircleIcon class="h-5 w-5 flex-shrink-0" />
          <span 
            class="ms-3 transition-all duration-200 overflow-hidden whitespace-nowrap"
            :class="{ 'opacity-0 w-0': sidebarState === 'icon', 'opacity-100': sidebarState === 'full' }"
          >
            Profile
          </span>
        </router-link>
        
        <!-- Logout Button -->
        <button 
          @click="$emit('logout')"
          @mouseenter="(e) => showTooltip(e, 'Log Out')"
          @mouseleave="hideTooltip"
          class="flex items-center w-full p-2.5 text-gray-600 rounded-lg hover:bg-red-50 hover:text-red-600 transition-all duration-200 text-sm font-medium group"
        >
          <ArrowLeftOnRectangleIcon class="h-5 w-5 flex-shrink-0" />
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
        <div class="px-3 py-2 bg-gray-800 text-white text-xs rounded-lg shadow-lg whitespace-nowrap">
          {{ tooltipState.text }}
          <div class="absolute left-0 top-1/2 transform -translate-y-1/2 -translate-x-1 w-2 h-2 bg-gray-800 rotate-45"></div>
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
  UserCircleIcon,
  ArrowLeftOnRectangleIcon
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
/* Custom scrollbar for sidebar - minimalist white theme */
::-webkit-scrollbar {
  width: 3px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: #e5e7eb;
  border-radius: 2px;
}

::-webkit-scrollbar-thumb:hover {
  background: #d1d5db;
}
</style>
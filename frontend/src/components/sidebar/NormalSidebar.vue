<template>
  <aside
    :class="[
      'fixed top-0 left-0 z-40 h-screen w-64 bg-white border-r border-gray-200 transition-transform',
      'lg:translate-x-0',
      isOpen ? 'translate-x-0' : '-translate-x-full'
    ]"
  >
    <!-- Logo -->
    <div class="hidden lg:flex items-center gap-2 px-6 py-5 border-b border-gray-200">
      <slot name="logo">
        <BxSolidNavigation class="h-10 w-10 text-black"/>
        <span class="text-xl font-semibold text-gray-800">CAMPUS NAVIGATOR</span>
      </slot>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto mt-16 lg:mt-0">
      <template v-for="item in navigationItems" :key="item.to || item.name">
        <!-- Parent Item -->
        <div>
          <component
            :is="item.to ? 'router-link' : 'button'"
            :to="item.to"
            :class="[
              'flex items-center justify-between w-full gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors',
              isActiveRoute(item.to)
                ? 'bg-gray-900 text-white'
                : 'text-gray-700 hover:bg-gray-100'
            ]"
            @click="handleItemClick(item)"
          >
            <div class="flex items-center gap-3">
              <component :is="item.icon" class="h-5 w-5" />
              {{ item.name }}
            </div>
            <BsChevronDown 
              v-if="item.children && item.children.length > 0"
              :class="[
                'h-4 w-4 transition-transform',
                expandedItems[item.name] ? 'rotate-180' : ''
              ]"
            />
          </component>

          <!-- Children Items -->
          <div
            v-if="item.children && item.children.length > 0"
            :class="[
              'overflow-hidden transition-all duration-200',
              expandedItems[item.name] ? 'max-h-96 mt-1' : 'max-h-0'
            ]"
          >
            <router-link
              v-for="child in item.children"
              :key="child.to"
              :to="child.to"
              :class="[
                'flex items-center gap-3 px-4 py-2 ml-8 rounded-lg text-sm font-medium transition-colors',
                isActiveRoute(child.to)
                  ? 'bg-gray-200 text-gray-900'
                  : 'text-gray-600 hover:bg-gray-100'
              ]"
              @click="closeMobileMenu"
            >
              <component v-if="child.icon" :is="child.icon" class="h-4 w-4" />
              {{ child.name }}
            </router-link>
          </div>
        </div>
      </template>
    </nav>

    <!-- User Section -->
    <div class="absolute bottom-0 left-0 right-0 border-t border-gray-200 bg-white">
      <slot name="user-section">
        <div class="px-4 py-4">
          <div class="flex items-center gap-3 mb-3">
            <BsPersonCircle class="h-8 w-8 text-gray-600" />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">
                {{ user?.name || 'Guest' }}
              </p>
              <p class="text-xs text-gray-500 truncate">
                {{ user?.email || '' }}
              </p>
            </div>
          </div>
          <div class="space-y-1">
            <router-link
              to="/profile"
              class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md"
              @click="closeMobileMenu"
            >
              <BsPersonCircle class="h-4 w-4" />
              Profile
            </router-link>
            <button
              @click="handleLogout"
              class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md"
            >
              <BsBoxArrowRight class="h-4 w-4" />
              Log Out
            </button>
          </div>
        </div>
      </slot>
    </div>
  </aside>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { 
  BxSolidNavigation,
  BsPersonCircle,
  BsBoxArrowRight,
  BsChevronDown
} from 'oh-vue-icons/icons';

const props = defineProps({
  navItems: {
    type: Array,
    default: () => []
  },
  isOpen: {
    type: Boolean,
    default: false
  },
  user: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'logout']);

const route = useRoute();
const router = useRouter();
const expandedItems = ref({});

const navigationItems = computed(() => props.navItems);

const isActiveRoute = (to) => {
  if (!to) return false;
  
  // Handle string paths
  if (typeof to === 'string') {
    return route.path === to || route.path.startsWith(to + '/');
  }
  
  // Handle route objects with name
  if (to.name) {
    return route.name === to.name;
  }
  
  // Handle route objects with path
  if (to.path) {
    return route.path === to.path || route.path.startsWith(to.path + '/');
  }
  
  return false;
};

const handleItemClick = (item) => {
  // If item has children, toggle expansion
  if (item.children && item.children.length > 0) {
    expandedItems.value[item.name] = !expandedItems.value[item.name];
  } else if (!item.to) {
    // If no route but no children, just close mobile menu
    closeMobileMenu();
  } else {
    // Has route, will be handled by router-link component
    closeMobileMenu();
  }
};

const closeMobileMenu = () => {
  emit('close');
};

const handleLogout = () => {
  emit('logout');
};
</script>
<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { 
        MailWarningIcon,
        LaptopMinimalIcon,
        SquareActivityIcon,
        MailIcon,
        FolderKanbanIcon,
        EarthIcon,
        ActivityIcon,
        StickyNoteIcon
        } from 'lucide-vue-next';
import { 
        HomeModernIcon,
        ComputerDesktopIcon,
        AdjustmentsHorizontalIcon,
        UsersIcon,
        EnvelopeIcon,
        AcademicCapIcon,
        CpuChipIcon,
        InboxStackIcon,
        FolderMinusIcon,
        Square2StackIcon,
        FolderIcon,
        DocumentIcon,
        CommandLineIcon,
        } from '@heroicons/vue/24/outline';
import { 
        AkDashboard,
        LaUserTieSolid,
        CaReportData
        } from '@kalimahapps/vue-icons';
import { useAuthStore } from '../../composable/useAuth';
import SideBar from '../../components/sidebar/SideBar.vue';
import LoaderSpinner from '../../components/spinner/LoaderSpinner.vue'
import NotificationDropdown from '../../components/notifications/NotificationDropdown.vue'

const auth = useAuthStore();
const router = useRouter();
const showingSettingsDropdown = ref(false);
const isCheckingAuth = ref(false);
const isLoading = ref(false);

const sidebarState = ref(localStorage.getItem('sidebarState') || 'full');

watch(sidebarState, (newState) => {
  localStorage.setItem('sidebarState', newState);
}, { immediate: true });

const handleSidebarChange = (state) => {
  sidebarState.value = state;
};

const mainContentClasses = computed(() => {
  const classes = [];
  
  switch (sidebarState.value) {
    case 'full':
      classes.push('lg:ml-60');
      break;
    case 'icon':
      classes.push('lg:ml-16');
      break;
    case 'closed':
    default:
      classes.push('ml-0');
      break;
  }
  
  return classes.join(' ');
});

const SideItems = ref([
  { id: 'dashboard',              label: 'Dashboard',         icon: AkDashboard,                  to: '/dashboard'},
  { id: 'users',                  label: 'Users',             icon: UsersIcon,                    to: '/users' ,              children: [
    { id: 'students',             label: 'Students',          icon: AcademicCapIcon,              to: '/students'},
    { id: 'admin',                label: 'Admin',             icon: LaUserTieSolid,               to: '/admin'},
    { id: 'workstation_mapping',  label: 'Mapping',           icon: CommandLineIcon ,            to: '/workstation-mapping'},
    { id: 'attendance',           label: 'Attendance',        icon: EnvelopeIcon,                 to: '/attendance'},
  ]},
  { id: 'academic_settings',    label: 'Academics',         icon: Square2StackIcon,              children: [
    { id: 'program',            label: 'Programs',          icon: FolderKanbanIcon,             to: '/program'},
    { id: 'section',            label: 'Sections',          icon: AdjustmentsHorizontalIcon,    to: '/section'},
  ]},
  { id: 'laboratories',         label: 'Laboratories',      icon: InboxStackIcon,           children: [
    { id: 'computers',          label: 'Computers',         icon: LaptopMinimalIcon,            to: '/computers' },
    { id: 'config',             label: 'Configuration',            icon: CpuChipIcon,                  to: '/laboratory' },
    { id: 'assigned_computers',  label: 'Assigned Computers', icon: MailWarningIcon,              to: '/assigned-computers'},
  ]},
  { id: 'system_activity',      label: 'System Activity',   icon: FolderMinusIcon,           children: [
      { id: 'user_session',     label: 'Student Activity',  icon: FolderMinusIcon,              to: '/computer_logs'},
      { id: 'audit-logs',       label: 'Audit Logs',        icon: SquareActivityIcon,           to: '/audit-logs'},
      { id: 'computer_activity',label: 'Computer Activity', icon: ComputerDesktopIcon,          to: '/computer-activity'},
      { id: 'browser_activity', label: 'Browser Activity',  icon: EarthIcon ,                   to: '/browser-activity'},
  ]},
  { id: 'request_access',       label: 'Access Requests',   icon: MailIcon,                     to: '/request-access'},
  { id: 'reports',              label: 'Reports',           icon: StickyNoteIcon,               to: '/reports'}
]);

const logout_func = async () => {
  try {
    isLoading.value = true;
    await auth.logout();
    localStorage.removeItem('sidebarState');
    localStorage.removeItem('auth_token');
    router.push('/login');
  } catch (error) {
    console.error('Logout error:', error);
    router.push('/login');
  } finally {
    isLoading.value = false;
  }
};

const closeDropdowns = () => {
  showingSettingsDropdown.value = false;
};

const toggleSettingsDropdown = () => {
  showingSettingsDropdown.value = !showingSettingsDropdown.value;
};

const navigateToProfile = () => {
  router.push('/profile');
  closeDropdowns();
};

const handleClickOutside = (event) => {
  if (!(event.target.closest('.dropdown-container'))) {
    closeDropdowns();
  }
};

const checkAuthentication = async () => {
  try {
    isCheckingAuth.value = true;
    
    const token = localStorage.getItem('auth_token');
    if (!token) {
      router.push('/login');
      return false;
    }
    
    if (!auth.user) {
      await auth.loadUser();
    }
    
    if (!auth.user) {
      router.push('/login');
      return false;
    }
    
    return true;
  } catch (error) {
    console.error('Authentication check failed:', error);
    router.push('/login');
    return false;
  } finally {
    isCheckingAuth.value = false;
  }
};

onMounted( async () => {
  document.addEventListener('click', handleClickOutside);
  
  // Only check authentication if we're on a protected route
  const requiresAuth = router.currentRoute.value.meta.requiresAuth;
  if (requiresAuth) {
    await checkAuthentication();
  }
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
          <LoaderSpinner :is-loading="isLoading" subMessage="Logging out please wait..." />
            <!-- Fixed Top Navigation -->
            <nav class="fixed top-0 left-0 right-0 z-50 border-b border-gray-200 bg-white">
                <div class="mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between items-center">
                        <!-- Logo - Always visible -->
                        <div class="flex items-center">
                  <div class="w-35 h-auto rounded flex items-center justify-center">
                <img src="../../assets/lb5.png" alt="" srcset="">
              </div>
                        </div>

                        <!-- User Menu -->
                        <div class="flex items-center ml-auto space-x-2">
                            <!-- Notification Bell -->
                            <NotificationDropdown />
                            
                            <div class="relative dropdown-container">
                                <button
                                    v-if="auth.user"
                                    @click="toggleSettingsDropdown"
                                    type="button"
                                    class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                >
                                    <div class="flex items-center space-x-2">
                                        <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-gray-700">
                                                {{ auth.user.name.charAt(0).toUpperCase() }}
                                            </span>
                                        </div>
                                        <span class="hidden sm:inline">{{ auth.user.name }}</span>
                                    </div>
                                    <svg
                                        class="ml-2 h-4 w-4 transition-transform duration-150"
                                        :class="{ 'rotate-180': showingSettingsDropdown }"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>

                                <!-- Dropdown Menu -->
                                <Transition
                                    enter-active-class="transition ease-out duration-200"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95"
                                >
                                    <div
                                        v-show="showingSettingsDropdown"
                                        class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                    >
                                        <button
                                            @click="navigateToProfile"
                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-150 ease-in-out"
                                        >
                                            Profile
                                        </button>
                                        <button
                                            @click="logout_func"
                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-150 ease-in-out"
                                        >
                                            Log Out
                                        </button>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Sidebar - Always visible with toggle -->
            <SideBar 
                @sidebarStateChange="handleSidebarChange" 
                @logout="logout_func"
                :customMenuItems="SideItems" 
            />

            <!-- Main Content Area -->
            <div 
                class="transition-all duration-300 ease-in-out pt-16 min-h-screen"
                :class="mainContentClasses"
            >
                <header class="bg-white shadow" v-if="$slots.header">
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>

                <main class="p-4">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
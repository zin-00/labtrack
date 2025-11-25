<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useNotificationStore } from '../../store/notifications/notifications';
import { 
  BellIcon, 
  CheckIcon, 
  TrashIcon, 
  XMarkIcon,
  InformationCircleIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  XCircleIcon,
  DocumentTextIcon,
  ComputerDesktopIcon,
  LockClosedIcon,
  MegaphoneIcon
} from '@heroicons/vue/24/outline';
import { BellAlertIcon } from '@heroicons/vue/24/solid';

const notificationStore = useNotificationStore();

const isOpen = ref(false);
const dropdownRef = ref(null);
let pollInterval = null;

// Format time ago
const formatTimeAgo = (dateString) => {
  const date = new Date(dateString);
  const now = new Date();
  const diffInSeconds = Math.floor((now - date) / 1000);
  
  if (diffInSeconds < 60) return 'Just now';
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
  if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;
  
  return date.toLocaleDateString();
};

// Get notification icon component based on type
const getNotificationIcon = (type) => {
  const icons = {
    info: InformationCircleIcon,
    warning: ExclamationTriangleIcon,
    success: CheckCircleIcon,
    error: XCircleIcon,
    report: DocumentTextIcon,
    computer: ComputerDesktopIcon,
    access_request: LockClosedIcon,
    announcement: MegaphoneIcon,
  };
  return icons[type] || InformationCircleIcon;
};

// Get notification color based on type
const getNotificationColor = (type) => {
  const colors = {
    info: 'bg-blue-100 text-blue-600',
    warning: 'bg-yellow-100 text-yellow-600',
    success: 'bg-green-100 text-green-600',
    error: 'bg-red-100 text-red-600',
    report: 'bg-purple-100 text-purple-600',
    computer: 'bg-gray-100 text-gray-600',
    access_request: 'bg-indigo-100 text-indigo-600',
    announcement: 'bg-orange-100 text-orange-600',
  };
  return colors[type] || 'bg-gray-100 text-gray-600';
};

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    notificationStore.fetchRecentNotifications();
  }
};

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isOpen.value = false;
  }
};

const handleMarkAsRead = async (notification) => {
  if (!notification.is_read) {
    await notificationStore.markAsRead(notification.id);
  }
};

const handleMarkAllAsRead = async () => {
  await notificationStore.markAllAsRead();
};

const handleDelete = async (notificationId, event) => {
  event.stopPropagation();
  await notificationStore.deleteNotification(notificationId);
};

// Setup real-time WebSocket listener
const setupRealtimeListener = () => {
  if (!window.Echo) {
    console.warn('Echo not available for notifications');
    return;
  }

  // Listen on main-channel for notification events
  window.Echo.channel('main-channel')
    .listen('MainEvent', (e) => {
      console.log('🔔 Notification event received:', e);
      if (e.type === 'Notification') {
        if (e.action === 'created') {
          notificationStore.addNotification(e.data);
          console.log('ew notification added, count:', notificationStore.unreadCount);
        } else if (e.action === 'updated') {
          notificationStore.updateNotification(e.data);
        }
      }
    });
    
  console.log('🔔 Notification listener registered on main-channel');
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  
  // Fetch initial notifications and unread count
  notificationStore.fetchRecentNotifications();
  notificationStore.fetchUnreadCount();
  
  // Setup real-time WebSocket listener
  setupRealtimeListener();
  
  // Poll for unread count every 30 seconds as fallback
  pollInterval = setInterval(() => {
    notificationStore.fetchUnreadCount();
  }, 30000);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  if (pollInterval) {
    clearInterval(pollInterval);
  }
});
</script>

<template>
  <div ref="dropdownRef" class="relative">
    <!-- Notification Bell Button -->
    <button
      @click="toggleDropdown"
      class="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-300"
    >
      <component
        :is="notificationStore.hasUnread ? BellAlertIcon : BellIcon"
        class="h-6 w-6"
        :class="{ 'text-gray-900 animate-pulse': notificationStore.hasUnread }"
      />
      
      <!-- Unread Badge -->
      <span
        v-if="notificationStore.unreadCount > 0"
        class="absolute -top-1 -right-1 flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold text-white bg-red-500 rounded-full"
      >
        {{ notificationStore.unreadCount > 99 ? '99+' : notificationStore.unreadCount }}
      </span>
    </button>

    <!-- Dropdown Panel -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-show="isOpen"
        class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 overflow-hidden z-50"
      >
        <!-- Header -->
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
          <div class="flex items-center space-x-2">
            <button
              v-if="notificationStore.unreadCount > 0"
              @click="handleMarkAllAsRead"
              class="text-xs text-blue-600 hover:text-blue-800 font-medium"
            >
              Mark all as read
            </button>
          </div>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
          <!-- Loading State -->
          <div v-if="notificationStore.isLoading" class="p-4 text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 mx-auto"></div>
            <p class="text-sm text-gray-500 mt-2">Loading...</p>
          </div>

          <!-- Empty State -->
          <div
            v-else-if="notificationStore.notifications.length === 0"
            class="p-8 text-center"
          >
            <BellIcon class="h-12 w-12 text-gray-300 mx-auto mb-3" />
            <p class="text-sm text-gray-500">No notifications yet</p>
          </div>

          <!-- Notification Items -->
          <div v-else>
            <div
              v-for="notification in notificationStore.notifications"
              :key="notification.id"
              @click="handleMarkAsRead(notification)"
              :class="[
                'px-4 py-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors duration-150',
                !notification.is_read ? 'bg-blue-50' : ''
              ]"
            >
              <div class="flex items-start space-x-3">
                <!-- Icon -->
                <div
                  :class="[
                    'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center',
                    getNotificationColor(notification.type)
                  ]"
                >
                  <component 
                    :is="getNotificationIcon(notification.type)" 
                    class="h-5 w-5"
                  />
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-start justify-between">
                    <p
                      :class="[
                        'text-sm',
                        !notification.is_read ? 'font-semibold text-gray-900' : 'font-medium text-gray-700'
                      ]"
                    >
                      {{ notification.title }}
                    </p>
                    
                    <!-- Delete Button -->
                    <button
                      @click="handleDelete(notification.id, $event)"
                      class="flex-shrink-0 ml-2 p-1 text-gray-400 hover:text-red-500 rounded transition-colors"
                    >
                      <XMarkIcon class="h-4 w-4" />
                    </button>
                  </div>
                  
                  <p class="text-sm text-gray-600 mt-0.5 line-clamp-2">
                    {{ notification.message }}
                  </p>
                  
                  <p class="text-xs text-gray-400 mt-1">
                    {{ formatTimeAgo(notification.created_at) }}
                  </p>
                </div>

                <!-- Unread Indicator -->
                <div
                  v-if="!notification.is_read"
                  class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full mt-2"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 text-center">
          <router-link
            to="/notifications"
            @click="isOpen = false"
            class="text-sm text-blue-600 hover:text-blue-800 font-medium"
          >
            View all notifications
          </router-link>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
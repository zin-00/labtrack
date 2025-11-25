<script setup>
import { ref, onMounted, computed } from 'vue';
import { useNotificationStore } from '../../store/notifications/notifications';
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
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
const currentPage = ref(1);
const filterType = ref('all'); // 'all', 'unread', 'read'

// Filtered notifications based on selected filter
const filteredNotifications = computed(() => {
  if (filterType.value === 'unread') {
    return notificationStore.notifications.filter(n => !n.is_read);
  } else if (filterType.value === 'read') {
    return notificationStore.notifications.filter(n => n.is_read);
  }
  return notificationStore.notifications;
});

// Format time ago
const formatTimeAgo = (dateString) => {
  const date = new Date(dateString);
  const now = new Date();
  const diffInSeconds = Math.floor((now - date) / 1000);
  
  if (diffInSeconds < 60) return 'Just now';
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
  if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} days ago`;
  
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
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
    info: 'bg-blue-100 text-blue-700 border-blue-200',
    warning: 'bg-yellow-100 text-yellow-700 border-yellow-200',
    success: 'bg-green-100 text-green-700 border-green-200',
    error: 'bg-red-100 text-red-700 border-red-200',
    report: 'bg-purple-100 text-purple-700 border-purple-200',
    computer: 'bg-gray-100 text-gray-700 border-gray-200',
    access_request: 'bg-indigo-100 text-indigo-700 border-indigo-200',
    announcement: 'bg-orange-100 text-orange-700 border-orange-200',
  };
  return colors[type] || 'bg-gray-100 text-gray-700 border-gray-200';
};

// Get notification badge color based on type
const getBadgeColor = (type) => {
  const colors = {
    info: 'bg-blue-100 text-blue-800',
    warning: 'bg-yellow-100 text-yellow-800',
    success: 'bg-green-100 text-green-800',
    error: 'bg-red-100 text-red-800',
    report: 'bg-purple-100 text-purple-800',
    computer: 'bg-gray-100 text-gray-800',
    access_request: 'bg-indigo-100 text-indigo-800',
    announcement: 'bg-orange-100 text-orange-800',
  };
  return colors[type] || 'bg-gray-100 text-gray-800';
};

const handleMarkAsRead = async (notification) => {
  if (!notification.is_read) {
    await notificationStore.markAsRead(notification.id);
  }
};

const handleMarkAllAsRead = async () => {
  await notificationStore.markAllAsRead();
};

const handleDelete = async (notificationId) => {
  await notificationStore.deleteNotification(notificationId);
};

const handleClearRead = async () => {
  await notificationStore.clearReadNotifications();
};

const loadMore = async () => {
  if (currentPage.value < notificationStore.meta.last_page) {
    currentPage.value++;
    await notificationStore.fetchNotifications(currentPage.value);
  }
};

onMounted(() => {
  notificationStore.fetchNotifications();
});
</script>

<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">
        Notifications
      </h2>
    </template>

    <div class="max-w-4xl mx-auto">
      <!-- Header Actions -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <!-- Filter Tabs -->
          <div class="flex items-center space-x-1 bg-gray-100 rounded-lg p-1">
            <button
              @click="filterType = 'all'"
              :class="[
                'px-4 py-2 text-sm font-medium rounded-md transition-colors',
                filterType === 'all' 
                  ? 'bg-white text-gray-900 shadow-sm' 
                  : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              All
            </button>
            <button
              @click="filterType = 'unread'"
              :class="[
                'px-4 py-2 text-sm font-medium rounded-md transition-colors',
                filterType === 'unread' 
                  ? 'bg-white text-gray-900 shadow-sm' 
                  : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              Unread
              <span 
                v-if="notificationStore.unreadCount > 0"
                class="ml-1.5 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full"
              >
                {{ notificationStore.unreadCount }}
              </span>
            </button>
            <button
              @click="filterType = 'read'"
              :class="[
                'px-4 py-2 text-sm font-medium rounded-md transition-colors',
                filterType === 'read' 
                  ? 'bg-white text-gray-900 shadow-sm' 
                  : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              Read
            </button>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center space-x-3">
            <button
              v-if="notificationStore.unreadCount > 0"
              @click="handleMarkAllAsRead"
              class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors"
            >
              <CheckIcon class="h-4 w-4 mr-1.5" />
              Mark all as read
            </button>
            <button
              @click="handleClearRead"
              class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
            >
              <TrashIcon class="h-4 w-4 mr-1.5" />
              Clear read
            </button>
          </div>
        </div>
      </div>

      <!-- Notifications List -->
      <div class="space-y-3">
        <!-- Loading State -->
        <div v-if="notificationStore.isLoading && notificationStore.notifications.length === 0" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-gray-900 mx-auto"></div>
          <p class="text-gray-500 mt-4">Loading notifications...</p>
        </div>

        <!-- Empty State -->
        <div
          v-else-if="filteredNotifications.length === 0"
          class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center"
        >
          <BellIcon class="h-16 w-16 text-gray-300 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 mb-2">No notifications</h3>
          <p class="text-gray-500">
            {{ filterType === 'unread' ? "You're all caught up!" : "You don't have any notifications yet." }}
          </p>
        </div>

        <!-- Notification Items -->
        <div
          v-for="notification in filteredNotifications"
          :key="notification.id"
          :class="[
            'bg-white rounded-lg shadow-sm border overflow-hidden transition-all duration-200 hover:shadow-md',
            !notification.is_read ? 'border-blue-200 bg-blue-50/30' : 'border-gray-200'
          ]"
        >
          <div class="p-4 sm:p-5">
            <div class="flex items-start space-x-4">
              <!-- Icon -->
              <div
                :class="[
                  'flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center border',
                  getNotificationColor(notification.type)
                ]"
              >
                <component 
                  :is="getNotificationIcon(notification.type)" 
                  class="h-6 w-6"
                />
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                  <div>
                    <p
                      :class="[
                        'text-base',
                        !notification.is_read ? 'font-semibold text-gray-900' : 'font-medium text-gray-700'
                      ]"
                    >
                      {{ notification.title }}
                    </p>
                    <span
                      :class="[
                        'inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full capitalize',
                        getBadgeColor(notification.type)
                      ]"
                    >
                      {{ notification.type.replace('_', ' ') }}
                    </span>
                  </div>
                  
                  <!-- Unread Indicator -->
                  <div
                    v-if="!notification.is_read"
                    class="flex-shrink-0 w-3 h-3 bg-blue-500 rounded-full"
                  ></div>
                </div>
                
                <p class="text-gray-600 mt-2">
                  {{ notification.message }}
                </p>
                
                <div class="flex items-center justify-between mt-3">
                  <p class="text-sm text-gray-400">
                    {{ formatTimeAgo(notification.created_at) }}
                  </p>
                  
                  <div class="flex items-center space-x-2">
                    <button
                      v-if="!notification.is_read"
                      @click="handleMarkAsRead(notification)"
                      class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded transition-colors"
                    >
                      <CheckIcon class="h-3.5 w-3.5 mr-1" />
                      Mark as read
                    </button>
                    <button
                      @click="handleDelete(notification.id)"
                      class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-gray-500 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                    >
                      <TrashIcon class="h-3.5 w-3.5 mr-1" />
                      Delete
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Load More -->
        <div
          v-if="notificationStore.meta.current_page < notificationStore.meta.last_page"
          class="text-center pt-4"
        >
          <button
            @click="loadMore"
            :disabled="notificationStore.isLoading"
            class="inline-flex items-center px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 transition-colors"
          >
            <span v-if="notificationStore.isLoading">Loading...</span>
            <span v-else>Load more</span>
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
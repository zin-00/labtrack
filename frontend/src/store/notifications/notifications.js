import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';
import { useApiUrl } from '../../api/api';

export const useNotificationStore = defineStore('notifications', () => {
  const { api, getAuthHeader } = useApiUrl();

  // State
  const notifications = ref([]);
  const unreadCount = ref(0);
  const isLoading = ref(false);
  const error = ref(null);
  const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
  });

  // Computed
  const hasUnread = computed(() => unreadCount.value > 0);
  const recentNotifications = computed(() => notifications.value.slice(0, 10));

  // Actions
  const fetchNotifications = async (page = 1) => {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await axios.get(`${api}/notifications?page=${page}`, getAuthHeader());
      notifications.value = response.data.data;
      unreadCount.value = response.data.unread_count;
      meta.value = response.data.meta;
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch notifications';
      console.error('Error fetching notifications:', err);
    } finally {
      isLoading.value = false;
    }
  };

  const fetchRecentNotifications = async (limit = 10) => {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await axios.get(`${api}/notifications/recent?limit=${limit}`, getAuthHeader());
      notifications.value = response.data.data;
      unreadCount.value = response.data.unread_count;
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch notifications';
      console.error('Error fetching recent notifications:', err);
    } finally {
      isLoading.value = false;
    }
  };

  const fetchUnreadCount = async () => {
    try {
      const response = await axios.get(`${api}/notifications/unread-count`, getAuthHeader());
      unreadCount.value = response.data.count;
    } catch (err) {
      console.error('Error fetching unread count:', err);
    }
  };

  const markAsRead = async (notificationId) => {
    try {
      await axios.put(`${api}/notifications/${notificationId}/read`, {}, getAuthHeader());
      
      // Update local state
      const index = notifications.value.findIndex(n => n.id === notificationId);
      if (index !== -1) {
        notifications.value[index].is_read = true;
        notifications.value[index].read_at = new Date().toISOString();
      }
      
      // Decrement unread count
      if (unreadCount.value > 0) {
        unreadCount.value--;
      }
    } catch (err) {
      console.error('Error marking notification as read:', err);
      throw err;
    }
  };

  const markAllAsRead = async () => {
    try {
      await axios.put(`${api}/notifications/mark-all-read`, {}, getAuthHeader());
      
      // Update local state
      notifications.value = notifications.value.map(n => ({
        ...n,
        is_read: true,
        read_at: new Date().toISOString(),
      }));
      
      unreadCount.value = 0;
    } catch (err) {
      console.error('Error marking all as read:', err);
      throw err;
    }
  };

  const deleteNotification = async (notificationId) => {
    try {
      await axios.delete(`${api}/notifications/${notificationId}`, getAuthHeader());
      
      // Remove from local state
      const notification = notifications.value.find(n => n.id === notificationId);
      if (notification && !notification.is_read) {
        unreadCount.value = Math.max(0, unreadCount.value - 1);
      }
      
      notifications.value = notifications.value.filter(n => n.id !== notificationId);
    } catch (err) {
      console.error('Error deleting notification:', err);
      throw err;
    }
  };

  const clearReadNotifications = async () => {
    try {
      await axios.delete(`${api}/notifications/clear-read`, getAuthHeader());
      
      // Remove read notifications from local state
      notifications.value = notifications.value.filter(n => !n.is_read);
    } catch (err) {
      console.error('Error clearing read notifications:', err);
      throw err;
    }
  };

  // Add new notification (for real-time updates)
  const addNotification = (notification) => {
    notifications.value.unshift(notification);
    if (!notification.is_read) {
      unreadCount.value++;
    }
  };

  // Update notification from real-time event
  const updateNotification = (notification) => {
    const index = notifications.value.findIndex(n => n.id === notification.id);
    if (index !== -1) {
      const wasUnread = !notifications.value[index].is_read;
      const isNowRead = notification.is_read;
      
      notifications.value[index] = notification;
      
      if (wasUnread && isNowRead) {
        unreadCount.value = Math.max(0, unreadCount.value - 1);
      }
    }
  };

  // Reset store
  const reset = () => {
    notifications.value = [];
    unreadCount.value = 0;
    error.value = null;
    meta.value = {
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 0,
    };
  };

  return {
    // State
    notifications,
    unreadCount,
    isLoading,
    error,
    meta,
    
    // Computed
    hasUnread,
    recentNotifications,
    
    // Actions
    fetchNotifications,
    fetchRecentNotifications,
    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
    deleteNotification,
    clearReadNotifications,
    addNotification,
    updateNotification,
    reset,
  };
});

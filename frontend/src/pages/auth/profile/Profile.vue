<script setup>
import AuthenticatedLayout from '../../../layouts/auth/AuthenticatedLayout.vue';
import Modal from '../../../components/modal/Modal.vue';
import { 
  ArrowRightOnRectangleIcon,
  PencilIcon,
  TrashIcon,
  PlusIcon,
  UserPlusIcon,
  DocumentTextIcon
} from '@heroicons/vue/24/outline';
import { useAdminProfileStore } from '../../../composable/admin/profile/profile';
import { useStates } from '../../../composable/states';
import { toRefs, onMounted, ref } from 'vue';
import { useApiUrl } from '../../../api/api';
import axios from 'axios';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';

dayjs.extend(relativeTime);

const { api, getAuthHeader } = useApiUrl();
const profile = useAdminProfileStore();
const states = useStates();
const { showAdminProfile, updateAdminProfile, changeAdminPassword } = profile;
const { isEditing, user, showPassword } = toRefs(states);

// Modal states
const showProfileModal = ref(false);
const showPasswordModal = ref(false);
const tempUserData = ref({});
const passwordData = ref({
  current: '',
  new: '',
  confirm: ''
});

// Recent activity data (audit logs)
const recentActivity = ref([]);
const isLoadingActivity = ref(false);

// Stats data
const stats = ref([
  { label: 'Total Logins', value: '0', change: null, trend: null },
  { label: 'Active Sessions', value: '1', change: null, trend: null },
  { label: 'Last Login', value: 'Loading...', change: null, trend: null },
]);

// Get icon based on action type
const getActionIcon = (action) => {
  const actionLower = action?.toLowerCase() || '';
  if (actionLower.includes('create') || actionLower.includes('add') || actionLower.includes('import')) {
    return PlusIcon;
  } else if (actionLower.includes('update') || actionLower.includes('edit')) {
    return PencilIcon;
  } else if (actionLower.includes('delete') || actionLower.includes('remove')) {
    return TrashIcon;
  } else if (actionLower.includes('login') || actionLower.includes('logout')) {
    return ArrowRightOnRectangleIcon;
  } else if (actionLower.includes('register') || actionLower.includes('approve')) {
    return UserPlusIcon;
  }
  return DocumentTextIcon;
};

const fetchRecentActivity = async () => {
  try {
    isLoadingActivity.value = true;
    const response = await axios.get(`${api}/audit-logs?per_page=5`, getAuthHeader());
    
    // Get top 5 recent audit logs
    const logs = response.data.audit_logs?.data || response.data.audit_logs || [];
    recentActivity.value = logs.slice(0, 5).map(log => ({
      id: log.id,
      action: log.action,
      description: log.description || `${log.action} on ${log.entity_type}`,
      time: dayjs(log.created_at).fromNow(),
      date: dayjs(log.created_at).format('MMM D, YYYY h:mm A'),
      entityType: log.entity_type,
      user: log.user?.name || 'System'
    }));
      
  } catch (error) {
    console.error('Failed to fetch recent activity:', error);
  } finally {
    isLoadingActivity.value = false;
  }
};

const fetchLoginHistory = async () => {
  try {
    const response = await axios.get(`${api}/auth/user/login-history`, getAuthHeader());
    
    const lastLogin = response.data.last_login;
    const totalLogins = response.data.total_logins;
    
    // Update stats
    stats.value[0].value = totalLogins?.toString() || '0';
    stats.value[2].value = lastLogin 
      ? dayjs(lastLogin.created_at).format('MMM D, h:mm A')
      : 'First login';
      
  } catch (error) {
    console.error('Failed to fetch login history:', error);
  }
};

const toggleEdit = () => {
  tempUserData.value = {...user.value};
  showProfileModal.value = true;
};

const togglePasswordEdit = () => {
  passwordData.value = { current: '', new: '', confirm: '' };
  showPasswordModal.value = true;
};

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};

const saveProfile = async () => {
  try {
    if (!user.value.id) {
      console.error('User ID is missing');
      return;
    }
    
    await updateAdminProfile(user.value.id, tempUserData.value);
    user.value = {...tempUserData.value};
    showProfileModal.value = false;
  } catch (error) {
    console.error('Error saving profile:', error);
  }
};

const savePassword = async () => {
  try {
    // Validate passwords match
    if (passwordData.value.new !== passwordData.value.confirm) {
      alert('New passwords do not match');
      return;
    }
    
    if (!user.value.id) {
      console.error('User ID is missing');
      return;
    }
    
    await changeAdminPassword(user.value.id, {
      current_password: passwordData.value.current,
      new_password: passwordData.value.new,
      new_password_confirmation: passwordData.value.confirm
    });
    
    showPasswordModal.value = false;
    passwordData.value = { current: '', new: '', confirm: '' };
  } catch (error) {
    console.error('Error changing password:', error);
  }
};

const cancelEdit = () => {
  showProfileModal.value = false;
};

const cancelPasswordEdit = () => {
  showPasswordModal.value = false;
};

onMounted(() => {
  showAdminProfile();
  fetchRecentActivity();
  fetchLoginHistory();
});
</script>

<template>
  <AuthenticatedLayout>
    <div class="py-6 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-xl font-medium text-gray-900">Profile</h1>
        <p class="text-sm text-gray-500 mt-0.5">Manage your account settings</p>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Left Column - Profile Information -->
        <div class="lg:col-span-2 space-y-5">
          <!-- Profile Card -->
          <div class="bg-white rounded-lg border border-gray-200">
            <div class="p-6">
              <!-- Profile Avatar Section -->
              <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                  <div class="w-14 h-14 bg-gray-900 rounded-full flex items-center justify-center">
                    <span class="text-lg font-medium text-white">
                      {{ user.name ? user.name.split(' ').map(n => n[0]).join('') : '' }}
                    </span>
                  </div>
                  <div>
                    <h3 class="text-base font-medium text-gray-900">{{ user.name }}</h3>
                    <div class="flex items-center gap-2 mt-1">
                      <span class="text-sm text-gray-500">{{ user.roles }}</span>
                      <span class="text-gray-300">•</span>
                      <span 
                        class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full"
                        :class="{
                          'bg-green-50 text-green-700': user.status === 'Active',
                          'bg-gray-100 text-gray-600': user.status === 'Inactive',
                          'bg-red-50 text-red-700': user.status === 'Suspended'
                        }"
                      >
                        {{ user.status }}
                      </span>
                    </div>
                  </div>
                </div>
                <button 
                  @click="toggleEdit"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                >
                  Edit
                </button>
              </div>

              <!-- Profile Details Grid -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Full Name</label>
                  <p class="text-sm text-gray-900">{{ user.name || 'Not provided' }}</p>
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Email</label>
                  <p class="text-sm text-gray-900">{{ user.email }}</p>
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Role</label>
                  <p class="text-sm text-gray-900">{{ user.roles }}</p>
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Password</label>
                  <div class="flex items-center gap-2">
                    <p class="text-sm text-gray-900">••••••••</p>
                    <button @click="togglePasswordEdit" class="text-xs text-gray-500 hover:text-gray-700 underline">
                      Change
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Stats Section -->
          <div class="bg-white rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-100">
              <h2 class="text-sm font-medium text-gray-900">Account Overview</h2>
            </div>
            <div class="p-6">
              <div class="grid grid-cols-3 gap-4">
                <div v-for="stat in stats" :key="stat.label" class="text-center p-4 bg-gray-50 rounded-lg">
                  <p class="text-xl font-semibold text-gray-900">{{ stat.value }}</p>
                  <p class="text-xs text-gray-500 mt-1">{{ stat.label }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Recent Activity -->
        <div>
          <div class="bg-white rounded-lg border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100">
              <h2 class="text-sm font-medium text-gray-900">Recent Activity</h2>
            </div>
            <div class="p-4">
              <div v-if="isLoadingActivity" class="text-center py-8">
                <p class="text-xs text-gray-400">Loading...</p>
              </div>
              <div v-else-if="recentActivity.length > 0" class="space-y-2">
                <div v-for="activity in recentActivity" :key="activity.id" 
                     class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                  <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <component :is="getActionIcon(activity.action)" class="w-4 h-4 text-gray-500" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-gray-900 truncate">{{ activity.action }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ activity.description }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ activity.time }}</p>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8">
                <p class="text-xs text-gray-400">No recent activity</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Profile Modal -->
    <Modal :show="showProfileModal" @close="cancelEdit" max-width="md">
      <div class="relative bg-white rounded-lg">
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-gray-100">
          <h3 class="text-lg font-semibold text-gray-900">Edit Profile</h3>
          <p class="text-xs text-gray-500 mt-1">Update your account information</p>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6">
          <div class="space-y-5">
            <!-- Name Field -->
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-2 uppercase tracking-wider">
                Full Name
              </label>
              <input
                v-model="tempUserData.name"
                type="text"
                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent text-sm"
                placeholder="Enter your full name"
              />
            </div>

            <!-- Email Field -->
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-2 uppercase tracking-wider">
                Email Address
              </label>
              <input
                v-model="tempUserData.email"
                type="email"
                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent text-sm"
                placeholder="Enter your email"
              />
            </div>
          </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-lg flex justify-end space-x-3">
          <button 
            @click="cancelEdit"
            class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors duration-200 text-sm font-medium"
          >
            Cancel
          </button>
          <button 
            @click="saveProfile"
            class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors duration-200 text-sm font-medium shadow-sm"
          >
            Save Changes
          </button>
        </div>
      </div>
    </Modal>

    <!-- Change Password Modal -->
    <Modal :show="showPasswordModal" @close="cancelPasswordEdit" max-width="md">
      <div class="relative bg-white rounded-lg">
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-gray-100">
          <h3 class="text-lg font-semibold text-gray-900">Change Password</h3>
          <p class="text-xs text-gray-500 mt-1">Update your account password</p>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6">
          <div class="space-y-5">
            <!-- Current Password Field -->
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-2 uppercase tracking-wider">
                Current Password
              </label>
              <input
                v-model="passwordData.current"
                type="password"
                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent text-sm"
                placeholder="Enter current password"
              />
            </div>

            <!-- New Password Field -->
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-2 uppercase tracking-wider">
                New Password
              </label>
              <input
                v-model="passwordData.new"
                type="password"
                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent text-sm"
                placeholder="Enter new password"
              />
            </div>

            <!-- Confirm Password Field -->
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-2 uppercase tracking-wider">
                Confirm Password
              </label>
              <input
                v-model="passwordData.confirm"
                type="password"
                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent text-sm"
                placeholder="Confirm new password"
              />
            </div>

            <!-- Password Requirements -->
            <div class="bg-blue-50 border border-blue-100 p-4 rounded-lg">
              <p class="text-xs font-semibold text-blue-900 mb-2">Password Requirements</p>
              <ul class="text-xs text-blue-800 space-y-1.5">
                <li class="flex items-center">
                  <span class="mr-2">✓</span>
                  <span>At least 8 characters</span>
                </li>
                <li class="flex items-center">
                  <span class="mr-2">✓</span>
                  <span>One uppercase letter</span>
                </li>
                <li class="flex items-center">
                  <span class="mr-2">✓</span>
                  <span>One number</span>
                </li>
                <li class="flex items-center">
                  <span class="mr-2">✓</span>
                  <span>One special character</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-lg flex justify-end space-x-3">
          <button 
            @click="cancelPasswordEdit"
            class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors duration-200 text-sm font-medium"
          >
            Cancel
          </button>
          <button 
            @click="savePassword"
            class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors duration-200 text-sm font-medium shadow-sm"
          >
            Update Password
          </button>
        </div>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>
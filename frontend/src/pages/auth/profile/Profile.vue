<script setup>
import AuthenticatedLayout from '../../../layouts/auth/AuthenticatedLayout.vue';
import Modal from '../../../components/modal/Modal.vue';
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

// Login history data
const loginHistory = ref([]);
const lastLogin = ref(null);
const totalLogins = ref(0);
const isLoadingHistory = ref(false);

// Stats data
const stats = ref([
  { label: 'Total Logins', value: '0', change: null, trend: null },
  { label: 'Active Sessions', value: '1', change: null, trend: null },
  { label: 'Last Login', value: 'Loading...', change: null, trend: null },
]);

const fetchLoginHistory = async () => {
  try {
    isLoadingHistory.value = true;
    const response = await axios.get(`${api}/auth/user/login-history`, getAuthHeader());
    
    // Limit to 5 entries only
    loginHistory.value = response.data.login_history.slice(0, 5).map(log => ({
      id: log.id,
      action: `Logged in from ${log.ip_address}`,
      time: dayjs(log.created_at).fromNow(),
      date: dayjs(log.created_at).format('MMM D, YYYY h:mm A'),
      icon: '�',
      type: 'authentication'
    }));
    
    lastLogin.value = response.data.last_login;
    totalLogins.value = response.data.total_logins;
    
    // Update stats
    stats.value[0].value = totalLogins.value.toString();
    stats.value[2].value = lastLogin.value 
      ? dayjs(lastLogin.value.created_at).format('MMM D, h:mm A')
      : 'First login';
      
  } catch (error) {
    console.error('Failed to fetch login history:', error);
  } finally {
    isLoadingHistory.value = false;
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
  fetchLoginHistory();
});
</script>

<template>
  <AuthenticatedLayout>
    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-3xl font-semibold text-gray-900">Profile</h1>
            <p class="text-sm text-gray-500 mt-1">Manage your account settings</p>
          </div>
          <button 
            @click="toggleEdit"
            class="px-5 py-2.5 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors duration-200 text-sm font-medium shadow-sm"
          >
            Edit Profile
          </button>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Profile Information -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Profile Card -->
          <div class="bg-white shadow-sm rounded-lg border border-gray-100">
            <div class="px-8 py-8">
              <!-- Profile Avatar Section -->
              <div class="flex items-center mb-10">
                <div class="w-20 h-20 bg-gradient-to-br from-gray-900 to-gray-700 rounded-full flex items-center justify-center shadow-md">
                  <span class="text-2xl font-semibold text-white">
                    {{ user.name ? user.name.split(' ').map(n => n[0]).join('') : '' }}
                  </span>
                </div>
                <div class="ml-6">
                  <h3 class="text-xl font-semibold text-gray-900">{{ user.name }}</h3>
                  <p class="text-sm text-gray-500 mt-0.5">{{ user.roles }}</p>
                  <div class="flex items-center mt-3">
                    <span 
                      class="inline-flex px-3 py-1 text-xs font-medium rounded-full"
                      :class="{
                        'bg-green-50 text-green-700 border border-green-200': user.status === 'Active',
                        'bg-gray-50 text-gray-600 border border-gray-200': user.status === 'Inactive',
                        'bg-red-50 text-red-700 border border-red-200': user.status === 'Suspended'
                      }"
                    >
                      {{ user.status }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Profile Details Grid -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Name Field -->
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-2 uppercase tracking-wider">
                    Full Name
                  </label>
                  <div class="px-4 py-3 bg-gray-50 rounded-lg text-sm text-gray-900">
                    {{ user.name || 'Not provided' }}
                  </div>
                </div>

                <!-- Email Field -->
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-2 uppercase tracking-wider">
                    Email Address
                  </label>
                  <div class="px-4 py-3 bg-gray-50 rounded-lg text-sm text-gray-900">
                    {{ user.email }}
                  </div>
                </div>

                <!-- Role Field -->
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-2 uppercase tracking-wider">
                    Role
                  </label>
                  <div class="px-4 py-3 bg-gray-50 rounded-lg text-sm text-gray-900">
                    {{ user.roles }}
                  </div>
                </div>

                <!-- Password Field -->
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-2 uppercase tracking-wider">
                    Password
                  </label>
                  <div class="px-4 py-3 bg-gray-50 rounded-lg text-sm text-gray-900 flex justify-between items-center">
                    <span>••••••••••</span>
                    <button @click="togglePasswordEdit" class="text-xs text-gray-600 hover:text-gray-900 font-medium">
                      Change
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Stats Section -->
          <div class="bg-white shadow-sm rounded-lg border border-gray-100">
            <div class="px-8 py-6">
              <h2 class="text-base font-semibold text-gray-900 mb-5">Account Overview</h2>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div v-for="stat in stats" :key="stat.label" class="bg-gray-50 p-5 rounded-lg border border-gray-100">
                  <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">{{ stat.label }}</p>
                  <p class="text-2xl font-semibold text-gray-900 mt-2">{{ stat.value }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Recent Activity -->
        <div class="space-y-6">
          <!-- Activities Card -->
          <div class="bg-white shadow-sm rounded-lg border border-gray-100">
            <div class="px-6 py-5">
              <h2 class="text-sm font-semibold text-gray-900 mb-4">Recent Activity</h2>
              <div v-if="isLoadingHistory" class="text-center py-8">
                <p class="text-xs text-gray-400">Loading...</p>
              </div>
              <div v-else-if="loginHistory.length > 0" class="space-y-2">
                <div v-for="activity in loginHistory" :key="activity.id" 
                     class="flex items-start p-2.5 rounded-md bg-gray-50">
                  <div class="flex-shrink-0 mr-2 text-sm">{{ activity.icon }}</div>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-gray-900 truncate">{{ activity.action }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ activity.time }}</p>
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
<script setup>
import AuthenticatedLayout from '../../../layouts/auth/AuthenticatedLayout.vue';
import { useAdminProfileStore } from '../../../composable/admin/profile/profile';
import { useStates } from '../../../composable/states';
import { toRefs, onMounted, ref } from 'vue';

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

// Activity data (mock data for demonstration)
const activities = ref([
  { id: 1, action: 'Logged in', time: '2 hours ago', icon: '🔐', type: 'authentication' },
  { id: 2, action: 'Updated user permissions', time: 'Yesterday', icon: '👥', type: 'user_management' },
  { id: 3, action: 'Created new post', time: '2 days ago', icon: '📝', type: 'content' },
  { id: 4, action: 'Changed password', time: '1 week ago', icon: '🔑', type: 'security' },
  { id: 5, action: 'Updated profile information', time: '2 weeks ago', icon: '👤', type: 'profile' },
]);

// Stats data
const stats = ref([
  { label: 'Total Logins', value: '247', change: '+12%', trend: 'up' },
  { label: 'Active Sessions', value: '1', change: null, trend: null },
  { label: 'Last Login', value: 'Today, 09:42', change: null, trend: null },
]);

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

const saveProfile = () => {
  user.value = {...tempUserData.value};
  showProfileModal.value = false;
  console.log('Profile saved:', user.value);
};

const savePassword = () => {
  // Validate passwords match
  if (passwordData.value.new !== passwordData.value.confirm) {
    alert('New passwords do not match');
    return;
  }
  
  // Here you would call the API to change the password
  console.log('Password changed');
  showPasswordModal.value = false;
};

const cancelEdit = () => {
  showProfileModal.value = false;
};

const cancelPasswordEdit = () => {
  showPasswordModal.value = false;
};

onMounted(() => {
  showAdminProfile();
});
</script>

<template>
  <AuthenticatedLayout>
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="bg-white shadow-sm border border-gray-200 rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex justify-between items-center">
            <div>
              <h1 class="text-2xl text-gray-900">Admin Profile</h1>
              <p class="text-sm text-gray-600 mt-1">Manage your administrative account settings</p>
            </div>
            <button 
              @click="toggleEdit"
              class="px-4 py-2 bg-gray-900 text-white rounded-md hover:bg-gray-800 transition-colors duration-200 text-sm font-medium"
            >
              Edit Profile
            </button>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Profile Information -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Profile Card -->
          <div class="bg-white shadow-sm border border-gray-200 rounded-lg">
            <div class="px-6 py-6">
              <!-- Profile Avatar Section -->
              <div class="flex items-center mb-8">
                <div class="w-24 h-24 bg-gray-800 rounded-full flex items-center justify-center">
                  <span class="text-3xl font-bold text-white">
                    {{ user.name ? user.name.split(' ').map(n => n[0]).join('') : '' }}
                  </span>
                </div>
                <div class="ml-6">
                  <h3 class="text-xl font-semibold text-gray-900">{{ user.name }}</h3>
                  <p class="text-gray-600">{{ user.roles }}</p>
                  <div class="flex items-center mt-2">
                    <span 
                      class="inline-flex px-3 py-1 text-xs font-medium rounded-full"
                      :class="{
                        'bg-gray-100 text-gray-800': user.status === 'Active',
                        'bg-gray-100 text-gray-800': user.status === 'Inactive',
                        'bg-gray-100 text-gray-800': user.status === 'Suspended'
                      }"
                    >
                      {{ user.status }}
                    </span>
                    <span class="ml-3 text-xs text-gray-500">Member since Jan 2023</span>
                  </div>
                </div>
              </div>

              <!-- Profile Details Grid -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name Field -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Full Name
                  </label>
                  <div class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-md text-gray-900">
                    {{ user.name || 'Not provided' }}
                  </div>
                </div>

                <!-- Email Field -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Email Address
                  </label>
                  <div class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-md text-gray-900">
                    {{ user.email }}
                  </div>
                </div>

                <!-- Password Field -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Password
                  </label>
                  <div class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-md text-gray-900 flex justify-between items-center">
                    <span>••••••••••</span>
                    <button @click="togglePasswordEdit" class="text-xs text-gray-600 hover:text-gray-900">
                      Change
                    </button>
                  </div>
                </div>

                <!-- Role Field -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Role
                  </label>
                  <div class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-md text-gray-900">
                    {{ user.roles }}
                  </div>
                </div>

                <!-- Status Field -->
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Account Status
                  </label>
                  <div class="flex items-center">
                    <span 
                      class="inline-flex px-3 py-1 text-sm font-medium rounded-full"
                      :class="{
                        'bg-gray-100 text-gray-800': user.status === 'Active',
                        'bg-gray-100 text-gray-800': user.status === 'Inactive',
                        'bg-gray-100 text-gray-800': user.status === 'Suspended'
                      }"
                    >
                      {{ user.status }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Profile Actions Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-lg">
              <div class="flex justify-between items-center">
                <div class="text-sm text-gray-600">
                  Last updated: {{ new Date().toLocaleDateString() }}
                </div>
                <div class="flex space-x-3">
                  <button 
                    @click="togglePasswordEdit"
                    class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 transition-colors"
                  >
                    Change Password
                  </button>
                  <button class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 transition-colors">
                    Download Data
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Stats Section -->
          <div class="bg-white shadow-sm border border-gray-200 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-semibold text-gray-900">Account Overview</h2>
            </div>
            <div class="p-6">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div v-for="stat in stats" :key="stat.label" class="bg-gray-50 p-4 rounded-lg">
                  <p class="text-sm text-gray-600">{{ stat.label }}</p>
                  <p class="text-2xl font-semibold text-gray-900 mt-1">{{ stat.value }}</p>
                  <p v-if="stat.change" class="text-xs mt-1" :class="stat.trend === 'up' ? 'text-gray-700' : 'text-gray-700'">
                    {{ stat.change }} from last week
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Activities and Security -->
        <div class="space-y-6">
          <!-- Activities Card -->
          <div class="bg-white shadow-sm border border-gray-200 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
              <h2 class="text-lg font-semibold text-gray-900">Recent Activities</h2>
              <span class="text-xs text-gray-500">Last 30 days</span>
            </div>
            <div class="p-6">
              <div class="space-y-4">
                <div v-for="activity in activities" :key="activity.id" class="flex items-start">
                  <div class="flex-shrink-0 mr-3 text-lg">{{ activity.icon }}</div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ activity.action }}</p>
                    <p class="text-xs text-gray-500">{{ activity.time }}</p>
                  </div>
                </div>
              </div>
              <button class="mt-6 w-full text-center px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors">
                View all activities
              </button>
            </div>
          </div>

          <!-- Security Card -->
          <div class="bg-white shadow-sm border border-gray-200 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-semibold text-gray-900">Security</h2>
            </div>
            <div class="p-6 space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-900">Two-factor authentication</p>
                  <p class="text-xs text-gray-500">Add an extra layer of security</p>
                </div>
                <button class="text-sm text-gray-600 hover:text-gray-900">Enable</button>
              </div>
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-900">Login notifications</p>
                  <p class="text-xs text-gray-500">Get alerted for new logins</p>
                </div>
                <button class="text-sm text-gray-600 hover:text-gray-900">Enable</button>
              </div>
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-900">Active sessions</p>
                  <p class="text-xs text-gray-500">Manage your active sessions</p>
                </div>
                <button class="text-sm text-gray-600 hover:text-gray-900">View all</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Profile Modal -->
    <div v-if="showProfileModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900">Edit Profile</h3>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6">
          <div class="space-y-4">
            <!-- Name Field -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Full Name
              </label>
              <input
                v-model="tempUserData.name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent"
                placeholder="Enter full name"
              />
            </div>

            <!-- Email Field -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Email Address
              </label>
              <input
                v-model="tempUserData.email"
                type="email"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent"
                placeholder="Enter email address"
              />
            </div>

            <!-- Role Field -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Role
              </label>
              <select
                v-model="tempUserData.roles"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent"
              >
                <option value="Super Administrator">Super Administrator</option>
                <option value="Administrator">Administrator</option>
                <option value="Manager">Manager</option>
                <option value="Editor">Editor</option>
                <option value="Viewer">Viewer</option>
              </select>
            </div>

            <!-- Status Field -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Account Status
              </label>
              <div class="flex space-x-4">
                <label class="flex items-center">
                  <input
                    v-model="tempUserData.status"
                    type="radio"
                    value="Active"
                    class="mr-2 text-gray-600 focus:ring-gray-500"
                  />
                  <span class="text-sm text-gray-700">Active</span>
                </label>
                <label class="flex items-center">
                  <input
                    v-model="tempUserData.status"
                    type="radio"
                    value="Inactive"
                    class="mr-2 text-gray-600 focus:ring-gray-500"
                  />
                  <span class="text-sm text-gray-700">Inactive</span>
                </label>
                <label class="flex items-center">
                  <input
                    v-model="tempUserData.status"
                    type="radio"
                    value="Suspended"
                    class="mr-2 text-gray-600 focus:ring-gray-500"
                  />
                  <span class="text-sm text-gray-700">Suspended</span>
                </label>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-lg flex justify-end space-x-3">
          <button 
            @click="cancelEdit"
            class="px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200 text-sm font-medium"
          >
            Cancel
          </button>
          <button 
            @click="saveProfile"
            class="px-4 py-2 bg-gray-900 text-white rounded-md hover:bg-gray-800 transition-colors duration-200 text-sm font-medium"
          >
            Save Changes
          </button>
        </div>
      </div>
    </div>

    <!-- Change Password Modal -->
    <div v-if="showPasswordModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900">Change Password</h3>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6">
          <div class="space-y-4">
            <!-- Current Password Field -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Current Password
              </label>
              <input
                v-model="passwordData.current"
                type="password"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent"
                placeholder="Enter current password"
              />
            </div>

            <!-- New Password Field -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                New Password
              </label>
              <input
                v-model="passwordData.new"
                type="password"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent"
                placeholder="Enter new password"
              />
            </div>

            <!-- Confirm Password Field -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Confirm New Password
              </label>
              <input
                v-model="passwordData.confirm"
                type="password"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent"
                placeholder="Confirm new password"
              />
            </div>

            <!-- Password Requirements -->
            <div class="bg-gray-50 p-3 rounded-md">
              <p class="text-xs font-medium text-gray-700 mb-2">Password requirements:</p>
              <ul class="text-xs text-gray-600 space-y-1">
                <li>• At least 8 characters</li>
                <li>• One uppercase letter</li>
                <li>• One number</li>
                <li>• One special character</li>
              </ul>
            </div>
          </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-lg flex justify-end space-x-3">
          <button 
            @click="cancelPasswordEdit"
            class="px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200 text-sm font-medium"
          >
            Cancel
          </button>
          <button 
            @click="savePassword"
            class="px-4 py-2 bg-gray-900 text-white rounded-md hover:bg-gray-800 transition-colors duration-200 text-sm font-medium"
          >
            Update Password
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
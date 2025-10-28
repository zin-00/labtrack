<script setup>
import { ref, computed, reactive, onMounted, toRef } from 'vue';
import { useRouter } from 'vue-router';
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import Button from '../../components/button/Button.vue';
import { LoopingRhombusesSpinner } from 'epic-spinners'
import { 
    TrashIcon, 
    PencilIcon, 
    XMarkIcon,
    ArrowDownTrayIcon, 
    UserPlusIcon, 
    ArrowPathIcon,
    EyeIcon
} from '@heroicons/vue/24/outline';
import Table from '../../components/table/Table.vue';
import Modal from '../../components/modal/Modal.vue';
import TextInput from '../../components/input/TextInput.vue';
import {useExcelStore} from '../../composable/excel';
import { useStudentStore } from '../../composable/users/students/student';
import { useProgramStore } from '../../composable/program';
import { useAdminStore } from '../../composable/admin';
import { storeToRefs } from 'pinia';


const admin = useAdminStore();
const { admins,
        isLoading,
        toast,
        modalState,
        isConfirmationModalOpen,
        selectedStatus,
        searchQuery,
        selectedAdmin,
        statusFilter,
        isEditMode,
        pagination,
        showDropdown, } = storeToRefs(admin);

const { fetchAdmins, deleteAdmin, updateAdmin, storeAdmin } = admin;

const router = useRouter();
const auth = useStudentStore();
const prog = useProgramStore();
const xl = useExcelStore();




const adminData = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [],
    status: 'active',
});

const filteredAdmins = computed(() => {
    if (!admins.value || !Array.isArray(admins.value)) {
        return [];
    }
    
    return admins.value.filter((admin) => {
        const statusMatch = 
            selectedStatus.value === 'all' || admin.status === selectedStatus.value;
        const searchMatch = 
            admin.name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            admin.email?.toLowerCase().includes(searchQuery.value.toLowerCase());
        return statusMatch && searchMatch;
    });
});

const handleView = (user) => {
    console.log('View user:', user);
    toast.success('User viewed successfully!');
};

const retrieveAdmins = (page = 1) => {
    try{
        fetchAdmins(page);
    }catch(error){
        toast.error('Failed to fetch Admins.');
        console.error(error);
    }
}
const saveAdmin = async () => {
    try {
        if(selectedAdmin.value) {
            await updateAdmin(selectedAdmin.value.id, adminData);
        } else {
            await storeAdmin(adminData);
        }
        modalState.value = false;
        clearForm();
        await fetchAdmins();
    } catch(error) {
        console.error('Error saving admin:', error);
    }
};

const clearForm = () => {
    adminData.name = '';
    adminData.email = '';
    adminData.password = '';
    adminData.password_confirmation = '';
    adminData.roles = [];
    adminData.status = 'active';

}

const deleteAdmin_func =  () => {
    deleteAdmin(selectedAdmin.value.id);
    isConfirmationModalOpen.value = false;
}
const editAdmin = (admin) => {
    selectedAdmin.value = admin;
    Object.assign(adminData, {
        name: admin.name,
        email: admin.email,
        password: '',
        password_confirmation: '',
        roles: admin.roles,
        status: admin.status,
    });
    modalState.value = true;
    isEditMode.value = true;
};

const openAddModal = () => {
    selectedAdmin.value = null;
    Object.assign(adminData, {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        roles: [],
        status: 'active',
    });
    modalState.value = true;
}

const open_delete_modal = (admin) => {
    selectedAdmin.value = admin;
    isConfirmationModalOpen.value = true;
}
const refreshData = () => {
    // Refresh the page or reload data
    router.go(0);
};

const getStatusClass = (status) => {
  const statusClasses = {
    'Active': 'bg-green-100 text-green-800',
    'active': 'bg-green-100 text-green-800',
    'Inactive': 'bg-red-100 text-red-800', 
    'inactive': 'bg-red-100 text-red-800',
    'Pending': 'bg-yellow-100 text-yellow-800',
    'pending': 'bg-yellow-100 text-yellow-800',
    'Suspended': 'bg-orange-100 text-orange-800',
    'suspended': 'bg-orange-100 text-orange-800',
  }
  return statusClasses[status] || 'bg-gray-100 text-gray-800'
}

onMounted(() => {
    retrieveAdmins();
});

</script>

<template>
    <AuthenticatedLayout>
        <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white">
                <div>
                    <h2 class="text-2xl  text-gray-900">Admin Management</h2>
                    <p class="mt-1 text-sm text-gray-600">
                       Manage faculty accounts with CRUD operations and import from Excel for batch uploads.
                    </p>
                </div>
                
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <!-- Left: Search & Filters -->
                <div class="flex flex-wrap gap-2 items-center">
                    <!-- Search Box -->
                    <div class="relative">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search globally..."
                        class="w-64 border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                    >
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="absolute right-2 top-2 text-gray-400 hover:text-gray-600"
                    >
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                    </div>

                    <!-- Status Filter -->
                    <select
                        v-model="statusFilter"
                        class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                        >
                        <option value="all">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="restricted">Restricted</option>
                    </select>
                </div>

                <!-- Right: Action Buttons -->
                <div class="flex gap-2">
                    <button
                        title="Refresh"
                        @click="refreshData"
                        class="p-2 text-white  bg-gray-800 hover:bg-gray-700  rounded-md transition duration-200"
                        >
                        <ArrowPathIcon class="h-5 w-5" />
                    </button>

                    <button
                        @click="openAddModal"
                        title="Add User"
                        class="p-2  text-white  bg-gray-800 hover:bg-gray-700 rounded-md transition duration-200"
                        >
                        <UserPlusIcon class="h-5 w-5" />
                    </button>

                    <button
                        @click="xl.isImportModalOpen = true"
                        title="Import Users"
                        class="p-2  text-white  bg-gray-800 hover:bg-gray-700 rounded-md transition duration-200"
                        >
                        <ArrowDownTrayIcon class="h-5 w-5" />
                    </button>
                </div>
                </div>

        <div class="mt-4 relative min-h-64">
            <!-- Loading Overlay -->
            <div v-if="isLoading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-80 z-10 mt-10">
                <div class="flex flex-col items-center gap-4">
                  <img src="../../assets/LABTrackv2.png" alt="" class="h-15 w-15 object-contain"/>
                  <LoopingRhombusesSpinner :animation-duration="1200" :size="100" color="black" />
                  <span class="text-gray-600 font-medium">Loading admins...</span>
                </div>
            </div>
        <Table
            v-if="!isLoading || filteredAdmins.length > 0"
          :pagination="pagination" 
          :loading="isLoading" 
          :data="filteredAdmins"
          @page-change="fetchAdmins"
          @edit="editAdmin"
          @view="handleView"
        >
          <!-- Clean Custom Header -->
          <template #header>
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">
                  ID
                </th>
                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">
                  Name
                </th>
                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">
                  Email
                </th>
                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">
                  Roles
                </th>
                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">
                  Status
                </th>
                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-center w-20">
                  Actions
                </th>
              </tr>
            </thead>
          </template>

          <!-- Clean Custom Body -->
          <template #default>
            <tr 
              v-for="admin in filteredAdmins" 
              :key="admin.id"
              class="hover:bg-gray-50 transition-colors"
            >
              <td class="px-3 py-2 text-gray-900 font-medium text-sm">
                {{ admin.id }}
              </td>
              <td class="px-3 py-2 text-gray-900 text-sm">
                {{ admin.name || '—' }}
              </td>
              <td class="px-3 py-2 text-sm">
                <a
                  v-if="admin.email"
                  :href="`mailto:${admin.email}`"
                  class="text-blue-600 hover:underline"
                >
                  {{ admin.email }}
                </a>
                <span v-else class="text-gray-400">—</span>
              </td>
              <td class="px-3 py-2 text-sm">
                <span 
                  v-if="admin.roles"
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                >
                  {{ admin.roles }}
                </span>
                <span v-else class="text-gray-400">—</span>
              </td>
              <td class="px-3 py-2 text-sm">
                <span 
                  v-if="admin.status"
                  :class="getStatusClass(admin.status)"
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                >
                  {{ admin.status }}
                </span>
                <span v-else class="text-gray-400">—</span>
              </td>
              <td class="px-3 py-2">
                <div class="flex items-center justify-center gap-1">
                  <button 
                    @click="handleView(admin)" 
                    class="p-1.5 hover:text-blue-600 hover:bg-blue-50 rounded-md transition-colors"
                    title="View"
                  >
                    <EyeIcon class="w-3.5 h-3.5" />
                  </button>
                  <button 
                    @click="editAdmin(admin)" 
                    class="p-1.5 hover:text-green-600 hover:bg-green-50 rounded-md transition-colors"
                    title="Edit"
                  >
                    <PencilIcon class="w-3.5 h-3.5" />
                  </button>
                  <button 
                    @click="open_delete_modal(admin)" 
                    class="p-1.5 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors"
                    title="Delete"
                  >
                    <TrashIcon class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>
          </template>
        </Table>
      </div>

                <!-- Delete Confirmation Modal -->
                <Modal :show="isConfirmationModalOpen" @close="isConfirmationModalOpen = false">
                    <div class="bg-white p-6 rounded-2xl shadow-2xl w-full max-w-md mx-auto relative">
                        <div class="flex items-center gap-3 mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M12 19c3.866 0 7-3.134 7-7S15.866 5 12 5 5 8.134 5 12s3.134 7 7 7z"/>
                        </svg>
                        <h2 class="text-xl font-semibold text-gray-800">Confirm Deletion</h2>
                        </div>

                        <p class="text-gray-600 text-sm leading-relaxed">
                        Are you sure you want to delete this user? This action cannot be undone.
                        </p>

                        <div class="flex justify-end gap-3 mt-6">
                        <button
                            @click="isConfirmationModalOpen = false"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition"
                        >
                            Cancel
                        </button>

                        <button
                            @click="deleteAdmin_func"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                        >
                            Yes, Delete
                        </button>
                        </div>
                    </div>
                </Modal>

                <!-- Import Modal -->
               <Modal :show="xl.isImportModalOpen" @close="xl.isImportModalOpen = false">
                    <div class="bg-white p-8 rounded-lg max-w-md mx-auto relative">
                        <h2 class="text-lg font-medium mb-6">Import Students</h2>
                        
                        <form @submit.prevent="xl.importStudents" class="space-y-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-2">
                            Excel file (.xlsx, .xls)
                            </label>
                            
                            <!-- Drag and Drop Area -->
                            <div
                            @drop="xl.handleDrop"
                            @dragover.prevent
                            @dragenter.prevent
                            @dragleave="xl.isDragOver = false"
                            @dragover="xl.isDragOver = true"
                            :class="[
                                'border-2 border-dashed rounded-lg p-8 text-center transition-colors',
                                xl.isDragOver ? 'border-gray-400 bg-gray-50' : 'border-gray-200',
                                xl.selectedFile ? 'bg-gray-50' : ''
                            ]"
                            >
                            <div v-if="!xl.selectedFile">
                                <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <p class="text-sm text-gray-600 mb-1">Drop your file here or</p>
                                <button
                                type="button"
                                @click="$refs.fileInput.click()"
                                class="text-sm text-black hover:underline"
                                >
                                browse files
                                </button>
                            </div>
                            
                            <div v-else class="flex items-center justify-between">
                                <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-sm text-gray-700">{{ xl.selectedFile.name }}</span>
                                </div>
                                <button
                                type="button"
                                @click="xl.removeFile"
                                class="text-sm text-gray-400 hover:text-gray-600"
                                >
                                Remove
                                </button>
                            </div>
                            </div>
                            
                            <!-- Hidden file input -->
                            <input
                            ref="fileInput"
                            type="file"
                            accept=".xlsx,.xls"
                            @change="xl.handleFileUpload"
                            class="hidden"
                            />
                        </div>
                        
                        <div class="flex gap-3 pt-2">
                            <button
                            type="button"
                            @click="xl.isImportModalOpen = false"
                            class="flex-1 px-4 py-2 text-sm text-gray-600 hover:text-gray-800"
                            >
                            Cancel
                            </button>
                            <button
                            type="submit"
                            :disabled="xl.isLoading"
                            class="flex-1 px-4 py-2 bg-black text-white text-sm rounded hover:bg-gray-800"
                            >
                                <span v-if="!xl.isLoading">Import</span>
                                <span v-else>Importing...</span>
                            </button>
                        </div>
                        </form>
                    </div>
                </Modal>



          <!-- Store and Edit Admin Modal -->
        <Modal :show="modalState" @close="modalState = false">
            <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-4xl mx-auto relative">
                <h2 class="text-xl font-bold mb-4">
                {{ selectedAdmin ? 'Edit Admin' : 'Add New Admin' }}
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Full Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <TextInput
                    v-model="adminData.name"
                    type="text"
                    placeholder="Enter full name"
                    class="w-full px-2 py-[7px] border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <TextInput
                    v-model="adminData.email"
                    type="email"
                    placeholder="Enter email address"
                    class="w-full px-2 py-[7px] border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>

                <!-- Password (only for new admin or change password) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <TextInput
                    v-model="adminData.password"
                    type="password"
                    placeholder="Enter password"
                    class="w-full px-2 py-[7px] border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>

                <!-- Confirm Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <TextInput
                    v-model="adminData.password_confirmation"
                    type="password"
                    placeholder="Confirm password"
                    class="w-full px-2 py-[7px] border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>

                <!-- Roles -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Roles</label>
                    <select
                    v-model="adminData.roles"
                    class="w-full p-2 py-[7px] border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    >
                    <option disabled value="">-- Select Role --</option>
                    <option value="superadmin">Super Admin</option>
                    <option value="admin">Admin</option>
                    <option value="faculty">Faculty</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select
                    v-model="adminData.status"
                    class="w-full p-2 py-[7px] border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    >
                    <option disabled value="">-- Select Status --</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="restricted">Restricted</option>
                    </select>
                </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-2 mt-6">
                <button
                    @click="modalState = false"
                    class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition"
                >
                    Cancel
                </button>
                <button
                    @click="saveAdmin"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                >
                    Save
                </button>
                </div>
            </div>
        </Modal>

            </div> 
    </AuthenticatedLayout>
</template>
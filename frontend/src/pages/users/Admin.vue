<script setup>
import { ref, computed, reactive, onMounted, toRef, watch } from 'vue';
import { useRouter } from 'vue-router';
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import Button from '../../components/button/Button.vue';
import LoaderSpinner from '../../components/spinner/LoaderSpinner.vue';
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
import { debounce } from 'lodash-es';


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
    return admins.value || [];
});

// Debounced filter function
const applyFilters = debounce((page = 1) => {
    const filters = {
        search: searchQuery.value,
        status: statusFilter.value
    };
    fetchAdmins(page, filters);
}, 300);

// Watch filters and trigger backend request
watch(searchQuery, () => {
    applyFilters(1);
});

watch(statusFilter, () => {
    applyFilters(1);
});

const handleView = (user) => {
    console.log('View user:', user);
    toast.success('User viewed successfully!');
};

const retrieveAdmins = (page = 1) => {
    try{
        applyFilters(page);
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
        applyFilters(pagination.value.current_page || 1);
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

const deleteAdmin_func = async () => {
    await deleteAdmin(selectedAdmin.value.id);
    isConfirmationModalOpen.value = false;
    applyFilters(pagination.value.current_page || 1);
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
    applyFilters(pagination.value.current_page || 1);
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
    applyFilters(1);
});

</script>

<template>
    <AuthenticatedLayout>
        <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white min-h-screen relative">
            <LoaderSpinner :is-loading="isLoading" subMessage="Please wait while we fetch your data" />
            
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
                <!-- Header -->
                <div class="mb-4">
                    <h2 class="text-xl font-medium text-gray-900">Admin Management</h2>
                    <p class="mt-1 text-xs text-gray-500">Manage faculty accounts with CRUD operations and batch import</p>
                </div>

                <!-- Filters and Actions -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <!-- Left: Search & Filters -->
                        <div class="flex flex-wrap gap-2 items-center">
                            <!-- Search Box -->
                            <div class="relative">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search..."
                                    class="w-52 border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:border-gray-400 focus:ring-1 focus:ring-gray-200 transition"
                                />
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
                                class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:border-gray-400 focus:ring-1 focus:ring-gray-200 transition bg-white"
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
                                @click="refreshData"
                                title="Refresh"
                                class="p-2 text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 rounded-md transition"
                            >
                                <ArrowPathIcon class="h-4 w-4" />
                            </button>

                            <button
                                @click="openAddModal"
                                title="Add Admin"
                                class="p-2 text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 rounded-md transition"
                            >
                                <UserPlusIcon class="h-4 w-4" />
                            </button>

                            <button
                                @click="xl.isImportModalOpen = true"
                                title="Import"
                                class="p-2 text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 rounded-md transition"
                            >
                                <ArrowDownTrayIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <Table
                        :pagination="pagination" 
                        :loading="isLoading" 
                        :data="filteredAdmins"
                        @page-change="applyFilters"
                        @edit="editAdmin"
                        @view="handleView"
                    >
                        <template #header>
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-xs font-medium text-gray-600 text-left">ID</th>
                                    <th class="px-4 py-2.5 text-xs font-medium text-gray-600 text-left">Name</th>
                                    <th class="px-4 py-2.5 text-xs font-medium text-gray-600 text-left">Email</th>
                                    <th class="px-4 py-2.5 text-xs font-medium text-gray-600 text-left">Roles</th>
                                    <th class="px-4 py-2.5 text-xs font-medium text-gray-600 text-left">Status</th>
                                    <th class="px-4 py-2.5 text-xs font-medium text-gray-600 text-center">Actions</th>
                                </tr>
                            </thead>
                        </template>

                        <template #default>
                            <tr 
                                v-for="admin in filteredAdmins" 
                                :key="admin.id"
                                class="border-b border-gray-100 hover:bg-gray-50 transition-colors"
                            >
                                <td class="px-4 py-3 text-xs text-gray-900 font-medium">{{ admin.id }}</td>
                                <td class="px-4 py-3 text-xs text-gray-900">{{ admin.name || 'N/A' }}</td>
                                <td class="px-4 py-3 text-xs">
                                    <a
                                        v-if="admin.email"
                                        :href="`mailto:${admin.email}`"
                                        class="text-gray-700 hover:text-gray-900 hover:underline"
                                    >
                                        {{ admin.email }}
                                    </a>
                                    <span v-else class="text-gray-400">N/A</span>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <span 
                                        v-if="admin.roles"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200"
                                    >
                                        {{ admin.roles }}
                                    </span>
                                    <span v-else class="text-gray-400">N/A</span>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <span 
                                        v-if="admin.status"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border"
                                        :class="{
                                            'bg-green-50 text-green-700 border-green-200': admin.status === 'active' || admin.status === 'Active',
                                            'bg-red-50 text-red-700 border-red-200': admin.status === 'inactive' || admin.status === 'Inactive',
                                            'bg-orange-50 text-orange-700 border-orange-200': admin.status === 'suspended' || admin.status === 'Suspended',
                                            'bg-yellow-50 text-yellow-700 border-yellow-200': admin.status === 'pending' || admin.status === 'Pending',
                                            'bg-gray-50 text-gray-700 border-gray-200': !['active', 'inactive', 'suspended', 'pending', 'Active', 'Inactive', 'Suspended', 'Pending'].includes(admin.status)
                                        }"
                                    >
                                        {{ admin.status }}
                                    </span>
                                    <span v-else class="text-gray-400">N/A</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button 
                                            @click="handleView(admin)" 
                                            class="p-1 text-gray-400 hover:text-gray-600 rounded transition"
                                            title="View"
                                        >
                                            <EyeIcon class="w-4 h-4" />
                                        </button>
                                        <button 
                                            @click="editAdmin(admin)" 
                                            class="p-1 text-gray-400 hover:text-gray-700 rounded transition"
                                            title="Edit"
                                        >
                                            <PencilIcon class="w-4 h-4" />
                                        </button>
                                        <button 
                                            @click="open_delete_modal(admin)" 
                                            class="p-1 text-red-400 hover:text-red-600 rounded transition"
                                            title="Delete"
                                        >
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </Table>
                </div>

                <!-- Delete Confirmation Modal -->
                <Modal :show="isConfirmationModalOpen" @close="isConfirmationModalOpen = false">
                    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md mx-auto">
                        <!-- Header -->
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-red-50 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <h3 class="text-base font-semibold text-gray-900">Confirm Deletion</h3>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="px-6 py-4">
                            <p class="text-sm text-gray-600">
                                Are you sure you want to delete this admin? All associated data will be permanently removed.
                            </p>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end gap-2">
                            <button
                                @click="isConfirmationModalOpen = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition"
                            >
                                Cancel
                            </button>
                            <button
                                @click="deleteAdmin_func"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 transition"
                            >
                                Delete
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
                                class="text-sm text-gray-700 hover:underline font-medium"
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
                            class="flex-1 px-4 py-2 bg-gray-700 text-white text-sm rounded hover:bg-gray-600"
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
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-auto relative border border-gray-200">
                        <!-- Header -->
                        <div class="px-8 py-5 border-b border-gray-200 bg-gray-50 rounded-t-xl">
                            <div class="flex items-center justify-between">
                                <h2 class="text-2xl font-semibold text-gray-900">
                                    {{ selectedAdmin ? 'Edit Admin' : 'Add New Admin' }}
                                </h2>
                                <button
                                    @click="modalState = false"
                                    class="p-2 hover:bg-gray-200 rounded-lg transition-colors text-gray-500 hover:text-gray-700"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Form Content -->
                        <div class="p-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Full Name -->
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Full Name</label>
                                    <TextInput
                                        v-model="adminData.name"
                                        type="text"
                                        placeholder="Enter full name"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-400 focus:border-gray-400 transition-all bg-white text-gray-900"
                                    />
                                </div>

                                <!-- Email -->
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Email</label>
                                    <TextInput
                                        v-model="adminData.email"
                                        type="email"
                                        placeholder="Enter email address"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-400 focus:border-gray-400 transition-all bg-white text-gray-900"
                                    />
                                </div>

                                <!-- Password (Hidden in Edit Mode) -->
                                <div v-if="!selectedAdmin" class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Password</label>
                                    <TextInput
                                        v-model="adminData.password"
                                        type="password"
                                        placeholder="Enter password"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-400 focus:border-gray-400 transition-all bg-white text-gray-900"
                                    />
                                </div>

                                <!-- Confirm Password (Hidden in Edit Mode) -->
                                <div v-if="!selectedAdmin" class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                    <TextInput
                                        v-model="adminData.password_confirmation"
                                        type="password"
                                        placeholder="Confirm password"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-400 focus:border-gray-400 transition-all bg-white text-gray-900"
                                    />
                                </div>

                                <!-- Roles -->
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Roles</label>
                                    <select
                                        v-model="adminData.roles"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-400 focus:border-gray-400 transition-all bg-white text-gray-900"
                                    >
                                        <option disabled value="">-- Select Role --</option>
                                        <option value="superadmin">Super Admin</option>
                                        <option value="admin">Admin</option>
                                        <option value="faculty">Faculty</option>
                                    </select>
                                </div>

                                <!-- Status -->
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Status</label>
                                    <select
                                        v-model="adminData.status"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-400 focus:border-gray-400 transition-all bg-white text-gray-900"
                                    >
                                        <option disabled value="">-- Select Status --</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="restricted">Restricted</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-8 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                            <div class="flex justify-end gap-3">
                                <button
                                    @click="modalState = false"
                                    class="px-6 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-all font-medium"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="saveAdmin"
                                    class="px-6 py-2.5 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-all font-medium shadow-sm"
                                >
                                    {{ selectedAdmin ? 'Update Admin' : 'Save Admin' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Modal>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
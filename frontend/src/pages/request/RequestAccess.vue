<script setup>
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import { useRequestAccessStore } from '../../composable/requestAccess';
import { storeToRefs } from 'pinia';
import { computed, onMounted } from 'vue';
import { 
        ArrowPathIcon,
        ArrowDownTrayIcon,
        EyeIcon,
        PencilIcon,
        TrashIcon,
        UserPlusIcon,
        XMarkIcon,
        XCircleIcon,
        CheckCircleIcon
        } from '@heroicons/vue/24/outline';
import Table from '../../components/table/Table.vue';
import { CheckCheckIcon } from 'lucide-vue-next';
import {LoopingRhombusesSpinner} from 'epic-spinners';

const requestAccess = useRequestAccessStore();

const {
    isLoading,
    requests,
    pagination,
    selectedStatus,
    searchQuery,
    statusFilter,
    } = storeToRefs(requestAccess);

const {
    fetchRequests,
    approveRequest,
    rejectRequest,
    } = requestAccess;


const filterRequests = computed(() => {
    if(!requests.value || !Array.isArray(requests.value)) return [];

    return requests.value.filter((request) => {
        const statusMatch = 
            selectedStatus.value === 'all' || 
            request.status.toLowerCase() === selectedStatus.value.toLowerCase();
        const searchMatch = 
            (request.fullname?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (request.email?.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            (request.id_number?.toString().includes(searchQuery.value.toLowerCase())) ||
            (request.role?.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            (request.status?.toLowerCase().includes(searchQuery.value.toLowerCase())));
        return statusMatch && searchMatch;
    });
});


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

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const formatTime = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  })
}

onMounted(() => {
    fetchRequests();
})
</script>
<template>
    <AuthenticatedLayout>
         <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                    <div>
                        <h2 class="text-2xl text-gray-900">Access Control Requests</h2>
                        <p class="mt-1 text-sm text-gray-600">Oversee system login privileges and account request approvals.</p>
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
                                @click="searchQuery  = ''"
                                class="absolute right-2 top-2 text-gray-400 hover:text-gray-600"
                            >
                                <XMarkIcon class="w-4 h-4" />
                            </button>
                            </div>

                            <!-- Status Filter -->
                            <select
                                v-model="selectedStatus"
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
                </div>

                <!-- Main Table -->
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
                    v-if="!isLoading || filterRequests.length > 0"
                    :users="filterRequests"
                    :pagination="pagination"
                    :loading="isLoading"
                    @page-change="fetchRequests"
                >
                      <template #header>
                            <thead class="bg-gray-50">
                                <tr>
                                <!-- <th class="px-3 py-2 text-xs font-medium text-gray-600 text-center">ID</th> -->
                                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left">ID Number</th>
                                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left">Full Name</th>
                                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left">Email</th>
                                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left">Role</th>
                                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left">Status</th>
                                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left">Requested At</th>
                                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-center">Actions</th>
                                </tr>
                            </thead>
                        </template>
                    <template #default>
                        <tr
                        v-for="request in filterRequests"
                        :key="request.id"
                        class="hover:bg-gray-50 transition-colors">
                            <td class="px-3 text-gray-900 font-medium text-xs">
                                {{ request.id }}
                            </td>
                            <td class="px-3 py-1 text-gray-900 font-medium text-xs">
                                {{ request.id_number }}
                            </td>
                            <td class="px-3 py-1 text-gray-900 text-xs">
                                {{ request.fullname }}
                            </td>
                            <td class="px-3 py-1 text-xs">
                                <a
                                    v-if="request.email"
                                    :href="`mailto:${request.email}`"
                                    class="text-blue-600 hover:underline"
                                >
                                    {{ request.email }}
                                </a>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="px-3 py-1 text-xs">
                                <span 
                                    v-if="request.role"
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                >
                                    {{ request.role }}
                                </span>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="px-3 py-1 text-xs">
                                <span 
                                    :class="getStatusClass(request.status)"
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                >
                                    {{ request.status || 'N/A' }}
                                </span>
                            </td>
                            <td class="px-3 py-1 text-xs text-gray-700">
                                <div class="text-xs">
                                    <div class="font-medium">{{ formatDate(request.created_at) }}</div>
                                    <div class="text-gray-500">{{ formatTime(request.created_at) }}</div>
                                </div>
                            </td>
                            <td class="px-3 py-1 text-xs text-center">
                                <div class="flex items-center justify-center">
                                   <button 
                                    v-if="request.status === 'pending'"
                                    @click="approveRequest(request.id)" 
                                    class="flex items-center gap-1 px-2 py-1 text-green-600 hover:bg-green-50 rounded-md transition-colors"
                                    >
                                    <CheckCircleIcon class="w-4 h-4" />
                                    Approve
                                    </button>

                                    <button 
                                    v-if="request.status === 'pending'"
                                    @click="rejectRequest(request.id)" 
                                    class="flex items-center gap-1 px-2 py-1 text-red-600 hover:bg-red-50 rounded-md transition-colors"
                                    >
                                    <XCircleIcon class="w-4 h-4" />
                                    Reject
                                    </button>
                                </div>
                                </td>

                        </tr>
                    </template>
                </Table>
            </div>
         </div>
    </AuthenticatedLayout>
</template>
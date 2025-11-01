<script setup>
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import Table from '../../components/table/Table.vue';
import { useRequestAccessStore } from '../../composable/requestAccess';
import { storeToRefs } from 'pinia';
import { computed, onMounted } from 'vue';
import { 
    ArrowPathIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
    XCircleIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline';
import LoaderSpinner from '../../components/spinner/LoaderSpinner.vue'

// Store initialization
const requestAccess = useRequestAccessStore();

// Reactive state
const {
    isLoading,
    requests,
    pagination,
    selectedStatus,
    searchQuery,
    statusFilter,
} = storeToRefs(requestAccess);

// Actions
const {
    fetchRequests,
    approveRequest,
    rejectRequest,
} = requestAccess;

// Computed properties
const filterRequests = computed(() => {
    if (!requests.value || !Array.isArray(requests.value)) {
        return [];
    }

    return requests.value.filter((request) => {
        // Status filter
        const statusMatch = 
            selectedStatus.value === 'all' || 
            request.status?.toLowerCase() === selectedStatus.value.toLowerCase();
        
        // Search filter
        const searchMatch = 
            searchQuery.value === '' ||
            request.fullname?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            request.email?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            request.id_number?.toString().includes(searchQuery.value) ||
            request.role?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            request.status?.toLowerCase().includes(searchQuery.value.toLowerCase());
        
        return statusMatch && searchMatch;
    });
});

// Methods
const getStatusClass = (status) => {
    const statusClasses = {
        'active': 'bg-green-50 text-green-700 border-green-200',
        'Active': 'bg-green-50 text-green-700 border-green-200',
        'inactive': 'bg-red-50 text-red-700 border-red-200', 
        'Inactive': 'bg-red-50 text-red-700 border-red-200',
        'pending': 'bg-yellow-50 text-yellow-700 border-yellow-200',
        'Pending': 'bg-yellow-50 text-yellow-700 border-yellow-200',
        'suspended': 'bg-orange-50 text-orange-700 border-orange-200',
        'Suspended': 'bg-orange-50 text-orange-700 border-orange-200',
        'approved': 'bg-blue-50 text-blue-700 border-blue-200',
        'Approved': 'bg-blue-50 text-blue-700 border-blue-200',
        'rejected': 'bg-gray-100 text-gray-700 border-gray-300',
        'Rejected': 'bg-gray-100 text-gray-700 border-gray-300',
    };
    return statusClasses[status] || 'bg-gray-50 text-gray-700 border-gray-200';
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};

const formatTime = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedStatus.value = 'all';
};

const refreshData = () => {
    fetchRequests();
};

// Lifecycle
onMounted(() => {
    fetchRequests();
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white min-h-screen relative">

            <LoaderSpinner :is-loading="isLoading" subMessage="Please wait while we fetch your data" />
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Header Section -->
                <div class="mb-8">
                    <h1 class="text-3xl font-light text-gray-900">Access Control Requests</h1>
                    <p class="mt-2 text-sm text-gray-500">Manage system access requests and account approvals</p>
                </div>

                <!-- Filters and Actions Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <!-- Left: Search & Filters -->
                        <div class="flex flex-col sm:flex-row gap-3 flex-1">
                            <!-- Search Box -->
                            <div class="relative flex-1 max-w-md">
                                <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search by name, email, ID, or role..."
                                    class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-gray-200 focus:border-gray-400 transition-all"
                                />
                                <button
                                    v-if="searchQuery"
                                    @click="searchQuery = ''"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                                >
                                    <XMarkIcon class="w-5 h-5" />
                                </button>
                            </div>

                            <!-- Status Filter -->
                            <select
                                v-model="selectedStatus"
                                class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-gray-200 focus:border-gray-400 transition-all bg-white"
                            >
                                <option value="all">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>

                            <!-- Clear Filters Button -->
                            <button
                                v-if="searchQuery || selectedStatus !== 'all'"
                                @click="clearFilters"
                                class="px-4 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors whitespace-nowrap"
                            >
                                Clear Filters
                            </button>
                        </div>

                        <!-- Right: Action Buttons -->
                        <div class="flex gap-2">
                            <button
                                @click="refreshData"
                                title="Refresh data"
                                class="p-2.5 text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg transition-colors"
                            >
                                <ArrowPathIcon class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <!-- Loading State -->
                    <div v-if="isLoading" class="flex items-center justify-center py-20">
                        <div class="flex flex-col items-center gap-4">
                            <div class="w-12 h-12 border-4 border-gray-200 border-t-gray-600 rounded-full animate-spin"></div>
                            <span class="text-sm text-gray-500 font-medium">Loading requests...</span>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="!filterRequests.length" class="text-center py-20">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-1">No requests found</h3>
                        <p class="text-sm text-gray-500">Try adjusting your search or filter criteria</p>
                    </div>

                    <!-- Table -->
                    <div v-else class="overflow-x-auto">
                        <Table
                            :users="filterRequests"
                            :pagination="pagination"
                            :loading="isLoading"
                            @page-change="fetchRequests"
                        >
                            <template #header>
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID Number</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Full Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Role</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Requested At</th>
                                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                            </template>
                            <template #default>
                                <tr
                                    v-for="request in filterRequests"
                                    :key="request.id"
                                    class="border-b border-gray-100 hover:bg-gray-50 transition-colors"
                                >
                                    <td class="px-6 py-4 text-sm text-gray-600 font-mono">
                                        {{ request.id }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                                        {{ request.id_number || 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                                        {{ request.fullname || 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <a
                                            v-if="request.email"
                                            :href="`mailto:${request.email}`"
                                            class="text-gray-700 hover:text-gray-900 hover:underline transition-colors"
                                        >
                                            {{ request.email }}
                                        </a>
                                        <span v-else class="text-gray-400">N/A</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span 
                                            v-if="request.role"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                                        >
                                            {{ request.role }}
                                        </span>
                                        <span v-else class="text-gray-400">N/A</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span 
                                            :class="getStatusClass(request.status)"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                        >
                                            {{ request.status || 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <div class="flex flex-col">
                                            <span class="font-medium text-gray-900">{{ formatDate(request.created_at) }}</span>
                                            <span class="text-xs text-gray-500">{{ formatTime(request.created_at) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="flex items-center justify-center gap-2">
                                            <button 
                                                v-if="request.status === 'pending' || request.status === 'Pending'"
                                                @click="approveRequest(request.id)" 
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-green-700 bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg transition-colors"
                                                title="Approve request"
                                            >
                                                <CheckCircleIcon class="w-4 h-4" />
                                                Approve
                                            </button>

                                            <button 
                                                v-if="request.status === 'pending' || request.status === 'Pending'"
                                                @click="rejectRequest(request.id)" 
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition-colors"
                                                title="Reject request"
                                            >
                                                <XCircleIcon class="w-4 h-4" />
                                                Reject
                                            </button>

                                            <span 
                                                v-if="request.status !== 'pending' && request.status !== 'Pending'"
                                                class="text-xs text-gray-400 italic"
                                            >
                                                No action needed
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </Table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
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
            <LoaderSpinner :is-loading="isLoading" subMessage="Loading access requests..." />
            
            <div v-show="!isLoading">
                <!-- Header Section -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <!-- Page Title -->
                            <div>
                                <h1 class="text-xl font-semibold text-gray-900">Access Control Requests</h1>
                                <p class="text-sm text-gray-600 mt-0.5">Manage system access requests and account approvals</p>
                            </div>

                            <!-- Filters Row -->
                            <div class="flex flex-wrap items-center gap-3">
                                <!-- Search Input -->
                                <div class="w-64">
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <MagnifyingGlassIcon class="h-4 w-4 text-gray-400" />
                                        </div>
                                        <input
                                            v-model="searchQuery"
                                            type="text"
                                            placeholder="Search requests..."
                                            class="block w-full pl-9 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-200 focus:border-gray-400 text-sm transition-colors bg-white"
                                        />
                                        <button
                                            v-if="searchQuery"
                                            @click="searchQuery = ''"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                        >
                                            <XMarkIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Status Filter -->
                                <div class="w-40">
                                    <select
                                        v-model="selectedStatus"
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-200 focus:border-gray-400 text-sm bg-white transition-colors"
                                    >
                                        <option value="all">All Status</option>
                                        <option value="pending">Pending</option>
                                        <option value="approved">Approved</option>
                                        <option value="rejected">Rejected</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>

                                <!-- Results Count -->
                                <div class="text-xs text-gray-600 bg-gray-100 px-3 py-2 rounded-lg border border-gray-200">
                                    {{ filterRequests.length }} result{{ filterRequests.length !== 1 ? 's' : '' }}
                                </div>

                                <!-- Refresh Button -->
                                <button
                                    @click="refreshData"
                                    class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-gray-300 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-50 transition-colors"
                                    title="Refresh data"
                                >
                                    <ArrowPathIcon class="h-4 w-4" />
                                    Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Section -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <!-- Empty State -->
                    <div v-if="!filterRequests.length" class="text-center py-16 bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="max-w-md mx-auto px-4">
                            <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="text-base font-medium text-gray-900 mb-1">No requests found</h3>
                            <p class="text-sm text-gray-500 mb-4">Try adjusting your search or filter criteria</p>
                        </div>
                    </div>

                    <!-- Table -->
                    <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Number</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr
                                        v-for="request in filterRequests"
                                        :key="request.id"
                                        class="hover:bg-gray-50 transition-colors"
                                    >
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-xs font-mono text-gray-600">{{ request.id }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-xs font-medium text-gray-900">{{ request.id_number || 'N/A' }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-xs font-medium text-gray-900">{{ request.fullname || 'N/A' }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <a
                                                v-if="request.email"
                                                :href="`mailto:${request.email}`"
                                                class="text-xs text-blue-600 hover:text-blue-800 hover:underline transition-colors"
                                            >
                                                {{ request.email }}
                                            </a>
                                            <span v-else class="text-xs text-gray-400">N/A</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span 
                                                v-if="request.role"
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800"
                                            >
                                                {{ request.role }}
                                            </span>
                                            <span v-else class="text-xs text-gray-400">N/A</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span 
                                                :class="getStatusClass(request.status)"
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border"
                                            >
                                                {{ request.status || 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-xs text-gray-900">{{ formatDate(request.created_at) }}</div>
                                            <div class="text-xs text-gray-500">{{ formatTime(request.created_at) }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-2">
                                                <button 
                                                    v-if="request.status === 'pending' || request.status === 'Pending'"
                                                    @click="approveRequest(request.id)" 
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-green-700 bg-green-50 hover:bg-green-100 border border-green-200 rounded-md transition-colors"
                                                    title="Approve request"
                                                >
                                                    <CheckCircleIcon class="w-3.5 h-3.5" />
                                                    Approve
                                                </button>

                                                <button 
                                                    v-if="request.status === 'pending' || request.status === 'Pending'"
                                                    @click="rejectRequest(request.id)" 
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 rounded-md transition-colors"
                                                    title="Reject request"
                                                >
                                                    <XCircleIcon class="w-3.5 h-3.5" />
                                                    Reject
                                                </button>

                                                <span 
                                                    v-if="request.status !== 'pending' && request.status !== 'Pending'"
                                                    class="text-xs text-gray-400 italic"
                                                >
                                                    —
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
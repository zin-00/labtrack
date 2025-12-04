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
    CheckCircleIcon,
    EyeIcon,
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
    fetchRequests(pagination.value.current_page);
};

const goToPage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page && page !== pagination.value.current_page) {
        fetchRequests(page);
    }
};

const handleView = (request) => {
    console.log('View request:', request);
};

const EventListener = () => {
    if(!window.Echo) return;
    window.Echo.channel('main-channel')
        .listen('.MainEvent', (e) => {
            if(e.type === 'request-access') {
                const index = requests.value.findIndex(r => r.id === e.data.id);
                if(index !== -1) {
                    requests.value[index] = {
                        ...requests.value[index],
                        ...e.data
                    };
                    requests.value.splice(index, 1, requests.value[index]);
                } else {
                    requests.value.unshift(e.data);
                }
            }
        });
}

// Lifecycle
onMounted(() => {
    fetchRequests();
    EventListener();
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
                    <!-- Table -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <Table
                            :users="filterRequests"
                            :pagination="pagination"
                            :loading="isLoading"
                            @view="handleView"
                            @page-change="goToPage"
                        >
                            <template #header>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2.5 text-xs font-medium text-gray-600 text-left">ID</th>
                                    <th class="px-4 py-2.5 text-xs font-medium text-gray-600 text-left">ID Number</th>
                                    <th class="px-4 py-2.5 text-xs font-medium text-gray-600 text-left">Full Name</th>
                                    <th class="px-4 py-2.5 text-xs font-medium text-gray-600 text-left">Email</th>
                                    <th class="px-4 py-2.5 text-xs font-medium text-gray-600 text-left">Role</th>
                                    <th class="px-4 py-2.5 text-xs font-medium text-gray-600 text-left">Status</th>
                                    <th class="px-4 py-2.5 text-xs font-medium text-gray-600 text-left">Requested</th>
                                    <th class="px-4 py-2.5 text-xs font-medium text-gray-600 text-center">Actions</th>
                                </tr>
                            </template>
                            <template #default>
                                <tr v-for="request in filterRequests" :key="request.id" class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 text-xs font-mono text-gray-600">{{ request.id }}</td>
                                    <td class="px-4 py-3 text-xs font-medium text-gray-900">{{ request.id_number || 'N/A' }}</td>
                                    <td class="px-4 py-3 text-xs font-medium text-gray-900">{{ request.fullname || 'N/A' }}</td>
                                    <td class="px-4 py-3 text-xs">
                                        <a
                                            v-if="request.email"
                                            :href="`mailto:${request.email}`"
                                            class="text-gray-700 hover:text-gray-900 hover:underline"
                                        >
                                            {{ request.email }}
                                        </a>
                                        <span v-else class="text-gray-400">N/A</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <span 
                                            v-if="request.role"
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800"
                                        >
                                            {{ request.role }}
                                        </span>
                                        <span v-else class="text-gray-400">N/A</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <span 
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border"
                                            :class="{
                                                'bg-green-50 text-green-700 border-green-200': request.status?.toLowerCase() === 'active',
                                                'bg-red-50 text-red-700 border-red-200': request.status?.toLowerCase() === 'inactive',
                                                'bg-yellow-50 text-yellow-700 border-yellow-200': request.status?.toLowerCase() === 'pending',
                                                'bg-blue-50 text-blue-700 border-blue-200': request.status?.toLowerCase() === 'approved',
                                                'bg-gray-100 text-gray-700 border-gray-300': request.status?.toLowerCase() === 'rejected',
                                                'bg-orange-50 text-orange-700 border-orange-200': request.status?.toLowerCase() === 'suspended',
                                                'bg-gray-50 text-gray-700 border-gray-200': !request.status
                                            }"
                                        >
                                            {{ request.status || 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-xs text-gray-900">{{ formatDate(request.created_at) }}</div>
                                        <div class="text-xs text-gray-500">{{ formatTime(request.created_at) }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button @click="handleView(request)" class="p-1 text-gray-400 hover:text-gray-600 rounded transition">
                                                <EyeIcon class="w-4 h-4" />
                                            </button>
                                            <button 
                                                v-if="request.status?.toLowerCase() === 'pending'"
                                                @click="approveRequest(request.id)" 
                                                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-green-700 bg-green-50 hover:bg-green-100 border border-green-200 rounded-md transition-colors"
                                                title="Approve request"
                                            >
                                                <CheckCircleIcon class="w-3.5 h-3.5" />
                                                Approve
                                            </button>
                                            <button 
                                                v-if="request.status?.toLowerCase() === 'pending'"
                                                @click="rejectRequest(request.id)" 
                                                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 rounded-md transition-colors"
                                                title="Reject request"
                                            >
                                                <XCircleIcon class="w-3.5 h-3.5" />
                                                Reject
                                            </button>
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
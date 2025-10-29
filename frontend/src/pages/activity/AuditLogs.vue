<script setup>
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import Table from '../../components/table/Table.vue';
import Modal from '../../components/modal/Modal.vue';
import { useStates } from '../../composable/states';
import { toRefs, onMounted, ref, watch } from 'vue';
import { useAuditLogsStore } from '../../composable/activity/audit/auditLogs';
import { XMarkIcon, FunnelIcon } from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import LoaderSpinner from '../../components/spinner/LoaderSpinner.vue';
import { debounce } from 'lodash-es';

const audit = useAuditLogsStore();
const states = useStates();

const { getAuditLogs } = audit;

const {
    searchQuery,
    pagination,
    isLoading,
    dateFrom,
    dateTo,
    auditLogs,
} = toRefs(states);

// Modal state
const showDataModal = ref(false);
const modalTitle = ref('');
const modalData = ref(null);

// Filter state
const showFilters = ref(false);

// Debounced filter function
const applyFilters = debounce(() => {
    getAuditLogs(1, {
        search: searchQuery.value,
        dateFrom: dateFrom.value,
        dateTo: dateTo.value,
    });
}, 300);

// Watch filters
watch([searchQuery, dateFrom, dateTo], () => {
    applyFilters();
});

const openDataModal = (data, type) => {
    modalTitle.value = type === 'old' ? 'Old Data' : 'New Data';
    modalData.value = data;
    showDataModal.value = true;
};

const formatJsonData = (data) => {
    if (!data) return 'N/A';
    if (typeof data === 'object') {
        return JSON.stringify(data, null, 2);
    }
    return data;
};

const handlePageChange = (page) => {
    getAuditLogs(page, {
        search: searchQuery.value,
        dateFrom: dateFrom.value,
        dateTo: dateTo.value,
    });
};

onMounted(async () => {
    getAuditLogs();
});
</script>
<template>
    <AuthenticatedLayout>
        <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-gray-50 min-h-screen relative">
            <LoaderSpinner :isLoading="isLoading" subMessage="Loading audit logs..." />
            
            <!-- Header -->
            <div class="mb-4">
                <h2 class="text-xl text-gray-900">Audit Logs</h2>
                <p class="text-xs text-gray-600">Monitor admin actions</p>
            </div>

            <!-- Single Row: Filters and Actions -->
            <div class="bg-white rounded-lg border border-gray-200 p-3 mb-4">
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Filter Toggle Button -->
                    <button
                        @click="showFilters = !showFilters"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors"
                        :class="showFilters ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    >
                        <FunnelIcon class="w-3.5 h-3.5" />
                        Filters
                    </button>

                    <!-- Date Filters (Inline when expanded) -->
                    <template v-if="showFilters">
                        <input
                            type="date"
                            v-model="dateFrom"
                            class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs focus:border-gray-400 focus:outline-none"
                            placeholder="From"
                        />
                        <span class="text-gray-400 text-xs">to</span>
                        <input
                            type="date"
                            v-model="dateTo"
                            class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs focus:border-gray-400 focus:outline-none"
                            placeholder="To"
                        />
                    </template>

                    <!-- Search -->
                    <div class="relative flex-1 min-w-[200px]">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search action, entity, user, description..."
                            class="w-full px-3 py-1.5 pr-8 border border-gray-200 rounded-lg text-xs focus:border-gray-400 focus:outline-none transition-colors"
                        />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        >
                            <XMarkIcon class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <!-- Results Count -->
                    <div class="text-xs text-gray-600 ml-auto">
                        {{ pagination.total || 0 }} total logs
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <Table
                    :data="auditLogs"
                    :is-loading="isLoading"
                    :pagination="pagination"
                    @page-change="handlePageChange"
                >
                    <template #header>
                        <tr>
                            <th class="px-4 py-2 text-xs font-medium text-gray-600 text-left">Name</th>
                            <th class="px-4 py-2 text-xs font-medium text-gray-600 text-left">IP Address</th>
                            <th class="px-4 py-2 text-xs font-medium text-gray-600 text-left">Entity</th>
                            <th class="px-4 py-2 text-xs font-medium text-gray-600 text-left">Entity ID</th>
                            <th class="px-4 py-2 text-xs font-medium text-gray-600 text-left">Description</th>
                            <th class="px-4 py-2 text-xs font-medium text-gray-600 text-left">Action</th>
                            <th class="px-4 py-2 text-xs font-medium text-gray-600 text-left">Old Data</th>
                            <th class="px-4 py-2 text-xs font-medium text-gray-600 text-left">New Data</th>
                            <th class="px-4 py-2 text-xs font-medium text-gray-600 text-left">Date & Time</th>
                        </tr>
                    </template>
                    <template #default>
                        <tr v-for="log in auditLogs" :key="log.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-2.5 text-xs text-gray-900">{{ log.user?.name || 'N/A' }}</td>
                            <td class="px-4 py-2.5 text-xs text-gray-900">{{ log.ip_address }}</td>
                            <td class="px-4 py-2.5 text-xs text-gray-900">{{ log.entity_type }}</td>
                            <td class="px-4 py-2.5 text-xs text-gray-900">{{ log.entity_id }}</td>
                            <td class="px-4 py-2.5 text-xs text-gray-900">{{ log.description }}</td>
                            <td class="px-4 py-2.5 text-xs text-gray-900">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium bg-gray-100 text-gray-700">
                                    {{ log.action }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-xs text-gray-900 max-w-[192px]">
                                <div 
                                    @click="openDataModal(log.old_data, 'old')"
                                    class="truncate cursor-pointer hover:text-gray-600 hover:underline"
                                >
                                    {{ typeof log.old_data === 'object' ? JSON.stringify(log.old_data) : log.old_data || 'N/A' }}
                                </div>
                            </td>
                            <td class="px-4 py-2.5 text-xs text-gray-900 max-w-[192px]">
                                <div 
                                    @click="openDataModal(log.new_data, 'new')"
                                    class="truncate cursor-pointer hover:text-gray-600 hover:underline"
                                >
                                    {{ typeof log.new_data === 'object' ? JSON.stringify(log.new_data) : log.new_data || 'N/A' }}
                                </div>
                            </td>
                            <td class="px-4 py-2.5 text-xs text-gray-900 whitespace-nowrap">{{ dayjs(log.created_at).format("MMM D, YYYY h:mm A") }}</td>
                        </tr>
                    </template>
                </Table>
            </div>

            <!-- Mobile View -->
            <div class="sm:hidden space-y-4 mt-4">
                <div
                    v-for="log in auditLogs"
                    :key="log.id"
                    class="bg-white rounded-lg border border-gray-200 p-4"
                >
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900">{{ log.user?.name || 'N/A' }}</h3>
                            <p class="text-xs text-gray-600 mt-0.5">{{ log.ip_address }}</p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium bg-gray-100 text-gray-700">
                            {{ log.action }}
                        </span>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-600">Entity:</span>
                            <span class="text-gray-900">{{ log.entity_type }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-600">Entity ID:</span>
                            <span class="text-gray-900">{{ log.entity_id }}</span>
                        </div>
                        <div class="text-xs">
                            <span class="text-gray-600">Description:</span>
                            <p class="text-gray-900 mt-1">{{ log.description }}</p>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-600">Date:</span>
                            <span class="text-gray-900">{{ dayjs(log.created_at).format("MMM D, YYYY h:mm A") }}</span>
                        </div>
                        <div class="flex gap-2 mt-3">
                            <button
                                @click="openDataModal(log.old_data, 'old')"
                                class="flex-1 px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                            >
                                Old Data
                            </button>
                            <button
                                @click="openDataModal(log.new_data, 'new')"
                                class="flex-1 px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                            >
                                New Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>

             <!-- JSON Data Modal -->
             <Modal :show="showDataModal" @close="showDataModal = false">
                <div class="bg-white/95 backdrop-blur-md rounded-lg shadow-xl w-full max-w-2xl mx-auto relative">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">{{ modalTitle }}</h3>
                    </div>

                    <!-- Modal Content -->
                    <div class="px-6 py-4 max-h-96 overflow-y-auto">
                        <pre class="text-sm text-gray-900 bg-gray-50 p-4 rounded-lg overflow-x-auto font-mono whitespace-pre-wrap">{{ formatJsonData(modalData) }}</pre>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                        <button
                            @click="showDataModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </Modal>
        </div>
    </AuthenticatedLayout>
</template>
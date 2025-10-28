<script setup>
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import Table from '../../components/table/Table.vue';
import Modal from '../../components/modal/Modal.vue';
import { useStates } from '../../composable/states';
import { toRefs, onMounted, computed, ref } from 'vue';
import { useAuditLogsStore } from '../../composable/activity/audit/auditLogs';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';

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

const filteredLogs = computed(() => {
    if(!auditLogs.value || !Array.isArray(auditLogs.value)){
      return [];
    }
    return auditLogs.value.filter((log) => {
        const matchesSearch =
        searchQuery.value === '' || 
        log.action?.toLowerCase().includes(searchQuery.value.toLowerCase());
        const createdAt = new Date(log.created_at);
        const from = dateFrom.value ? new Date(dateFrom.value) : null;
        const to = dateTo.value ? new Date(dateTo.value) : null;
        const matchesDate =
        (!from || createdAt >= from) && (!to || createdAt <= to);
        return matchesSearch && matchesDate
    });
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

onMounted(async () => {
    getAuditLogs();
});
</script>
<template>
    <AuthenticatedLayout>
        <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                <div>
                    <h2 class="text-2xl text-gray-900">Audit Logs</h2>
                    <p class="mt-1 text-xs text-gray-600">Monitor Admin actions</p>
                </div>
            </div>

            <!-- Filters Panel And Actions -->
            <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row gap-2">

                <!-- Search -->
                <div class="flex items-center gap-4">
                <div class="relative flex-1 max-w-md">
                    <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search programs..."
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:border-gray-400 focus:outline-none transition-colors"
                    />
                    <button
                    v-if="searchQuery"
                    @click="searchQuery = ''"
                    class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600"
                    >
                    <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>
                </div>

                <!-- Date Filters -->
                <div class="flex items-center gap-2">
                <input
                    type="date"
                    v-model="dateFrom"
                    class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-gray-400 focus:outline-none"
                />
                <span class="text-gray-500 text-sm">to</span>
                <input
                    type="date"
                    v-model="dateTo"
                    class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-gray-400 focus:outline-none"
                />
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
                v-if="!isLoading || filteredLogs.length > 0"
                :data="filteredLogs"
                :is-loading="isLoading"
                :pagination="pagination"
                @page-change="getAuditLogs"
                >
                <template #header>
                    <tr>
                        <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">Name</th>
                        <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">IP Address</th>
                        <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">Entity</th>
                        <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">Entity ID</th>
                        <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">Description</th>
                        <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">Action</th>
                        <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">Old Data</th>
                        <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">New Data</th>
                        <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">Date & Time</th>
                    </tr>
                </template>
                <template #default>
                    <tr v-for="log in filteredLogs" :key="log.id">
                        <td class="px-3 py-0 text-[10px] text-gray-900">{{ log.user?.name }}</td>
                        <td class="px-4 py-0 text-[10px] text-gray-900">{{ log.ip_address }}</td>
                        <td class="px-4 py-0 text-[10px] text-gray-900">{{ log.entity_type }}</td>
                        <td class="px-4 py-0 text-[10px] text-gray-900">{{ log.entity_id }}</td>
                        <td class="px-4 py-0  text-[10px] text-gray-900">{{ log.description }}</td>
                        <td class="px-4 py-0 text-[10px] text-gray-900">{{ log.action }}</td>
                        <!-- Clickable Old Data -->
                        <td class="px-4 py-0 text-[10px] text-gray-900 w-48 min-w-[192px] max-w-[192px]">
                            <div 
                                @click="openDataModal(log.old_data, 'old')"
                                class="truncate cursor-pointer hover:text-gray-600 hover:underline"
                            >
                                {{ typeof log.old_data === 'object' ? JSON.stringify(log.old_data) : log.old_data || 'N/A' }}
                            </div>
                        </td>
                        <!-- Clickable New Data -->
                        <td class="px-3 py-4 text-[10px] text-gray-900 w-48 min-w-[192px] max-w-[192px]">
                            <div 
                                @click="openDataModal(log.new_data, 'new')"
                                class="truncate cursor-pointer hover:text-gray-600 hover:underline"
                            >
                                {{ typeof log.new_data === 'object' ? JSON.stringify(log.new_data) : log.new_data || 'N/A' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-[10px] text-gray-900">{{ dayjs(log.created_at).format("MMM D, YYYY h:mm A") }}</td>
                    </tr>
                </template>
                </Table>
             </div>

             <!-- JSON Data Modal -->
             <Modal :show="showDataModal" @close="showDataModal = false">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-auto relative">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-medium text-gray-900">{{ modalTitle }}</h3>
                    </div>

                    <!-- Modal Content -->
                    <div class="px-6 py-4 max-h-96 overflow-y-auto">
                        <pre class="text-sm text-gray-900 bg-gray-50 p-4 rounded-lg overflow-x-auto font-mono whitespace-pre-wrap">{{ formatJsonData(modalData) }}</pre>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                        <button
                            @click="showDataModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </Modal>
        </div>
    </AuthenticatedLayout>
</template>
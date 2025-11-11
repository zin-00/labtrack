<script setup>
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import LoaderSpinner from '../../components/spinner/LoaderSpinner.vue';
import Table from '../../components/table/Table.vue';
import Modal from '../../components/modal/Modal.vue';
import { useStates } from '../../composable/states';
import { computed, toRefs, ref, onMounted } from 'vue';
import { useLaboratoryStore } from '../../store/laboratory/laboratory.js';
import {
    TrashIcon,
    DocumentArrowDownIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
    ArrowPathIcon,
    PlusIcon,
    ComputerDesktopIcon
} from '@heroicons/vue/24/outline';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { debounce } from 'lodash-es';
import axios from 'axios';
import { useApiUrl } from '../../api/api';
import { useToast } from '../../composable/toastification/useToast';

const toast = useToast();

// Store initialization
const states = useStates();
const laboratory = useLaboratoryStore();

// Reactive state
const { searchQuery, selectedLab, isLoading } = toRefs(states);

// Bulk selection state
const selectedRows = ref(new Set());
const showBulkDeleteModal = ref(false);

// Assigned computers data
const assignedComputers = ref([]);
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0
});

// Bulk assignment modal state
const showBulkAssignModal = ref(false);
const availableComputers = ref([]);
const selectedComputers = ref(new Set());
const isLoadingComputers = ref(false);
const isAssigning = ref(false);

// Bulk assignment filters
const bulkComputerSearch = ref('');
const bulkLabFilter = ref('');
const statusFilter = ref('all');

// Computed
const filterData = computed(() => {
    // Return the raw data since filtering is now done on backend
    return assignedComputers.value;
});

const filteredAvailableComputers = computed(() => {
    if (!bulkComputerSearch.value) return availableComputers.value;
    const query = bulkComputerSearch.value.toLowerCase();
    return availableComputers.value.filter(c =>
        c.computer_number?.toLowerCase().includes(query) ||
        c.ip_address?.toLowerCase().includes(query) ||
        c.mac_address?.toLowerCase().includes(query)
    );
});

const isAllSelected = computed(() => {
    return filterData.value.length > 0 && selectedRows.value.size === filterData.value.length;
});

// Methods
const fetchAssignedComputers = async (page = 1) => {
    isLoading.value = true;
    try {
        const { api, getAuthHeader } = useApiUrl();

        const params = {
            search: searchQuery.value,
            laboratory_id: (selectedLab.value !== 'all' && selectedLab.value?.id) ? selectedLab.value.id : null,
            status: statusFilter.value !== 'all' ? statusFilter.value : null,
            page: page,
            per_page: 10,
            paginate: 'true',
            assigned_only: 'true' // Only fetch computers with laboratory_id
        };
        
        const queryString = new URLSearchParams(
            Object.entries(params).filter(([_, v]) => v !== null && v !== undefined && v !== '')
        ).toString();

        const url = queryString ? `${api}/computers?${queryString}` : `${api}/computers?paginate=true&assigned_only=true`;
        const response = await axios.get(url, getAuthHeader());

        // Store paginated data - no need to filter since backend now handles it
        assignedComputers.value = response.data.computers || [];
        pagination.value = response.data.pagination || {
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0,
            from: 0,
            to: 0
        };
    } catch (error) {
        console.error('Error fetching assigned computers:', error);
        assignedComputers.value = [];
        toast.error('Failed to fetch assigned computers');
    } finally {
        isLoading.value = false;
    }
};

const fetchAvailableComputers = async () => {
    isLoadingComputers.value = true;
    try {
        const { api, getAuthHeader } = useApiUrl();

        // Get computers without laboratory assignment
        const response = await axios.get(`${api}/computers/null-lab`, getAuthHeader());
        availableComputers.value = response.data.computers || [];
    } catch (error) {
        console.error('Error fetching available computers:', error);
        availableComputers.value = [];
        toast.error('Failed to fetch available computers');
    } finally {
        isLoadingComputers.value = false;
    }
};

const toggleComputerSelection = (computerId) => {
    if (selectedComputers.value.has(computerId)) {
        selectedComputers.value.delete(computerId);
    } else {
        selectedComputers.value.add(computerId);
    }
};

const toggleAllComputers = () => {
    if (selectedComputers.value.size === filteredAvailableComputers.value.length) {
        selectedComputers.value.clear();
    } else {
        filteredAvailableComputers.value.forEach(c => selectedComputers.value.add(c.id));
    }
};

const performBulkAssignment = async () => {
    if (selectedComputers.value.size === 0) {
        toast.error('Please select at least one computer');
        return;
    }

    if (!bulkLabFilter.value) {
        toast.error('Please select a laboratory');
        return;
    }

    isAssigning.value = true;
    try {
        const { api, getAuthHeader } = useApiUrl();

        const computerIds = Array.from(selectedComputers.value);

        const response = await axios.post(`${api}/assign-laboratories`, {
            computer_ids: computerIds,
            laboratory_id: bulkLabFilter.value.id
        }, getAuthHeader());

        toast.success(response.data.message || `${computerIds.length} computer(s) assigned successfully`);

        // Clear selections and close modal
        selectedComputers.value.clear();
        closeBulkAssignModal();

        // Refresh the main table
        fetchAssignedComputers(1);
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to assign computers');
        console.error('Assignment error:', error);
    } finally {
        isAssigning.value = false;
    }
};

const toggleRowSelection = (id) => {
    if (selectedRows.value.has(id)) {
        selectedRows.value.delete(id);
    } else {
        selectedRows.value.add(id);
    }
};

const toggleSelectAll = () => {
    if (selectedRows.value.size === filterData.value.length) {
        selectedRows.value.clear();
    } else {
        filterData.value.forEach(item => selectedRows.value.add(item.id));
    }
};

const bulkUnassign = async () => {
    try {
        const { api, getAuthHeader } = useApiUrl();
        const ids = Array.from(selectedRows.value);

        const response = await axios.post(`${api}/unassign-laboratories`, {
            computer_ids: ids
        }, getAuthHeader());

        toast.success(response.data.message || `${ids.length} computer(s) unassigned successfully`);
        selectedRows.value.clear();
        showBulkDeleteModal.value = false;
        fetchAssignedComputers(1);
    } catch (error) {
        toast.error('Failed to unassign computers');
        console.error(error);
    }
};

const unassignSingle = async (id) => {
    try {
        const { api, getAuthHeader } = useApiUrl();

        const response = await axios.post(`${api}/unassign-laboratories`, {
            computer_ids: [id]
        }, getAuthHeader());

        toast.success(response.data.message || 'Computer unassigned successfully');
        fetchAssignedComputers(pagination.value.current_page);
    } catch (error) {
        toast.error('Failed to unassign computer');
        console.error(error);
    }
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedLab.value = 'all';
    statusFilter.value = 'all';
    fetchAssignedComputers(1);
};

const refreshData = () => {
    fetchAssignedComputers(pagination.value.current_page);
};

const openBulkAssignModal = () => {
    showBulkAssignModal.value = true;
    fetchAvailableComputers();
};

const closeBulkAssignModal = () => {
    showBulkAssignModal.value = false;
    selectedComputers.value.clear();
    bulkComputerSearch.value = '';
    bulkLabFilter.value = '';
};

const applyFilters = debounce((page = 1) => {
    fetchAssignedComputers(page);
}, 300);

const generatePDF = () => {
    const doc = new jsPDF();

    doc.setFontSize(18);
    doc.text('Assigned Computers to Laboratories Report', 14, 20);

    doc.setFontSize(11);
    doc.text(`Generated: ${new Date().toLocaleString()}`, 14, 30);

    const tableData = filterData.value.map(item => [
        item.computer_number || 'N/A',
        item.ip_address || 'N/A',
        item.mac_address || 'N/A',
        item.laboratory?.name || 'N/A',
        item.status || 'N/A'
    ]);

    autoTable(doc, {
        startY: 35,
        head: [['Computer Number', 'IP Address', 'MAC Address', 'Laboratory', 'Status']],
        body: tableData,
        theme: 'grid',
        styles: { fontSize: 8 },
        headStyles: { fillColor: [55, 65, 81] }
    });

    doc.save(`assigned-computers-${new Date().toISOString().split('T')[0]}.pdf`);
};

// Lifecycle
onMounted(() => {
    laboratory.fetchLaboratories();
    fetchAssignedComputers();
});

</script>
<template>
    <AuthenticatedLayout>
         <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white min-h-screen relative">
            <LoaderSpinner :is-loading="isLoading" subMessage="Please wait while we fetch your data" />

            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
                <!-- Header -->
                <div class="mb-4">
                    <h1 class="text-xl font-medium text-gray-900">Assigned Computers to Laboratories</h1>
                    <p class="mt-1 text-xs text-gray-500">Manage computer assignments to laboratories</p>
                </div>

                                <!-- Filters Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
                    <!-- Single Row with Search, Filters, and Actions -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Search Box -->
                        <div class="relative w-64">
                            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by ID, name, or IP..."
                                class="w-full pl-9 pr-9 py-2 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder-gray-400 focus:ring-1 focus:ring-gray-200 focus:border-gray-400 transition-all"
                            />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                            >
                                <XMarkIcon class="w-4 h-4" />
                            </button>
                        </div>


                        <!-- Lab Filter -->
                        <select
                            v-model="selectedLab"
                            @change="applyFilters"
                            class="w-44 px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-900 focus:ring-1 focus:ring-gray-200 focus:border-gray-400 transition-all bg-white"
                        >
                            <option value="all">All Labs</option>
                            <option v-for="lab in laboratory.laboratories" :key="lab.id" :value="lab">
                                {{ lab.name }} - {{ lab.code }}
                            </option>
                        </select>

                        <!-- Status Filter -->
                        <select
                            v-model="statusFilter"
                            @change="applyFilters"
                            class="w-36 px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-900 focus:ring-1 focus:ring-gray-200 focus:border-gray-400 transition-all bg-white"
                        >
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="maintenance">Maintenance</option>
                        </select>

                        <!-- Clear Filters -->
                        <button
                            v-if="searchQuery || selectedLab !== 'all' || statusFilter !== 'all'"
                            @click="clearFilters"
                            class="px-3 py-2 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
                        >
                            Clear Filters
                        </button>

                        <div class="flex-1"></div>

                        <!-- Bulk Assign Button -->
                        <button
                            @click="openBulkAssignModal"
                            class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium text-white bg-gray-700 hover:bg-gray-600 rounded-md transition-colors"
                        >
                            <PlusIcon class="w-4 h-4" />
                            Bulk Assign
                        </button>

                        <!-- Bulk Unassign Button -->
                        <button
                            v-if="selectedRows.size > 0"
                            @click="showBulkDeleteModal = true"
                            class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 rounded-md transition-colors"
                        >
                            <TrashIcon class="w-4 h-4" />
                            Unassign ({{ selectedRows.size }})
                        </button>

                        <!-- Refresh Button -->
                        <button
                            @click="refreshData"
                            class="p-2 text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 rounded-md transition-colors"
                            title="Refresh"
                        >
                            <ArrowPathIcon class="w-4 h-4" />
                        </button>

                        <!-- PDF Export Button -->
                        <button
                            @click="generatePDF"
                            class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-md transition-colors"
                        >
                            <DocumentArrowDownIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                 <!-- Table Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <!-- Loading State -->
                    <div v-if="isLoading" class="flex items-center justify-center py-16">
                        <div class="flex flex-col items-center gap-3">
                            <div class="w-10 h-10 border-4 border-gray-200 border-t-gray-600 rounded-full animate-spin"></div>
                            <span class="text-xs text-gray-500 font-medium">Loading computers assignments...</span>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="!filterData.length" class="text-center py-16">
                        <div class="inline-flex items-center justify-center w-14 h-14 bg-gray-100 rounded-full mb-3">
                            <ComputerDesktopIcon class="w-7 h-7 text-gray-400" />
                        </div>
                        <h3 class="text-base font-medium text-gray-900 mb-1">No assigned computers found</h3>
                        <p class="text-xs text-gray-500">Try adjusting your search or filter criteria, or assign computers to laboratories</p>
                    </div>

                    <!-- Table -->
                    <div v-else class="overflow-x-auto">
                        <Table
                            :data="filterData"
                            :loading="isLoading"
                            :pagination="pagination"
                            :mobile-fields="['computer_number', 'ip_address', 'mac_address', 'laboratory', 'status']"
                            @page-change="applyFilters"
                        >
                            <template #header>
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th class="px-4 py-2.5 text-left w-10">
                                            <input
                                                type="checkbox"
                                                :checked="isAllSelected"
                                                @change="toggleSelectAll"
                                                class="rounded border-gray-300 text-gray-600 focus:ring-gray-200"
                                            />
                                        </th>
                                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-600">Computer Number</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-600">IP Address</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-600">MAC Address</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-600">Laboratory</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-600">Status</th>
                                        <th class="px-4 py-2.5 text-center text-xs font-medium text-gray-600">Actions</th>
                                    </tr>
                                </thead>
                            </template>
                            <template #default>
                                <tr
                                    v-for="computer in filterData"
                                    :key="computer.id"
                                    class="border-b border-gray-100 hover:bg-gray-50 transition-colors"
                                    :class="{ 'bg-blue-50': selectedRows.has(computer.id) }"
                                >
                                    <td class="px-4 py-3">
                                        <input
                                            type="checkbox"
                                            :checked="selectedRows.has(computer.id)"
                                            @change="toggleRowSelection(computer.id)"
                                            class="rounded border-gray-300 text-gray-600 focus:ring-gray-200"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-900 font-medium">
                                        {{ computer.computer_number || 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600 font-mono">
                                        {{ computer.ip_address || 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600 font-mono">
                                        {{ computer.mac_address || 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-900">
                                        {{ computer.laboratory?.name || 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                            :class="{
                                                'bg-green-100 text-green-700': computer.status === 'active',
                                                'bg-red-100 text-red-700': computer.status === 'inactive',
                                                'bg-yellow-100 text-yellow-700': computer.status === 'maintenance'
                                            }"
                                        >
                                            {{ computer.status || 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button
                                                @click="unassignSingle(computer.id)"
                                                class="p-1 text-red-400 hover:text-red-600 rounded transition-colors"
                                                title="Unassign from Laboratory"
                                            >
                                                <TrashIcon class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </Table>
                    </div>
                </div>


            </div>

            <!-- Bulk Delete Confirmation Modal -->
            <Modal :show="showBulkDeleteModal" @close="showBulkDeleteModal = false">
                <div class="relative bg-white rounded-lg p-6 max-w-md mx-auto">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Confirm Bulk Unassignment</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Are you sure you want to unassign {{ selectedRows.size }} computer(s) from their laboratories? This action cannot be undone.
                    </p>
                    <div class="flex gap-3 justify-end">
                        <button
                            @click="showBulkDeleteModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            @click="bulkUnassign"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 transition-colors"
                        >
                            Unassign {{ selectedRows.size }} Computer(s)
                        </button>
                    </div>
                </div>
            </Modal>

            <!-- Bulk Assignment Modal -->
            <Transition
                enter-active-class="transition-opacity duration-300"
                leave-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
            <div v-if="showBulkAssignModal" class="fixed inset-0  bg-white/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto">
                <Transition
                    enter-active-class="transition-all duration-300"
                    leave-active-class="transition-all duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div v-if="showBulkAssignModal" @click.stop class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-auto my-8 flex flex-col max-h-[90vh]">
                        <!-- Modal Header - Always Visible -->
                        <div class="px-6 py-4 border-b border-gray-200 flex-shrink-0">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">Bulk Computer Assignment</h2>
                                    <p class="text-sm text-gray-600 mt-1">Assign multiple computers to a laboratory</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        v-if="selectedComputers.size > 0"
                                        @click="selectedComputers.clear()"
                                        class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
                                    >
                                        Clear Selections
                                    </button>
                                    <button @click="closeBulkAssignModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                                        <XMarkIcon class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Scrollable Content Area -->
                        <div class="flex-1 overflow-y-auto px-6 py-4">
                        <div class="space-y-6">
                            <!-- Laboratory Selection -->
                            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                                <label class="text-sm font-medium text-gray-900 mb-2 block">
                                    Select Laboratory <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="bulkLabFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                                >
                                    <option value="">-- Select a Laboratory --</option>
                                    <option v-for="lab in laboratory.laboratories" :key="lab.id" :value="lab">
                                        {{ lab.name }} - {{ lab.code }}
                                    </option>
                                </select>
                                <p class="text-xs text-gray-600 mt-2">
                                    All selected computers will be assigned to this laboratory
                                </p>
                            </div>

                            <!-- Computer Filters -->
                            <div>
                                <h3 class="text-sm font-medium text-gray-900 mb-3 flex items-center gap-2">
                                    <ComputerDesktopIcon class="w-4 h-4" />
                                    Search Computers
                                </h3>
                                <input
                                    v-model="bulkComputerSearch"
                                    type="text"
                                    placeholder="Search by computer number, IP, or MAC address..."
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-gray-200 focus:border-gray-400"
                                />
                            </div>

                            <!-- Available Computers List -->
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900">Available Computers (Unassigned)</h3>
                                        <p class="text-xs text-gray-500 mt-0.5">{{ filteredAvailableComputers.length }} computers without laboratory assignment</p>
                                    </div>
                                    <button
                                        @click="toggleAllComputers"
                                        class="text-xs font-medium text-gray-600 hover:text-gray-900 transition-colors"
                                        :disabled="filteredAvailableComputers.length === 0"
                                    >
                                        {{ selectedComputers.size === filteredAvailableComputers.length && filteredAvailableComputers.length > 0 ? 'Deselect All' : 'Select All' }}
                                    </button>
                                </div>
                                <div class="max-h-96 overflow-y-auto">
                                    <div v-if="isLoadingComputers" class="flex items-center justify-center py-12">
                                        <div class="w-8 h-8 border-4 border-gray-200 border-t-gray-600 rounded-full animate-spin"></div>
                                    </div>
                                    <div v-else-if="filteredAvailableComputers.length === 0" class="py-12 text-center text-sm text-gray-500">
                                        <ComputerDesktopIcon class="w-12 h-12 mx-auto text-gray-300 mb-2" />
                                        <p>No unassigned computers available</p>
                                        <p class="text-xs mt-1">All computers are already assigned to laboratories</p>
                                    </div>
                                    <div v-else class="divide-y divide-gray-100">
                                        <label
                                            v-for="computer in filteredAvailableComputers"
                                            :key="computer.id"
                                            class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors"
                                            :class="{ 'bg-green-50': selectedComputers.has(computer.id) }"
                                        >
                                            <input
                                                type="checkbox"
                                                :checked="selectedComputers.has(computer.id)"
                                                @change="toggleComputerSelection(computer.id)"
                                                class="rounded border-gray-300 text-gray-600 focus:ring-gray-200"
                                            />
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ computer.computer_number }}
                                                </div>
                                                <div class="text-xs text-gray-500 font-mono">
                                                    {{ computer.ip_address }} • {{ computer.mac_address }}
                                                </div>
                                            </div>
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                                :class="{
                                                    'bg-green-100 text-green-700': computer.status === 'active',
                                                    'bg-red-100 text-red-700': computer.status === 'inactive',
                                                    'bg-yellow-100 text-yellow-700': computer.status === 'maintenance'
                                                }"
                                            >
                                                {{ computer.status }}
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div><!-- End Scrollable Content Area -->

                        <!-- Footer - Always Visible -->
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                            <div class="text-sm text-gray-600 space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-gray-700">Selected:</span>
                                    <span class="text-green-600 font-semibold">{{ selectedComputers.size }} computer(s)</span>
                                </div>
                                <div v-if="bulkLabFilter" class="text-xs text-gray-500">
                                    📍 Assigning to: <span class="font-medium">{{ bulkLabFilter.name }}</span>
                                </div>
                                <div v-if="!bulkLabFilter" class="text-xs text-red-500">
                                    ⚠️ Please select a laboratory
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    @click="closeBulkAssignModal"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
                                    :disabled="isAssigning"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="performBulkAssignment"
                                    :disabled="selectedComputers.size === 0 || !bulkLabFilter || isAssigning"
                                    class="px-4 py-2 text-sm font-medium text-white bg-gray-700 rounded-md hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                                >
                                    <div v-if="isAssigning" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                    {{ isAssigning ? 'Assigning...' : `Assign ${selectedComputers.size} Computer(s)` }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
        </div>
    </AuthenticatedLayout>
</template>
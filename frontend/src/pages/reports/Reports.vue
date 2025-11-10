<script setup>
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import { ref, reactive, onMounted, computed, toRefs } from 'vue';
import { useReportsStore } from '../../store/reports/reports.js';
import { debounce } from 'lodash-es';
import dayjs from 'dayjs';
import { 
    UserIcon, 
    XIcon, 
    RefreshCcwIcon, 
    EyeIcon,
    TrashIcon,
    FileTextIcon,
    FilterIcon
} from 'lucide-vue-next';
import LoaderSpinner from '../../components/spinner/LoaderSpinner.vue';
import { useStates } from '../../composable/states';
import { useRouter } from 'vue-router';
import Modal from '../../components/modal/Modal.vue';

const state = useStates();
const reportStore = useReportsStore();
const { reports, pagination, isLoading } = toRefs(state);
const { fetchReports, deleteReport } = reportStore;

const router = useRouter();

// Search and filters
const searchQuery = ref('');
const statusFilter = ref('');
const dateFrom = ref('');
const dateTo = ref('');
const showFilters = ref(false);

// Modal state
const showModal = ref(false);
const selectedReport = ref(null);
const showDeleteModal = ref(false);
const reportToDelete = ref(null);

// Debounced filter application
const applyFilters = debounce(() => {
    const filters = {
        search: searchQuery.value,
        status: statusFilter.value,
        date_from: dateFrom.value,
        date_to: dateTo.value
    };
    fetchReports(1, filters);
}, 300);

// Watch for filter changes
const handlePageChange = (page) => {
    const filters = {
        search: searchQuery.value,
        status: statusFilter.value,
        date_from: dateFrom.value,
        date_to: dateTo.value
    };
    fetchReports(page, filters);
};

const refreshReports = () => {
    applyFilters();
};

const handleViewReport = (report) => {
    selectedReport.value = report;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedReport.value = null;
};

const openDeleteModal = (report) => {
    reportToDelete.value = report;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    reportToDelete.value = null;
};

const confirmDelete = async () => {
    if (reportToDelete.value) {
        try {
            await deleteReport(reportToDelete.value.id);
            closeDeleteModal();
            refreshReports();
        } catch (err) {
            console.error('Failed to delete report:', err);
        }
    }
};

const handleDeleteReport = async (report) => {
    openDeleteModal(report);
};

onMounted(() => {
    fetchReports();
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white min-h-screen relative">
            <LoaderSpinner :is-loading="isLoading" subMessage="Loading reports..." />
            
            <div v-show="!isLoading">
                <!-- Header Section -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <!-- Page Title -->
                            <div>
                                <h1 class="text-xl font-semibold text-gray-900">Reports</h1>
                                <p class="text-sm text-gray-600 mt-0.5">View and manage student incident reports</p>
                            </div>

                            <!-- Filters Row -->
                            <div class="flex flex-wrap items-center gap-3">
                                <!-- Date Filters -->
                                <div v-if="showFilters" class="flex items-center gap-2">
                                    <input
                                        v-model="dateFrom"
                                        @change="applyFilters"
                                        type="date"
                                        class="px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-200 focus:border-gray-400 bg-white transition-colors"
                                        placeholder="From"
                                    >
                                    <input
                                        v-model="dateTo"
                                        @change="applyFilters"
                                        type="date"
                                        class="px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-200 focus:border-gray-400 bg-white transition-colors"
                                        placeholder="To"
                                    >
                                </div>

                                <!-- Search Input -->
                                <div class="w-64">
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                        </div>
                                        <input
                                            v-model="searchQuery"
                                            @input="applyFilters"
                                            type="text"
                                            placeholder="Search reports..."
                                            class="block w-full pl-9 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-200 focus:border-gray-400 text-sm transition-colors bg-white"
                                        />
                                        <button
                                            v-if="searchQuery"
                                            @click="searchQuery = ''; applyFilters()"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                        >
                                            <XIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Results Count -->
                                <div class="text-xs text-gray-600 bg-gray-100 px-3 py-2 rounded-lg border border-gray-200">
                                    {{ pagination.total || 0 }} result{{ (pagination.total !== 1) ? 's' : '' }}
                                </div>

                                <!-- Filter Toggle Button -->
                                <button
                                    @click="showFilters = !showFilters"
                                    class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                                >
                                    <FilterIcon class="w-4 h-4" />
                                    {{ showFilters ? 'Hide' : 'Show' }} Dates
                                </button>

                                <!-- Refresh Button -->
                                <button
                                    @click="refreshReports"
                                    class="inline-flex items-center gap-2 px-3 py-2 bg-gray-700 text-white text-xs font-medium rounded-lg hover:bg-gray-600 transition-colors"
                                    title="Refresh"
                                >
                                    <RefreshCcwIcon class="h-4 w-4" />
                                    Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Section -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <!-- Empty State -->
                    <div v-if="reports.length === 0" class="text-center py-16 bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="max-w-md mx-auto px-4">
                            <FileTextIcon class="mx-auto h-12 w-12 text-gray-300 mb-3" />
                            <h3 class="text-base font-medium text-gray-900 mb-1">No reports found</h3>
                            <p class="text-sm text-gray-500 mb-4">
                                {{ searchQuery || dateFrom || dateTo ? 'No reports match your current filters.' : 'No reports have been submitted yet.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Reports Table -->
                    <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            ID
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Student Name
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Description
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Submitted
                                        </th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="report in reports" :key="report.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-xs font-mono text-gray-600">#{{ report.id }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-xs font-medium text-gray-900">{{ report.fullname }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-xs text-gray-700 max-w-md truncate">{{ report.description }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-xs text-gray-900">{{ dayjs(report.created_at).format('MMM D, YYYY') }}</div>
                                            <div class="text-xs text-gray-500">{{ dayjs(report.created_at).format('h:mm A') }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-2">
                                                <button
                                                    @click="handleViewReport(report)"
                                                    title="View Details"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-md transition-colors"
                                                >
                                                    <EyeIcon class="h-3.5 w-3.5" />
                                                    View
                                                </button>
                                                <button
                                                    @click="handleDeleteReport(report)"
                                                    title="Delete"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 rounded-md transition-colors"
                                                >
                                                    <TrashIcon class="h-3.5 w-3.5" />
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="pagination.last_page > 1" class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="text-xs text-gray-700">
                                    Showing <span class="font-medium">{{ pagination.from }}</span> to 
                                    <span class="font-medium">{{ pagination.to }}</span> of 
                                    <span class="font-medium">{{ pagination.total }}</span> reports
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        @click="handlePageChange(pagination.current_page - 1)"
                                        :disabled="pagination.current_page <= 1"
                                        class="px-3 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                    >
                                        Previous
                                    </button>
                                    <button
                                        @click="handlePageChange(pagination.current_page + 1)"
                                        :disabled="pagination.current_page >= pagination.last_page"
                                        class="px-3 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                    >
                                        Next
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Report Details Modal -->
        <Modal :show="showModal" @close="closeModal" max-width="2xl">
            <div class="relative bg-white p-6 rounded-lg">
                <!-- Close button -->
                <button
                    @click="closeModal"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors"
                >
                    <XIcon class="w-5 h-5" />
                </button>

                <!-- Modal header -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Report Details
                    </h3>
                    <div class="mt-2 h-px bg-gray-200"></div>
                </div>

                <!-- Modal content -->
                <div v-if="selectedReport" class="space-y-4">
                    <!-- Report ID -->
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                            Report ID
                        </label>
                        <p class="text-sm text-gray-900 font-mono">
                            #{{ selectedReport.id }}
                        </p>
                    </div>

                    <!-- Student Name -->
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                            Student Name
                        </label>
                        <p class="text-sm text-gray-900">
                            {{ selectedReport.fullname }}
                        </p>
                    </div>

                    <!-- Date Submitted -->
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                            Date Submitted
                        </label>
                        <p class="text-sm text-gray-900">
                            {{ dayjs(selectedReport.created_at).format('MMMM D, YYYY h:mm A') }}
                        </p>
                    </div>

                    <!-- Description -->
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                            Description
                        </label>
                        <p class="text-sm text-gray-900 whitespace-pre-wrap leading-relaxed">
                            {{ selectedReport.description }}
                        </p>
                    </div>
                </div>

                <!-- Modal footer -->
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <div class="flex justify-end">
                        <button
                            @click="closeModal"
                            class="px-4 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="closeDeleteModal" max-width="md">
            <div class="relative bg-white p-6 rounded-lg">
                <!-- Icon -->
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-50 rounded-full border border-red-200">
                    <TrashIcon class="w-6 h-6 text-red-600" />
                </div>

                <!-- Modal header -->
                <div class="text-center mb-4">
                    <h3 class="text-base font-semibold text-gray-900 mb-2">
                        Delete Report
                    </h3>
                    <p class="text-sm text-gray-600">
                        Are you sure you want to delete this report? This action cannot be undone.
                    </p>
                </div>

                <!-- Report info -->
                <div v-if="reportToDelete" class="bg-gray-50 rounded-lg p-3 mb-6 border border-gray-200">
                    <div class="flex items-start gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                {{ reportToDelete.fullname }}
                            </p>
                            <p class="text-xs text-gray-600 mt-1 font-mono">
                                Report ID: #{{ reportToDelete.id }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1 line-clamp-2">
                                {{ reportToDelete.description }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Modal footer -->
                <div class="flex gap-3">
                    <button
                        @click="closeDeleteModal"
                        class="flex-1 px-4 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirmDelete"
                        class="flex-1 px-4 py-2 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-600 transition-colors"
                    >
                        Delete Report
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
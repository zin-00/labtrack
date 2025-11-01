<script setup>
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import { ref, reactive, onMounted, computed, toRefs } from 'vue';
import { useToast } from 'vue-toastification';
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

const state = useStates();
const reportStore = useReportsStore();
const { reports, pagination, isLoading } = toRefs(state);
const { fetchReports, } = reportStore;

const toast = useToast();
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

const handleDeleteReport = async (reportId) => {
    if (confirm('Are you sure you want to delete this report?')) {
        try {
            await removeReport(reportId);
            toast.success('Report deleted successfully');
        } catch (err) {
            toast.error('Failed to delete report');
        }
    }
};

onMounted(() => {
    fetchReports();
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-50">
            <LoaderSpinner :is-loading="isLoading" subMessage="Please wait while we fetch your data" />
            
            <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Reports</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        View and manage student incident reports
                    </p>
                </div>
                <!-- Search and Filters -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Filter Toggle -->
                        <button
                            @click="showFilters = !showFilters"
                            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors"
                        >
                            <FilterIcon class="w-4 h-4" />
                            {{ showFilters ? 'Hide' : 'Show' }} Filters
                        </button>

                        <!-- Date Filters (inline when shown) -->
                        <template v-if="showFilters">
                            <input
                                v-model="dateFrom"
                                @change="applyFilters"
                                type="date"
                                class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="From"
                            >
                            <input
                                v-model="dateTo"
                                @change="applyFilters"
                                type="date"
                                class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="To"
                            >
                        </template>

                        <!-- Search Input -->
                        <div class="relative flex-1 min-w-[200px]">
                            <input
                                v-model="searchQuery"
                                @input="applyFilters"
                                type="text"
                                placeholder="Search by name, description, student ID, or RFID..."
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-gray-400 focus:border-gray-400"
                            >
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''; applyFilters()"
                                class="absolute right-2 top-2.5 text-gray-400 hover:text-gray-600"
                            >
                                <XIcon :stroke-width="1.50" class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Results Count -->
                        <span class="text-sm text-gray-600 whitespace-nowrap">
                            {{ pagination.total || 0 }} {{ (pagination.total === 1) ? 'report' : 'reports' }}
                        </span>

                        <!-- Action Buttons -->
                        <button
                            @click="refreshReports"
                            title="Refresh"
                            class="p-2 text-white bg-gray-900 hover:bg-gray-800 rounded-md transition duration-200"
                        >
                            <RefreshCcwIcon :stroke-width="1.50" class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- Reports Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        ID
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Student Name
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Description
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Submitted Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="report in reports" :key="report.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        #{{ report.id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ report.fullname }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 max-w-md truncate">
                                        {{ report.description }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ dayjs(report.created_at).format('MMM D, YYYY h:mm A') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button
                                                @click="handleViewReport(report)"
                                                title="View Details"
                                                class="text-gray-600 hover:text-gray-900 p-1 rounded transition-colors"
                                            >
                                                <EyeIcon class="h-4 w-4" />
                                            </button>
                                            <button
                                                @click="handleDeleteReport(report.id)"
                                                title="Delete"
                                                class="text-red-600 hover:text-red-900 p-1 rounded transition-colors"
                                            >
                                                <TrashIcon class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty state -->
                    <div v-if="reports.length === 0 && !isLoading" class="text-center py-12">
                        <FileTextIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No reports found</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ searchQuery || dateFrom || dateTo ? 'No reports match your current filters.' : 'No reports have been submitted yet.' }}
                        </p>
                    </div>

                    <!-- Pagination -->
                    <div v-if="reports.length > 0 && pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing <span class="font-medium">{{ pagination.from }}</span> to 
                                <span class="font-medium">{{ pagination.to }}</span> of 
                                <span class="font-medium">{{ pagination.total }}</span> reports
                            </div>
                            <div class="flex gap-2">
                                <button
                                    @click="handlePageChange(pagination.current_page - 1)"
                                    :disabled="pagination.current_page <= 1"
                                    class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                >
                                    Previous
                                </button>
                                <button
                                    @click="handlePageChange(pagination.current_page + 1)"
                                    :disabled="pagination.current_page >= pagination.last_page"
                                    class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <transition name="modal">
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" @click="closeModal">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <!-- Background overlay -->
                    <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>

                    <!-- Modal panel -->
                    <div 
                        class="relative inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg"
                        @click.stop
                    >
                        <!-- Close button -->
                        <button
                            @click="closeModal"
                            class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors"
                        >
                            <XIcon class="w-5 h-5" />
                        </button>

                        <!-- Modal header -->
                        <div class="mb-6">
                            <h3 class="text-xl font-semibold text-gray-900">
                                Report Details
                            </h3>
                            <div class="mt-1 h-px bg-gray-200"></div>
                        </div>

                        <!-- Modal content -->
                        <div v-if="selectedReport" class="space-y-4">
                            <!-- Report ID -->
                            <div class="bg-gray-50 rounded-md p-4">
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                    Report ID
                                </label>
                                <p class="text-sm text-gray-900">
                                    #{{ selectedReport.id }}
                                </p>
                            </div>

                            <!-- Student Name -->
                            <div class="bg-gray-50 rounded-md p-4">
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                    Student Name
                                </label>
                                <p class="text-sm text-gray-900">
                                    {{ selectedReport.fullname }}
                                </p>
                            </div>

                            <!-- Date Submitted -->
                            <div class="bg-gray-50 rounded-md p-4">
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                                    Date Submitted
                                </label>
                                <p class="text-sm text-gray-900">
                                    {{ dayjs(selectedReport.created_at).format('MMMM D, YYYY h:mm A') }}
                                </p>
                            </div>

                            <!-- Description -->
                            <div class="bg-gray-50 rounded-md p-4">
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
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
                                >
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </AuthenticatedLayout>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.3s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: scale(0.95);
}
</style>
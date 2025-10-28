<script setup>
import { ref, onMounted, watch, computed, toRef, toRefs } from 'vue';
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import Table from '../../components/table/Table.vue';
import { useToast } from 'vue-toastification';
import * as XLSX from 'xlsx';
import { useComputerLogStore } from '../../composable/computerLog';
import { DocumentArrowDownIcon, ExclamationCircleIcon, FunnelIcon } from '@heroicons/vue/24/outline';
import { storeToRefs } from 'pinia';

const toast = useToast();
const computerLogStore = useComputerLogStore();

const {
    computerLogs,
    showFilters,
    dateFilter,
    isLoading,
    selectedStatus
} = toRefs(computerLogStore);

const {
    fetchComputerLogs,
    fetchAllLogsForExport,
    clearFilters: clearStoreFilters
} = computerLogStore;

const applyFilters = () => {
    fetchComputerLogs(1);
};

watch([() => dateFilter.value.from, () => dateFilter.value.to], () => {
    clearTimeout(window.filterTimeout);
    window.filterTimeout = setTimeout(applyFilters, 500);
});

const fetchLogs = async (page = 1) => {
    fetchComputerLogs(page);
};

const clearFilters = () => {
    clearStoreFilters();
};

const exportToExcel = async () => {
    try {
        isLoading.value = true;
        const allLogs = await fetchAllLogsForExport();
        
        if (allLogs.length === 0) {
            toast.warning('No data to export');
            return;
        }

        // Format data for Excel
        const formattedData = allLogs.map(log => ({
            'Computer': `PC-${log.computer?.computer_number || 'N/A'}`,
            'Laboratory': log.computer?.laboratory?.name || 'N/A',
            'Student ID': log.student?.student_id || '—',
            'Full Name': getFullName(log),
            'IP Address': log.ip_address || 'N/A',
            'MAC Address': log.mac_address || 'N/A',
            'Event': log.event_type || 'N/A',
            'Uptime': log.uptime || '—',
            'Date': formatDate(log.created_at),
            'Time': formatTime(log.created_at),
            'Timestamp': log.created_at
        }));

        const worksheet = XLSX.utils.json_to_sheet(formattedData);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'ActivityLogs');
        
        // Generate filename with date range
        let fileName = 'activity_logs';
        if (dateFilter.value.from && dateFilter.value.to) {
            fileName += `_${dateFilter.value.from}_to_${dateFilter.value.to}`;
        } else if (dateFilter.value.from) {
            fileName += `_from_${dateFilter.value.from}`;
        } else if (dateFilter.value.to) {
            fileName += `_to_${dateFilter.value.to}`;
        }
        
        XLSX.writeFile(workbook, `${fileName}.xlsx`);
        toast.success('Excel exported successfully');
    } catch (error) {
        toast.error('Failed to export Excel');
        console.error('Error exporting Excel:', error);
    } finally {
        isLoading.value = false;
    }
};

const generateIncidentReport = async () => {
    try {
        isLoading.value = true;
        const allLogs = await fetchAllLogsForExport();
        
        if (allLogs.length === 0) {
            toast.warning('No data to generate report');
            return;
        }

        const incidents = allLogs.filter(log => 
            log.event_type === 'Error' || 
            log.event_type === 'Warning' ||
            log.uptime === 'N/A'
        );

        if (incidents.length === 0) {
            toast.info('No incidents found in the selected date range');
            return;
        }

        // Format data for Excel
        const formattedData = incidents.map(log => ({
            'Computer': `PC-${log.computer?.computer_number || 'N/A'}`,
            'Laboratory': log.computer?.laboratory?.name || 'N/A',
            'Student ID': log.student?.student_id || '—',
            'Full Name': getFullName(log),
            'IP Address': log.ip_address || 'N/A',
            'Event Type': log.event_type || 'N/A',
            'Uptime': log.uptime || '—',
            'Date': formatDate(log.created_at),
            'Time': formatTime(log.created_at),
            'Timestamp': log.created_at
        }));

        const worksheet = XLSX.utils.json_to_sheet(formattedData);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'IncidentReport');
        
        // Generate filename with date range
        let fileName = 'incident_report';
        if (dateFilter.value.from && dateFilter.value.to) {
            fileName += `_${dateFilter.value.from}_to_${dateFilter.value.to}`;
        }
        
        XLSX.writeFile(workbook, `${fileName}.xlsx`);
        toast.success('Incident report generated successfully');
    } catch (error) {
        toast.error('Failed to generate incident report');
        console.error('Error generating incident report:', error);
    } finally {
        isLoading.value = false;
    }
};

// Helper functions
const getFullName = (log) => {
    if (log.student?.first_name || log.student?.last_name) {
        return `${log.student.first_name || ''} ${log.student.last_name || ''}`.trim();
    }
    return log.student_id || 'N/A';
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

const getEventBadge = (eventType) => {
    const eventClasses = {
        'Login': 'bg-green-100 text-green-800',
        'Logout': 'bg-blue-100 text-blue-800',
        'Unlock': 'bg-purple-100 text-purple-800',
        'Lock': 'bg-gray-100 text-gray-800',
        'Error': 'bg-red-100 text-red-800',
        'Warning': 'bg-yellow-100 text-yellow-800',
    };
    return eventClasses[eventType] || 'bg-gray-100 text-gray-800';
};

onMounted(() => {
    fetchLogs();
});
</script>

<template>
  <AuthenticatedLayout>
   <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">User Sessions</h2>
          <p class="mt-1 text-xs text-gray-600">Monitor computer usage and system events</p>
        </div>
        
        <!-- Actions -->
        <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row gap-2">
          <button
            @click="showFilters = !showFilters"
            class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
          >
            <FunnelIcon class="w-4 h-4" />
            Filters
          </button>
          
          <button
            @click="generateIncidentReport"
            class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-md transition-colors"
          >
            <ExclamationCircleIcon class="w-4 h-4" />
            Incident Report
          </button>

          <button
            @click="exportToExcel"
            class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium text-white bg-gray-800 hover:bg-gray-700 rounded-md transition-colors"
          >
            <DocumentArrowDownIcon class="w-4 h-4" />
            Export Excel
          </button>
        </div>
      </div>

      <!-- Filters Panel -->
      <div 
        v-if="showFilters"
        class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6"
      >
        <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-end">
          <div class="flex flex-col sm:flex-row gap-4 flex-1">
            <!-- Date Range -->
            <div class="flex gap-4">
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">From Date</label>
                <input 
                  type="date" 
                  v-model="dateFilter.from" 
                  class="px-3 py-2 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">To Date</label>
                <input 
                  type="date" 
                  v-model="dateFilter.to" 
                  class="px-3 py-2 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>
          </div>

          <!-- Filter Actions -->
          <div class="flex gap-2">
            <button
              @click="clearFilters"
              class="px-3 py-2 text-xs text-gray-600 hover:text-gray-800 transition-colors"
            >
              Clear
            </button>
          </div>
        </div>
      </div>

      <!-- Results Summary -->
      <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
        <div class="flex items-center justify-between text-xs">
          <span class="text-gray-600">
            Showing <span class="font-medium">{{ computerLogs.data?.length || 0 }}</span> logs
            <span v-if="dateFilter.from || dateFilter.to" class="text-blue-600">
              (filtered)
            </span>
          </span>
          <span class="text-gray-500">
            Last updated: {{ new Date().toLocaleTimeString() }}
          </span>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <Table :pagination="computerLogs" :loading="isLoading" :users="computerLogs.data || []" @page-change="fetchLogs">
          <template #header>
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-3 text-xs font-medium text-gray-600 text-left">Computer</th>
                <th class="px-3 py-3 text-xs font-medium text-gray-600 text-left hidden md:table-cell">Laboratory</th>
                <th class="px-3 py-3 text-xs font-medium text-gray-600 text-left">Student ID</th>
                <th class="px-3 py-3 text-xs font-medium text-gray-600 text-left hidden sm:table-cell">Full Name</th>
                <th class="px-3 py-3 text-xs font-medium text-gray-600 text-left hidden lg:table-cell">IP Address</th>
                <th class="px-3 py-3 text-xs font-medium text-gray-600 text-left hidden xl:table-cell">MAC Address</th>
                <th class="px-3 py-3 text-xs font-medium text-gray-600 text-left">Event</th>
                <th class="px-3 py-3 text-xs font-medium text-gray-600 text-left hidden lg:table-cell">Uptime</th>
                <th class="px-3 py-3 text-xs font-medium text-gray-600 text-left">Date & Time</th>
              </tr>
            </thead>
          </template>

          <template #default>
            <tr 
              v-for="log in computerLogs.data" 
              :key="log.id"
              class="hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-b-0"
            >
              <td class="px-3 py-1 text-xs text-gray-900">
                <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">
                  PC-{{ log.computer?.computer_number || 'N/A' }}
                </span>
              </td>
              <td class="px-3 py-1 text-xs text-gray-700 hidden md:table-cell">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">
                  {{ log.computer?.laboratory?.name || 'N/A' }}
                </span>
              </td>
              <td class="px-3 py-1 text-xs font-medium text-gray-900">
                {{ log.student?.student_id || '—' }}
              </td>
              <td class="px-3 py-1 text-xs text-gray-900 hidden sm:table-cell">
                <div class="max-w-xs truncate">
                  {{ getFullName(log) }}
                </div>
              </td>
              <td class="px-3 py-1 text-xs text-gray-700 hidden lg:table-cell">
                <code class="text-xs bg-gray-100 px-2 py-1 rounded">
                  {{ log.ip_address || 'N/A' }}
                </code>
              </td>
              <td class="px-3 py-1 text-xs text-gray-700 hidden xl:table-cell">
                <code class="text-xs bg-gray-100 px-2 py-1 rounded">
                  {{ log.mac_address || 'N/A' }}
                </code>
              </td>
              <td class="px-3 py-1 text-xs">
                <span 
                  :class="getEventBadge(log.event_type)"
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                >
                  {{ log.event_type || 'N/A' }}
                </span>
              </td>
              <td class="px-3 py-1 text-xs text-gray-700 hidden lg:table-cell">
                {{ log.uptime || '—' }}
              </td>
              <td class="px-3 py-1 text-xs text-gray-700">
                <div class="text-xs">
                  <div class="font-medium">{{ formatDate(log.created_at) }}</div>
                  <div class="text-gray-500">{{ formatTime(log.created_at) }}</div>
                </div>
              </td>
            </tr>

            <!-- Empty State -->
            <tr v-if="!computerLogs.data || computerLogs.data.length === 0">
              <td colspan="10" class="px-3 py-12 text-center text-gray-500">
                <div class="flex flex-col items-center gap-3">
                  <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                  <div>
                    <h3 class="text-xs font-medium text-gray-900">No activity logs found</h3>
                    <p class="text-xs text-gray-500 mt-1">Try adjusting your filters or check back later.</p>
                  </div>
                </div>
              </td>
            </tr>
          </template>
        </Table>
      </div>

      <!-- Mobile Card View for very small screens -->
      <div class="block sm:hidden mt-6 space-y-3">
        <div 
          v-for="log in computerLogs.data" 
          :key="`mobile-${log.id}`"
          class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm"
        >
          <div class="flex justify-between items-start mb-3">
            <div>
              <h3 class="font-medium text-gray-900">{{ getFullName(log) }}</h3>
              <p class="text-xs text-gray-500">ID: {{ log.student?.student_id || '—' }} • PC-{{ log.computer?.computer_number || 'N/A' }}</p>
            </div>
            <div class="text-right">
              <div class="text-xs font-medium text-gray-900">{{ formatDate(log.created_at) }}</div>
              <div class="text-xs text-gray-500">{{ formatTime(log.created_at) }}</div>
            </div>
          </div>
          
          <div class="flex items-center justify-between">
            <span 
              :class="getEventBadge(log.event_type)"
              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
            >
              {{ log.event_type || 'N/A' }}
            </span>
            <div class="text-xs text-gray-500">
              {{ log.computer?.laboratory?.name || 'N/A' }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
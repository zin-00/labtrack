<script setup>
import { toRefs, onMounted, ref, watch, computed } from 'vue';
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import { useStates } from '../../composable/states';
import Table from '../../components/table/Table.vue';
import { XMarkIcon } from '@heroicons/vue/24/solid';
import { useComputerActivityStore } from '../../composable/activity/computerActivity';
import dayjs from 'dayjs';
import LoaderSpinner from '../../components/spinner/LoaderSpinner.vue';

const states = useStates();
const activity = useComputerActivityStore();

const {
    searchQuery,
    pagination,
    isLoading,
    computerActivity,
    allActivity,
    dateFrom,
    dateTo,
} = toRefs(states);

const { getComputerActivity } = activity;


watch([dateFrom, dateTo, searchQuery], () => {
  getComputerActivity(1, {
    search: searchQuery.value,
    start_date: dateFrom.value,
    end_date: dateTo.value,
  });
});

const filteredActivity = computed(() => {
if(!allActivity.value || !Array.isArray(allActivity.value)){
  return [];
}

return allActivity.value.filter((act) => {
    const matchesSearch =
      !searchQuery.value ||
      act.computer?.computer_number?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      act.computer?.ip_address?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      act.activity_type?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      act.reason?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      act.details?.toLowerCase().includes(searchQuery.value.toLowerCase());

    const createdAt = new Date(act.created_at);
    const from = dateFrom.value ? new Date(dateFrom.value) : null;
    const to = dateTo.value ? new Date(dateTo.value) : null;

    const matchesDate =
      (!from || createdAt >= from) && (!to || createdAt <= to);

    return matchesSearch && matchesDate;
  });
});


// 🔹 Initial fetch
onMounted(() => {
  getComputerActivity();
});
</script>

<template>
  <AuthenticatedLayout>
    <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white min-h-screen relative">
      <LoaderSpinner :isLoading="isLoading" subMessage="Loading computer activity..." />
      
      <div v-show="!isLoading">
        <!-- Header Section -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
              <!-- Page Title -->
              <div>
                <h1 class="text-xl font-semibold text-gray-900">Computer Activity</h1>
                <p class="text-sm text-gray-600 mt-0.5">Monitor computer usage and system events</p>
              </div>

              <!-- Filters Row -->
              <div class="flex flex-wrap items-center gap-3">
                <!-- Date Filters -->
                <div class="flex items-center gap-2">
                  <input
                    type="date"
                    v-model="dateFrom"
                    class="px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-200 focus:border-gray-400 bg-white transition-colors"
                  />
                  <span class="text-gray-400 text-xs">to</span>
                  <input
                    type="date"
                    v-model="dateTo"
                    class="px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-200 focus:border-gray-400 bg-white transition-colors"
                  />
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
                      type="text"
                      placeholder="Search activity..."
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

                <!-- Results Count -->
                <div class="text-xs text-gray-600 bg-gray-100 px-3 py-2 rounded-lg border border-gray-200">
                  {{ filteredActivity.length }} result{{ (filteredActivity.length !== 1) ? 's' : '' }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Content Section -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <!-- Empty State -->
          <div v-if="!filteredActivity || filteredActivity.length === 0" class="text-center py-16 bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="max-w-md mx-auto px-4">
              <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
              <h3 class="text-base font-medium text-gray-900 mb-1">No computer activity found</h3>
              <p class="text-sm text-gray-500 mb-4">
                {{ searchQuery || dateFrom || dateTo ? 'No activity matches your current filters.' : 'Computer activity will appear here once events are logged.' }}
              </p>
            </div>
          </div>

          <!-- Table -->
          <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Computer #</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="activity in filteredActivity" :key="activity.id" class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 whitespace-nowrap">
                      <div class="text-xs font-mono text-gray-600">{{ activity.computer_id }}</div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                      <div class="text-xs font-medium text-gray-900">{{ activity.computer?.computer_number || 'N/A' }}</div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                      <div class="text-xs font-mono text-gray-600">{{ activity.computer?.ip_address || 'N/A' }}</div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                        {{ activity.activity_type || 'N/A' }}
                      </span>
                    </td>
                    <td class="px-4 py-3">
                      <div class="text-xs text-gray-900 max-w-xs truncate">{{ activity.reason || 'N/A' }}</div>
                    </td>
                    <td class="px-4 py-3">
                      <div class="text-xs text-gray-900 max-w-sm truncate">{{ activity.details || 'N/A' }}</div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                      <div class="text-xs text-gray-900">{{ dayjs(activity.created_at).format("MMM D, YYYY") }}</div>
                      <div class="text-xs text-gray-500">{{ dayjs(activity.created_at).format("h:mm A") }}</div>
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
                  <span class="font-medium">{{ pagination.total }}</span> activities
                </div>
                <div class="flex gap-2">
                  <button
                    @click="getComputerActivity(pagination.current_page - 1)"
                    :disabled="pagination.current_page <= 1"
                    class="px-3 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    Previous
                  </button>
                  <button
                    @click="getComputerActivity(pagination.current_page + 1)"
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
  </AuthenticatedLayout>
</template>

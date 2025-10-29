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
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
          <h2 class="text-2xl text-gray-900">Computer Activity</h2>
          <p class="mt-1 text-xs text-gray-600">Monitor computer usage and system events</p>
        </div>
      </div>

      <!-- Filters Panel And Actions -->
      <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row gap-2 items-center">
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

        <!-- 🔹 Date Filters -->
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

      <!-- Table -->
      <Table
        :data="filteredActivity"
        :is-loading="isLoading"
        :pagination="pagination"
        @page-change="getComputerActivity"
      >
        <template #header>
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Computer ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
          </tr>
        </template>
        <template #default>
            <tr v-for="activity in filteredActivity" :key="activity.id">
                <td class="px-6 py-4 text-xs text-gray-900">{{ activity.computer_id }}</td>
                <td class="px-6 py-4 text-xs text-gray-900">{{ activity.computer?.computer_number }}</td>
                <td class="px-6 py-4 text-xs text-gray-900">{{ activity.computer?.ip_address }}</td>
                <td class="px-6 py-4 text-xs text-gray-900">{{ activity.activity_type }}</td>
                <td class="px-6 py-4 text-xs text-gray-900">{{ activity.reason }}</td>
                <td class="px-6 py-4 text-xs text-gray-900">{{ activity.details }}</td>
                <td class="px-6 py-4 text-xs text-gray-900">{{ dayjs(activity.created_at).format("MMM D, YYYY h:mm A") }}</td>
            </tr>
        </template>
      </Table>
    </div>
  </AuthenticatedLayout>
</template>

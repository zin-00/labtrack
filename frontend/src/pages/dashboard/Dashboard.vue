<script setup>
import StatCard from '../../components/card/StatCard.vue';
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import { computed, onMounted, watch, toRefs, ref } from 'vue';
import { useStatusDistributionStore } from '../../composable/statistics';
import { useToast } from 'vue-toastification';
import Table from '../../components/table/Table.vue';
import { useStates } from '../../composable/states';
import { 
  TrendingUpIcon, 
  TrendingDownIcon, 
  ActivityIcon,
  ClockIcon,
  UsersIcon,
  MonitorIcon
} from 'lucide-vue-next';

const toast = useToast();
const func = useStatusDistributionStore();
const states = useStates();
const { fetchStatusDistribution, fetchDataDistribution } = func;
const { selectedPeriod, selectedCampaignFilter, isLoading} = toRefs(states);

// Add new refs for weekly data
const weeklySessionData = ref([]);
const weeklyPeakHours = ref([]);
const averageSessionDuration = ref(0);

const { 
  onlineCount,
  offlineCount,
  totalCount,
  activeCount,
  inactiveCount,
  maintenanceCount,
  activeComputerCount,
  inactiveComputerCount,
  maintenanceComputerCount,
  latestLogs,
} = toRefs(func);

// Toast helper
function showError(error) {
  toast.error(`Failed to fetch data: ${error}`);
}

const loadDataDistribution = async () => {
  isLoading.value = true;
  try {
    await fetchDataDistribution(selectedPeriod.value);
  } catch (error) {
    console.error(error);
    showError(error.message || 'Unknown error');
  } finally {
    isLoading.value = false;
  }
};

watch(selectedPeriod, () => {
  loadDataDistribution();
});

const radialSeries = computed(() => [
  activeCount.value || 0,
  inactiveCount.value || 0,
  maintenanceCount.value || 0
]);

const lineChartSeries = computed(() => [
  { name: "Active Units", data: activeComputerCount.value || [] },
  { name: "Inactive Units", data: inactiveComputerCount.value || [] },
  { name: "Maintenance Units", data: maintenanceComputerCount.value || [] },
  {
    name: "Total Sessions",
    data: (activeComputerCount.value || []).map((val, i) =>
      val + (inactiveComputerCount.value[i] || 0) + (maintenanceComputerCount.value[i] || 0)
    )
  }
]);

const lineChartCategories = computed(() => {
  if (selectedPeriod.value === 'month') {
    return ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
  } else if (selectedPeriod.value === 'week') {
    const days = [];
    const today = new Date();
    for (let i = 6; i >= 0; i--) {
      const d = new Date(today);
      d.setDate(today.getDate() - i);
      days.push(d.toLocaleDateString('en-US', { weekday: 'short' }));
    }
    return days;
  } else if (selectedPeriod.value === 'day' || selectedPeriod.value === 'today') {
    return ['Today'];
  }
  return [];
});

const lineChartOptions = computed(() => ({
  chart: { 
    toolbar: { show: false },
    dropShadow: {
      enabled: true,
      top: 3,
      left: 3,
      blur: 3,
      opacity: 0.1
    }
  },
  xaxis: {
    categories: lineChartCategories.value,
    labels: { 
      style: { colors: '#6B7280', fontSize: '12px' }
    }
  },
  yaxis: {
    labels: { 
      style: { colors: '#6B7280', fontSize: '12px' }
    }
  },
  stroke: { 
    curve: "smooth",
    width: 2
  },
  colors: ['#10B981','#F43F5E' ,'#F59E0B', '#3B82F6'],
  grid: {
    borderColor: '#F3F4F6',
    strokeDashArray: 5
  },
  legend: {
    position: 'top',
    horizontalAlign: 'right',
    fontSize: '12px'
  },
  tooltip: {
    theme: 'light',
    y: {
      formatter: function (val) {
        return val + ' sessions';
      }
    }
  }
}));

// Enhanced bar chart - Weekly Session Hours
const barChartSeries = computed(() => [
  { 
    name: 'Session Hours',
    data: weeklySessionData.value.length > 0 
      ? weeklySessionData.value 
      : [156, 183, 167, 194, 172, 188, 201] // Fallback data
  }
]);

const barChartOptions = computed(() => ({
  chart: { 
    toolbar: { show: false },
    sparkline: { enabled: false }
  },
  xaxis: { 
    categories: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
    labels: { 
      style: { colors: '#6B7280', fontSize: '11px' }
    }
  },
  yaxis: {
    labels: { 
      style: { colors: '#6B7280', fontSize: '11px' },
      formatter: function (val) {
        return Math.round(val) + 'h';
      }
    }
  },
  plotOptions: { 
    bar: { 
      columnWidth: "60%",
      borderRadius: 6,
      distributed: false,
      dataLabels: {
        position: 'top'
      }
    } 
  },
  colors: ['#3B82F6'],
  grid: {
    show: true,
    borderColor: '#F3F4F6',
    strokeDashArray: 3,
    yaxis: { lines: { show: true } },
    xaxis: { lines: { show: false } }
  },
  dataLabels: { 
    enabled: true,
    formatter: function (val) {
      return Math.round(val) + 'h';
    },
    offsetY: -20,
    style: {
      fontSize: '10px',
      colors: ['#374151'],
      fontWeight: 600
    }
  },
  tooltip: {
    theme: 'light',
    y: {
      formatter: function (val) {
        return Math.round(val) + ' hours (' + Math.round(val * 60) + ' minutes)';
      }
    }
  }
}));

const donutOptions = {
  chart: {
    type: 'donut',
    sparkline: { enabled: false }
  },
  plotOptions: {
    pie: {
      donut: {
        size: '70%',
        labels: {
          show: true,
          name: {
            show: true,
            fontSize: '14px',
            fontWeight: '600',
            color: '#374151',
            offsetY: -10
          },
          value: {
            show: true,
            fontSize: '28px',
            fontWeight: 'bold',
            color: '#111827',
            offsetY: 5,
            formatter: function (val) {
              return val;
            }
          },
          total: {
            show: true,
            label: 'Total Nodes',
            fontSize: '13px',
            fontWeight: '500',
            color: '#6B7280',
            formatter: function (w) {
              const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
              return total;
            }
          }
        }
      }
    }
  },
  colors: ['#10B981', '#F43F5E', '#F59E0B'],
  labels: ['Active', 'Inactive', 'Maintenance'],
  legend: {
    show: true,
    position: 'bottom',
    horizontalAlign: 'center',
    fontSize: '12px',
    fontWeight: '500',
    markers: { 
      width: 10, 
      height: 10,
      radius: 2
    },
    itemMargin: {
      horizontal: 8,
      vertical: 4
    }
  },
  dataLabels: {
    enabled: false
  },
  stroke: {
    width: 2,
    colors: ['#fff']
  },
  tooltip: {
    theme: 'light',
    y: {
      formatter: function(val, { seriesIndex, w }) {
        const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
        const percentage = ((val / total) * 100).toFixed(1);
        return val + ' (' + percentage + '%)';
      }
    }
  }
};

const setPeriod = (period) => {
  selectedPeriod.value = period;
};

const setCampaignFilter = (filter) => {
  selectedCampaignFilter.value = filter;
};

const getFullName = (log) => {
  if (log.student?.first_name || log.student?.last_name) {
    return `${log.student.first_name || ''} ${log.student.last_name || ''}`.trim()
  }
  return log.student_id || 'N/A'
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const formatTime = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  })
}

const getStatusBadgeClass = (status) => {
  const statusMap = {
    'active': 'bg-green-100 text-green-800',
    'inactive': 'bg-gray-100 text-gray-800',
    'maintenance': 'bg-amber-100 text-amber-800'
  };
  return statusMap[status?.toLowerCase()] || 'bg-gray-100 text-gray-800';
};

const getTimeAgo = (dateString) => {
  if (!dateString) return 'N/A';
  
  const now = new Date();
  const date = new Date(dateString);
  const seconds = Math.floor((now - date) / 1000);
  
  if (seconds < 60) return 'Just now';
  if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
  if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
  return `${Math.floor(seconds / 86400)}d ago`;
};

// Calculate total weekly hours
const totalWeeklyHours = computed(() => {
  return weeklySessionData.value.reduce((a, b) => a + b, 0);
});

const weeklyGrowth = computed(() => {
  // You can calculate this from previous week data
  return '+12.5%'; // Placeholder
});

onMounted(async () => {
  try {
    await fetchStatusDistribution();
    await loadDataDistribution();
    
    // Simulate fetching weekly session data
    // Replace this with actual API call
    weeklySessionData.value = [156, 183, 167, 194, 172, 188, 201];
    averageSessionDuration.value = 2.3; // hours
  } catch (error) {
    console.error('Error loading data:', error);
    showError(error.message || 'Unknown error');
  } finally {
    isLoading.value = false;
  }
});
</script>

<template>
  <AuthenticatedLayout>
    <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white">
      <!-- Enhanced Header with Quick Stats -->
      <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="text-2xl font-bold text-gray-900">Overview Dashboard</h2>
            <p class="mt-1 text-sm text-gray-600">
              Track computer activity, usage statistics, and system events in real time
            </p>
          </div>
          <div class="text-right">
            <div class="text-sm text-gray-500">Last updated</div>
            <div class="text-sm font-medium text-gray-900">{{ new Date().toLocaleTimeString() }}</div>
          </div>
        </div>
      </div>

      <!-- Main Grid Section -->
      <div class="space-y-6">
        <!-- Top Stats Cards - Full Width -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <StatCard 
            title="Online Nodes" 
            :value="onlineCount" 
            change="+1.26%"
            :trend="'up'"
          />
          <StatCard 
            title="Offline Nodes" 
            :value="offlineCount" 
            change="-1.56%"
            :trend="'down'"
          />
          <StatCard 
            title="Active Sessions" 
            :value="activeCount" 
            change="+3.26%"
            :trend="'up'"
          />
          <StatCard 
            title="Total Units" 
            :value="totalCount" 
            change="+3.25%"
            :trend="'up'"
          />
        </div>

        <!-- Charts Row - 3 Columns -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Donut Chart - Compact -->
          <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <div class="mb-3">
              <h3 class="text-base font-semibold text-gray-900">Node Distribution</h3>
              <p class="text-xs text-gray-500 mt-0.5">Status breakdown</p>
            </div>
            <apexchart
              v-if="!isLoading"
              height="220"
              type="donut"
              :options="donutOptions"
              :series="radialSeries"
            />
            <div v-else class="h-52 flex items-center justify-center">
              <div class="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2 border-gray-500"></div>
            </div>
          </div>

          <!-- Line Chart - Takes 2 columns -->
          <div class="lg:col-span-2 bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-base font-semibold text-gray-900">Activity Trends</h3>
                <p class="text-xs text-gray-500 mt-0.5">System nodes monitoring</p>
              </div>
              <div class="flex gap-1.5">
                <button 
                  @click="setPeriod('month')"
                  :class="selectedPeriod === 'month' ? 'bg-gray-200 text-gray-800' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'"
                  class="px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors"
                >
                  Month
                </button>
                <button 
                  @click="setPeriod('week')"
                  :class="selectedPeriod === 'week' ? 'bg-gray-200 text-gray-800' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'"
                  class="px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors"
                >
                  Week
                </button>
                <button 
                  @click="setPeriod('day')"
                  :class="selectedPeriod === 'day' ? 'bg-gray-200 text-gray-800' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'"
                  class="px-2.5 py-1.5 text-xs font-medium rounded-md transition-colors"
                >
                  Day
                </button>
              </div>
            </div>
            <apexchart
              v-if="!isLoading"
              height="220"
              type="area"
              :options="lineChartOptions"
              :series="lineChartSeries"
            />
            <div v-else class="h-52 flex items-center justify-center">
              <div class="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2 border-gray-500"></div>
            </div>
          </div>
        </div>

        <!-- Bottom Row - Bar Chart and Stats -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Weekly Session Hours - Takes 2 columns -->
          <div class="lg:col-span-2 bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-base font-semibold text-gray-900">Weekly Session Hours</h3>
                <p class="text-xs text-gray-500 mt-0.5">Lab usage overview</p>
              </div>
              <div class="text-right">
                <div class="text-xl font-bold text-gray-900">{{ totalWeeklyHours }}<span class="text-sm text-gray-500">h</span></div>
                <div class="flex items-center justify-end gap-1 mt-0.5">
                  <TrendingUpIcon class="w-3 h-3 text-green-600" />
                  <span class="text-xs font-medium text-green-600">{{ weeklyGrowth }}</span>
                </div>
              </div>
            </div>

            <apexchart
              height="200"
              type="bar"
              :options="barChartOptions"
              :series="barChartSeries"
            />
            
            <div class="mt-4 pt-3 border-t border-gray-100 grid grid-cols-3 gap-4 text-center">
              <div>
                <div class="text-xs text-gray-500">Peak Day</div>
                <div class="text-sm font-semibold text-gray-900 mt-0.5">Sunday</div>
              </div>
              <div>
                <div class="text-xs text-gray-500">Avg/Day</div>
                <div class="text-sm font-semibold text-gray-900 mt-0.5">{{ (totalWeeklyHours / 7).toFixed(1) }}h</div>
              </div>
              <div>
                <div class="text-xs text-gray-500">Avg Session</div>
                <div class="text-sm font-semibold text-gray-900 mt-0.5">{{ averageSessionDuration }}h</div>
              </div>
            </div>
          </div>

          <!-- Quick Stats Summary -->
          <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <div class="mb-4">
              <h3 class="text-base font-semibold text-gray-900">Quick Stats</h3>
              <p class="text-xs text-gray-500 mt-0.5">System overview</p>
            </div>
            
            <div class="space-y-3">
              <div class="p-3 rounded-lg bg-green-50 border border-green-100">
                <div class="flex items-center justify-between">
                  <div>
                    <div class="text-xs text-gray-600 font-medium">Active Nodes</div>
                    <div class="text-2xl font-bold text-green-600 mt-1">{{ activeCount }}</div>
                  </div>
                  <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <ActivityIcon class="w-6 h-6 text-green-600" />
                  </div>
                </div>
              </div>

              <div class="p-3 rounded-lg bg-red-50 border border-red-100">
                <div class="flex items-center justify-between">
                  <div>
                    <div class="text-xs text-gray-600 font-medium">Inactive Nodes</div>
                    <div class="text-2xl font-bold text-red-600 mt-1">{{ inactiveCount }}</div>
                  </div>
                  <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                    <MonitorIcon class="w-6 h-6 text-red-600" />
                  </div>
                </div>
              </div>

              <div class="p-3 rounded-lg bg-amber-50 border border-amber-100">
                <div class="flex items-center justify-between">
                  <div>
                    <div class="text-xs text-gray-600 font-medium">Maintenance</div>
                    <div class="text-2xl font-bold text-amber-600 mt-1">{{ maintenanceCount }}</div>
                  </div>
                  <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                    <ClockIcon class="w-6 h-6 text-amber-600" />
                  </div>
                </div>
              </div>

              <div class="p-3 rounded-lg bg-gray-100 border border-gray-200">
                <div class="flex items-center justify-between">
                  <div>
                    <div class="text-xs text-gray-600 font-medium">Total Units</div>
                    <div class="text-2xl font-bold text-gray-900 mt-1">{{ totalCount }}</div>
                  </div>
                  <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                    <UsersIcon class="w-6 h-6 text-gray-700" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Latest Logs Table - Full Width -->
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-base font-semibold text-gray-900">Recent Activity</h3>
              <p class="text-xs text-gray-500 mt-0.5">Latest system access logs</p>
            </div>
            <router-link 
              to="/computer_logs"
              class="text-xs bg-gray-100 text-gray-700 px-3 py-1.5 rounded-lg font-medium hover:bg-gray-200 transition-colors inline-flex items-center gap-1"
            >
              <span>View All</span>
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </router-link>
          </div>

          <div class="overflow-x-auto">
              <Table>
                <template #header>
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-4 py-2.5 text-xs font-semibold text-gray-700 text-left uppercase tracking-wider">
                        Student
                      </th>
                      <th class="px-4 py-2.5 text-xs font-semibold text-gray-700 text-left uppercase tracking-wider hidden lg:table-cell">
                        Laboratory
                      </th>
                      <th class="px-4 py-2.5 text-xs font-semibold text-gray-700 text-left uppercase tracking-wider hidden md:table-cell">
                        Workstation
                      </th>
                      <th class="px-4 py-2.5 text-xs font-semibold text-gray-700 text-left uppercase tracking-wider hidden xl:table-cell">
                        IP Address
                      </th>
                      <th class="px-4 py-2.5 text-xs font-semibold text-gray-700 text-left uppercase tracking-wider">
                        Time
                      </th>
                      <th class="px-4 py-2.5 text-xs font-semibold text-gray-700 text-left uppercase tracking-wider hidden sm:table-cell">
                        Status
                      </th>
                    </tr>
                  </thead>
                </template>
                
                <template #default>
                  <tr 
                    v-for="log in latestLogs" 
                    :key="log.id"
                    class="hover:bg-gray-50 transition-colors border-b border-gray-100"
                  >
                    <td class="px-4 py-3">
                      <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 bg-gradient-to-br from-gray-500 to-gray-600 rounded-full flex items-center justify-center flex-shrink-0">
                          <span class="text-white text-xs font-semibold">
                            {{ log.student?.first_name?.charAt(0) || 'N' }}{{ log.student?.last_name?.charAt(0) || 'A' }}
                          </span>
                        </div>
                        <div class="min-w-0">
                          <p class="text-sm font-medium text-gray-900 truncate">
                            {{ getFullName(log) }}
                          </p>
                          <p class="text-xs text-gray-500 truncate">
                            ID: {{ log.student?.student_id || '—' }}
                          </p>
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-3 hidden lg:table-cell">
                      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                        <MonitorIcon class="w-3 h-3" />
                        {{ log.computer?.laboratory?.name || 'N/A' }}
                      </span>
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                      <span class="inline-flex items-center gap-1 font-mono text-xs bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                        </svg>
                        PC-{{ log.computer?.computer_number || 'N/A' }}
                      </span>
                    </td>
                    <td class="px-4 py-3 hidden xl:table-cell">
                      <code class="text-xs bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md font-mono">
                        {{ log.ip_address || '—' }}
                      </code>
                    </td>
                    <td class="px-4 py-3">
                      <div class="flex items-center gap-2">
                        <ClockIcon class="w-3.5 h-3.5 text-gray-400" />
                        <div>
                          <div class="text-xs font-medium text-gray-900">
                            {{ getTimeAgo(log.created_at) }}
                          </div>
                          <div class="text-xs text-gray-500">
                            {{ formatTime(log.created_at) }}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-3 hidden sm:table-cell">
                      <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                        Active
                      </span>
                    </td>
                  </tr>
                  
                  <!-- Empty State -->
                  <tr v-if="!latestLogs || latestLogs.length === 0">
                    <td colspan="6" class="px-4 py-10 text-center">
                      <div class="flex flex-col items-center gap-2">
                        <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center">
                          <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                          </svg>
                        </div>
                        <div>
                          <p class="text-sm font-medium text-gray-900">No activity found</p>
                          <p class="text-xs text-gray-500 mt-0.5">Recent system logs will appear here</p>
                        </div>
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
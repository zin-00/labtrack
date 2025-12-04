<script setup>
import StatCard from '../../components/card/StatCard.vue';
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import { computed, onMounted, watch, toRefs, ref } from 'vue';
import { useStatusDistributionStore } from '../../composable/statistics';
import { useToast } from '../../composable/toastification/useToast';
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
  topWebsites,
  weeklySessionHours,
  laboratoryUsage,
  studentStats,
} = toRefs(func);

// Previous values for trend calculation
const prevOnlineCount = ref(0);
const prevOfflineCount = ref(0);
const prevActiveCount = ref(0);
const prevTotalCount = ref(0);

// Toast helper
function showError(error) {
  toast.error(`Failed to fetch data: ${error}`);
}

// Calculate percentage change
const calculateChange = (current, previous) => {
  if (previous === 0) return current > 0 ? '+100%' : '0%';
  const change = ((current - previous) / previous) * 100;
  const sign = change >= 0 ? '+' : '';
  return `${sign}${change.toFixed(1)}%`;
};

// Computed properties for stat card changes
const onlineChange = computed(() => calculateChange(onlineCount.value, prevOnlineCount.value));
const offlineChange = computed(() => calculateChange(offlineCount.value, prevOfflineCount.value));
const activeChange = computed(() => calculateChange(activeCount.value, prevActiveCount.value));
const totalChange = computed(() => calculateChange(totalCount.value, prevTotalCount.value));

// Computed properties for trends
const onlineTrend = computed(() => onlineCount.value >= prevOnlineCount.value ? 'up' : 'down');
const offlineTrend = computed(() => offlineCount.value <= prevOfflineCount.value ? 'up' : 'down');
const activeTrend = computed(() => activeCount.value >= prevActiveCount.value ? 'up' : 'down');
const totalTrend = computed(() => totalCount.value >= prevTotalCount.value ? 'up' : 'down');

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
  { name: "Active Nodes", data: activeComputerCount.value || [] },
  { name: "Inactive Nodes", data: inactiveComputerCount.value || [] },
  { name: "Maintenance Nodes", data: maintenanceComputerCount.value || [] },
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
    data: weeklySessionHours.value.length > 0 
      ? weeklySessionHours.value 
      : [0, 0, 0, 0, 0, 0, 0]
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
  if (weeklySessionHours.value.length === 0) return 0;
  return weeklySessionHours.value.reduce((a, b) => a + b, 0).toFixed(1);
});

// Calculate peak day
const peakDay = computed(() => {
  if (weeklySessionHours.value.length === 0) return 'N/A';
  
  const days = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
  const maxHours = Math.max(...weeklySessionHours.value);
  const maxIndex = weeklySessionHours.value.indexOf(maxHours);
  
  return maxIndex !== -1 ? days[maxIndex] : 'N/A';
});

// Calculate average session duration
const averageSessionDuration = computed(() => {
  if (weeklySessionHours.value.length === 0) return '0.0';
  
  const total = weeklySessionHours.value.reduce((a, b) => a + b, 0);
  const avg = total / weeklySessionHours.value.filter(h => h > 0).length || 0;
  
  return avg.toFixed(1);
});

// Calculate weekly growth
const weeklyGrowth = computed(() => {
  if (weeklySessionHours.value.length < 7) return '+0%';
  
  // Compare last 3 days vs first 4 days
  const lastThreeDays = weeklySessionHours.value.slice(4, 7).reduce((a, b) => a + b, 0);
  const firstFourDays = weeklySessionHours.value.slice(0, 4).reduce((a, b) => a + b, 0);
  
  if (firstFourDays === 0) return '+0%';
  
  const growth = ((lastThreeDays / 3 - firstFourDays / 4) / (firstFourDays / 4)) * 100;
  const sign = growth >= 0 ? '+' : '';
  
  return `${sign}${growth.toFixed(1)}%`;
});

// Student statistics computed properties
const studentStatusSeries = computed(() => [
  {
    name: 'Students',
    data: [
      studentStats.value.active || 0,
      studentStats.value.inactive || 0,
      studentStats.value.restricted || 0
    ]
  }
]);

// Laboratory usage chart data
const labUsageSeries = computed(() => [
  {
    name: selectedPeriod.value === 'month' ? 'Sessions (Year)' : 
          selectedPeriod.value === 'week' ? 'Sessions (Week)' : 'Sessions (Today)',
    data: laboratoryUsage.value.map(lab => lab.session_count || 0)
  },
  {
    name: 'Online',
    data: laboratoryUsage.value.map(lab => lab.online_count || 0)
  },
  {
    name: 'Offline',
    data: laboratoryUsage.value.map(lab => lab.offline_count || 0)
  }
]);

// Dynamic grid columns based on number of laboratories
const labGridCols = computed(() => {
  const count = laboratoryUsage.value.length;
  if (count <= 3) return 'grid-cols-3';
  if (count === 4) return 'grid-cols-4';
  if (count === 5) return 'grid-cols-5';
  return 'grid-cols-6'; // 6+ labs
});

const labUsageCategories = computed(() => 
  laboratoryUsage.value.map(lab => lab.lab_code || lab.lab_name)
);

const labUsageOptions = computed(() => ({
  chart: {
    type: 'bar',
    toolbar: { show: false },
    stacked: false
  },
  plotOptions: {
    bar: {
      horizontal: false,
      columnWidth: laboratoryUsage.value.length <= 3 ? '60%' : 
                   laboratoryUsage.value.length === 4 ? '55%' : '50%',
      borderRadius: 4,
      dataLabels: {
        position: 'top'
      }
    }
  },
  colors: ['#3B82F6', '#10B981', '#EF4444'],
  dataLabels: {
    enabled: true,
    offsetY: -20,
    style: {
      fontSize: '10px',
      fontWeight: 600,
      colors: ['#374151']
    }
  },
  xaxis: {
    categories: labUsageCategories.value,
    labels: {
      style: { colors: '#6B7280', fontSize: '11px', fontWeight: 500 },
      rotate: -45,
      rotateAlways: labUsageCategories.value.length > 5
    }
  },
  yaxis: {
    title: {
      text: 'Count',
      style: { color: '#6B7280', fontSize: '11px', fontWeight: 500 }
    },
    labels: {
      style: { colors: '#6B7280', fontSize: '11px' }
    }
  },
  grid: {
    show: true,
    borderColor: '#F3F4F6',
    strokeDashArray: 3,
    xaxis: { lines: { show: false } },
    yaxis: { lines: { show: true } }
  },
  legend: {
    position: 'top',
    horizontalAlign: 'right',
    fontSize: '12px',
    fontWeight: 500,
    markers: { width: 10, height: 10, radius: 2 }
  },
  tooltip: {
    theme: 'light',
    y: {
      formatter: function(val, { seriesIndex }) {
        if (seriesIndex === 0) return val + ' sessions';
        return val + ' computers';
      }
    }
  }
}));

const studentStatusOptions = computed(() => ({
  chart: {
    type: 'bar',
    toolbar: { show: false },
    sparkline: { enabled: false }
  },
  plotOptions: {
    bar: {
      horizontal: true,
      borderRadius: 6,
      barHeight: '60%',
      distributed: true,
      dataLabels: {
        position: 'top'
      }
    }
  },
  colors: ['#10B981', '#F43F5E', '#F59E0B'],
  dataLabels: {
    enabled: true,
    textAnchor: 'start',
    offsetX: 5,
    style: {
      fontSize: '12px',
      fontWeight: 600,
      colors: ['#fff']
    },
    formatter: function(val) {
      return val;
    }
  },
  xaxis: {
    categories: ['Active', 'Inactive', 'Restricted'],
    labels: {
      style: { colors: '#6B7280', fontSize: '11px' }
    }
  },
  yaxis: {
    labels: {
      style: { colors: '#6B7280', fontSize: '12px', fontWeight: 500 }
    }
  },
  grid: {
    show: true,
    borderColor: '#F3F4F6',
    strokeDashArray: 3,
    xaxis: { lines: { show: true } },
    yaxis: { lines: { show: false } }
  },
  legend: {
    show: false
  },
  tooltip: {
    theme: 'light',
    y: {
      formatter: function(val) {
        const total = studentStats.value.total || 1;
        const percentage = ((val / total) * 100).toFixed(1);
        return val + ' students (' + percentage + '%)';
      }
    }
  }
}));
const EventListener = () => {
  if(!window.Echo) return;
  
  window.Echo.channel('main-channel')
  .listen('.MainEvent', (e) => {
    console.log('Dashboard received event:', e.type, e.data);
    
    switch(e.type) {
      case 'Computer':
      case 'computer':
        // Update computer status counts
        handleComputerStatusUpdate(e.data);
        break;
        
      case 'Student':
      case 'student':
        fetchStatusDistribution();
        break;
        
      case 'RecentScan':
      case 'recent_scan':
        // Update latest logs
        if (e.data && !latestLogs.value.find(log => log.id === e.data.id)) {
          latestLogs.value.unshift(e.data);
          if (latestLogs.value.length > 10) {
            latestLogs.value.pop();
          }
        }
        break;
        
      case 'BrowserActivity':
      case 'browser_activity':
        loadDataDistribution();
        break;
        
      default:
        // console.warn('Unknown event type:', e.type);
    }
  });
};

const handleComputerStatusUpdate = (computerData) => {
  if (!computerData) return;
  
  // Store previous values before fetching new data
  prevOnlineCount.value = onlineCount.value;
  prevOfflineCount.value = offlineCount.value;
  prevActiveCount.value = activeCount.value;
  prevTotalCount.value = totalCount.value;
  
  fetchStatusDistribution();
  
  if (computerData.is_online !== undefined) {
    const wasOnline = computerData.previous_online_status;
    if (computerData.is_online && !wasOnline) {
      onlineCount.value++;
      if (offlineCount.value > 0) offlineCount.value--;
    } else if (!computerData.is_online && wasOnline) {
      offlineCount.value++;
      if (onlineCount.value > 0) onlineCount.value--;
    }
  }
};

// Update previous values after initial data fetch
const updatePreviousValues = () => {
  prevOnlineCount.value = onlineCount.value;
  prevOfflineCount.value = offlineCount.value;
  prevActiveCount.value = activeCount.value;
  prevTotalCount.value = totalCount.value;
};

onMounted(async () => {
  try {
    await fetchStatusDistribution();
    await loadDataDistribution();
    // Store initial values as previous for first render
    updatePreviousValues();
    EventListener();
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
            :change="onlineChange"
            :trend="onlineTrend"
          />
          <StatCard 
            title="Offline Nodes" 
            :value="offlineCount" 
            :change="offlineChange"
            :trend="offlineTrend"
          />
          <StatCard 
            title="Active Sessions" 
            :value="activeCount" 
            :change="activeChange"
            :trend="activeTrend"
          />
          <StatCard 
            title="Total Units" 
            :value="totalCount" 
            :change="totalChange"
            :trend="totalTrend"
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

        <!-- Bottom Row - Laboratory Usage (Full Width) -->
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-base font-semibold text-gray-900">Laboratory Usage Statistics</h3>
              <p class="text-xs text-gray-500 mt-0.5">
                Sessions, online and offline computers per laboratory
                <span v-if="selectedPeriod === 'month'">(This Year)</span>
                <span v-else-if="selectedPeriod === 'week'">(Last 7 Days)</span>
                <span v-else-if="selectedPeriod === 'day'">(Today)</span>
              </p>
            </div>
            <div class="flex items-center gap-3">
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
              <router-link 
                to="/laboratory"
                class="text-xs bg-gray-100 text-gray-700 px-3 py-1.5 rounded-lg font-medium hover:bg-gray-200 transition-colors inline-flex items-center gap-1"
              >
                <span>View All</span>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
              </router-link>
            </div>
          </div>

          <div v-if="!isLoading && laboratoryUsage.length > 0">
            <apexchart
              height="350"
              type="bar"
              :options="labUsageOptions"
              :series="labUsageSeries"
            />

            <!-- Laboratory Names - Dynamic Column Layout -->
            <div class="mt-6 grid gap-4" :class="labGridCols">
              <div 
                v-for="lab in laboratoryUsage" 
                :key="lab.lab_code"
                class="text-center p-3 rounded-lg bg-gray-50 border border-gray-200"
              >
                <h4 class="text-sm font-semibold text-gray-900">{{ lab.lab_name }}</h4>
                <p class="text-xs text-gray-500 mt-1">{{ lab.lab_code }}</p>
              </div>
            </div>
          </div>

          <div v-else class="py-12 text-center">
            <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <p class="text-sm font-medium text-gray-900">No laboratory data available</p>
            <p class="text-xs text-gray-500 mt-0.5">Laboratory statistics will appear here</p>
          </div>
        </div>

        <!-- Top 3 Most Visited Websites - Redesigned -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h3 class="text-lg font-bold text-gray-900">Top Visited Websites</h3>
              <p class="text-xs text-gray-500 mt-1">Most accessed sites across all laboratories</p>
            </div>
            <router-link 
              to="/browser-activity"
              class="text-xs bg-gray-100 text-gray-700 px-3 py-1.5 rounded-lg font-medium hover:bg-gray-200 transition-colors inline-flex items-center gap-1"
            >
              <span>View All</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </router-link>
          </div>

          <!-- Clean Minimal Cards -->
          <div v-if="topWebsites && topWebsites.length > 0" class="space-y-3">
            <div
              v-for="(website, index) in topWebsites"
              :key="website.url"
              class="p-4 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors border border-gray-200"
            >
              <div class="flex items-start gap-4">
                <!-- Rank Number -->
                <div class="flex-shrink-0">
                  <div class="w-10 h-10 rounded-lg bg-white border border-gray-300 flex items-center justify-center">
                    <span class="text-lg font-bold text-gray-700">{{ index + 1 }}</span>
                  </div>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                  <!-- Website Title -->
                  <h4 class="text-sm font-semibold text-gray-900 mb-1 truncate" :title="website.title">
                    {{ website.title || 'Untitled Page' }}
                  </h4>

                  <!-- Website URL -->
                  <a 
                    :href="website.url" 
                    target="_blank"
                    class="text-xs text-gray-600 hover:text-gray-900 hover:underline block mb-2 truncate"
                    :title="website.url"
                  >
                    {{ website.url }}
                  </a>

                  <!-- Visit Count Bar -->
                  <div class="flex items-center gap-3">
                    <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                      <div 
                        class="h-full bg-gray-400 rounded-full transition-all duration-500"
                        :style="{ width: topWebsites.length > 0 ? (website.visit_count / topWebsites[0].visit_count * 100) + '%' : '0%' }"
                      ></div>
                    </div>
                    <span class="text-sm font-semibold text-gray-700 min-w-[3rem] text-right">
                      {{ website.visit_count }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="py-12 text-center">
            <div class="w-14 h-14 bg-gray-100 rounded-xl flex items-center justify-center mx-auto mb-3">
              <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
              </svg>
            </div>
            <p class="text-sm font-medium text-gray-900">No Website Activity</p>
            <p class="text-xs text-gray-500 mt-1">Data will appear here</p>
          </div>
        </div>

        <!-- Latest Logs Table - Full Width -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h3 class="text-base font-semibold text-gray-900">Recent Activity</h3>
              <p class="text-xs text-gray-500 mt-1">Latest system access logs</p>
            </div>
            <router-link 
              to="/computer_logs"
              class="text-xs text-gray-600 hover:text-gray-900 font-medium transition-colors inline-flex items-center gap-1"
            >
              <span>View All</span>
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </router-link>
          </div>

          <!-- Clean Activity List -->
          <div v-if="latestLogs && latestLogs.length > 0" class="space-y-2">
            <div
              v-for="log in latestLogs?.slice(0, 8) || []"
              :key="log.id"
              class="flex items-center gap-4 p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors border border-gray-200"
            >
              <!-- Avatar -->
              <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-white border border-gray-300 rounded-lg flex items-center justify-center">
                  <span class="text-xs font-semibold text-gray-700">
                    {{ getFullName(log).split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() }}
                  </span>
                </div>
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-1">
                  <h4 class="text-sm font-medium text-gray-900 truncate">{{ getFullName(log) }}</h4>
                  <span class="text-xs text-gray-500 ml-2">{{ getTimeAgo(log.created_at) }}</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-600">
                  <span>PC-{{ log.computer?.computer_number || 'N/A' }}</span>
                  <span class="text-gray-400">•</span>
                  <span>{{ log.computer?.laboratory?.name || 'N/A' }}</span>
                  <span v-if="log.ip_address" class="text-gray-400">•</span>
                  <span v-if="log.ip_address" class="font-mono">{{ log.ip_address }}</span>
                </div>
              </div>

              <!-- Status Indicator -->
              <div class="flex-shrink-0">
                <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="py-12 text-center">
            <div class="w-14 h-14 bg-gray-100 rounded-xl flex items-center justify-center mx-auto mb-3">
              <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
            <p class="text-sm font-medium text-gray-900">No Recent Activity</p>
            <p class="text-xs text-gray-500 mt-1">Logs will appear here</p>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue';
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import { useToast } from '../../composable/toastification/useToast';
import { 
    ArrowPathIcon, 
    DocumentArrowDownIcon, 
    ChartBarIcon,
    ComputerDesktopIcon,
    UserGroupIcon,
    ClockIcon,
    PrinterIcon,
    FunnelIcon
} from '@heroicons/vue/24/outline';
import LoaderSpinner from '../../components/spinner/LoaderSpinner.vue';
import axios from 'axios';
import { useApiUrl } from '../../api/api';

const { api } = useApiUrl();
const toast = useToast();

// State
const isLoading = ref(false);
const laboratories = ref([]);
const reportData = ref(null);

// Filters
const filters = reactive({
    laboratory_id: 'all',
    filter_type: 'month',
    date: new Date().toISOString().split('T')[0],
    month: new Date().getMonth() + 1,
    year: new Date().getFullYear(),
});

// Month options
const months = [
    { value: 1, label: 'January' },
    { value: 2, label: 'February' },
    { value: 3, label: 'March' },
    { value: 4, label: 'April' },
    { value: 5, label: 'May' },
    { value: 6, label: 'June' },
    { value: 7, label: 'July' },
    { value: 8, label: 'August' },
    { value: 9, label: 'September' },
    { value: 10, label: 'October' },
    { value: 11, label: 'November' },
    { value: 12, label: 'December' },
];

// Generate year options (last 5 years)
const years = computed(() => {
    const currentYear = new Date().getFullYear();
    return Array.from({ length: 5 }, (_, i) => currentYear - i);
});

// Fetch laboratories
const fetchLaboratories = async () => {
    try {
        const response = await axios.get(`${api}/laboratory-reports/laboratories`);
        laboratories.value = response.data.data;
    } catch (error) {
        console.error('Error fetching laboratories:', error);
        toast.error('Failed to fetch laboratories');
    }
};

// Fetch usage report
const fetchUsageReport = async () => {
    isLoading.value = true;
    try {
        const params = {
            laboratory_id: filters.laboratory_id,
            filter_type: filters.filter_type,
        };

        if (filters.filter_type === 'day') {
            params.date = filters.date;
        } else if (filters.filter_type === 'month') {
            params.month = filters.month;
            params.year = filters.year;
        } else if (filters.filter_type === 'year') {
            params.year = filters.year;
        }

        const response = await axios.get(`${api}/laboratory-reports/usage`, { params });
        reportData.value = response.data.data;
    } catch (error) {
        console.error('Error fetching usage report:', error);
        toast.error('Failed to fetch usage report');
    } finally {
        isLoading.value = false;
    }
};

// Get selected laboratory name
const selectedLabName = computed(() => {
    if (filters.laboratory_id === 'all') return 'All Laboratories';
    const lab = laboratories.value.find(l => l.id == filters.laboratory_id);
    return lab?.name || 'Unknown Laboratory';
});

// Get filter period text
const filterPeriodText = computed(() => {
    if (!reportData.value) return '';
    const { start_date, end_date } = reportData.value.filter;
    if (filters.filter_type === 'day') {
        return new Date(start_date).toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    } else if (filters.filter_type === 'month') {
        return new Date(start_date).toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long' 
        });
    } else {
        return `Year ${filters.year}`;
    }
});

// Calculate max value for chart scaling
const maxChartValue = computed(() => {
    if (!reportData.value?.usage_chart_data) return 10;
    const max = Math.max(...reportData.value.usage_chart_data.map(d => d.sessions), 1);
    return Math.ceil(max * 1.2); // Add 20% padding
});

// Print PDF Report
const printReport = () => {
    if (!reportData.value) {
        toast.error('No report data to print');
        return;
    }

    const data = reportData.value;
    
    // Generate chart bars HTML
    const chartBarsHtml = data.usage_chart_data.map(item => {
        const heightPercent = maxChartValue.value > 0 
            ? (item.sessions / maxChartValue.value) * 100 
            : 0;
        return `
            <div style="display: flex; flex-direction: column; align-items: center; flex: 1; min-width: 30px;">
                <div style="height: 200px; display: flex; flex-direction: column; justify-content: flex-end; width: 100%;">
                    <div style="background: linear-gradient(180deg, #166534 0%, #14532d 100%); height: ${heightPercent}%; min-height: ${item.sessions > 0 ? '4px' : '0'}; width: 80%; margin: 0 auto; border-radius: 4px 4px 0 0;"></div>
                </div>
                <div style="font-size: 10px; color: #666; margin-top: 4px; transform: rotate(-45deg); white-space: nowrap;">${item.label}</div>
                <div style="font-size: 9px; color: #333; font-weight: 600; margin-top: 2px;">${item.sessions}</div>
            </div>
        `;
    }).join('');

    // Generate laboratory summary table rows
    const labSummaryRows = data.laboratory_summary.map((lab, index) => `
        <tr style="background: ${index % 2 === 0 ? '#fff' : '#f9fafb'};">
            <td style="border: 1px solid #e5e7eb; padding: 8px 12px; font-size: 12px;">${lab.name}</td>
            <td style="border: 1px solid #e5e7eb; padding: 8px 12px; font-size: 12px; text-align: center;">${lab.total_computers}</td>
            <td style="border: 1px solid #e5e7eb; padding: 8px 12px; font-size: 12px; text-align: center;">${lab.online_computers}</td>
            <td style="border: 1px solid #e5e7eb; padding: 8px 12px; font-size: 12px; text-align: center;">${lab.total_sessions}</td>
            <td style="border: 1px solid #e5e7eb; padding: 8px 12px; font-size: 12px; text-align: center;">${lab.total_uptime_minutes.toFixed(1)} min</td>
        </tr>
    `).join('');

    // Generate top computers rows
    const topComputersRows = data.top_computers.slice(0, 5).map((comp, index) => `
        <tr style="background: ${index % 2 === 0 ? '#fff' : '#f9fafb'};">
            <td style="border: 1px solid #e5e7eb; padding: 6px 10px; font-size: 11px;">${comp.computer_number}</td>
            <td style="border: 1px solid #e5e7eb; padding: 6px 10px; font-size: 11px;">${comp.laboratory_name || 'N/A'}</td>
            <td style="border: 1px solid #e5e7eb; padding: 6px 10px; font-size: 11px; text-align: center;">${comp.session_count}</td>
        </tr>
    `).join('');

    // Generate sessions by program rows
    const programRows = data.sessions_by_program.slice(0, 5).map((prog, index) => `
        <tr style="background: ${index % 2 === 0 ? '#fff' : '#f9fafb'};">
            <td style="border: 1px solid #e5e7eb; padding: 6px 10px; font-size: 11px;">${prog.program}</td>
            <td style="border: 1px solid #e5e7eb; padding: 6px 10px; font-size: 11px; text-align: center;">${prog.count}</td>
        </tr>
    `).join('');

    const html = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratory Usage Report</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0.5in;
        }
        @media print {
            body { margin: 0; padding: 0; background-color: white; }
            .page-wrapper { box-shadow: none; padding: 0; }
            .no-print { display: none !important; }
        }
        body {
            margin: 0;
            padding: 20px;
            background-color: #f2f2f2;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .page-wrapper {
            max-width: 8.5in;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #166534;
        }
        .logo {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }
        .header-text {
            flex: 1;
        }
        .school-name {
            font-size: 18px;
            font-weight: 700;
            color: #166534;
            letter-spacing: 1px;
        }
        .school-location {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }
        .report-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-top: 4px;
        }
        .report-subtitle {
            font-size: 11px;
            color: #666;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        .summary-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }
        .summary-value {
            font-size: 24px;
            font-weight: 700;
            color: #166534;
        }
        .summary-label {
            font-size: 10px;
            color: #666;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }
        .chart-container {
            background: #fafafa;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .chart-bars {
            display: flex;
            align-items: flex-end;
            gap: 4px;
            min-height: 250px;
            padding-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background: #166534;
            color: white;
            padding: 8px 12px;
            font-size: 11px;
            font-weight: 600;
            text-align: left;
            border: 1px solid #14532d;
        }
        .computer-status-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .status-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
        }
        .status-value {
            font-size: 20px;
            font-weight: 700;
        }
        .status-label {
            font-size: 10px;
            color: #666;
            margin-top: 2px;
        }
        .online { color: #16a34a; }
        .offline { color: #dc2626; }
        .active { color: #2563eb; }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <!-- Header with Logo -->
        <div class="header">
            <img src="/src/assets/sfxclogov1.png" alt="School Logo" class="logo" onerror="this.style.display='none'" />
            <div class="header-text">
                <div class="school-name">ST. FRANCIS XAVIER COLLEGE</div>
                <div class="school-location">SAN FRANCISCO • AGUSAN DEL SUR</div>
                <div class="report-title">Laboratory Usage Report</div>
                <div class="report-subtitle">${selectedLabName.value} | ${filterPeriodText.value}</div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-value">${data.summary.total_sessions}</div>
                <div class="summary-label">Total Sessions</div>
            </div>
            <div class="summary-card">
                <div class="summary-value">${data.summary.unique_students}</div>
                <div class="summary-label">Unique Students</div>
            </div>
            <div class="summary-card">
                <div class="summary-value">${data.summary.total_uptime_hours.toFixed(1)}h</div>
                <div class="summary-label">Total Uptime</div>
            </div>
            <div class="summary-card">
                <div class="summary-value">${data.summary.avg_session_duration_minutes.toFixed(1)}m</div>
                <div class="summary-label">Avg Session</div>
            </div>
        </div>

        <!-- Computer Status -->
        <div class="section-title">Computer Status Overview</div>
        <div class="computer-status-grid">
            <div class="status-card">
                <div class="status-value online">${data.computer_status?.online || 0}</div>
                <div class="status-label">Online</div>
            </div>
            <div class="status-card">
                <div class="status-value offline">${data.computer_status?.offline || 0}</div>
                <div class="status-label">Offline</div>
            </div>
            <div class="status-card">
                <div class="status-value active">${data.computer_status?.total || 0}</div>
                <div class="status-label">Total Computers</div>
            </div>
        </div>

        <!-- Usage Chart -->
        <div class="section-title">Session Activity (${filters.filter_type === 'day' ? 'Hourly' : filters.filter_type === 'month' ? 'Daily' : 'Monthly'})</div>
        <div class="chart-container">
            <div class="chart-bars">
                ${chartBarsHtml || '<div style="text-align: center; color: #666; width: 100%; padding: 50px;">No session data available</div>'}
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="two-column">
            <!-- Top Computers -->
            <div>
                <div class="section-title">Top Used Computers</div>
                <table>
                    <thead>
                        <tr>
                            <th>Computer</th>
                            <th>Laboratory</th>
                            <th style="text-align: center;">Sessions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${topComputersRows || '<tr><td colspan="3" style="text-align: center; padding: 20px; color: #666;">No data</td></tr>'}
                    </tbody>
                </table>
            </div>

            <!-- Sessions by Program -->
            <div>
                <div class="section-title">Sessions by Program</div>
                <table>
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th style="text-align: center;">Sessions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${programRows || '<tr><td colspan="2" style="text-align: center; padding: 20px; color: #666;">No data</td></tr>'}
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Laboratory Summary -->
        <div class="section-title">Laboratory Summary</div>
        <table>
            <thead>
                <tr>
                    <th>Laboratory</th>
                    <th style="text-align: center;">Total PCs</th>
                    <th style="text-align: center;">Online</th>
                    <th style="text-align: center;">Sessions</th>
                    <th style="text-align: center;">Total Uptime</th>
                </tr>
            </thead>
            <tbody>
                ${labSummaryRows || '<tr><td colspan="5" style="text-align: center; padding: 20px; color: #666;">No laboratory data</td></tr>'}
            </tbody>
        </table>

        <!-- Footer -->
        <div class="footer">
            Generated on ${new Date().toLocaleString()} | LabTrack - Laboratory Management System
        </div>
    </div>
</body>
</html>`;

    const printWindow = window.open('', '_blank', 'width=900,height=1100');
    
    if (!printWindow) {
        toast.error('Please allow popups to print the report');
        return;
    }

    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
    
    printWindow.onload = () => {
        setTimeout(() => {
            printWindow.focus();
            printWindow.print();
        }, 500);
    };
};

// Watch for filter changes
watch(() => [filters.filter_type, filters.laboratory_id, filters.date, filters.month, filters.year], () => {
    fetchUsageReport();
}, { deep: true });

// Initialize
onMounted(async () => {
    await fetchLaboratories();
    await fetchUsageReport();
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white min-h-screen relative">
            <LoaderSpinner :isLoading="isLoading" subMessage="Generating usage report..." />

            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
                <!-- Header -->
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">Laboratory Usage Report</h1>
                        <p class="mt-1 text-sm text-gray-500">View and analyze laboratory usage statistics</p>
                    </div>
                    <div class="flex gap-2">
                        <button
                            @click="fetchUsageReport"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            <ArrowPathIcon class="w-4 h-4" />
                            Refresh
                        </button>
                        <button
                            @click="printReport"
                            :disabled="!reportData"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-800 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <PrinterIcon class="w-4 h-4" />
                            Print Report
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                    <div class="flex items-center gap-2 mb-3">
                        <FunnelIcon class="w-4 h-4 text-gray-500" />
                        <span class="text-sm font-medium text-gray-700">Filters</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-4">
                        <!-- Laboratory Select -->
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-medium text-gray-500">Laboratory</label>
                            <select
                                v-model="filters.laboratory_id"
                                class="w-48 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            >
                                <option value="all">All Laboratories</option>
                                <option v-for="lab in laboratories" :key="lab.id" :value="lab.id">
                                    {{ lab.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Filter Type -->
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-medium text-gray-500">Filter By</label>
                            <select
                                v-model="filters.filter_type"
                                class="w-36 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            >
                                <option value="day">Day</option>
                                <option value="month">Month</option>
                                <option value="year">Year</option>
                            </select>
                        </div>

                        <!-- Date (for day filter) -->
                        <div v-if="filters.filter_type === 'day'" class="flex flex-col gap-1">
                            <label class="text-xs font-medium text-gray-500">Date</label>
                            <input
                                v-model="filters.date"
                                type="date"
                                class="w-44 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            />
                        </div>

                        <!-- Month (for month filter) -->
                        <div v-if="filters.filter_type === 'month'" class="flex flex-col gap-1">
                            <label class="text-xs font-medium text-gray-500">Month</label>
                            <select
                                v-model="filters.month"
                                class="w-36 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            >
                                <option v-for="m in months" :key="m.value" :value="m.value">
                                    {{ m.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Year (for month and year filter) -->
                        <div v-if="filters.filter_type !== 'day'" class="flex flex-col gap-1">
                            <label class="text-xs font-medium text-gray-500">Year</label>
                            <select
                                v-model="filters.year"
                                class="w-28 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            >
                                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Report Content -->
                <div v-if="reportData">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-green-600 rounded-lg">
                                    <ChartBarIcon class="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-green-800">{{ reportData.summary.total_sessions }}</p>
                                    <p class="text-xs text-green-600">Total Sessions</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-blue-600 rounded-lg">
                                    <UserGroupIcon class="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-blue-800">{{ reportData.summary.unique_students }}</p>
                                    <p class="text-xs text-blue-600">Unique Students</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-xl p-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-purple-600 rounded-lg">
                                    <ClockIcon class="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-purple-800">{{ reportData.summary.total_uptime_hours.toFixed(1) }}h</p>
                                    <p class="text-xs text-purple-600">Total Uptime</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-200 rounded-xl p-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-amber-600 rounded-lg">
                                    <ComputerDesktopIcon class="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-amber-800">{{ reportData.summary.avg_session_duration_minutes.toFixed(1) }}m</p>
                                    <p class="text-xs text-amber-600">Avg Session Duration</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Computer Status -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4">Computer Status Overview</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <p class="text-xl font-bold text-gray-800">{{ reportData.computer_status?.total || 0 }}</p>
                                <p class="text-xs text-gray-500">Total</p>
                            </div>
                            <div class="text-center p-3 bg-green-50 rounded-lg">
                                <p class="text-xl font-bold text-green-600">{{ reportData.computer_status?.online || 0 }}</p>
                                <p class="text-xs text-gray-500">Online</p>
                            </div>
                            <div class="text-center p-3 bg-red-50 rounded-lg">
                                <p class="text-xl font-bold text-red-600">{{ reportData.computer_status?.offline || 0 }}</p>
                                <p class="text-xs text-gray-500">Offline</p>
                            </div>
                            <div class="text-center p-3 bg-blue-50 rounded-lg">
                                <p class="text-xl font-bold text-blue-600">{{ reportData.computer_status?.active || 0 }}</p>
                                <p class="text-xs text-gray-500">Active</p>
                            </div>
                            <div class="text-center p-3 bg-gray-100 rounded-lg">
                                <p class="text-xl font-bold text-gray-600">{{ reportData.computer_status?.inactive || 0 }}</p>
                                <p class="text-xs text-gray-500">Inactive</p>
                            </div>
                            <div class="text-center p-3 bg-yellow-50 rounded-lg">
                                <p class="text-xl font-bold text-yellow-600">{{ reportData.computer_status?.maintenance || 0 }}</p>
                                <p class="text-xs text-gray-500">Maintenance</p>
                            </div>
                        </div>
                    </div>

                    <!-- Usage Chart -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4">
                            Session Activity 
                            <span class="text-gray-400 font-normal">
                                ({{ filters.filter_type === 'day' ? 'Hourly' : filters.filter_type === 'month' ? 'Daily' : 'Monthly' }})
                            </span>
                        </h3>
                        <div class="relative">
                            <div v-if="reportData.usage_chart_data && reportData.usage_chart_data.length > 0" class="flex items-end gap-1 h-64 overflow-x-auto pb-8">
                                <div 
                                    v-for="(item, index) in reportData.usage_chart_data" 
                                    :key="index"
                                    class="flex flex-col items-center min-w-[40px] flex-1"
                                >
                                    <div class="relative w-full h-52 flex flex-col justify-end">
                                        <div 
                                            class="w-4/5 mx-auto bg-gradient-to-t from-green-800 to-green-600 rounded-t-md transition-all duration-300 hover:from-green-700 hover:to-green-500"
                                            :style="{ height: `${maxChartValue > 0 ? (item.sessions / maxChartValue) * 100 : 0}%`, minHeight: item.sessions > 0 ? '4px' : '0' }"
                                        ></div>
                                    </div>
                                    <div class="text-[10px] text-gray-500 mt-1 transform -rotate-45 origin-top-left whitespace-nowrap">
                                        {{ item.label }}
                                    </div>
                                    <div class="text-xs font-semibold text-gray-700 mt-1">{{ item.sessions }}</div>
                                </div>
                            </div>
                            <div v-else class="flex items-center justify-center h-64 text-gray-400">
                                No session data available for this period
                            </div>
                        </div>
                    </div>

                    <!-- Two Column Layout -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <!-- Top Used Computers -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                            <h3 class="text-sm font-semibold text-gray-800 mb-4">Top Used Computers</h3>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="text-left py-2 px-3 font-medium text-gray-600">Computer</th>
                                            <th class="text-left py-2 px-3 font-medium text-gray-600">Laboratory</th>
                                            <th class="text-center py-2 px-3 font-medium text-gray-600">Sessions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr 
                                            v-for="(comp, index) in reportData.top_computers.slice(0, 5)" 
                                            :key="comp.id"
                                            :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                                        >
                                            <td class="py-2 px-3 text-gray-800">{{ comp.computer_number }}</td>
                                            <td class="py-2 px-3 text-gray-600">{{ comp.laboratory_name || 'N/A' }}</td>
                                            <td class="py-2 px-3 text-center font-semibold text-green-700">{{ comp.session_count }}</td>
                                        </tr>
                                        <tr v-if="!reportData.top_computers.length">
                                            <td colspan="3" class="py-8 text-center text-gray-400">No data available</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Sessions by Program -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                            <h3 class="text-sm font-semibold text-gray-800 mb-4">Sessions by Program</h3>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="text-left py-2 px-3 font-medium text-gray-600">Program</th>
                                            <th class="text-center py-2 px-3 font-medium text-gray-600">Sessions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr 
                                            v-for="(prog, index) in reportData.sessions_by_program" 
                                            :key="prog.program"
                                            :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                                        >
                                            <td class="py-2 px-3 text-gray-800">{{ prog.program }}</td>
                                            <td class="py-2 px-3 text-center font-semibold text-blue-700">{{ prog.count }}</td>
                                        </tr>
                                        <tr v-if="!reportData.sessions_by_program.length">
                                            <td colspan="2" class="py-8 text-center text-gray-400">No data available</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Laboratory Summary -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-800 mb-4">Laboratory Summary</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-green-800 text-white">
                                        <th class="text-left py-3 px-4 font-medium">Laboratory</th>
                                        <th class="text-center py-3 px-4 font-medium">Total PCs</th>
                                        <th class="text-center py-3 px-4 font-medium">Online</th>
                                        <th class="text-center py-3 px-4 font-medium">Sessions</th>
                                        <th class="text-center py-3 px-4 font-medium">Total Uptime</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr 
                                        v-for="(lab, index) in reportData.laboratory_summary" 
                                        :key="lab.id"
                                        :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                                        class="hover:bg-green-50 transition-colors"
                                    >
                                        <td class="py-3 px-4 font-medium text-gray-800">{{ lab.name }}</td>
                                        <td class="py-3 px-4 text-center text-gray-600">{{ lab.total_computers }}</td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                {{ lab.online_computers }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center font-semibold text-green-700">{{ lab.total_sessions }}</td>
                                        <td class="py-3 px-4 text-center text-gray-600">{{ lab.total_uptime_minutes.toFixed(1) }} min</td>
                                    </tr>
                                    <tr v-if="!reportData.laboratory_summary.length">
                                        <td colspan="5" class="py-8 text-center text-gray-400">No laboratory data available</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- No Data State -->
                <div v-else-if="!isLoading" class="text-center py-16">
                    <ChartBarIcon class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                    <p class="text-gray-500">No report data available</p>
                    <button 
                        @click="fetchUsageReport"
                        class="mt-4 px-4 py-2 text-sm text-green-700 hover:text-green-800 font-medium"
                    >
                        Try Again
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Custom scrollbar for chart */
.overflow-x-auto::-webkit-scrollbar {
    height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #999;
}
</style>

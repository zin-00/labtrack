<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue';
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import { useToast } from '../../composable/toastification/useToast';
import { 
    ArrowPathIcon, 
    PlusIcon, 
    ArrowDownTrayIcon, 
    XMarkIcon, 
    EllipsisVerticalIcon,
    ChartBarIcon,
    ComputerDesktopIcon,
    UserGroupIcon,
    ClockIcon,
    PrinterIcon,
    FunnelIcon
} from '@heroicons/vue/24/outline';
import { useLaboratoryStore } from '../../composable/laboratory';
import { storeToRefs } from 'pinia';
import { useRouter } from 'vue-router';
import Modal from '../../components/modal/Modal.vue';
import { debounce } from 'lodash-es'
import Button from '../../components/button/Button.vue';
import { useComputerStore } from '../../composable/computers';
import LoaderSpinner from '../../components/spinner/LoaderSpinner.vue';
import Table from '../../components/table/Table.vue';
import axios from 'axios';
import { useApiUrl } from '../../api/api';
import TextInput from '../../components/input/TextInput.vue';
import InputLabel from '../../components/input/InputLabel.vue';



const router = useRouter();
const labStore = useLaboratoryStore();
const cstore = useComputerStore();
const toast = useToast();

const {
    laboratories,
    isLoading,
    statusFilter,
    selectedLab,
    isModalOpen,
    isEditMode,
    isImportModalOpen,
    showDropdown,
    addModal,
    searchQuery,
    populateModal,
} = storeToRefs(labStore);
const {
        computers
    } = storeToRefs(cstore);
const {
        fetchLaboratories,
        storeLaboratory,
        updateLaboratory,
        deleteLaboratory,
        selectedLabFilter
    } = labStore;

const { 
        fetchComputers,
        fetchNoLabComputers,
        assignLabToComputer,
        unassignLabFromComputer
    } = cstore;

// Local refs for computer assignment
const assignMode = ref('assign'); // 'assign' or 'unassign'
const selectedLabForUnassign = ref('all');
const assignedComputers = ref([]);
const selectedComputersForUnassign = ref([]);
const unassignedComputers = ref([]);
const selectedComputers = ref([]);
const currentLabId = ref(null);
const allComputers = ref([]);
const modalSearchQuery = ref(''); // Local search query for the modal
const reportModal = ref(false);
const reportLoading = ref(false);
const reportData = ref(null);

// Report filters
const reportFilters = reactive({
    laboratory_id: 'all',
    filter_type: 'month',
    date: new Date().toISOString().split('T')[0],
    month: new Date().getMonth() + 1,
    year: new Date().getFullYear(),
});

// Month options for report
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

// Get selected laboratory name for report
const selectedLabName = computed(() => {
    if (reportFilters.laboratory_id === 'all') return 'All Laboratories';
    const lab = laboratories.value.find(l => l.id == reportFilters.laboratory_id);
    return lab?.name || 'Unknown Laboratory';
});

// Get filter period text
const filterPeriodText = computed(() => {
    if (!reportData.value) return '';
    const { start_date, end_date } = reportData.value.filter;
    if (reportFilters.filter_type === 'day') {
        return new Date(start_date).toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    } else if (reportFilters.filter_type === 'month') {
        return new Date(start_date).toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long' 
        });
    } else {
        return `Year ${reportFilters.year}`;
    }
});

// Calculate max value for chart scaling
const maxChartValue = computed(() => {
    if (!reportData.value?.usage_chart_data) return 10;
    const max = Math.max(...reportData.value.usage_chart_data.map(d => d.sessions), 1);
    return Math.ceil(max * 1.2);
});

const props = defineProps({
    LabName: String,
});

const newLab = reactive({
    name: '',
    code: '',
    description: '',
    status: 'active',
});

const goToLaboratory = (lab) => {
    router.push({ name: 'computer', params: { lab: lab.id } });
};

// Debounced filter function for faster response
const debouncedFilter = debounce(() => {
    fetchLaboratories({
        search: searchQuery.value,
        status: statusFilter.value,
    });
}, 200);

// Watch for search query changes with debounce
watch(searchQuery, () => {
    debouncedFilter();
});

// Watch for status filter changes - apply immediately (no debounce for dropdown)
watch(statusFilter, () => {
    fetchLaboratories({
        search: searchQuery.value,
        status: statusFilter.value,
    });
});

const saveLaboratory = () => {
    try {
        if (selectedLab.value?.id) {
            updateLaboratory(selectedLab.value.id, newLab);
        } else {
            storeLaboratory(newLab);
        }
        // Refresh with current filters
        fetchLaboratories({
            search: searchQuery.value,
            status: statusFilter.value,
        });
        clearForm();
        isModalOpen.value = false;
        toast.success(selectedLab.value?.id ? 'Laboratory updated successfully' : 'Laboratory created successfully');
    } catch (error) {
        console.error('Error saving laboratory:', error);
        toast.error('Failed to save laboratory');
    }
};

const clearForm = () => {
    newLab.id = null;
    newLab.name = '';
    newLab.code = '';
    newLab.description = '';
    newLab.status = 'active';
};

const editlab = (laboratory) => {
    selectedLab.value = laboratory;
    Object.assign(newLab, {
        id: laboratory.id,
        name: laboratory.name,
        code: laboratory.code,
        description: laboratory.description,
        status: laboratory.status,
    });
    isModalOpen.value = true;
};

const openAddModal = () => {
    selectedLab.value = null;
    clearForm();
    isModalOpen.value = true;
};

const openReportModal = async () => {
    reportModal.value = true;
    reportFilters.laboratory_id = 'all';
    await fetchUsageReport();
};

// Fetch usage report
const fetchUsageReport = async () => {
    reportLoading.value = true;
    try {
        const { api, getAuthHeader } = useApiUrl();
        const params = {
            laboratory_id: reportFilters.laboratory_id,
            filter_type: reportFilters.filter_type,
        };

        if (reportFilters.filter_type === 'day') {
            params.date = reportFilters.date;
        } else if (reportFilters.filter_type === 'month') {
            params.month = reportFilters.month;
            params.year = reportFilters.year;
        } else if (reportFilters.filter_type === 'year') {
            params.year = reportFilters.year;
        }

        const response = await axios.get(`${api}/laboratory-reports/usage`, { params, ...getAuthHeader() });
        reportData.value = response.data.data;
    } catch (error) {
        console.error('Error fetching usage report:', error);
        toast.error('Failed to fetch usage report');
    } finally {
        reportLoading.value = false;
    }
};

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
        @page { size: A4 portrait; margin: 0.5in; }
        @media print { body { margin: 0; padding: 0; background-color: white; } .page-wrapper { box-shadow: none; padding: 0; } }
        body { margin: 0; padding: 20px; background-color: #f2f2f2; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .page-wrapper { max-width: 8.5in; margin: 0 auto; background-color: white; padding: 30px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #166534; }
        .logo { width: 70px; height: 70px; object-fit: contain; }
        .header-text { flex: 1; }
        .school-name { font-size: 18px; font-weight: 700; color: #166534; letter-spacing: 1px; }
        .school-location { font-size: 12px; color: #666; margin-top: 2px; }
        .report-title { font-size: 16px; font-weight: 600; color: #333; margin-top: 4px; }
        .report-subtitle { font-size: 11px; color: #666; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px; }
        .summary-card { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px; text-align: center; }
        .summary-value { font-size: 24px; font-weight: 700; color: #166534; }
        .summary-label { font-size: 10px; color: #666; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
        .section-title { font-size: 14px; font-weight: 600; color: #333; margin: 20px 0 10px 0; padding-bottom: 5px; border-bottom: 1px solid #e5e7eb; }
        .chart-container { background: #fafafa; border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
        .chart-bars { display: flex; align-items: flex-end; gap: 4px; min-height: 250px; padding-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background: #166534; color: white; padding: 8px 12px; font-size: 11px; font-weight: 600; text-align: left; border: 1px solid #14532d; }
        .computer-status-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
        .status-card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; text-align: center; }
        .status-value { font-size: 20px; font-weight: 700; }
        .status-label { font-size: 10px; color: #666; margin-top: 2px; }
        .online { color: #16a34a; }
        .offline { color: #dc2626; }
        .active { color: #2563eb; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #e5e7eb; font-size: 10px; color: #666; text-align: center; }
        .two-column { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <div class="header">
            <img src="/src/assets/sfxclogov1.png" alt="School Logo" class="logo" onerror="this.style.display='none'" />
            <div class="header-text">
                <div class="school-name">ST. FRANCIS XAVIER COLLEGE</div>
                <div class="school-location">SAN FRANCISCO • AGUSAN DEL SUR</div>
                <div class="report-title">Laboratory Usage Report</div>
                <div class="report-subtitle">${selectedLabName.value} | ${filterPeriodText.value}</div>
            </div>
        </div>
        <div class="summary-grid">
            <div class="summary-card"><div class="summary-value">${data.summary.total_sessions}</div><div class="summary-label">Total Sessions</div></div>
            <div class="summary-card"><div class="summary-value">${data.summary.unique_students}</div><div class="summary-label">Unique Students</div></div>
            <div class="summary-card"><div class="summary-value">${data.summary.total_uptime_hours.toFixed(1)}h</div><div class="summary-label">Total Uptime</div></div>
            <div class="summary-card"><div class="summary-value">${data.summary.avg_session_duration_minutes.toFixed(1)}m</div><div class="summary-label">Avg Session</div></div>
        </div>
        <div class="section-title">Computer Status Overview</div>
        <div class="computer-status-grid">
            <div class="status-card"><div class="status-value online">${data.computer_status?.online || 0}</div><div class="status-label">Online</div></div>
            <div class="status-card"><div class="status-value offline">${data.computer_status?.offline || 0}</div><div class="status-label">Offline</div></div>
            <div class="status-card"><div class="status-value active">${data.computer_status?.total || 0}</div><div class="status-label">Total Computers</div></div>
        </div>
        <div class="section-title">Session Activity (${reportFilters.filter_type === 'day' ? 'Hourly' : reportFilters.filter_type === 'month' ? 'Daily' : 'Monthly'})</div>
        <div class="chart-container"><div class="chart-bars">${chartBarsHtml || '<div style="text-align: center; color: #666; width: 100%; padding: 50px;">No session data available</div>'}</div></div>
        <div class="two-column">
            <div><div class="section-title">Top Used Computers</div><table><thead><tr><th>Computer</th><th>Laboratory</th><th style="text-align: center;">Sessions</th></tr></thead><tbody>${topComputersRows || '<tr><td colspan="3" style="text-align: center; padding: 20px; color: #666;">No data</td></tr>'}</tbody></table></div>
            <div><div class="section-title">Sessions by Program</div><table><thead><tr><th>Program</th><th style="text-align: center;">Sessions</th></tr></thead><tbody>${programRows || '<tr><td colspan="2" style="text-align: center; padding: 20px; color: #666;">No data</td></tr>'}</tbody></table></div>
        </div>
        <div class="section-title">Laboratory Summary</div>
        <table><thead><tr><th>Laboratory</th><th style="text-align: center;">Total PCs</th><th style="text-align: center;">Online</th><th style="text-align: center;">Sessions</th><th style="text-align: center;">Total Uptime</th></tr></thead><tbody>${labSummaryRows || '<tr><td colspan="5" style="text-align: center; padding: 20px; color: #666;">No laboratory data</td></tr>'}</tbody></table>
        <div class="footer">Generated on ${new Date().toLocaleString()} | LabTrack - Laboratory Management System</div>
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

// Watch for report filter changes
watch(() => [reportFilters.filter_type, reportFilters.laboratory_id, reportFilters.date, reportFilters.month, reportFilters.year], () => {
    if (reportModal.value) {
        fetchUsageReport();
    }
}, { deep: true });

const toggleDropdown = (id) => {
    showDropdown.value = showDropdown.value === id ? null : id;
};

const openPopulateModal = (labId) => {
    currentLabId.value = labId;
    populateModal.value = true;
    assignMode.value = 'assign';
    selectedComputers.value = [];
    selectedComputersForUnassign.value = [];
    modalSearchQuery.value = ''; // Initialize modal search query
    loadUnassignedComputers();
};

const switchToUnassignMode = () => {
    assignMode.value = 'unassign';
    selectedComputersForUnassign.value = [];
    modalSearchQuery.value = '';
    loadAssignedComputers();
};

const switchToAssignMode = () => {
    assignMode.value = 'assign';
    selectedComputers.value = [];
    modalSearchQuery.value = '';
    loadUnassignedComputers();
};

const toggleComputerSelection = (computerId) => {
    if (selectedComputers.value.includes(computerId)) {
        selectedComputers.value = selectedComputers.value.filter(id => id !== computerId);
    } else {
        selectedComputers.value.push(computerId);
    }
};

const toggleAllComputers = (event) => {
    if (event.target.checked) {
        selectedComputers.value = unassignedComputers.value.map(computer => computer.id);
    } else {
        selectedComputers.value = [];
    }
};

const loadUnassignedComputers = async () => {
    await fetchNoLabComputers();
    unassignedComputers.value = computers.value.filter(computer => !computer.laboratory_id);
};

const loadAssignedComputers = async () => {
    await fetchComputers({
        laboratory_id: currentLabId.value,
        search: modalSearchQuery.value
    });
    assignedComputers.value = computers.value;
};

const filteredAssignedComputers = computed(() => {
    if (!modalSearchQuery.value) {
        return assignedComputers.value;
    }
    const query = modalSearchQuery.value.toLowerCase();
    return assignedComputers.value.filter(computer => 
        computer.computer_number?.toLowerCase().includes(query) ||
        computer.ip_address?.toLowerCase().includes(query) ||
        computer.mac_address?.toLowerCase().includes(query)
    );
});

const assignComputers = async () => {
    try {
        if (selectedComputers.value.length === 0) {
            toast.error('Please select at least one computer');
            return;
        }

        const success = await assignLabToComputer(selectedComputers.value, currentLabId.value);
        
        if (success) {
            toast.success(`${selectedComputers.value.length} computer(s) assigned successfully`);
            selectedComputers.value = [];
            loadAllComputers(); // Refresh all computers for status display
        }

        populateModal.value = false;
        loadUnassignedComputers();
    } catch (error) {
        toast.error('Failed to assign computers');
        console.error(error);
    }
};

const unassignComputers = async () => {
    try {
        if (selectedComputersForUnassign.value.length === 0) {
            toast.error('Please select at least one computer to unassign');
            return;
        }

        const success = await unassignLabFromComputer(selectedComputersForUnassign.value);
        
        if (success) {
            toast.success(`${selectedComputersForUnassign.value.length} computer(s) unassigned successfully`);
            selectedComputersForUnassign.value = [];
            loadAllComputers(); // Refresh all computers for status display
            loadAssignedComputers();
        }
    } catch (error) {
        toast.error('Failed to unassign computers');
        console.error(error);
    }
};

const toggleComputerForUnassign = (computerId) => {
    if (selectedComputersForUnassign.value.includes(computerId)) {
        selectedComputersForUnassign.value = selectedComputersForUnassign.value.filter(id => id !== computerId);
    } else {
        selectedComputersForUnassign.value.push(computerId);
    }
};

const toggleAllForUnassign = (event) => {
    if (event.target.checked) {
        selectedComputersForUnassign.value = filteredAssignedComputers.value.map(computer => computer.id);
    } else {
        selectedComputersForUnassign.value = [];
    }
};

// Helper function to get online computers count for a lab
const getOnlineComputersCount = (labId) => {
    return allComputers.value.filter(c => c.laboratory_id === labId && c.is_online).length;
};

// Helper function to get total computers count for a lab
const getTotalComputersCount = (labId) => {
    return allComputers.value.filter(c => c.laboratory_id === labId).length;
};

// Helper function to check if any computer is online in a lab
const hasOnlineComputers = (labId) => {
    return allComputers.value.some(c => c.laboratory_id === labId && c.is_online);
};

// Load all computers for status tracking
const loadAllComputers = async () => {
    try {
        const { api, getAuthHeader } = useApiUrl();
        const response = await axios.get(`${api}/computers?include=laboratory`, getAuthHeader());
        allComputers.value = response.data.computers || [];
        console.log('All computers loaded:', allComputers.value.length);
        console.log('Computers with is_online=true:', allComputers.value.filter(c => c.is_online).length);
        
        // Log computers by lab
        laboratories.value.forEach(lab => {
            const labComputers = allComputers.value.filter(c => c.laboratory_id === lab.id);
            const onlineCount = labComputers.filter(c => c.is_online).length;
            console.log(`Lab "${lab.name}" (ID: ${lab.id}): ${labComputers.length} total, ${onlineCount} online`);
        });
    } catch (error) {
        console.error('Error loading all computers:', error);
        allComputers.value = [];
        toast.error('Failed to load computer status');
    }
};

onMounted( async () => {
    await fetchLaboratories({
        search: searchQuery.value,
        status: statusFilter.value,
    });
    await loadAllComputers();
    await loadUnassignedComputers();
});
</script>
<template>
    <AuthenticatedLayout>
        <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white min-h-screen relative">
            <LoaderSpinner :isLoading="isLoading" subMessage="Loading laboratories..." />
            
            <div v-show="!isLoading">
                <!-- Header Section -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <!-- Page Title -->
                        <div>
                            <h1 class="text-xl font-semibold text-gray-900">Laboratory Management</h1>
                            <p class="text-sm text-gray-600 mt-0.5">Manage and organize your computer laboratories</p>
                        </div>

                        <!-- Filters and Actions Row -->
                        <div class="flex flex-wrap items-center gap-3">
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
                                        placeholder="Search laboratories..."
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

                            <!-- Status Filter -->
                            <div class="w-40">
                                <select
                                    v-model="statusFilter"
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-200 focus:border-gray-400 text-sm bg-white transition-colors"
                                >
                                    <option value="all">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>

                            <!-- Results Count -->
                            <div class="text-xs text-gray-600 bg-gray-100 px-3 py-2 rounded-lg border border-gray-200">
                                {{ laboratories.length }} result{{ laboratories.length !== 1 ? 's' : '' }}
                            </div>

                            <!-- Add Button -->
                            <Button
                                @click="openAddModal"
                                class="inline-flex items-center gap-2 px-3 py-2 bg-gray-700 text-white text-xs font-medium rounded-lg hover:bg-gray-600 transition-colors shadow-sm"
                            >
                                <PlusIcon class="h-4 w-4" />
                                Add Laboratory
                            </Button>

                            <!-- Generate usage report -->
                             <Button
                                @click="openReportModal"
                                class="inline-flex items-center gap-2 px-3 py-2 bg-gray-700 text-white text-xs font-medium rounded-lg hover:bg-gray-600 transition-colors shadow-sm"
                             >
                             Generate Usage
                             </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Section -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Laboratory Cards Grid -->
                <div v-if="laboratories.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div
                        v-for="lab in laboratories"
                        :key="lab.id"
                        @click="goToLaboratory(lab)"
                        class="group relative bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md hover:border-gray-300 transition-all duration-200 cursor-pointer overflow-hidden"
                    >
                        <!-- Card Header -->
                        <div class="p-4 pb-3">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1">
                                    <h3 class="text-base font-semibold text-gray-900 group-hover:text-gray-700 transition-colors">
                                        {{ lab.name }}
                                    </h3>
                                    <p class="text-xs text-gray-600 mt-1 font-mono bg-gray-50 px-2 py-0.5 rounded inline-block border border-gray-200">
                                        {{ lab.code }}
                                    </p>
                                </div>

                                <!-- Dropdown Menu -->
                                <div class="relative">
                                    <button 
                                        @click.stop="toggleDropdown(lab.id)"
                                        class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-50 rounded-lg transition-colors"
                                    >
                                        <EllipsisVerticalIcon class="h-4 w-4" />
                                    </button>

                                    <div
                                        v-if="showDropdown === lab.id"
                                        class="absolute right-0 top-full mt-1 w-36 bg-white rounded-lg shadow-lg border border-gray-200 z-20 py-1"
                                    >
                                        <button 
                                            @click.stop="editlab(lab)"
                                            class="flex items-center w-full px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            Edit Lab
                                        </button>
                                        <button 
                                            @click.stop="deleteLaboratory(lab.id)"
                                            class="flex items-center w-full px-3 py-2 text-xs text-red-600 hover:bg-red-50 transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <p v-if="lab.description" class="text-xs text-gray-600 line-clamp-2 mb-3">
                                {{ lab.description }}
                            </p>
                            
                            <!-- Computer Stats -->
                            <div class="flex items-center gap-2 mt-3">
                                <!-- Total Computers -->
                                <div class="flex items-center gap-1.5 px-2 py-1 bg-gray-100 border border-gray-200 rounded-md">
                                    <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-xs font-medium text-gray-700">
                                        {{ getTotalComputersCount(lab.id) }} PCs
                                    </span>
                                </div>
                                
                                <!-- Online/Occupied Status -->
                                <div v-if="hasOnlineComputers(lab.id)" class="flex items-center gap-1.5 px-2 py-1 bg-green-50 border border-green-200 rounded-md">
                                    <div class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></div>
                                    <span class="text-xs font-medium text-green-700">
                                        {{ getOnlineComputersCount(lab.id) }} online
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <!-- Status Indicator -->
                                <div class="flex items-center">
                                    <div :class="{
                                        'w-2 h-2 rounded-full mr-1.5': true,
                                        'bg-emerald-500': lab.status === 'active',
                                        'bg-gray-400': lab.status === 'inactive',
                                        'bg-yellow-500': lab.status === 'maintenance'
                                    }"></div>
                                    <span class="text-xs font-medium text-gray-700 capitalize">
                                        {{ lab.status }}
                                    </span>
                                </div>
                                
                                <!-- Occupied Badge -->
                                <div v-if="hasOnlineComputers(lab.id)" class="flex items-center gap-1.5 px-2 py-1 bg-orange-50 border border-orange-200 rounded-md">
                                    <svg class="w-3 h-3 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-xs font-semibold text-orange-700 uppercase tracking-wide">
                                        Occupied
                                    </span>
                                </div>
                            </div>

                            <Button
                                @click.stop="openPopulateModal(lab.id)"
                                class="text-xs px-2.5 py-1.5 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition-colors font-medium"
                            >
                                Assign PCs
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-16 bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="max-w-md mx-auto px-4">
                        <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <h3 class="text-base font-medium text-gray-900 mb-1">No laboratories found</h3>
                        <p class="text-sm text-gray-500 mb-4">Get started by creating your first laboratory.</p>
                        <Button
                            @click="openAddModal"
                            class="inline-flex items-center gap-2 px-3 py-2 bg-gray-700 text-white text-xs font-medium rounded-lg hover:bg-gray-600 transition-colors"
                        >
                            <PlusIcon class="h-4 w-4" />
                            Add Laboratory
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Computer Assignment Modal -->
            <Modal :show="populateModal" @close="populateModal = false">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-6xl mx-auto relative overflow-hidden border border-gray-200">
                    <!-- Modal Header -->
                    <div class="px-4 py-3 border-b border-gray-100 bg-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900">Manage Laboratory Computers</h2>
                                <p class="text-gray-600 text-xs mt-0.5">
                                    {{ assignMode === 'assign' ? 'Select unassigned computers to add to this laboratory' : 'Select assigned computers to remove from this laboratory' }}
                                </p>
                            </div>
                            
                            <!-- Mode Toggle -->
                            <div class="flex gap-1 bg-gray-100 p-1 rounded-lg">
                                <button
                                    @click="switchToAssignMode"
                                    :class="[
                                        'px-3 py-1.5 text-xs font-medium rounded-md transition-all',
                                        assignMode === 'assign' 
                                            ? 'bg-white text-gray-900 shadow-sm' 
                                            : 'text-gray-600 hover:text-gray-900'
                                    ]"
                                >
                                    Assign Mode
                                </button>
                                <button
                                    @click="switchToUnassignMode"
                                    :class="[
                                        'px-3 py-1.5 text-xs font-medium rounded-md transition-all',
                                        assignMode === 'unassign' 
                                            ? 'bg-white text-gray-900 shadow-sm' 
                                            : 'text-gray-600 hover:text-gray-900'
                                    ]"
                                >
                                    Unassign Mode
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Content -->
                    <div class="px-4 py-4">
                        <!-- Assign Mode Content -->
                        <div v-if="assignMode === 'assign'">
                            <div v-if="unassignedComputers.length > 0" class="max-h-96 overflow-y-auto rounded-lg border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <input
                                                    type="checkbox"
                                                    @change="toggleAllComputers($event)"
                                                    class="h-3.5 w-3.5 text-gray-600 focus:ring-gray-300 border-gray-300 rounded"
                                                />
                                            </th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Computer #
                                            </th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                IP Address
                                            </th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                MAC Address
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="computer in unassignedComputers" :key="computer.id" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <input
                                                    type="checkbox"
                                                    :checked="selectedComputers.includes(computer.id)"
                                                    @change="toggleComputerSelection(computer.id)"
                                                    class="h-3.5 w-3.5 text-gray-600 focus:ring-gray-300 border-gray-300 rounded"
                                                />
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-xs font-medium text-gray-900">{{ computer.computer_number }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-xs text-gray-600 font-mono">{{ computer.ip_address }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-xs text-gray-600 font-mono">{{ computer.mac_address || 'N/A' }}</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div v-else class="text-center py-12 bg-gray-50 rounded-lg border border-gray-200">
                                <svg class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <h3 class="text-xs font-medium text-gray-900 mb-1">No unassigned computers</h3>
                                <p class="text-xs text-gray-500">All computers are currently assigned to laboratories.</p>
                            </div>
                        </div>

                        <!-- Unassign Mode Content -->
                        <div v-else>
                            <!-- Search Filter -->
                            <div class="mb-3">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </div>
                                    <input
                                        v-model="modalSearchQuery"
                                        type="text"
                                        placeholder="Search computers..."
                                        class="block w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-200 focus:border-gray-400 text-sm transition-colors bg-white"
                                    />
                                </div>
                            </div>

                            <div v-if="filteredAssignedComputers.length > 0" class="max-h-96 overflow-y-auto rounded-lg border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <input
                                                    type="checkbox"
                                                    @change="toggleAllForUnassign($event)"
                                                    :checked="selectedComputersForUnassign.length === filteredAssignedComputers.length && filteredAssignedComputers.length > 0"
                                                    class="h-3.5 w-3.5 text-gray-600 focus:ring-gray-300 border-gray-300 rounded"
                                                />
                                            </th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Computer #
                                            </th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                IP Address
                                            </th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                MAC Address
                                            </th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="computer in filteredAssignedComputers" :key="computer.id" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <input
                                                    type="checkbox"
                                                    :checked="selectedComputersForUnassign.includes(computer.id)"
                                                    @change="toggleComputerForUnassign(computer.id)"
                                                    class="h-3.5 w-3.5 text-gray-600 focus:ring-gray-300 border-gray-300 rounded"
                                                />
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-xs font-medium text-gray-900">{{ computer.computer_number }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-xs text-gray-600 font-mono">{{ computer.ip_address }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-xs text-gray-600 font-mono">{{ computer.mac_address || 'N/A' }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span :class="[
                                                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                                                    computer.status === 'active' ? 'bg-green-100 text-green-800' :
                                                    computer.status === 'inactive' ? 'bg-gray-100 text-gray-800' :
                                                    'bg-yellow-100 text-yellow-800'
                                                ]">
                                                    {{ computer.status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div v-else class="text-center py-12 bg-gray-50 rounded-lg border border-gray-200">
                                <svg class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <h3 class="text-xs font-medium text-gray-900 mb-1">No assigned computers found</h3>
                                <p class="text-xs text-gray-500">This laboratory has no computers assigned{{ modalSearchQuery ? ' matching your search' : '' }}.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                        <div v-if="assignMode === 'assign' && selectedComputers.length > 0" class="text-xs text-gray-600">
                            {{ selectedComputers.length }} computer{{ selectedComputers.length !== 1 ? 's' : '' }} selected
                        </div>
                        <div v-else-if="assignMode === 'unassign' && selectedComputersForUnassign.length > 0" class="text-xs text-gray-600">
                            {{ selectedComputersForUnassign.length }} computer{{ selectedComputersForUnassign.length !== 1 ? 's' : '' }} selected
                        </div>
                        <div v-else></div>
                        
                        <div class="flex gap-2">
                            <Button
                                @click="populateModal = false"
                                class="px-3 py-2 text-xs text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                Close
                            </Button>
                            <Button
                                v-if="assignMode === 'assign'"
                                @click="assignComputers"
                                :disabled="selectedComputers.length === 0"
                                class="px-4 py-2 text-xs bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Assign Selected ({{ selectedComputers.length }})
                            </Button>
                            <Button
                                v-else
                                @click="unassignComputers"
                                :disabled="selectedComputersForUnassign.length === 0"
                                class="px-4 py-2 text-xs bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Unassign Selected ({{ selectedComputersForUnassign.length }})
                            </Button>
                        </div>
                    </div>
                </div>
            </Modal>

            <!-- Generate Usage Report Modal -->
             <Modal :show="reportModal" @close="reportModal = false">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-6xl mx-auto relative border border-gray-200 max-h-[90vh] flex flex-col">
                    <!-- Header -->
                    <div class="px-4 py-3 border-b border-gray-100 bg-white rounded-t-lg flex items-center justify-between shrink-0">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">
                                Laboratory Usage Report
                            </h2>
                            <p class="text-xs text-gray-500 mt-0.5">View and analyze laboratory usage statistics</p>
                        </div>
                        <div class="flex gap-2">
                            <button
                                @click="fetchUsageReport"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                <ArrowPathIcon class="w-3.5 h-3.5" />
                                Refresh
                            </button>
                            <button
                                @click="printReport"
                                :disabled="!reportData"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-green-800 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <PrinterIcon class="w-3.5 h-3.5" />
                                Print Report
                            </button>
                        </div>
                    </div>

                    <!-- Content - Scrollable -->
                    <div class="px-4 py-4 overflow-y-auto flex-1">
                        <!-- Filters -->
                        <div class="bg-gray-50 rounded-lg border border-gray-200 p-3 mb-4">
                            <div class="flex items-center gap-2 mb-2">
                                <FunnelIcon class="w-3.5 h-3.5 text-gray-500" />
                                <span class="text-xs font-medium text-gray-700">Filters</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <!-- Laboratory Select -->
                                <div class="flex flex-col gap-1">
                                    <label class="text-xs font-medium text-gray-500">Laboratory</label>
                                    <select
                                        v-model="reportFilters.laboratory_id"
                                        class="w-44 px-2.5 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
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
                                        v-model="reportFilters.filter_type"
                                        class="w-28 px-2.5 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option value="day">Day</option>
                                        <option value="month">Month</option>
                                        <option value="year">Year</option>
                                    </select>
                                </div>

                                <!-- Date (for day filter) -->
                                <div v-if="reportFilters.filter_type === 'day'" class="flex flex-col gap-1">
                                    <label class="text-xs font-medium text-gray-500">Date</label>
                                    <input
                                        v-model="reportFilters.date"
                                        type="date"
                                        class="w-36 px-2.5 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    />
                                </div>

                                <!-- Month (for month filter) -->
                                <div v-if="reportFilters.filter_type === 'month'" class="flex flex-col gap-1">
                                    <label class="text-xs font-medium text-gray-500">Month</label>
                                    <select
                                        v-model="reportFilters.month"
                                        class="w-28 px-2.5 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option v-for="m in months" :key="m.value" :value="m.value">
                                            {{ m.label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Year (for month and year filter) -->
                                <div v-if="reportFilters.filter_type !== 'day'" class="flex flex-col gap-1">
                                    <label class="text-xs font-medium text-gray-500">Year</label>
                                    <select
                                        v-model="reportFilters.year"
                                        class="w-24 px-2.5 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    >
                                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Loading State -->
                        <div v-if="reportLoading" class="flex items-center justify-center py-12">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-800"></div>
                            <span class="ml-3 text-sm text-gray-500">Loading report...</span>
                        </div>

                        <!-- Report Content -->
                        <div v-else-if="reportData">
                            <!-- Summary Cards -->
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                                <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-3">
                                    <div class="flex items-center gap-2">
                                        <div class="p-1.5 bg-green-600 rounded-lg">
                                            <ChartBarIcon class="w-4 h-4 text-white" />
                                        </div>
                                        <div>
                                            <p class="text-lg font-bold text-green-800">{{ reportData.summary.total_sessions }}</p>
                                            <p class="text-[10px] text-green-600">Total Sessions</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-3">
                                    <div class="flex items-center gap-2">
                                        <div class="p-1.5 bg-blue-600 rounded-lg">
                                            <UserGroupIcon class="w-4 h-4 text-white" />
                                        </div>
                                        <div>
                                            <p class="text-lg font-bold text-blue-800">{{ reportData.summary.unique_students }}</p>
                                            <p class="text-[10px] text-blue-600">Unique Students</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-lg p-3">
                                    <div class="flex items-center gap-2">
                                        <div class="p-1.5 bg-purple-600 rounded-lg">
                                            <ClockIcon class="w-4 h-4 text-white" />
                                        </div>
                                        <div>
                                            <p class="text-lg font-bold text-purple-800">{{ reportData.summary.total_uptime_hours.toFixed(1) }}h</p>
                                            <p class="text-[10px] text-purple-600">Total Uptime</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-200 rounded-lg p-3">
                                    <div class="flex items-center gap-2">
                                        <div class="p-1.5 bg-amber-600 rounded-lg">
                                            <ComputerDesktopIcon class="w-4 h-4 text-white" />
                                        </div>
                                        <div>
                                            <p class="text-lg font-bold text-amber-800">{{ reportData.summary.avg_session_duration_minutes.toFixed(1) }}m</p>
                                            <p class="text-[10px] text-amber-600">Avg Session</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Computer Status -->
                            <div class="bg-white rounded-lg border border-gray-200 p-3 mb-4">
                                <h3 class="text-xs font-semibold text-gray-800 mb-3">Computer Status Overview</h3>
                                <div class="grid grid-cols-3 lg:grid-cols-6 gap-2">
                                    <div class="text-center p-2 bg-gray-50 rounded-lg">
                                        <p class="text-base font-bold text-gray-800">{{ reportData.computer_status?.total || 0 }}</p>
                                        <p class="text-[10px] text-gray-500">Total</p>
                                    </div>
                                    <div class="text-center p-2 bg-green-50 rounded-lg">
                                        <p class="text-base font-bold text-green-600">{{ reportData.computer_status?.online || 0 }}</p>
                                        <p class="text-[10px] text-gray-500">Online</p>
                                    </div>
                                    <div class="text-center p-2 bg-red-50 rounded-lg">
                                        <p class="text-base font-bold text-red-600">{{ reportData.computer_status?.offline || 0 }}</p>
                                        <p class="text-[10px] text-gray-500">Offline</p>
                                    </div>
                                    <div class="text-center p-2 bg-blue-50 rounded-lg">
                                        <p class="text-base font-bold text-blue-600">{{ reportData.computer_status?.active || 0 }}</p>
                                        <p class="text-[10px] text-gray-500">Active</p>
                                    </div>
                                    <div class="text-center p-2 bg-gray-100 rounded-lg">
                                        <p class="text-base font-bold text-gray-600">{{ reportData.computer_status?.inactive || 0 }}</p>
                                        <p class="text-[10px] text-gray-500">Inactive</p>
                                    </div>
                                    <div class="text-center p-2 bg-yellow-50 rounded-lg">
                                        <p class="text-base font-bold text-yellow-600">{{ reportData.computer_status?.maintenance || 0 }}</p>
                                        <p class="text-[10px] text-gray-500">Maintenance</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Usage Chart -->
                            <div class="bg-white rounded-lg border border-gray-200 p-3 mb-4">
                                <h3 class="text-xs font-semibold text-gray-800 mb-3">
                                    Session Activity 
                                    <span class="text-gray-400 font-normal">
                                        ({{ reportFilters.filter_type === 'day' ? 'Hourly' : reportFilters.filter_type === 'month' ? 'Daily' : 'Monthly' }})
                                    </span>
                                </h3>
                                <div class="relative">
                                    <div v-if="reportData.usage_chart_data && reportData.usage_chart_data.length > 0" class="flex items-end gap-1 h-48 overflow-x-auto pb-6">
                                        <div 
                                            v-for="(item, index) in reportData.usage_chart_data" 
                                            :key="index"
                                            class="flex flex-col items-center min-w-[32px] flex-1"
                                        >
                                            <div class="relative w-full h-36 flex flex-col justify-end">
                                                <div 
                                                    class="w-4/5 mx-auto bg-gradient-to-t from-green-800 to-green-600 rounded-t-sm transition-all duration-300 hover:from-green-700 hover:to-green-500"
                                                    :style="{ height: `${maxChartValue > 0 ? (item.sessions / maxChartValue) * 100 : 0}%`, minHeight: item.sessions > 0 ? '4px' : '0' }"
                                                ></div>
                                            </div>
                                            <div class="text-[8px] text-gray-500 mt-1 transform -rotate-45 origin-top-left whitespace-nowrap">
                                                {{ item.label }}
                                            </div>
                                            <div class="text-[10px] font-semibold text-gray-700 mt-1">{{ item.sessions }}</div>
                                        </div>
                                    </div>
                                    <div v-else class="flex items-center justify-center h-48 text-gray-400 text-sm">
                                        No session data available for this period
                                    </div>
                                </div>
                            </div>

                            <!-- Two Column Layout -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
                                <!-- Top Used Computers -->
                                <div class="bg-white rounded-lg border border-gray-200 p-3">
                                    <h3 class="text-xs font-semibold text-gray-800 mb-3">Top Used Computers</h3>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs">
                                            <thead>
                                                <tr class="bg-gray-50">
                                                    <th class="text-left py-1.5 px-2 font-medium text-gray-600">Computer</th>
                                                    <th class="text-left py-1.5 px-2 font-medium text-gray-600">Laboratory</th>
                                                    <th class="text-center py-1.5 px-2 font-medium text-gray-600">Sessions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr 
                                                    v-for="(comp, index) in reportData.top_computers.slice(0, 5)" 
                                                    :key="comp.id"
                                                    :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                                                >
                                                    <td class="py-1.5 px-2 text-gray-800">{{ comp.computer_number }}</td>
                                                    <td class="py-1.5 px-2 text-gray-600">{{ comp.laboratory_name || 'N/A' }}</td>
                                                    <td class="py-1.5 px-2 text-center font-semibold text-green-700">{{ comp.session_count }}</td>
                                                </tr>
                                                <tr v-if="!reportData.top_computers.length">
                                                    <td colspan="3" class="py-6 text-center text-gray-400">No data available</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Sessions by Program -->
                                <div class="bg-white rounded-lg border border-gray-200 p-3">
                                    <h3 class="text-xs font-semibold text-gray-800 mb-3">Sessions by Program</h3>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs">
                                            <thead>
                                                <tr class="bg-gray-50">
                                                    <th class="text-left py-1.5 px-2 font-medium text-gray-600">Program</th>
                                                    <th class="text-center py-1.5 px-2 font-medium text-gray-600">Sessions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr 
                                                    v-for="(prog, index) in reportData.sessions_by_program" 
                                                    :key="prog.program"
                                                    :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                                                >
                                                    <td class="py-1.5 px-2 text-gray-800">{{ prog.program }}</td>
                                                    <td class="py-1.5 px-2 text-center font-semibold text-blue-700">{{ prog.count }}</td>
                                                </tr>
                                                <tr v-if="!reportData.sessions_by_program.length">
                                                    <td colspan="2" class="py-6 text-center text-gray-400">No data available</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Laboratory Summary -->
                            <div class="bg-white rounded-lg border border-gray-200 p-3">
                                <h3 class="text-xs font-semibold text-gray-800 mb-3">Laboratory Summary</h3>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="bg-green-800 text-white">
                                                <th class="text-left py-2 px-3 font-medium">Laboratory</th>
                                                <th class="text-center py-2 px-3 font-medium">Total PCs</th>
                                                <th class="text-center py-2 px-3 font-medium">Online</th>
                                                <th class="text-center py-2 px-3 font-medium">Sessions</th>
                                                <th class="text-center py-2 px-3 font-medium">Total Uptime</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr 
                                                v-for="(lab, index) in reportData.laboratory_summary" 
                                                :key="lab.id"
                                                :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                                                class="hover:bg-green-50 transition-colors"
                                            >
                                                <td class="py-2 px-3 font-medium text-gray-800">{{ lab.name }}</td>
                                                <td class="py-2 px-3 text-center text-gray-600">{{ lab.total_computers }}</td>
                                                <td class="py-2 px-3 text-center">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-800">
                                                        {{ lab.online_computers }}
                                                    </span>
                                                </td>
                                                <td class="py-2 px-3 text-center font-semibold text-green-700">{{ lab.total_sessions }}</td>
                                                <td class="py-2 px-3 text-center text-gray-600">{{ lab.total_uptime_minutes.toFixed(1) }} min</td>
                                            </tr>
                                            <tr v-if="!reportData.laboratory_summary.length">
                                                <td colspan="5" class="py-6 text-center text-gray-400">No laboratory data available</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- No Data State -->
                        <div v-else class="text-center py-12">
                            <ChartBarIcon class="w-12 h-12 mx-auto text-gray-300 mb-3" />
                            <p class="text-sm text-gray-500">No report data available</p>
                            <button 
                                @click="fetchUsageReport"
                                class="mt-3 px-3 py-1.5 text-xs text-green-700 hover:text-green-800 font-medium"
                            >
                                Try Again
                            </button>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 rounded-b-xl flex justify-end shrink-0">
                        <Button
                            @click="reportModal = false"
                            class="px-3 py-2 text-xs text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            Close
                        </Button>
                    </div>
                </div>
             </Modal>

            <!-- Add/Edit Laboratory Modal -->
            <Modal :show="isModalOpen" @close="isModalOpen = false">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-auto relative border border-gray-200">
                    <!-- Modal Header -->
                    <div class="px-4 py-3 border-b border-gray-100 bg-white">
                        <h2 class="text-base font-semibold text-gray-900">
                            {{ selectedLab ? 'Edit Laboratory' : 'Add Laboratory' }}
                        </h2>
                    </div>

                    <!-- Modal Content -->
                    <div class="px-4 py-4">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">Laboratory Name</label>
                                <input
                                    v-model="newLab.name"
                                    type="text"
                                    required
                                    placeholder="Enter laboratory name"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-gray-400 focus:ring-2 focus:ring-gray-200 bg-white transition"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">Laboratory Code</label>
                                <input
                                    v-model="newLab.code"
                                    type="text"
                                    required
                                    placeholder="LAB-001"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-gray-400 focus:ring-2 focus:ring-gray-200 bg-white transition font-mono"
                                />
                            </div>
                            
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">Description</label>
                                <textarea
                                    v-model="newLab.description"
                                    rows="3"
                                    placeholder="Enter description (optional)"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-gray-400 focus:ring-2 focus:ring-gray-200 bg-white transition resize-none"
                                ></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">Status</label>
                                <select
                                    v-model="newLab.status"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-gray-400 focus:ring-2 focus:ring-gray-200 bg-white transition"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal Footer -->
                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 rounded-b-xl flex justify-end gap-2">
                        <Button
                            type="button"
                            @click="isModalOpen = false"
                            class="px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                        >
                            Cancel
                        </Button>
                        <Button
                            @click="saveLaboratory"
                            type="submit"
                            class="px-4 py-2 text-xs font-medium text-white bg-gray-700 rounded-lg hover:bg-gray-600 transition"
                        >
                            Save
                        </Button>
                    </div>
                </div>
            </Modal>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
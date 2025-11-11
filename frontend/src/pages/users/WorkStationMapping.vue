<script setup>
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import Table from '../../components/table/Table.vue';
import Modal from '../../components/modal/Modal.vue';
import LoaderSpinner from '../../components/spinner/LoaderSpinner.vue';
import { computed, onMounted, toRefs, ref, watch } from 'vue';
import { useWorkstationStore } from '../../composable/users/students/configuration/workstationMapping';
import { useStates } from '../../composable/states';
import { useLaboratoryStore } from '../../store/laboratory/laboratory.js';
import { useProgramStore } from '../../composable/program.js';
import { useSectionStore } from '../../composable/section.js';
import { useYearLevelStore } from '../../composable/yearlevel.js';
import { 
    EyeIcon, 
    TrashIcon, 
    DocumentArrowDownIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
    ArrowPathIcon,
    PlusIcon,
    UserGroupIcon,
    ComputerDesktopIcon
} from '@heroicons/vue/24/outline';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { debounce } from 'lodash-es';
import axios from 'axios';
import { useApiUrl } from '../../api/api';
import { useToast } from '../../composable/toastification/useToast';

// Store initialization
const states = useStates();
const toast = useToast();
const station = useWorkstationStore();
const laboratory = useLaboratoryStore();
const program = useProgramStore();
const section = useSectionStore();
const yearLevel = useYearLevelStore();

// Reactive state
const {
    yearLevels,
    assignedStudents,
    isLoading,
    searchQuery,
    pagination,
    isConfirmationModalOpen,
    selectedData,
} = toRefs(states);

// Actions
const { getListAssignedStudents, unAssignStudent } = station;

// Filter state
const selectedLab = ref('');
const selectedYearLevel = ref('');
const selectedSection = ref('');
const selectedProgram = ref('');

// Bulk selection state
const selectedRows = ref(new Set());
const showBulkDeleteModal = ref(false);

// Bulk assignment modal state
const showBulkAssignModal = ref(false);
const availableStudents = ref([]);
const availableComputers = ref([]);
const selectedStudents = ref(new Set());
const selectedComputers = ref(new Set());
const assignmentMode = ref('sequential'); // 'sequential' or 'random'
const isLoadingStudents = ref(false);
const isLoadingComputers = ref(false);
const isAssigning = ref(false);

// Bulk assignment filters
const bulkStudentSearch = ref('');
const bulkComputerSearch = ref('');
const bulkLabFilter = ref('');
const bulkProgramFilter = ref('');
const bulkYearLevelFilter = ref('');
const bulkSectionFilter = ref('');
const bulkStatusFilter = ref('active');

// Methods
const deleteAssignedStudent = (student) => {
    selectedData.value = student;
    isConfirmationModalOpen.value = true;
};

const unAssignStudent_func = async () => {
    if (!selectedData.value) {
        return;
    }
    await unAssignStudent(selectedData.value.id);
    isConfirmationModalOpen.value = false;
    applyFilters(pagination.value.current_page || 1);
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
    for (const id of selectedRows.value) {
        await unAssignStudent(id);
    }
    selectedRows.value.clear();
    showBulkDeleteModal.value = false;
    applyFilters(pagination.value.current_page || 1);
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedLab.value = '';
    selectedYearLevel.value = '';
    selectedSection.value = '';
    selectedProgram.value = '';
    applyFilters(1);
};

const refreshData = () => {
    applyFilters(pagination.value.current_page || 1);
};

// Bulk assignment methods
const openBulkAssignModal = () => {
    showBulkAssignModal.value = true;
    fetchAvailableStudents();
    fetchAvailableComputers();
};

const fetchAvailableStudents = async () => {
    isLoadingStudents.value = true;
    try {
        const { api, getAuthHeader } = useApiUrl();
        
        const params = {
            status: bulkStatusFilter.value,
            program_id: bulkProgramFilter.value?.id,
            year_level_id: bulkYearLevelFilter.value?.id,
            section_id: bulkSectionFilter.value?.id,
            search: bulkStudentSearch.value,
            unassigned_only: true,
            laboratory_id: bulkLabFilter.value?.id
        };
        
        const queryString = new URLSearchParams(
            Object.entries(params).filter(([_, v]) => v)
        ).toString();
        
        const response = await axios.get(`${api}/students/available-for-assignment?${queryString}`, getAuthHeader());
        availableStudents.value = response.data.students || [];
    } catch (error) {
        console.error('Error fetching students:', error);
        availableStudents.value = [];
    } finally {
        isLoadingStudents.value = false;
    }
};

const fetchAvailableComputers = async () => {
    isLoadingComputers.value = true;
    try {
        const { api, getAuthHeader } = useApiUrl();
        
        const params = {
            laboratory_id: bulkLabFilter.value?.id,
            search: bulkComputerSearch.value,
            available_only: true
        };
        
        const queryString = new URLSearchParams(
            Object.entries(params).filter(([_, v]) => v)
        ).toString();
        
        const response = await axios.get(`${api}/computers/available-for-assignment?${queryString}`, getAuthHeader());
        availableComputers.value = response.data.computers || [];
    } catch (error) {
        console.error('Error fetching computers:', error);
        availableComputers.value = [];
    } finally {
        isLoadingComputers.value = false;
    }
};

const toggleStudentSelection = (studentId) => {
    if (selectedStudents.value.has(studentId)) {
        selectedStudents.value.delete(studentId);
    } else {
        selectedStudents.value.add(studentId);
    }
};

const toggleComputerSelection = (computerId) => {
    if (selectedComputers.value.has(computerId)) {
        selectedComputers.value.delete(computerId);
    } else {
        selectedComputers.value.add(computerId);
    }
};

const toggleAllStudents = () => {
    if (selectedStudents.value.size === filteredAvailableStudents.value.length) {
        selectedStudents.value.clear();
    } else {
        filteredAvailableStudents.value.forEach(s => selectedStudents.value.add(s.id));
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
    if (selectedStudents.value.size === 0 || selectedComputers.value.size === 0) {
        toast.error('Error', 'Please select at least one student and one computer');
        return;
    }
    
    isAssigning.value = true;
    try {
        const { api, getAuthHeader } = useApiUrl();
        
        const studentIds = Array.from(selectedStudents.value);
        const computerIds = Array.from(selectedComputers.value);
        
        // console.log('=== BULK ASSIGNMENT DEBUG ===');
        // console.log('Student IDs:', studentIds);
        // console.log('Computer IDs:', computerIds);
        // console.log('Total assignments to create:', studentIds.length * computerIds.length);
        // console.log('Assignment Mode:', assignmentMode.value);
        // console.log('Laboratory ID:', bulkLabFilter.value?.id || null);
        // console.log('============================');
        
        const response = await axios.post(`${api}/computer/bulk-assign-auto`, {
            laboratory_id: bulkLabFilter.value?.id || null,
            student_ids: studentIds,
            computer_ids: computerIds,
            mode: assignmentMode.value
        }, getAuthHeader());
        
        console.log('Backend response:', response.data);
        
        toast.success('Success', response.data.message || 'Students assigned successfully');
        
        // Clear selections but keep modal open
        selectedStudents.value.clear();
        selectedComputers.value.clear();
        
        // Refresh the available lists
        fetchAvailableStudents();
        fetchAvailableComputers();
        
        // Refresh the main table
        applyFilters(1);
    } catch (error) {
        toast.error('Error', error.response?.data?.message || 'Failed to assign students');
        console.error('Assignment error:', error);
    } finally {
        isAssigning.value = false;
    }
};

const closeBulkAssignModal = () => {
    showBulkAssignModal.value = false;
    selectedStudents.value.clear();
    selectedComputers.value.clear();
    bulkStudentSearch.value = '';
    bulkComputerSearch.value = '';
    bulkLabFilter.value = '';
    bulkProgramFilter.value = '';
    bulkYearLevelFilter.value = '';
    bulkSectionFilter.value = '';
    bulkStatusFilter.value = 'active';
    assignmentMode.value = 'sequential';
};

const filteredAvailableStudents = computed(() => {
    if (!bulkStudentSearch.value) return availableStudents.value;
    const query = bulkStudentSearch.value.toLowerCase();
    return availableStudents.value.filter(s => 
        s.student_id?.toLowerCase().includes(query) ||
        s.first_name?.toLowerCase().includes(query) ||
        s.last_name?.toLowerCase().includes(query)
    );
});

const filteredAvailableComputers = computed(() => {
    if (!bulkComputerSearch.value) return availableComputers.value;
    const query = bulkComputerSearch.value.toLowerCase();
    return availableComputers.value.filter(c => 
        c.computer_number?.toLowerCase().includes(query) ||
        c.ip_address?.toLowerCase().includes(query)
    );
});

// Debounced filter function
const applyFilters = debounce((page = 1) => {
    const filters = {
        search: searchQuery.value,
        program: selectedProgram.value?.id,
        section: selectedSection.value?.id,
        year_level: selectedYearLevel.value?.id,
        laboratory: selectedLab.value?.id
    };
    getListAssignedStudents(page, filters);
}, 300);

// Watch filters and trigger backend request
watch(searchQuery, () => {
    applyFilters(1);
});

watch(selectedProgram, () => {
    applyFilters(1);
});

watch(selectedSection, () => {
    applyFilters(1);
});

watch(selectedYearLevel, () => {
    applyFilters(1);
});

watch(selectedLab, () => {
    applyFilters(1);
});

// Computed properties
const isAllSelected = computed(() => {
    return filterData.value.length > 0 && selectedRows.value.size === filterData.value.length;
});

const filterData = computed(() => {
    if (!Array.isArray(assignedStudents.value)) {
        return [];
    }

    return assignedStudents.value.map(assigned => ({
        ...assigned,
        student_id: assigned.student?.student_id,
        first_name: assigned.student?.first_name,
        last_name: assigned.student?.last_name,
        workstation: assigned.computer?.computer_number,
        ip_address: assigned.computer?.ip_address,
        program: assigned.student?.program?.name || assigned.student?.program?.program_code,
        lab: assigned.computer?.laboratory?.name,
        year_level: assigned.student?.year_level?.name,
        section: assigned.student?.section?.name,
    }));
});
const generatePDF = async () => {
    try {
        // Show loading state
        isLoading.value = true;
        
        // Fetch ALL data with current filters (not paginated)
        const { api, getAuthHeader } = useApiUrl();
        const params = new URLSearchParams();
        
        if (searchQuery.value) params.append('search', searchQuery.value);
        if (selectedProgram.value?.id) params.append('program', selectedProgram.value.id);
        if (selectedSection.value?.id) params.append('section', selectedSection.value.id);
        if (selectedYearLevel.value?.id) params.append('year_level', selectedYearLevel.value.id);
        if (selectedLab.value?.id) params.append('laboratory', selectedLab.value.id);
        params.append('all', 'true'); // Request all data without pagination
        
        const response = await axios.get(`${api}/configurations?${params.toString()}`, getAuthHeader());
        const allData = response.data.assigned_students || [];
        
        // Transform the data
        const pdfData = allData.map(assigned => ({
            student_id: assigned.student?.student_id || 'N/A',
            first_name: assigned.student?.first_name || 'N/A',
            last_name: assigned.student?.last_name || 'N/A',
            workstation: assigned.computer?.computer_number || 'N/A',
            ip_address: assigned.computer?.ip_address || 'N/A',
            lab: assigned.computer?.laboratory?.name || 'N/A'
        }));
        
        // Create PDF
        const doc = new jsPDF({
            orientation: 'landscape',
            unit: 'mm',
            format: 'a4'
        });
        
        const pageWidth = doc.internal.pageSize.getWidth();
        let yPosition = 20;
        
        // Title
        doc.setFontSize(16);
        doc.setFont(undefined, 'bold');
        doc.text('User-Pc Binding', pageWidth / 2, yPosition, { align: 'center' });
        
        // Subheading with filter info
        yPosition += 12;
        doc.setFontSize(10);
        doc.setFont(undefined, 'normal');
        
        const selectedProgramName = selectedProgram.value ? selectedProgram.value.program_code : 'All Programs';
        const selectedLabName = selectedLab.value ? `${selectedLab.value.name} - ${selectedLab.value.code}` : 'All Labs';
        const selectedYearLevelName = selectedYearLevel.value ? selectedYearLevel.value.name : 'All Year Levels';
        const selectedSectionName = selectedSection.value ? selectedSection.value.name : 'All Sections';
        
        doc.text(`Program: ${selectedProgramName}`, pageWidth / 2, yPosition, { align: 'center' });
        yPosition += 6;
        
        doc.text(`Computer Laboratory: ${selectedLabName}`, pageWidth / 2, yPosition, { align: 'center' });
        yPosition += 6;
        
        doc.text(`Year Level: ${selectedYearLevelName}`, pageWidth / 2, yPosition, { align: 'center' });
        yPosition += 6;
        
        doc.text(`Section: ${selectedSectionName}`, pageWidth / 2, yPosition, { align: 'center' });
        yPosition += 6;
        
        // Add total records
        doc.text(`Total Records: ${pdfData.length}`, pageWidth / 2, yPosition, { align: 'center' });
        
        // Table data
        const tableData = pdfData.map(item => [
            item.student_id,
            item.workstation,
            item.ip_address,
            item.lab
        ]);
        
        // Table
        autoTable(doc, {
            head: [['Student ID', 'Workstation', 'IP Address', 'Computer Lab']],
            body: tableData,
            startY: yPosition + 10,
            theme: 'grid',
            headerStyles: {
                fillColor: [59, 130, 246],
                textColor: [255, 255, 255],
                fontStyle: 'bold',
                halign: 'center'
            },
            bodyStyles: {
                textColor: [0, 0, 0],
                halign: 'left'
            },
            alternateRowStyles: {
                fillColor: [240, 240, 240]
            },
            margin: { left: 10, right: 10 }
        });
        
        // Download PDF
        const timestamp = new Date().toISOString().split('T')[0];
        doc.save(`Assigned_Workstation_${timestamp}.pdf`);
        
        // Show success message
        toast.success('Success', `PDF generated with ${pdfData.length} record(s)`);
    } catch (error) {
        console.error('Error generating PDF:', error);
        toast.error('Error', 'Failed to generate PDF');
    } finally {
        isLoading.value = false;
    }
};

const eventListener = () => {
    if (window.Echo) {
        window.Echo.channel('config-event')
            .listen('.config-event', (e) => {
                console.log('📡 Received:', e);

                const { action, data } = e;

                if (action === 'deleted' && data?.id) {
                    let list = assignedStudents.value;
                    if (list.data) list = list.data;

                    const index = list.findIndex(a => a.id == data.id);
                    if (index !== -1) {
                        list.splice(index, 1);
                        console.log('✅ Deleted assignment:', data.id);
                    }
                }
            });
    } else {
        console.warn('⚠️ Echo is not initialized.');
    }
};

// Lifecycle
onMounted(async () => {
    eventListener();

    await Promise.all([
        laboratory.fetchLaboratories(),
        program.fetchPrograms(),
        section.getSections(),
        yearLevel.getYearLevels()
    ]);

    applyFilters(1);
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white min-h-screen relative">
            <LoaderSpinner :is-loading="isLoading" subMessage="Please wait while we fetch your data" />
            
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
                <!-- Header -->
                <div class="mb-4">
                    <h1 class="text-xl font-medium text-gray-900">Workstation Mapping</h1>
                    <p class="mt-1 text-xs text-gray-500">Manage student workstation assignments</p>
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

                        <!-- Program Filter -->
                        <select
                            v-model="selectedProgram"
                            class="w-36 px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-900 focus:ring-1 focus:ring-gray-200 focus:border-gray-400 transition-all bg-white"
                        >
                            <option value="">All Programs</option>
                            <option v-for="prog in program.programs" :key="prog.id" :value="prog">
                                {{ prog.program_code }}
                            </option>
                        </select>

                        <!-- Lab Filter -->
                        <select
                            v-model="selectedLab"
                            class="w-44 px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-900 focus:ring-1 focus:ring-gray-200 focus:border-gray-400 transition-all bg-white"
                        >
                            <option value="">All Labs</option>
                            <option v-for="lab in laboratory.laboratories" :key="lab.id" :value="lab">
                                {{ lab.name }} - {{ lab.code }}
                            </option>
                        </select>

                        <!-- Year Level Filter -->
                        <select
                            v-model="selectedYearLevel"
                            class="w-36 px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-900 focus:ring-1 focus:ring-gray-200 focus:border-gray-400 transition-all bg-white"
                        >
                            <option value="">All Year Levels</option>
                            <option v-for="year in yearLevels" :key="year.id" :value="year">
                                {{ year.name }}
                            </option>
                        </select>

                        <!-- Section Filter -->
                        <select
                            v-model="selectedSection"
                            class="w-36 px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-900 focus:ring-1 focus:ring-gray-200 focus:border-gray-400 transition-all bg-white"
                        >
                            <option value="">All Sections</option>
                            <option v-for="sec in section.sections" :key="sec.id" :value="sec">
                                {{ sec.name }}
                            </option>
                        </select>

                        <!-- Clear Filters -->
                        <button
                            v-if="searchQuery || selectedProgram || selectedLab || selectedYearLevel || selectedSection"
                            @click="clearFilters"
                            class="px-3 py-2 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors">
                        </button>
                        <!-- Clear Filters -->
                        <button
                            v-if="searchQuery || selectedProgram || selectedLab || selectedYearLevel || selectedSection"
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
                            <span class="text-xs text-gray-500 font-medium">Loading workstation assignments...</span>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="!filterData.length" class="text-center py-16">
                        <div class="inline-flex items-center justify-center w-14 h-14 bg-gray-100 rounded-full mb-3">
                            <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-medium text-gray-900 mb-1">No assignments found</h3>
                        <p class="text-xs text-gray-500">Try adjusting your search or filter criteria</p>
                    </div>

                    <!-- Table -->
                    <div v-else class="overflow-x-auto">
                        <Table
                            :data="filterData"
                            :loading="isLoading"
                            :pagination="pagination"
                            :mobile-fields="['student_id', 'first_name', 'last_name', 'workstation', 'ip_address', 'lab']"
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
                                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-600">Student ID</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-600">First Name</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-600">Last Name</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-600">Workstation</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-600">IP Address</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-600">Lab</th>
                                        <th class="px-4 py-2.5 text-center text-xs font-medium text-gray-600">Actions</th>
                                    </tr>
                                </thead>
                            </template>
                            <template #default>
                                <tr 
                                    v-for="assigned in filterData" 
                                    :key="assigned.id" 
                                    class="border-b border-gray-100 hover:bg-gray-50 transition-colors"
                                    :class="{ 'bg-blue-50': selectedRows.has(assigned.id) }"
                                >
                                    <td class="px-4 py-3">
                                        <input
                                            type="checkbox"
                                            :checked="selectedRows.has(assigned.id)"
                                            @change="toggleRowSelection(assigned.id)"
                                            class="rounded border-gray-300 text-gray-600 focus:ring-gray-200"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-900 font-medium">
                                        {{ assigned.student?.student_id || 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-900">
                                        {{ assigned.student?.first_name || 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-900">
                                        {{ assigned.student?.last_name || 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        {{ assigned.computer?.computer_number || 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600 font-mono">
                                        {{ assigned.computer?.ip_address || 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        {{ assigned.lab || 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button 
                                                @click="deleteAssignedStudent(assigned)" 
                                                class="p-1 text-red-400 hover:text-red-600 rounded transition-colors"
                                                title="Unassign"
                                            >
                                                <TrashIcon class="w-4 h-4" />
                                            </button>
                                            <button 
                                                class="p-1 text-gray-400 hover:text-gray-600 rounded transition-colors"
                                                title="View"
                                            >
                                                <EyeIcon class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </Table>
                    </div>
                </div>

                <!-- Single Delete Confirmation Modal -->
                <Modal :show="isConfirmationModalOpen" @close="isConfirmationModalOpen = false">
                    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md mx-auto">
                        <!-- Modal Header -->
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-red-50 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <h3 class="text-base font-semibold text-gray-900">Confirm Unassignment</h3>
                            </div>
                        </div>

                        <!-- Modal Content -->
                        <div class="px-6 py-4">
                            <p class="text-sm text-gray-600">
                                Are you sure you want to unassign this student from their workstation? This action cannot be undone.
                            </p>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end gap-2">
                            <button
                                @click="isConfirmationModalOpen = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                @click="unAssignStudent_func"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 transition-colors"
                            >
                                Unassign
                            </button>
                        </div>
                    </div>
                </Modal>

                <!-- Bulk Delete Confirmation Modal -->
                <Modal :show="showBulkDeleteModal" @close="showBulkDeleteModal = false">
                    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md mx-auto">
                        <!-- Modal Header -->
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-red-50 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <h3 class="text-base font-semibold text-gray-900">Confirm Bulk Unassignment</h3>
                            </div>
                        </div>

                        <!-- Modal Content -->
                        <div class="px-6 py-4">
                            <p class="text-sm text-gray-600">
                                Are you sure you want to unassign <strong class="font-semibold text-gray-900">{{ selectedRows.size }}</strong> student{{ selectedRows.size > 1 ? 's' : '' }} from their workstations? This action cannot be undone.
                            </p>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end gap-2">
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
                                Unassign All
                            </button>
                        </div>
                    </div>
                </Modal>

                <!-- Bulk Assignment Panel with Transition -->
                <Transition
                    enter-active-class="transition-opacity duration-300"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition-opacity duration-300"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-if="showBulkAssignModal" class="fixed inset-0  bg-white/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="closeBulkAssignModal">
                        <Transition
                            enter-active-class="transition-all duration-300"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition-all duration-300"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div v-if="showBulkAssignModal" class="bg-white rounded-xl shadow-2xl w-full max-w-[95vw] max-h-[90vh] relative border border-gray-200 flex flex-col overflow-hidden">
                                <!-- Modal Header -->
                                <div class="px-6 py-4 border-b border-gray-200 flex-shrink-0">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h2 class="text-lg font-semibold text-gray-900">Bulk Student Assignment</h2>
                                            <p class="text-sm text-gray-600 mt-1">Assign multiple students to multiple computers</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button
                                                v-if="selectedStudents.size > 0 || selectedComputers.size > 0"
                                                @click="() => { selectedStudents.clear(); selectedComputers.clear(); }"
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
                        <div class="flex-1 overflow-y-auto">
                        <!-- Filters Section -->
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex-shrink-0">
                            <div class="grid grid-cols-2 gap-6">
                                <!-- Student Filters -->
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 mb-3 flex items-center gap-2">
                                        <UserGroupIcon class="w-4 h-4" />
                                        Student Filters
                                    </h3>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input
                                            v-model="bulkStudentSearch"
                                            @input="fetchAvailableStudents"
                                            type="text"
                                            placeholder="Search students..."
                                            class="col-span-2 px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-gray-200 focus:border-gray-400"
                                        />
                                        <select v-model="bulkProgramFilter" @change="fetchAvailableStudents" class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-gray-200 focus:border-gray-400 bg-white">
                                            <option value="">All Programs</option>
                                            <option v-for="prog in program.programs" :key="prog.id" :value="prog">{{ prog.program_code }}</option>
                                        </select>
                                        <select v-model="bulkYearLevelFilter" @change="fetchAvailableStudents" class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-gray-200 focus:border-gray-400 bg-white">
                                            <option value="">All Year Levels</option>
                                            <option v-for="year in yearLevels" :key="year.id" :value="year">{{ year.name }}</option>
                                        </select>
                                        <select v-model="bulkSectionFilter" @change="fetchAvailableStudents" class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-gray-200 focus:border-gray-400 bg-white">
                                            <option value="">All Sections</option>
                                            <option v-for="sec in section.sections" :key="sec.id" :value="sec">{{ sec.name }}</option>
                                        </select>
                                        <select v-model="bulkStatusFilter" @change="fetchAvailableStudents" class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-gray-200 focus:border-gray-400 bg-white">
                                            <option value="">All Status</option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Computer Filters -->
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 mb-3 flex items-center gap-2">
                                        <ComputerDesktopIcon class="w-4 h-4" />
                                        Computer Filters
                                    </h3>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input
                                            v-model="bulkComputerSearch"
                                            @input="fetchAvailableComputers"
                                            type="text"
                                            placeholder="Search computers..."
                                            class="col-span-2 px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-gray-200 focus:border-gray-400"
                                        />
                                        <select v-model="bulkLabFilter" @change="() => { fetchAvailableStudents(); fetchAvailableComputers(); }" class="col-span-2 px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-gray-200 focus:border-gray-400 bg-white">
                                            <option value="">All Laboratories</option>
                                            <option v-for="lab in laboratory.laboratories" :key="lab.id" :value="lab">{{ lab.name }} - {{ lab.code }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Assignment Mode -->
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <label class="text-sm font-medium text-gray-900 mb-2 block">Assignment Mode:</label>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" v-model="assignmentMode" value="sequential" class="text-gray-600 focus:ring-gray-200" />
                                        <span class="text-sm text-gray-700">Sequential (Student 1 → PC 1, 2 → 2, etc.)</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" v-model="assignmentMode" value="random" class="text-gray-600 focus:ring-gray-200" />
                                        <span class="text-sm text-gray-700">Random Assignment</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Lists Section -->
                        <div class="flex px-6 py-4 gap-4 min-h-[600px]">
                            <!-- Students List -->
                            <div class="flex-1 flex flex-col border border-gray-200 rounded-lg overflow-hidden">
                                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            :checked="selectedStudents.size === filteredAvailableStudents.length && filteredAvailableStudents.length > 0"
                                            @change="toggleAllStudents"
                                            class="rounded border-gray-300 text-gray-600 focus:ring-gray-200"
                                        />
                                        <h3 class="text-sm font-medium text-gray-900">
                                            Available Students ({{ filteredAvailableStudents.length }})
                                        </h3>
                                    </div>
                                    <span class="text-xs text-gray-600">{{ selectedStudents.size }} selected</span>
                                </div>
                                <div class="flex-1 overflow-y-auto">
                                    <div v-if="isLoadingStudents" class="flex items-center justify-center py-8">
                                        <div class="w-6 h-6 border-2 border-gray-300 border-t-gray-600 rounded-full animate-spin"></div>
                                    </div>
                                    <div v-else-if="filteredAvailableStudents.length === 0" class="text-center py-8 text-sm text-gray-500">
                                        No students available
                                    </div>
                                    <div v-else class="divide-y divide-gray-100">
                                        <label
                                            v-for="student in filteredAvailableStudents"
                                            :key="student.id"
                                            class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors"
                                            :class="{ 'bg-blue-50': selectedStudents.has(student.id) }"
                                        >
                                            <input
                                                type="checkbox"
                                                :checked="selectedStudents.has(student.id)"
                                                @change="toggleStudentSelection(student.id)"
                                                class="rounded border-gray-300 text-gray-600 focus:ring-gray-200"
                                            />
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-gray-900 truncate">
                                                    {{ student.first_name }} {{ student.last_name }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    {{ student.student_id }} • {{ student.program?.program_code }} • {{ student.year_level?.name }}
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Computers List -->
                            <div class="flex-1 flex flex-col border border-gray-200 rounded-lg overflow-hidden">
                                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            :checked="selectedComputers.size === filteredAvailableComputers.length && filteredAvailableComputers.length > 0"
                                            @change="toggleAllComputers"
                                            class="rounded border-gray-300 text-gray-600 focus:ring-gray-200"
                                        />
                                        <h3 class="text-sm font-medium text-gray-900">
                                            Available Computers ({{ filteredAvailableComputers.length }})
                                        </h3>
                                    </div>
                                    <span class="text-xs text-gray-600">{{ selectedComputers.size }} selected</span>
                                </div>
                                <div class="flex-1 overflow-y-auto">
                                    <div v-if="isLoadingComputers" class="flex items-center justify-center py-8">
                                        <div class="w-6 h-6 border-2 border-gray-300 border-t-gray-600 rounded-full animate-spin"></div>
                                    </div>
                                    <div v-else-if="filteredAvailableComputers.length === 0" class="text-center py-8 text-sm text-gray-500">
                                        No computers available
                                    </div>
                                    <div v-else class="divide-y divide-gray-100">
                                        <label
                                            v-for="computer in filteredAvailableComputers"
                                            :key="computer.id"
                                            class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors"
                                            :class="{ 'bg-blue-50': selectedComputers.has(computer.id) }"
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
                                                    {{ computer.ip_address }} • {{ computer.laboratory?.name }}
                                                </div>
                                            </div>
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
                                    <span class="text-blue-600 font-semibold">{{ selectedStudents.size }} student(s)</span>
                                    <span class="text-gray-400">•</span>
                                    <span class="text-green-600 font-semibold">{{ selectedComputers.size }} computer(s)</span>
                                </div>
                                <div v-if="selectedStudents.size > 0 && selectedComputers.size > 0">
                                    <span class="text-purple-600 font-medium text-xs">
                                        ✓ Will create {{ selectedStudents.size * selectedComputers.size }} assignment(s)
                                        ({{ selectedStudents.size }} student(s) × {{ selectedComputers.size }} computer(s))
                                    </span>
                                </div>
                                <div v-if="bulkLabFilter" class="text-xs text-gray-500">
                                    📍 Assigning to: <span class="font-medium">{{ bulkLabFilter.name }}</span>
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
                                    :disabled="selectedStudents.size === 0 || selectedComputers.size === 0 || isAssigning"
                                    class="px-4 py-2 text-sm font-medium text-white bg-gray-700 rounded-md hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                                >
                                    <div v-if="isAssigning" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                    {{ isAssigning ? 'Assigning...' : `Create ${selectedStudents.size * selectedComputers.size} Assignment(s)` }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
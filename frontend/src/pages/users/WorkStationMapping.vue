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
    ArrowPathIcon
} from '@heroicons/vue/24/outline';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { debounce } from 'lodash-es';

// Store initialization
const states = useStates();
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
const generatePDF = () => {
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
    
    // FIXED: Display the actual selected names instead of objects
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
    
    // Table data
    const tableData = filterData.value.map(item => [
        item.student_id || '',
        item.workstation || '',
        item.ip_address || '',
        item.lab || '' // Added computer lab to PDF
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
    doc.save('Assigned_Workstation.pdf');
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
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<script setup>
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import Table from '../../components/table/Table.vue';
import { computed, onMounted, toRef, toRefs, ref } from 'vue';
import { useWorkstationStore } from '../../composable/users/students/configuration/workstationMapping';
import { useStates } from '../../composable/states';
import { LoopingRhombusesSpinner } from 'epic-spinners';
import { PencilIcon, EyeIcon, TrashIcon, DocumentArrowDownIcon } from '@heroicons/vue/24/outline';
import Modal from '../../components/modal/Modal.vue';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { useLaboratoryStore } from '../../store/laboratory/laboratory.js';
import { useProgramStore } from '../../composable/program.js';
import { useSectionStore } from '../../composable/section.js';
import { useYearLevelStore } from '../../composable/yearlevel.js';

const states = useStates();
const station = useWorkstationStore();
const laboratory = useLaboratoryStore();
const program = useProgramStore();
const section = useSectionStore();
const yearLevel = useYearLevelStore();

const {
    yearLevels,
    assignedStudents,
    isLoading,
    searchQuery,
    pagination,
    isConfirmationModalOpen,
    selectedData,
} = toRefs(states);

const { getListAssignedStudents, unAssignStudent } = station;

// New state for filters and bulk selection
const selectedLab = ref('');
const selectedYearLevel = ref('');
const selectedSection = ref('');
const selectedProgram = ref('');
const selectedRows = ref(new Set());
const showBulkDeleteModal = ref(false);

const deleteAssignedStudent = (student) => {
    selectedData.value = student;
    isConfirmationModalOpen.value = true;
}

const unAssignStudent_func = (id) => {
    if (!selectedData.value) {
        return;
    }
    unAssignStudent(selectedData.value.id);
    isConfirmationModalOpen.value = false;
}

const toggleRowSelection = (id) => {
    if (selectedRows.value.has(id)) {
        selectedRows.value.delete(id);
    } else {
        selectedRows.value.add(id);
    }
}

const toggleSelectAll = () => {
    if (selectedRows.value.size === filterData.value.length) {
        selectedRows.value.clear();
    } else {
        filterData.value.forEach(item => selectedRows.value.add(item.id));
    }
}

const isAllSelected = computed(() => {
    return filterData.value.length > 0 && selectedRows.value.size === filterData.value.length;
});

const bulkUnassign = () => {
    selectedRows.value.forEach(id => {
        unAssignStudent(id);
    });
    selectedRows.value.clear();
    showBulkDeleteModal.value = false;
}

const filterData = computed(() => {
    if (!Array.isArray(assignedStudents.value)) return [];

    const query = searchQuery.value.toLowerCase();

    return assignedStudents.value
        .filter(a => {
            const idMatch = String(a.student?.student_id ?? '').toLowerCase().includes(query);
            const nameMatch = `${a.student?.first_name ?? ''} ${a.student?.last_name ?? ''}`
                .toLowerCase()
                .includes(query);

            // FIXED: Compare IDs from the nested relationships
            const sectionMatch = !selectedSection.value || a.student?.section_id == selectedSection.value.id;
            const labMatch = !selectedLab.value || a.computer?.laboratory_id == selectedLab.value.id;
            const yearLevelMatch = !selectedYearLevel.value || a.student?.year_level_id == selectedYearLevel.value.id;
            const programMatch = !selectedProgram.value || a.student?.program_id == selectedProgram.value.id;

            return (idMatch || nameMatch) && sectionMatch && labMatch && yearLevelMatch && programMatch;
        })
        .map(assigned => ({
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

// Also update the select options to use IDs as values
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
}

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

onMounted(async() => {
    eventListener();
    getListAssignedStudents();

    await Promise.all([
        laboratory.fetchLaboratories(),
        program.fetchPrograms(),
        section.getSections(),
        yearLevel.getYearLevels()
    ]);


    console.log('Laboratories Test:', laboratory.laboratories);
    console.log('Sections Test:', section.sections);
    console.log('Programs Test:', program.programs);
    console.log('Year Levels Test:', yearLevels.value);

});
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-4 max-w-full mx-auto sm:px-4 bg-white">
            <div>
                <h2 class="text-2xl text-gray-900">Workstation Mapping</h2>
                <p class="mt-1 text-xs text-gray-600">
                    Assigned workstations to students
                </p>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 items-end mt-4 p-4 bg-gray-50 rounded-lg">
                <!-- Search Box -->
                <div class="relative">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Search</label>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search globally..."
                        class="w-64 border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                    >
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="absolute right-2 top-8 text-gray-400 hover:text-gray-600"
                    >
                        ✕
                    </button>
                </div>

                <!-- Program Filter -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Program</label>
                    <select
                        v-model="selectedProgram"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    >
                        <option value="">All Programs</option>
                        <!-- FIXED: Use object as value but compare by ID in filter -->
                        <option v-for="prog in program.programs" :key="prog.id" :value="prog">{{ prog.program_code }}</option>
                    </select>
                </div>

                <!-- Lab Filter -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Computer Lab</label>
                    <select
                        v-model="selectedLab"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    >
                        <option value="">All Labs</option>
                        <!-- FIXED: Use object as value but compare by ID in filter -->
                        <option v-for="lab in laboratory.laboratories" :key="lab.id" :value="lab">{{ lab.name }} - {{ lab.code }}</option>
                    </select>
                </div>

                <!-- Year Level Filter -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Year Level</label>
                    <select
                        v-model="selectedYearLevel"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    >
                        <option value="">All Year Levels</option>
                        <!-- FIXED: Use object as value but compare by ID in filter -->
                        <option v-for="year in yearLevels" :key="year.id" :value="year">{{ year.name }}</option>
                    </select>
                </div>

                <!-- Section Filter -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Section</label>
                    <select
                        v-model="selectedSection"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    >
                        <option value="">All Sections</option>
                        <!-- FIXED: Use object as value but compare by ID in filter -->
                        <option v-for="sec in section.sections" :key="sec.id" :value="sec">{{ sec.name }}</option>
                    </select>
                </div>

                <!-- PDF Export Button -->
                <button
                    @click="generatePDF"
                    class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition"
                >
                    <DocumentArrowDownIcon class="w-4 h-4" />
                    Export PDF
                </button>

                <!-- Bulk Unassign Button -->
                <button
                    v-if="selectedRows.size > 0"
                    @click="showBulkDeleteModal = true"
                    class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition"
                >
                    <TrashIcon class="w-4 h-4" />
                    Unassign Selected ({{ selectedRows.size }})
                </button>
            </div>

            <!-- Table Section -->
            <div class="mt-4 relative min-h-64">
                <!-- Loading Overlay -->
                <div v-if="isLoading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-80 z-10 mt-10">
                    <div class="flex flex-col items-center gap-4">
                        <img src="../../assets/LABTrackv2.png" alt="" class="h-15 w-15 object-contain" />
                        <LoopingRhombusesSpinner :animation-duration="1200" :size="100" color="black" />
                        <span class="text-gray-600 font-medium">Loading assigned students...</span>
                    </div>
                </div>

                <!-- Table -->
                <Table
                    v-if="!isLoading || filterData.length > 0"
                    :data="filterData"
                    :loading="isLoading"
                    :pagination="pagination"
                    :mobile-fields="['student_id', 'first_name', 'last_name', 'workstation', 'ip_address', 'lab']"
                    @page-change="getListAssignedStudents"
                >
                    <template #header>
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left w-8">
                                    <input
                                        type="checkbox"
                                        :checked="isAllSelected"
                                        @change="toggleSelectAll"
                                        class="rounded"
                                    >
                                </th>
                                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">Student ID</th>
                                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">First Name</th>
                                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">Last Name</th>
                                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">Workstation</th>
                                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">IP Address</th>
                                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">Computer Lab</th>
                                <th class="px-3 py-2 text-xs font-medium text-gray-600 text-center w-20">Actions</th>
                            </tr>
                        </thead>
                    </template>
                    <template #default>
                        <tr v-for="assigned in filterData" :key="assigned.id" class="hover:bg-gray-50 transition-colors" :class="{ 'bg-blue-50': selectedRows.has(assigned.id) }">
                            <td class="px-3 py-2 text-xs">
                                <input
                                    type="checkbox"
                                    :checked="selectedRows.has(assigned.id)"
                                    @change="toggleRowSelection(assigned.id)"
                                    class="rounded"
                                >
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-900">
                                {{ assigned.student?.student_id }}
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-900">
                                {{ assigned.student?.first_name }}
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-900">
                                {{ assigned.student?.last_name }}
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-900">
                                {{ assigned.computer?.computer_number }}
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-900">
                                {{ assigned.computer?.ip_address }}
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-900">
                                {{ assigned.lab }}
                            </td>
                            <td>
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="deleteAssignedStudent(assigned)" class="text-red-600 hover:text-red-800">
                                        <TrashIcon :stroke-width="1.50" class="w-3.5 h-3.5" />
                                    </button>
                                    <button>
                                        <EyeIcon :stroke-width="1.50" class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </Table>
            </div>

            <!-- Single Delete Confirmation Modal -->
            <Modal :show="isConfirmationModalOpen" @close="isConfirmationModalOpen = false">
                <div class="bg-white p-6 rounded-2xl shadow-2xl w-full max-w-md mx-auto relative">
                    <div class="flex items-center gap-3 mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M12 19c3.866 0 7-3.134 7-7S15.866 5 12 5 5 8.134 5 12s3.134 7 7 7z" />
                        </svg>
                        <h2 class="text-xl font-semibold text-gray-800">Confirm Deletion</h2>
                    </div>

                    <p class="text-gray-600 text-sm leading-relaxed">
                        Are you sure you want to unassign this user from a workstation? This action cannot be undone.
                    </p>

                    <div class="flex justify-end gap-3 mt-6">
                        <button
                            @click="isConfirmationModalOpen = false"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition"
                        >
                            Cancel
                        </button>

                        <button
                            @click="unAssignStudent_func"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                        >
                            Yes, Unassign
                        </button>
                    </div>
                </div>
            </Modal>

            <!-- Bulk Delete Confirmation Modal -->
            <Modal :show="showBulkDeleteModal" @close="showBulkDeleteModal = false">
                <div class="bg-white p-6 rounded-2xl shadow-2xl w-full max-w-md mx-auto relative">
                    <div class="flex items-center gap-3 mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M12 19c3.866 0 7-3.134 7-7S15.866 5 12 5 5 8.134 5 12s3.134 7 7 7z" />
                        </svg>
                        <h2 class="text-xl font-semibold text-gray-800">Confirm Bulk Unassign</h2>
                    </div>

                    <p class="text-gray-600 text-sm leading-relaxed">
                        Are you sure you want to unassign {{ selectedRows.size }} students from their workstations? This action cannot be undone.
                    </p>

                    <div class="flex justify-end gap-3 mt-6">
                        <button
                            @click="showBulkDeleteModal = false"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition"
                        >
                            Cancel
                        </button>

                        <button
                            @click="bulkUnassign"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                        >
                            Yes, Unassign All
                        </button>
                    </div>
                </div>
            </Modal>
        </div>
    </AuthenticatedLayout>
</template>
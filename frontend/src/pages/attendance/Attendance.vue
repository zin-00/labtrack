<script setup>
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import LoaderSpinner from '../../components/spinner/LoaderSpinner.vue';
import { ref, onMounted, computed, watch } from 'vue';
import { useAttendanceStore } from '../../composable/attendance';
import { useStates } from '../../composable/states';
import { ArrowPathIcon, ArrowUpTrayIcon, XMarkIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';
import { Document, Packer, Paragraph, Table, TableCell, TableRow, WidthType, AlignmentType } from 'docx';
import { useProgramStore } from '../../composable/program';
import { useYearLevelStore } from '../../composable/yearlevel';
import { useSectionStore } from '../../composable/section';

const pr = useProgramStore();
const yl = useYearLevelStore();
const sec = useSectionStore();
const att = useAttendanceStore();
const states = useStates();

const currentPage = ref(1);
const showFilters = ref(false);

onMounted(async () => {
    await pr.fetchPrograms();
    await yl.getYearLevels();
    await sec.getSections();
    await fetchAttendances();
});

const fetchAttendances = async () => {
    const filters = {
        search: states.searchQuery,
        program_id: states.selectedProgram,
        year_level_id: states.selectedYearLevel,
        section_id: states.selectedSection
    };
    await att.fetchAttendances(currentPage.value, filters);
};

// Watch for filter changes
watch([() => states.searchQuery, () => states.selectedProgram, () => states.selectedYearLevel, () => states.selectedSection], () => {
    currentPage.value = 1;
    fetchAttendances();
});

// Watch for date filter changes
watch(() => att.dateFilter, () => {
    currentPage.value = 1;
    fetchAttendances();
}, { deep: true });

const refreshData = () => {
    fetchAttendances();
};

const clearFilters = () => {
    states.searchQuery = '';
    states.selectedProgram = 'all';
    states.selectedYearLevel = 'all';
    states.selectedSection = 'all';
    att.clearFilters();
    currentPage.value = 1;
    fetchAttendances();
};

const exportToDocx = async () => {
    const filters = {
        search: states.searchQuery,
        program_id: states.selectedProgram,
        year_level_id: states.selectedYearLevel,
        section_id: states.selectedSection
    };
    
    const data = await att.exportAttendances(filters);
    
    if (!data || data.length === 0) {
        alert('No data to export');
        return;
    }

    // Create table headers
    const tableHeaders = new TableRow({
        children: [
            new TableCell({
                children: [new Paragraph({ text: 'Student ID', alignment: AlignmentType.CENTER, bold: true })],
                width: { size: 20, type: WidthType.PERCENTAGE }
            }),
            new TableCell({
                children: [new Paragraph({ text: 'Fullname', alignment: AlignmentType.CENTER, bold: true })],
                width: { size: 25, type: WidthType.PERCENTAGE }
            }),
            new TableCell({
                children: [new Paragraph({ text: 'Section', alignment: AlignmentType.CENTER, bold: true })],
                width: { size: 15, type: WidthType.PERCENTAGE }
            }),
            new TableCell({
                children: [new Paragraph({ text: 'Program', alignment: AlignmentType.CENTER, bold: true })],
                width: { size: 20, type: WidthType.PERCENTAGE }
            }),
            new TableCell({
                children: [new Paragraph({ text: 'Year Level', alignment: AlignmentType.CENTER, bold: true })],
                width: { size: 20, type: WidthType.PERCENTAGE }
            })
        ]
    });

    // Create table rows
    const tableRows = data.map(attendance => {
        const student = attendance.student;
        return new TableRow({
            children: [
                new TableCell({
                    children: [new Paragraph(student?.student_id || 'N/A')],
                    width: { size: 20, type: WidthType.PERCENTAGE }
                }),
                new TableCell({
                    children: [new Paragraph(`${student?.first_name || ''} ${student?.middle_name ? student.middle_name + ' ' : ''}${student?.last_name || ''}`)],
                    width: { size: 25, type: WidthType.PERCENTAGE }
                }),
                new TableCell({
                    children: [new Paragraph(student?.section?.name || 'N/A')],
                    width: { size: 15, type: WidthType.PERCENTAGE }
                }),
                new TableCell({
                    children: [new Paragraph(student?.program?.program_code || 'N/A')],
                    width: { size: 20, type: WidthType.PERCENTAGE }
                }),
                new TableCell({
                    children: [new Paragraph(student?.year_level?.name || 'N/A')],
                    width: { size: 20, type: WidthType.PERCENTAGE }
                })
            ]
        });
    });

    const table = new Table({
        rows: [tableHeaders, ...tableRows],
        width: { size: 100, type: WidthType.PERCENTAGE }
    });

    const doc = new Document({
        sections: [{
            properties: {},
            children: [
                new Paragraph({
                    text: 'Attendance Report',
                    heading: 'Heading1',
                    alignment: AlignmentType.CENTER
                }),
                new Paragraph({ text: '' }),
                table
            ]
        }]
    });

    const blob = await Packer.toBlob(doc);
    
    // Create download link
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `Attendance_Report_${new Date().toISOString().split('T')[0]}.docx`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
};

const goToPage = (page) => {
    currentPage.value = page;
    fetchAttendances();
};

const attendanceList = computed(() => att.attendances?.data || []);
const pagination = computed(() => att.attendances?.meta || {});

</script>
<template>
    <AuthenticatedLayout>
        <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white min-h-screen relative">
            <LoaderSpinner :isLoading="isLoading" subMessage="Fetching attendance data..." />
            
            <!-- Header -->
            <div class="mb-4">
                <h2 class="text-xl font-medium text-gray-900">Attendance Management</h2>
                <p class="mt-1 text-xs text-gray-500">View student attendance records when they unlock computers</p>
            </div>

            <!-- Filters and Actions -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <!-- Left: Search & Filters -->
                    <div class="flex flex-wrap gap-2 items-center">
                        <!-- Search Box -->
                        <div class="relative">
                            <input
                                v-model="states.searchQuery"
                                type="text"
                                placeholder="Search student..."
                                class="w-52 border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:border-gray-400 focus:ring-1 focus:ring-gray-200 transition"
                            />
                            <button
                                v-if="states.searchQuery"
                                @click="states.searchQuery = ''"
                                class="absolute right-2 top-2 text-gray-400 hover:text-gray-600"
                            >
                                <XMarkIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Date From -->
                        <input
                            v-model="att.dateFilter.from"
                            type="date"
                            placeholder="From"
                            class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:border-gray-400 focus:ring-1 focus:ring-gray-200 transition"
                        />

                        <!-- Date To -->
                        <input
                            v-model="att.dateFilter.to"
                            type="date"
                            placeholder="To"
                            class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:border-gray-400 focus:ring-1 focus:ring-gray-200 transition"
                        />

                        <!-- Program Filter -->
                        <select
                            v-model="states.selectedProgram"
                            class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:border-gray-400 focus:ring-1 focus:ring-gray-200 transition bg-white"
                        >
                            <option value="all">All Programs</option>
                            <option 
                                v-for="program in states.programs"
                                :key="program.id" 
                                :value="program.id"
                            >
                                {{ program.program_code }}
                            </option>
                        </select>

                        <!-- Year Level Filter -->
                        <select
                            v-model="states.selectedYearLevel"
                            class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:border-gray-400 focus:ring-1 focus:ring-gray-200 transition bg-white"
                        >
                            <option value="all">All Year Levels</option>
                            <option 
                                v-for="yearLevel in states.yearLevelsNotPaginated"
                                :key="yearLevel.id" 
                                :value="yearLevel.id"
                            >
                                {{ yearLevel.name }}
                            </option>
                        </select>

                        <!-- Section Filter -->
                        <select
                            v-model="states.selectedSection"
                            class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:border-gray-400 focus:ring-1 focus:ring-gray-200 transition bg-white"
                        >
                            <option value="all">All Sections</option>
                            <option 
                                v-for="section in states.secNotPaginated"
                                :key="section.id" 
                                :value="section.id"
                            >
                                {{ section.name }}
                            </option>
                        </select>

                        <!-- Clear Filters -->
                        <button
                            @click="clearFilters"
                            class="px-3 py-1.5 text-sm text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 rounded-md transition"
                        >
                            Clear
                        </button>
                    </div>

                    <!-- Right: Action Buttons -->
                    <div class="flex gap-2">
                        <button
                            @click="refreshData"
                            title="Refresh"
                            class="p-2 text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 rounded-md transition"
                        >
                            <ArrowPathIcon class="h-4 w-4" />
                        </button>

                        <button
                            @click="exportToDocx"
                            title="Export to DOCX"
                            class="p-2 text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 rounded-md transition"
                        >
                            <ArrowUpTrayIcon class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Student ID
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Fullname
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Section
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Program
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Year Level
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="attendanceList.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">
                                    No attendance records found
                                </td>
                            </tr>
                            <tr v-for="attendance in attendanceList" :key="attendance.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ attendance.student?.student_id || 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ attendance.student?.first_name }} 
                                    {{ attendance.student?.middle_name ? attendance.student.middle_name + ' ' : '' }}
                                    {{ attendance.student?.last_name }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ attendance.student?.section?.name || 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ attendance.student?.program?.program_code || 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ attendance.student?.year_level?.name || 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    {{ new Date(attendance.attendance_date).toLocaleDateString() }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.total > 0" class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <button
                            @click="goToPage(currentPage - 1)"
                            :disabled="currentPage === 1"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Previous
                        </button>
                        <button
                            @click="goToPage(currentPage + 1)"
                            :disabled="currentPage === pagination.last_page"
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Next
                        </button>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium">{{ pagination.from || 0 }}</span>
                                to
                                <span class="font-medium">{{ pagination.to || 0 }}</span>
                                of
                                <span class="font-medium">{{ pagination.total || 0 }}</span>
                                results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <button
                                    @click="goToPage(currentPage - 1)"
                                    :disabled="currentPage === 1"
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <ChevronLeftIcon class="h-5 w-5" />
                                </button>
                                <button
                                    v-for="page in pagination.last_page"
                                    :key="page"
                                    @click="goToPage(page)"
                                    :class="[
                                        page === currentPage
                                            ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                                    ]"
                                >
                                    {{ page }}
                                </button>
                                <button
                                    @click="goToPage(currentPage + 1)"
                                    :disabled="currentPage === pagination.last_page"
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <ChevronRightIcon class="h-5 w-5" />
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<style scoped>
</style>

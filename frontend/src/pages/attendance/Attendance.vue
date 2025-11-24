<script setup>
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import LoaderSpinner from '../../components/spinner/LoaderSpinner.vue';
import { ref, onMounted, computed, watch } from 'vue';
import { useAttendanceStore } from '../../composable/attendance';
import { useStates } from '../../composable/states';
import { ArrowPathIcon, ArrowUpTrayIcon, XMarkIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';
import { useProgramStore } from '../../composable/program';
import { useYearLevelStore } from '../../composable/yearlevel';
import { useSectionStore } from '../../composable/section';
import { useLaboratoryStore } from '../../composable/laboratory';
import { useToast } from '../../composable/toastification/useToast.js';

const pr = useProgramStore();
const yl = useYearLevelStore();
const sec = useSectionStore();
const lab = useLaboratoryStore();
const att = useAttendanceStore();
const states = useStates();

const toast = useToast();

const currentPage = ref(1);
const showFilters = ref(false);
const selectedLaboratory = ref('all');

const fetchAttendances = async () => {
    const filters = {
        search: states.searchQuery,
        program_id: states.selectedProgram,
        year_level_id: states.selectedYearLevel,
        section_id: states.selectedSection,
        laboratory_id: selectedLaboratory.value
    };
    await att.fetchAttendances(currentPage.value, filters);
};

// Watch for filter changes
watch([() => states.searchQuery, () => states.selectedProgram, () => states.selectedYearLevel, () => states.selectedSection, () => selectedLaboratory.value], () => {
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
    selectedLaboratory.value = 'all';
    att.clearFilters();
    currentPage.value = 1;
    fetchAttendances();
};

const exportToDocx = async () => {
    const filters = {
        search: states.searchQuery,
        program_id: states.selectedProgram,
        year_level_id: states.selectedYearLevel,
        section_id: states.selectedSection,
        laboratory_id: selectedLaboratory.value
    };
    
    const data = await att.exportAttendances(filters);
    
    if (!data || data.length === 0) {
        alert('No data to export');
        return;
    }

    // Get the logo image
    const logoUrl = new URL('../../assets/log-trans.png', import.meta.url).href;
    
    // Fetch and convert logo to base64
    let logoBase64 = '';
    try {
        const response = await fetch(logoUrl);
        const blob = await response.blob();
        logoBase64 = await new Promise((resolve) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result);
            reader.readAsDataURL(blob);
        });
    } catch (error) {
        console.error('Failed to load logo:', error);
    }

    // Generate HTML content with the attendance format
    const generateHTML = () => {
        // Prepare rows - ensure we have at least 45 rows
        const totalRows = Math.max(45, data.length);
        const rows = [];
        
        // Get laboratory name from selected filter or first attendance record
        let labName = 'N/A';
        if (selectedLaboratory.value !== 'all') {
            const selectedLab = lab.laboratories?.find(l => l.id == selectedLaboratory.value);
            labName = selectedLab?.name || 'N/A';
        } else if (data[0]?.student?.computer_students && data[0]?.student?.computer_students.length > 0) {
            // Get lab name from first student's computer assignment
            labName = data[0].student.computer_students[0]?.laboratory?.name || 'N/A';
        }
        
        for (let i = 0; i < totalRows; i++) {
            const attendance = data[i];
            if (attendance) {
                const student = attendance.student;
                const fullName = `${student?.first_name || ''} ${student?.middle_name ? student.middle_name + ' ' : ''}${student?.last_name || ''}`.trim();
                
                // Format section and year (e.g., "1B" for 1st year section B)
                const yearLevel = student?.year_level?.name || '';
                const section = student?.section?.name || '';
                const yearNumber = yearLevel.match(/\d+/)?.[0] || '';
                const sectionLetter = section.replace(/[^A-Za-z]/g, '') || '';
                const sectionYear = yearNumber && sectionLetter ? `${yearNumber}${sectionLetter}` : `${section} - ${yearLevel}`;
                
                // Format date as MM/DD/YYYY
                const date = new Date(attendance.attendance_date).toLocaleDateString('en-US', { 
                    month: '2-digit', 
                    day: '2-digit', 
                    year: 'numeric' 
                });
                
                // Use student ID as signature
                const signature = student?.student_id || '';
                
                rows.push(`
                    <tr>
                        <td style="border: 1px solid #000; padding: 8px 12px; text-align: center; font-size: 13px;">${i + 1}.</td>
                        <td style="border: 1px solid #000; padding: 8px 12px; font-size: 13px;">${fullName}</td>
                        <td style="border: 1px solid #000; padding: 8px 12px; text-align: center; font-size: 13px;">${sectionYear}</td>
                        <td style="border: 1px solid #000; padding: 8px 12px; text-align: center; font-size: 13px;">${date}</td>
                        <td style="border: 1px solid #000; padding: 8px 12px; text-align: center; font-size: 13px;">${signature}</td>
                        <td style="border: 1px solid #000; padding: 8px 12px; font-size: 13px;">&nbsp;</td>
                    </tr>
                `);
            } else {
                rows.push(`
                    <tr>
                        <td style="border: 1px solid #000; padding: 8px 12px; text-align: center; font-size: 13px;">${i + 1}.</td>
                        <td style="border: 1px solid #000; padding: 8px 12px; font-size: 13px;">&nbsp;</td>
                        <td style="border: 1px solid #000; padding: 8px 12px; text-align: center; font-size: 13px;">&nbsp;</td>
                        <td style="border: 1px solid #000; padding: 8px 12px; text-align: center; font-size: 13px;">&nbsp;</td>
                        <td style="border: 1px solid #000; padding: 8px 12px; font-size: 13px;">&nbsp;</td>
                        <td style="border: 1px solid #000; padding: 8px 12px; font-size: 13px;">&nbsp;</td>
                    </tr>
                `);
            }
        }

        return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Computer Laboratory Student Attendance and Usage Log Sheet</title>

    <style>
        /* ----- PRINT SETTINGS ----- */
        @page {
            size: legal portrait;
            margin: 0.35in 0.4in; /* NARROW MARGINS */
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                background-color: white;
            }
            .page-wrapper {
                box-shadow: none;
                padding: 0.35in 0.4in;
            }
        }

        /* ----- PAGE WRAPPER ----- */
        body {
            margin: 0;
            padding: 0;
            background-color: #f2f2f2;
            display: flex;
            justify-content: center;
            font-family: "Times New Roman", serif;
        }

        .page-wrapper {
            width: 8.5in;
            min-height: 13in;
            background-color: white;
            padding: 0.35in 0.4in;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        /* ----- HEADER ----- */
        .header {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 18px;
            margin-bottom: 10px;
        }

        .logo {
            width: 95px;
            height: auto;
        }

        .header-text {
            line-height: 1.15;
        }

        .school-name {
            font-size: 17px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .underline {
            border-bottom: 2px solid green;
            width: 100%;
            margin: 2px 0 4px 0;
        }

        .school-location {
            font-size: 14px;
            text-transform: uppercase;
        }

        /* ----- TITLE ----- */
        .title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.25;
            margin: 8px 0 12px 0;
        }

        /* ----- ROOM FIELD ----- */
        .room-field {
            font-size: 14px;
            margin-bottom: 10px;
        }

        .room-field span {
            display: inline-block;
            min-width: 200px;
            border-bottom: 1px solid black;
            padding: 0 5px;
        }

        /* ----- TABLE ----- */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            border: 1px solid #000;
            background: #f0f0f0;
            padding: 6px;
            font-size: 12.5px;
            text-align: center;
        }

        td {
            border: 1px solid #000;
            padding: 6px;
            font-size: 12.5px;
            height: 22px;
        }

        .col-no { width: 6%; }
        .col-name { width: 28%; }
        .col-section { width: 15%; }
        .col-datetime { width: 17%; }
        .col-signature { width: 15%; }
        .col-remarks { width: 19%; }
    </style>
</head>

<body>
    <div class="page-wrapper">

        <!-- HEADER -->
        <div class="header">
            ${logoBase64 ? `<img src="${logoBase64}" class="logo" alt="School Logo">` : ""}
            
            <div class="header-text">
                <div class="school-name">ST. FRANCIS XAVIER COLLEGE</div>
                <div class="underline"></div>
                <div class="school-location">SAN FRANCISCO • AGUSAN DEL SUR</div>
            </div>
        </div>

        <!-- TITLE -->
        <div class="title">
            Computer Laboratory Student Attendance<br>
            and Usage Log Sheet
        </div>

        <!-- ROOM FIELD -->
        <div class="room-field">
            Room: <span>${labName}</span>
        </div>

        <!-- TABLE -->
        <table>
            <thead>
                <tr>
                    <th class="col-no">No.</th>
                    <th class="col-name">Name</th>
                    <th class="col-section">Section and Year</th>
                    <th class="col-datetime">Date and Time</th>
                    <th class="col-signature">Signature</th>
                    <th class="col-remarks">Remarks</th>
                </tr>
            </thead>

            <tbody>
                ${rows.join("")}
            </tbody>
        </table>

    </div>
</body>
</html>
`;
    };

    // Open print dialog
    const html = generateHTML();
    const printWindow = window.open('', '_blank', 'width=900,height=1100');
    
    if (!printWindow) {
        alert('Please allow popups to print the attendance sheet.');
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

const goToPage = (page) => {
    currentPage.value = page;
    fetchAttendances();
};

const attendanceList = computed(() => att.attendances?.data || []);
const pagination = computed(() => att.attendances?.meta || {});

const EventListener = () => {
 if (!window.Echo) {
    console.log('Echo is not available');
    return;
  }

  window.Echo.channel('main-channel')
    .listen('.MainEvent', (e) => {
      console.log("📡 Computer update received:", e.data);
      fetchAttendances();
    });
}

onMounted(async () => {
    await pr.fetchPrograms();
    await yl.getYearLevels();
    await sec.getSections();
    await lab.fetchLaboratories();
    await fetchAttendances();
    EventListener();
});

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

                        <!-- Laboratory Filter -->
                        <select
                            v-model="selectedLaboratory"
                            class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:border-gray-400 focus:ring-1 focus:ring-gray-200 transition bg-white"
                        >
                            <option value="all">All Laboratories</option>
                            <option 
                                v-for="laboratory in lab.laboratories"
                                :key="laboratory.id" 
                                :value="laboratory.id"
                            >
                                {{ laboratory.name }}
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

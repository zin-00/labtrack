<script setup>
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import { ref, reactive, onMounted, computed } from 'vue';
import { useToast } from 'vue-toastification';
import { 
    UserIcon, 
    XIcon, 
    RefreshCcwIcon, 
    UserRoundPlusIcon, 
    FolderDownIcon, 
    FolderUpIcon,
    EyeIcon,
    EditIcon,
    TrashIcon,
    DownloadIcon,
    PrinterIcon,
    PlusIcon,
    FileTextIcon,
    CalendarIcon,
    UserCheckIcon
} from 'lucide-vue-next';

const toast = useToast();

// Navigation state
const activeTab = ref('list'); // 'list' or 'request'

// Search and filters
const searchQuery = ref('');
const statusFilter = ref('');
const selectedProgram = ref('');

// Form data for incident report
const incidentForm = reactive({
    reporterName: '',
    incidentDate: '',
    personInvolved: '',
    description: '',
    recommendation: '',
    repairRecommended: false,
    replacementRecommended: false,
    otherRecommendation: '',
    reporterSignature: '',
    reporterActualDate: '',
    immediateHeadSignature: '',
    immediateHeadName: '',
    immediateHeadActualDate: '',
    // Property office fields (for internal use)
    receivingPersonnel: '',
    actualDateReceived: '',
    actualTimeReceived: '',
    actionsTaken: '',
    notes: ''
});

// Mock data for incident reports list
const incidentReports = ref([
    {
        id: 'IR-2024-001',
        reporterName: 'John Doe',
        incidentDate: '2024-08-15',
        personInvolved: 'Jane Smith',
        description: 'Computer monitor not working properly',
        status: 'Pending',
        priority: 'Medium',
        submittedDate: '2024-08-15',
        recommendation: 'For Repair'
    },
    {
        id: 'IR-2024-002',
        reporterName: 'Maria Garcia',
        incidentDate: '2024-08-20',
        personInvolved: 'N/A',
        description: 'Air conditioning unit making unusual noise',
        status: 'In Progress',
        priority: 'High',
        submittedDate: '2024-08-20',
        recommendation: 'For Replacement'
    },
    {
        id: 'IR-2024-003',
        reporterName: 'Robert Johnson',
        incidentDate: '2024-08-25',
        personInvolved: 'Student Alex Brown',
        description: 'Broken chair in classroom 201',
        status: 'Resolved',
        priority: 'Low',
        submittedDate: '2024-08-25',
        recommendation: 'For Repair'
    }
]);

// Computed filtered reports
const filteredReports = computed(() => {
    return incidentReports.value.filter(report => {
        const matchesSearch = !searchQuery.value || 
            Object.values(report).some(value => 
                String(value).toLowerCase().includes(searchQuery.value.toLowerCase())
            );
        const matchesStatus = !statusFilter.value || report.status === statusFilter.value;
        return matchesSearch && matchesStatus;
    });
});

// Form methods
const resetForm = () => {
    Object.keys(incidentForm).forEach(key => {
        if (typeof incidentForm[key] === 'boolean') {
            incidentForm[key] = false;
        } else {
            incidentForm[key] = '';
        }
    });
};

const generateReportId = () => {
    const year = new Date().getFullYear();
    const count = incidentReports.value.length + 1;
    return `IR-${year}-${String(count).padStart(3, '0')}`;
};

const submitIncidentReport = () => {
    // Validate required fields
    const requiredFields = ['reporterName', 'incidentDate', 'description'];
    const missingFields = requiredFields.filter(field => !incidentForm[field]);
    
    if (missingFields.length > 0) {
        toast.error('Please fill in all required fields');
        return;
    }

    // Create new incident report
    const newReport = {
        id: generateReportId(),
        reporterName: incidentForm.reporterName,
        incidentDate: incidentForm.incidentDate,
        personInvolved: incidentForm.personInvolved || 'N/A',
        description: incidentForm.description,
        status: 'Pending',
        priority: 'Medium',
        submittedDate: new Date().toISOString().split('T')[0],
        recommendation: getRecommendationText()
    };

    incidentReports.value.unshift(newReport);
    toast.success('Incident report submitted successfully');
    resetForm();
    activeTab.value = 'list';
};

const getRecommendationText = () => {
    if (incidentForm.repairRecommended) return 'For Repair';
    if (incidentForm.replacementRecommended) return 'For Replacement';
    if (incidentForm.otherRecommendation) return incidentForm.otherRecommendation;
    return 'No Recommendation';
};

// PDF Generation (simplified - would need actual PDF library in real implementation)
const generatePDF = (report) => {
    // This would use a library like jsPDF in a real implementation
    const pdfContent = generatePDFContent(report);
    
    // Create blob and download
    const blob = new Blob([pdfContent], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${report.id}_incident_report.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    toast.success('Report downloaded successfully');
};

const generatePDFContent = (report) => {
    const fullReport = incidentReports.value.find(r => r.id === report.id);
    
    return `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment/Facility Incident Report Form</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 0;
            width: 8.5in; /* Standard US letter width */
            min-height: 11in; /* Standard US letter height */
            font-size: 11px;
            line-height: 1.2;
            color: #000;
        }
        
        /* Header table */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            margin-bottom: 8px;
        }
        
        .header-table td {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: middle;
        }
        
        .logo-cell {
            width: 60px;
            text-align: center;
            padding: 8px 4px;
        }
        
        .logo-placeholder {
            width: 50px;
            height: 50px;
            border: 2px solid #000;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            font-weight: bold;
        }
        
        .title-cell {
            width: 110px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            padding: 8px;
            line-height: 1.3;
        }
        
        .info-cell {
            width: auto;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-table td {
            border: 1px solid #000;
            padding: 2px 4px;
            font-size: 9px;
        }
        
        .info-label {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        
        .info-content {
            text-align: center;
        }
        
        /* Instruction text */
        .instruction {
            margin: 8px 0;
            font-size: 11px;
            text-align: justify;
            line-height: 1.3;
        }
        
        /* Main form table */
        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        
        .form-table td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
        }
        
        .field-label {
            font-weight: bold;
            width: 250px;
            background-color: #f8f8f8;
            font-size: 11px;
        }
        
        .field-input {
            min-height: 20px;
            font-size: 11px;
        }
        
        .description-cell {
            height: 80px;
        }
        
        /* Recommendation section */
        .recommendation-content {
            padding: 6px;
        }
        
        .checkbox-line {
            margin: 8px 0;
            display: flex;
            align-items: center;
        }
        
        .checkbox {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 2px solid #000;
            margin-right: 8px;
            text-align: center;
            line-height: 8px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .checkbox-text {
            margin-right: 40px;
        }
        
        .others-line {
            margin-top: 8px;
        }
        
        .others-text {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 300px;
            height: 20px;
        }
        
        /* Signature section */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        
        .signature-table td {
            border: 1px solid #000;
            padding: 6px;
            height: 40px;
            vertical-align: bottom;
            font-size: 11px;
            font-weight: bold;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            height: 25px;
            margin-top: 5px;
        }
        
        /* Property office section */
        .property-section {
            margin-top: 12px;
        }
        
        .property-divider {
            border-top: 2px solid #000;
            margin: 8px 0;
        }
        
        .property-title {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 11px;
        }
        
        .property-line {
            margin: 8px 0;
            font-size: 11px;
        }
        
        .property-underline {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 200px;
            height: 18px;
        }
        
        .property-inline {
            display: inline-block;
            margin-right: 30px;
            font-size: 11px;
        }
        
        .property-inline-underline {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 120px;
            height: 18px;
            margin-left: 5px;
        }
        
        .action-section {
            margin: 12px 0;
        }
        
        .action-title {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 11px;
        }
        
        .action-line {
            border-bottom: 1px solid #000;
            height: 20px;
            margin: 4px 0;
        }
        
        /* Footer section */
        .noted-section {
            margin: 15px 0 8px 0;
            font-weight: bold;
            font-size: 11px;
        }
        
        .footer-name {
            font-weight: bold;
            margin: 8px 0 2px 0;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .footer-title {
            margin: 0 0 15px 0;
            font-size: 11px;
        }
        
        .signature-space {
            float: right;
            margin-top: -40px;
            margin-right: 20px;
        }
        
        .signature-image {
            width: 80px;
            height: 30px;
        }
        
        .date-notation {
            font-size: 10px;
            margin-top: 5px;
        }
        
        /* Contact information */
        .contact-info {
            font-size: 10px;
            margin-top: 12px;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 8px;
        }
        
        /* Print-specific styles */
        @media print {
            body {
                width: 8.5in;
                min-height: 11in;
                font-size: 10px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .header-table {
                page-break-inside: avoid;
            }
            
            .form-table {
                page-break-inside: avoid;
            }
            
            .signature-table {
                page-break-inside: avoid;
            }
            
            .property-section {
                page-break-inside: avoid;
            }
            
            /* Ensure no page breaks in critical sections */
            .logo-placeholder,
            .title-cell,
            .instruction,
            .property-title,
            .noted-section,
            .footer-name,
            .footer-title {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <!-- Header section with logo and document info -->
    <table class="header-table">
        <tr>
            <td rowspan="4" class="logo-cell">
                <div class="logo-placeholder">LOGO</div>
            </td>
            <td rowspan="4" class="title-cell">
                EQUIPMENT/FACILITY INCIDENT<br>
                REPORT FORM
            </td>
            <td class="info-cell">
                <table class="info-table">
                    <tr>
                        <td class="info-label">VERSION NO.</td>
                        <td class="info-content">2</td>
                        <td class="info-label">DOCUMENT NO.</td>
                        <td class="info-content"></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="info-cell">
                <table class="info-table">
                    <tr>
                        <td class="info-label">DOCUMENT NAME</td>
                        <td class="info-content" colspan="3">EQUIPMENT/FACILITY INCIDENT REPORT FORM</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="info-cell">
                <table class="info-table">
                    <tr>
                        <td class="info-label">REVISION NO.</td>
                        <td class="info-content">2</td>
                        <td class="info-label">EFFECTIVITY DATE</td>
                        <td class="info-content">SEPTEMBER 20, 2023</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="info-cell">
                <table class="info-table">
                    <tr>
                        <td class="info-label">PAGE NO.</td>
                        <td class="info-content" colspan="3">Page 1 of 1</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Instruction text -->
    <p class="instruction">
        Please report any incident or issues with the school's equipment/facility. This form shall be sent to the Property Office for review and appropriate action.
    </p>

    <!-- Main form fields -->
    <table class="form-table">
        <tr>
            <td class="field-label">Name of the person filling out this form:</td>
            <td class="field-input"></td>
        </tr>
        <tr>
            <td class="field-label">Date of incident:</td>
            <td class="field-input"></td>
        </tr>
        <tr>
            <td class="field-label">Name of the person the incident/issue occurred (if applicable):</td>
            <td class="field-input"></td>
        </tr>
        <tr>
            <td class="field-label">Please describe the situation with as much detail as possible:</td>
            <td class="field-input description-cell"></td>
        </tr>
        <tr>
            <td class="field-label">Recommendation(s):</td>
            <td class="field-input">
                <div class="recommendation-content">
                    <div class="checkbox-line">
                        <span class="checkbox">□</span>
                        <span class="checkbox-text">For Repair</span>
                        <span class="checkbox">□</span>
                        <span class="checkbox-text">For Replacement</span>
                    </div>
                    <div class="others-line">
                        Others, please specify: <span class="others-text"></span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Signature section -->
    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                Signature of the person filling out this form:
                <div class="signature-line"></div>
            </td>
            <td style="width: 50%;">
                Actual Date:
                <div class="signature-line"></div>
            </td>
        </tr>
        <tr>
            <td>
                Signature and Name of Immediate Head:
                <div class="signature-line"></div>
            </td>
            <td>
                Actual Date:
                <div class="signature-line"></div>
            </td>
        </tr>
    </table>

    <!-- Property office section -->
    <div class="property-section">
        <div class="property-divider"></div>
        <p class="property-title">This section is for Property Office Personnel only</p>
        
        <p class="property-line">
            Inspecting/Receiving Property Personnel Name & Signature: <span class="property-underline"></span>
        </p>
        
        <div style="margin: 12px 0;">
            <span class="property-inline">
                Actual Date Received: <span class="property-inline-underline"></span>
            </span>
            <span class="property-inline">
                Actual Time Received: <span class="property-inline-underline"></span>
            </span>
        </div>
        
        <div class="action-section">
            <p class="action-title">Action(s) Taken:</p>
            <div class="action-line"></div>
            <div class="action-line"></div>
            <div class="action-line"></div>
            <div class="action-line"></div>
        </div>
        
        <p class="noted-section">Noted by:</p>
        <p class="footer-name">IAN P. ABUZO, CPA, MAIA</p>
        <p class="footer-title">Accounting and Finance Office Head</p>
        
        <!-- Signature area with date -->
        <div class="signature-space">
            <div class="signature-image" style="border-bottom: 1px solid #000; margin-bottom: 5px;"></div>
            <div class="date-notation" style="text-align: center;">9/20/2023</div>
        </div>
    </div>

    <!-- Contact information footer -->
    <div class="contact-info">
        Barangay 5, San Francisco, Agusan del Sur, Philippines 8501 | +63 85 8390321 | +63 85 9855006
    </div>
</body>
</html>
    `;
};

const printReport = (report) => {
    const content = generatePDFContent(report);
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Incident Report - ${report.id}</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    pre { white-space: pre-wrap; font-family: inherit; }
                </style>
            </head>
            <body>
                <pre>${content}</pre>
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
};

const deleteReport = (reportId) => {
    if (confirm('Are you sure you want to delete this incident report?')) {
        const index = incidentReports.value.findIndex(r => r.id === reportId);
        if (index > -1) {
            incidentReports.value.splice(index, 1);
            toast.success('Incident report deleted successfully');
        }
    }
};

const getStatusColor = (status) => {
    switch (status) {
        case 'Pending': return 'bg-yellow-100 text-yellow-800';
        case 'In Progress': return 'bg-blue-100 text-blue-800';
        case 'Resolved': return 'bg-green-100 text-green-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getPriorityColor = (priority) => {
    switch (priority) {
        case 'High': return 'bg-red-100 text-red-800';
        case 'Medium': return 'bg-yellow-100 text-yellow-800';
        case 'Low': return 'bg-green-100 text-green-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

onMounted(() => {
    // Initialize component
});
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white">
            <!-- Header -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Incident Report Management</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Manage equipment and facility incident reports with full tracking and documentation.
                </p>
            </div>

            <!-- Sub Navigation -->
            <div class="border-b border-gray-300 mb-6">
                <nav class="-mb-px flex space-x-8">
                    <button
                        @click="activeTab = 'list'"
                        :class="[
                            'py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                            activeTab === 'list' 
                                ? 'border-blue-500 text-blue-600' 
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-400'
                        ]"
                    >
                        <FileTextIcon class="w-4 h-4 inline mr-1" />
                        IR List
                    </button>
                    <button
                        @click="activeTab = 'request'"
                        :class="[
                            'py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                            activeTab === 'request' 
                                ? 'border-blue-500 text-blue-600' 
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-400'
                        ]"
                    >
                        <PlusIcon class="w-4 h-4 inline mr-1" />
                        New Request
                    </button>
                </nav>
            </div>

            <!-- IR List Tab -->
            <div v-if="activeTab === 'list'">
                <!-- Search and Filters -->
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <!-- Left: Search & Filters -->
                    <div class="flex flex-wrap gap-2 items-center">
                        <div class="relative">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search reports..."
                                class="w-64 border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                            >
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                class="absolute right-2 top-2 text-gray-400 hover:text-gray-600"
                            >
                                <XIcon :stroke-width="1.50" class="w-4 h-4" />
                            </button>
                        </div>

                        <select
                            v-model="statusFilter"
                            class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                        >
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Resolved">Resolved</option>
                        </select>
                    </div>

                    <!-- Right: Action Buttons -->
                    <div class="flex gap-2">
                        <button
                            title="Refresh"
                            class="p-2 text-white bg-gray-800 hover:bg-gray-700 rounded-md transition duration-200"
                        >
                            <RefreshCcwIcon :stroke-width="1.50" class="h-5 w-5" />
                        </button>

                        <button
                            @click="activeTab = 'request'"
                            title="New Incident Report"
                            class="px-4 py-2 text-white bg-blue-600 hover:bg-blue-700 rounded-md transition duration-200 flex items-center gap-2"
                        >
                            <PlusIcon :stroke-width="1.50" class="h-4 w-4" />
                            <span class="text-sm font-medium">New Report</span>
                        </button>
                    </div>
                </div>

                <!-- Reports Table -->
                <div class="bg-white shadow rounded-lg border border-gray-300">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Report ID
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Reporter
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Incident Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Description
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Priority
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-300">
                                <tr v-for="report in filteredReports" :key="report.id" class="hover:bg-gray-100">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ report.id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ report.reporterName }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ report.incidentDate }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                                        {{ report.description }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="[
                                            'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                            getStatusColor(report.status)
                                        ]">
                                            {{ report.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="[
                                            'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                                            getPriorityColor(report.priority)
                                        ]">
                                            {{ report.priority }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-1">
                                            <button
                                                title="View"
                                                class="text-blue-600 hover:text-blue-900 p-1 rounded"
                                            >
                                                <EyeIcon class="h-4 w-4" />
                                            </button>
                                            <button
                                                title="Edit"
                                                class="text-green-600 hover:text-green-900 p-1 rounded"
                                            >
                                                <EditIcon class="h-4 w-4" />
                                            </button>
                                            <button
                                                @click="generatePDF(report)"
                                                title="Download PDF"
                                                class="text-purple-600 hover:text-purple-900 p-1 rounded"
                                            >
                                                <DownloadIcon class="h-4 w-4" />
                                            </button>
                                            <button
                                                @click="printReport(report)"
                                                title="Print"
                                                class="text-gray-600 hover:text-gray-900 p-1 rounded"
                                            >
                                                <PrinterIcon class="h-4 w-4" />
                                            </button>
                                            <button
                                                @click="deleteReport(report.id)"
                                                title="Delete"
                                                class="text-red-600 hover:text-red-900 p-1 rounded"
                                            >
                                                <TrashIcon class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty state -->
                    <div v-if="filteredReports.length === 0" class="text-center py-12">
                        <FileTextIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No incident reports</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ searchQuery || statusFilter ? 'No reports match your current filters.' : 'Get started by creating a new incident report.' }}
                        </p>
                        <div class="mt-6">
                            <button
                                @click="activeTab = 'request'"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                            >
                                <PlusIcon class="-ml-1 mr-2 h-4 w-4" />
                                New Incident Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- New Request Tab -->
            <div v-if="activeTab === 'request'">
                <div class="bg-white shadow rounded-lg border border-gray-300">
                    <div class="px-6 py-4 border-b border-gray-300">
                        <h3 class="text-lg font-medium text-gray-900">Equipment/Facility Incident Report Form</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Please report any incident or issues with the school's equipment/facility. This form shall be sent to the Property Office for review and appropriate action.
                        </p>
                    </div>

                    <form @submit.prevent="submitIncidentReport" class="px-6 py-6 space-y-6">
                        <!-- Reporter Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Name of the person filling out this form <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="incidentForm.reporterName"
                                    type="text"
                                    required
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                    placeholder="Enter your full name"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Date of incident <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="incidentForm.incidentDate"
                                    type="date"
                                    required
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                >
                            </div>
                        </div>

                        <!-- Person Involved -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Name of the person the incident/issue occurred (if applicable)
                            </label>
                            <input
                                v-model="incidentForm.personInvolved"
                                type="text"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                placeholder="Enter name if applicable, otherwise leave blank"
                            >
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Please describe the situation with as much detail as possible <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="incidentForm.description"
                                required
                                rows="6"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                placeholder="Provide detailed description of the incident, including time, location, equipment involved, and what happened..."
                            ></textarea>
                        </div>

                        <!-- Recommendations -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                Recommendation(s)
                            </label>
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <input
                                        v-model="incidentForm.repairRecommended"
                                        type="checkbox"
                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    >
                                    <label class="ml-3 text-sm text-gray-700">For Repair</label>
                                </div>
                                <div class="flex items-center">
                                    <input
                                        v-model="incidentForm.replacementRecommended"
                                        type="checkbox"
                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    >
                                    <label class="ml-3 text-sm text-gray-700">For Replacement</label>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-700 mb-2">Others, please specify:</label>
                                    <input
                                        v-model="incidentForm.otherRecommendation"
                                        type="text"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                        placeholder="Specify other recommendations"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Signature Section -->
                        <div class="border-t border-gray-300 pt-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Signature Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Signature of the person filling out this form
                                    </label>
                                    <input
                                        v-model="incidentForm.reporterSignature"
                                        type="text"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                        placeholder="Type your full name as digital signature"
                                    >
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Actual Date
                                    </label>
                                    <input
                                        v-model="incidentForm.reporterActualDate"
                                        type="date"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                    >
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Signature and Name of Immediate Head
                                    </label>
                                    <input
                                        v-model="incidentForm.immediateHeadSignature"
                                        type="text"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                        placeholder="Immediate head's full name"
                                    >
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Actual Date
                                    </label>
                                    <input
                                        v-model="incidentForm.immediateHeadActualDate"
                                        type="date"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Property Office Section (Optional - for administrative use) -->
                        <div class="border-t border-gray-300 pt-6">
                            <h4 class="text-md font-medium text-gray-900 mb-2">Property Office Section</h4>
                            <p class="text-sm text-gray-600 mb-4">(This section is for Property Office Personnel only - optional for initial submission)</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Receiving Personnel Name
                                    </label>
                                    <input
                                        v-model="incidentForm.receivingPersonnel"
                                        type="text"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                        placeholder="Property office personnel name"
                                    >
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Actual Date Received
                                    </label>
                                    <input
                                        v-model="incidentForm.actualDateReceived"
                                        type="date"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                    >
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Actual Time Received
                                    </label>
                                    <input
                                        v-model="incidentForm.actualTimeReceived"
                                        type="time"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                    >
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Action(s) Taken
                                </label>
                                <textarea
                                    v-model="incidentForm.actionsTaken"
                                    rows="3"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                    placeholder="Describe actions taken by property office..."
                                ></textarea>
                            </div>

                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Notes
                                </label>
                                <textarea
                                    v-model="incidentForm.notes"
                                    rows="2"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                    placeholder="Additional notes..."
                                ></textarea>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-300">
                            <button
                                type="button"
                                @click="resetForm"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition duration-200"
                            >
                                Reset Form
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'list'"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition duration-200"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="px-6 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition duration-200 flex items-center gap-2"
                            >
                                <FileTextIcon class="h-4 w-4" />
                                Submit Report
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Form Preview Section -->
                <div v-if="incidentForm.reporterName || incidentForm.description" class="mt-6 bg-gray-100 rounded-lg border border-gray-300">
                    <div class="px-6 py-4 border-b border-gray-300">
                        <h4 class="text-md font-medium text-gray-900">Live Preview</h4>
                        <p class="text-sm text-gray-600">Preview of how your report will appear</p>
                    </div>
                    <div class="px-6 py-4">
                        <div class="bg-white p-4 rounded border text-sm font-mono">
                            <div class="text-center font-bold mb-4">EQUIPMENT/FACILITY INCIDENT REPORT FORM</div>
                            
                            <div class="space-y-2">
                                <div><strong>Reporter:</strong> {{ incidentForm.reporterName || '[Not filled]' }}</div>
                                <div><strong>Date of Incident:</strong> {{ incidentForm.incidentDate || '[Not filled]' }}</div>
                                <div><strong>Person Involved:</strong> {{ incidentForm.personInvolved || 'N/A' }}</div>
                                <div class="mt-3">
                                    <strong>Description:</strong>
                                    <div class="mt-1 p-2 bg-gray-100 rounded">
                                        {{ incidentForm.description || '[Not filled]' }}
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <strong>Recommendations:</strong>
                                    <div class="mt-1">
                                        <span v-if="incidentForm.repairRecommended">☑ For Repair</span>
                                        <span v-else>☐ For Repair</span>
                                        <br>
                                        <span v-if="incidentForm.replacementRecommended">☑ For Replacement</span>
                                        <span v-else>☐ For Replacement</span>
                                        <div v-if="incidentForm.otherRecommendation" class="mt-1">
                                            <strong>Others:</strong> {{ incidentForm.otherRecommendation }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
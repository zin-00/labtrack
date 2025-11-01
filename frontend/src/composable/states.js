import { reactive, ref } from 'vue'
import { defineStore } from 'pinia'
import { useToast } from 'vue-toastification'

export const useStates = defineStore('state', () => {
    const toast = useToast();
    
    // Boolean states
    const isEditing = ref(false);
    const showPassword = ref(false);
    const yearLevelModalState = ref(false);
    const deleteModalState = ref(false);
    const isEditMode = ref(false);
    const showDropdown = ref(null);
    const isConfirmationModalOpen = ref(false);
    const modalState = ref(false);
    const showFilters = ref(false);
    const isLoading = ref(false);
    const isSubmitting = ref(false);
    const showAssignmentModal = ref(false);
    const isSuccess = ref(false);
    const saveModal = ref(false);
    const isDropdownOpen = ref(false);
    const hasError = ref(false);


    // Selection states
    const selectedSection = ref('all');
    const selectedYearLevel = ref('all');
    const selectedUser = ref(null);
    const selectedComputer = ref(null);
    const selectedProgram = ref('all');
    const selectedLab = ref('all');
    const selectedStatus = ref('all');
    const selectedAdmin = ref(null);
    const activeTable = ref('sections');
    const statusFilter = ref('all');
    const deleteType = ref('');
    const selectedPeriod = ref('month');
    const selectedCampaignFilter = ref('all');
    const selectedComputerForAssignment = ref(null);
    const selectedData = ref(null);

    // Empty States
    const errorMessage = ref('');
    const successMessage = ref('Tag accepted. Unlocking…');

    // Data arrays
    const sections = ref([]);
    const yearLevels = ref([]);
    const students = ref([]);
    const admins = ref([]);
    const programs = ref([]);
    const laboratories = ref([]);
    const computerActivity = ref([]);
    const allActivity = ref([]);
    const browserActivity = ref([]);
    const latestScan = ref([]);
    const secNotPaginated = ref([]);
    const yearLevelsNotPaginated = ref([]);
    const paginatedPrograms = ref([]);
    const heartbeat = ref([]);
    const computers = ref([]);
    const recentScans = ref([]);
    const auditLogs = ref([]);
    const assignedStudents = ref([]);
    const reports = ref([]);

    // Pagination objects
    const pagination = ref({});
    const paginationSections = ref({});
    const paginationYearLevels = ref({});
    const paginationPrograms = ref({});
    const computerLogs = ref({ data: [], meta: {} });

    // Date filters
    const dateFrom = ref('');
    const dateTo = ref('');
    const dateFilter = ref({ from: '', to: '' });

    // Form data objects
    const SectionData = reactive({
        name: '',
        description: '',
        status: '',
        errors: {}
    });

    const yearLevelData = ref({
        name: '',
        description: '',
        status: '',
        errors: {}
    });


    // Search and filter
    const searchQuery = ref('');

    // Echo channel
    let echoChannel = null;

    // Admin profile
    const user = ref({
        name: '',
        email: '',
        password: '',
        roles: '',
        status: ''
    });

    const itemToDelete = ref(null);

    // Toast notification functions
    const success = (message) => {
        toast.success(message);
    }

    const error = (message) => {
        toast.error(message);
    }

    const warning = (message) => {
        toast.warning(message);
    }

    const info = (message) => {
        toast.info(message);
    }

    const ModalState = (value) => {
        modalState.value = value;
    }

    return {
        // Boolean states
        isLoading,
        isEditing,
        showPassword,
        yearLevelModalState,
        deleteModalState,
        isEditMode,
        showDropdown,
        isConfirmationModalOpen,
        modalState,
        showFilters,
        isSubmitting,
        showAssignmentModal,
        isSuccess,
        saveModal,
        isDropdownOpen,
        hasError,


        // Selection states
        selectedSection,
        selectedYearLevel,
        selectedUser,
        selectedProgram,
        selectedLab,
        selectedStatus,
        selectedAdmin,
        activeTable,
        statusFilter,
        deleteType,
        selectedPeriod,
        selectedCampaignFilter,
        selectedComputerForAssignment,
        selectedComputer,
        selectedData,

        // Empty States
        errorMessage,
        successMessage,

        // Data arrays
        sections,
        yearLevels,
        students,
        admins,
        programs,
        laboratories,
        computerActivity,
        allActivity,
        browserActivity,
        latestScan,
        secNotPaginated,
        yearLevelsNotPaginated,
        paginatedPrograms,
        heartbeat,
        computers,
        recentScans,
        auditLogs,
        assignedStudents,
        reports,



        // Pagination objects
        pagination,
        paginationSections,
        paginationYearLevels,
        paginationPrograms,
        computerLogs,

        // Date filters
        dateFrom,
        dateTo,
        dateFilter,

        // Form data objects
        SectionData,
        yearLevelData,

        // Search and filter
        searchQuery,

        // Echo channel
        echoChannel,

        // Admin profile
        user,
        itemToDelete,

        // Toast functions
        success,
        error,
        warning,
        info,
        ModalState
    }
})
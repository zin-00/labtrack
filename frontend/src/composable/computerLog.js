import { ref } from 'vue';
import { defineStore, storeToRefs } from 'pinia';
import { useApiUrl } from '../api/api';
import axios from 'axios';
import { useToast } from './toastification/useToast';

const { api, getAuthHeader } = useApiUrl();

export const useComputerLogStore = defineStore('computerLog', () => {
    const toast = useToast();

    const latestScan = ref([]);
    const selectedStatus = ref('all');
    const programFilter = ref('');
    const yearLevelFilter = ref('');
    const sectionFilter = ref('');
    let echoChannel = null;
        
    const computerLogs = ref({
        data: [],
        meta: {}
    });
    const isLoading = ref(false);
    const showFilters = ref(false);
    const dateFilter = ref({
        from: '',
        to: '',
    });
    const fetchComputerLogs = async (page = 1) => {
        isLoading.value = true;
        try {
            const params = {
                page: page,
                per_page: 7
            };

            if (dateFilter.value.from) {
                params.from = dateFilter.value.from;
            }
            
            if (dateFilter.value.to) {
                params.to = dateFilter.value.to;
            }

            if (programFilter.value) {
                params.program_id = programFilter.value;
            }

            if (yearLevelFilter.value) {
                params.year_level_id = yearLevelFilter.value;
            }

            if (sectionFilter.value) {
                params.section_id = sectionFilter.value;
            }

            const response = await axios.get(`${api}/student-sessions`, {
                ...getAuthHeader(),
                params: params
            });
            
            computerLogs.value = response.data.computer_logs || [];
            const message = response.data.message || 'Computer logs fetched successfully';
            toast.success('Success', message);
        } catch (error) {
            computerLogs.value = { data: [], meta: {} };
            toast.error('Failed to fetch computer logs');
            console.error('Error fetching computer logs:', error);
        } finally {
            isLoading.value = false;
        }
    };

      // fetch ALL logs for export (without pagination)
    const fetchAllLogsForExport = async () => {
        try {
            const params = {};

            if (dateFilter.value.from) {
                params.from = dateFilter.value.from;
            }
            
            if (dateFilter.value.to) {
                params.to = dateFilter.value.to;
            }

            if (programFilter.value) {
                params.program_id = programFilter.value;
            }

            if (yearLevelFilter.value) {
                params.year_level_id = yearLevelFilter.value;
            }

            if (sectionFilter.value) {
                params.section_id = sectionFilter.value;
            }

            const response = await axios.get(`${api}/logs/export`, {
                ...getAuthHeader(),
                params: params
            });
            
            return response.data.logs || [];
        } catch (error) {
            toast.error('Failed to fetch logs for export');
            console.error('Error fetching logs for export:', error);
            return [];
        }
    };

    const clearFilters = () => {
        dateFilter.value.from = '';
        dateFilter.value.to = '';
        selectedStatus.value = 'all';
        programFilter.value = '';
        yearLevelFilter.value = '';
        sectionFilter.value = '';
        fetchComputerLogs(1);
    };

    const fetchRecentScans = async () => {
    try{
        const response = await axios.get(`${api}/student-sessions`, getAuthHeader());
        latestScan.value = response.data.latestScans || [];
    }catch(err) {
        toast.error('Error', 'Failed to fetch recent scans');
        console.error('Error fetching recent scans:', err);
    }
    }
    return {
        // State
        computerLogs,
        isLoading,
        showFilters,
        selectedStatus,
        dateFilter,
        latestScan,
        echoChannel,
        programFilter,
        yearLevelFilter,
        sectionFilter,
    

        // Functions
        fetchComputerLogs,
        fetchAllLogsForExport,
        clearFilters,
        fetchRecentScans
    
    };
});
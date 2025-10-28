import { ref } from 'vue';
import { defineStore, storeToRefs } from 'pinia';
import { useApiUrl } from '../api/api';
import { useToast } from 'vue-toastification';
import axios from 'axios';

const toast = useToast();
const { api, getAuthHeader } = useApiUrl();

export const useComputerLogStore = defineStore('computerLog', () => {

    const latestScan = ref([]);
    const selectedStatus = ref('all');
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

            const response = await axios.get(`${api}/logs`, {
                ...getAuthHeader(),
                params: params
            });
            
            computerLogs.value = response.data.computer_logs || [];
            console.log(computerLogs.value);
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
        fetchComputerLogs(1); // Refresh with cleared filters
    };

    const fetchRecentScans = async () => {
    try{
        const response = await axios.get(`${api}/logs`, getAuthHeader());
        latestScan.value = response.data.latestScans || [];
    }catch(err) {
        error('Failed to fetch recent scans');
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
    

        // Functions
        fetchComputerLogs,
        fetchAllLogsForExport,
        clearFilters,
        fetchRecentScans
    
    };
});
import { defineStore} from 'pinia';
import { toRefs } from 'vue';
import axios from 'axios';
import { useApiUrl } from '../../api/api';
import { useStates } from '../../composable/states';
import { useToast } from '../../composable/toastification/useToast';

const { api, getAuthHeader } = useApiUrl();

export const useReportsStore = defineStore('reports', () => {
    const states = useStates();
    const toast = useToast();
    const {
        success,
        error
    } = states;
    const {
        isLoading,
        recentScans,
        reports,
        pagination,
    } = toRefs(states);

    const fetchReports = async (page = 1, filters = {}) => {
        try {
            isLoading.value = true;
            const params = new URLSearchParams();
            params.append('page', page);
            
            if (filters.search) params.append('search', filters.search);
            if (filters.status) params.append('status', filters.status);
            if (filters.date_from) params.append('date_from', filters.date_from);
            if (filters.date_to) params.append('date_to', filters.date_to);
            const response = await axios.get(`${api}/reports?${params.toString()}`, getAuthHeader());
            reports.value = response.data.reports.data || [];
            pagination.value = {
                current_page: response.data.reports.current_page,
                last_page: response.data.reports.last_page,
                total: response.data.reports.total,
                from: response.data.reports.from,
                to: response.data.reports.to
            };
        } catch (err) {
            toast.error(err.response?.data?.message || 'Failed to fetch reports');
        } finally {
            isLoading.value = false;
        }
    };

    const submitReport = async (rfid_uid, description) => {
        try {
            isLoading.value = true;
            const response = await axios.post(`${api}/reports`, { 
                input: rfid_uid,
                description: description 
            });
            toast.success(response.data.message || 'Report submitted successfully!');
            console.log(response.data.message);
            isLoading.value = false;
        } catch (err) {
            console.error('Error submitting report:', err);
            toast.error(err.response?.data?.message || 'Error submitting report');
        } finally {
            isLoading.value = false;
        }
    
    }

    const deleteReport = async (reportId) => {
        try {
            const response = await axios.delete(`${api}/reports/${reportId}`, getAuthHeader());
            toast.success(response.data.message || 'Report deleted successfully!');
            console.log(response.data.message);
        } catch (err) {
            console.error('Error deleting report:', err);
            toast.error(err.response?.data?.message || 'Error deleting report');
        } finally {
            isLoading.value = false;
        }
    }

    return {
        submitReport,
        deleteReport,
        fetchReports,
    }
});
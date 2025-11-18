import { ref } from 'vue';
import { defineStore } from 'pinia';
import { useApiUrl } from '../api/api';
import axios from 'axios';
import { useToast } from './toastification/useToast';

const { api, getAuthHeader } = useApiUrl();

export const useAttendanceStore = defineStore('attendance', () => {
    const toast = useToast();

    const attendances = ref({
        data: [],
        meta: {}
    });
    const isLoading = ref(false);
    const dateFilter = ref({
        from: '',
        to: '',
    });

    const fetchAttendances = async (page = 1, filters = {}) => {
        isLoading.value = true;
        try {
            const params = {
                page: page,
                per_page: 10,
                ...filters
            };

            if (dateFilter.value.from) {
                params.from = dateFilter.value.from;
            }

            if (dateFilter.value.to) {
                params.to = dateFilter.value.to;
            }

            const response = await axios.get(`${api}/attendance`, {
                ...getAuthHeader(),
                params: params
            });

            attendances.value = response.data.attendances || { data: [], meta: {} };
            toast.success('Success', response.data.message || 'Attendances fetched successfully');
        } catch (error) {
            console.error('Error fetching attendances:', error);
            toast.error('Error', error.response?.data?.message || 'Failed to fetch attendances');
        } finally {
            isLoading.value = false;
        }
    };

    const exportAttendances = async (filters = {}) => {
        isLoading.value = true;
        try {
            const params = { ...filters };

            if (dateFilter.value.from) {
                params.from = dateFilter.value.from;
            }

            if (dateFilter.value.to) {
                params.to = dateFilter.value.to;
            }

            const response = await axios.get(`${api}/attendance/export`, {
                ...getAuthHeader(),
                params: params
            });

            return response.data.attendances || [];
        } catch (error) {
            console.error('Error exporting attendances:', error);
            toast.error('Error', error.response?.data?.message || 'Failed to export attendances');
            return [];
        } finally {
            isLoading.value = false;
        }
    };

    const clearFilters = () => {
        dateFilter.value = {
            from: '',
            to: ''
        };
    };

    return {
        attendances,
        isLoading,
        dateFilter,
        fetchAttendances,
        exportAttendances,
        clearFilters
    };
});

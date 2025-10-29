import { defineStore } from "pinia";
import { toRefs } from "vue";
import { useApiUrl } from "../../api/api";
import { useStates } from "../states";

export const useBrowserActivityStore = defineStore('browserActivity', () => {
    const { api, getAuthHeader } = useApiUrl();
    const states = useStates();
    const {
        browserActivity,
        pagination
        } = toRefs(states);
    const {
        error
       } = states

    const getBrowserActivity = async (page = 1, filters = {}) => {
        try {
            const params = new URLSearchParams();
            params.append('page', page);

            if (filters.search) params.append('search', filters.search);
            if (filters.dateFrom) params.append('date_from', filters.dateFrom);
            if (filters.dateTo) params.append('date_to', filters.dateTo);

            const response = await axios.get(`${api}/browser-activities?${params.toString()}`, getAuthHeader());
            browserActivity.value = response.data.activities.data || [];
            pagination.value = {
                current_page: response.data.activities.current_page,
                last_page: response.data.activities.last_page,
                per_page: response.data.activities.per_page,
                total: response.data.activities.total
            }
            console.log('Browser Activity data:', browserActivity.value);
        } catch (err) {
            console.error('Error fetching browser activity:', err);
            browserActivity.value = [];
            error(err.response.data.message || 'Failed to fetch browser activity');
        }
    }

    return {
        getBrowserActivity,
    }

});
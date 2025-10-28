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
        success,
        error
       } = states

    const getBrowserActivity = async (page = 1) => {
        try {
            const response = await axios.get(`${api}/browser-activities?page=${page}`, getAuthHeader());
            browserActivity.value = response.data.activities.data || [];
            pagination.value = {
                current_page: response.data.activities.current_page,
                last_page: response.data.activities.last_page,
                per_page: response.data.activities.per_page,
                total: response.data.activities.total
            }
            console.log('Browser Activity data:', browserActivity.value);
            success(response.data.message || 'Browser activity fetched successfully!');
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
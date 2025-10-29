import { defineStore } from "pinia";
import axios from "axios";
import { useStates } from "./states";
import { toRefs } from "vue";
import { useApiUrl } from "../api/api";

export const useYearLevelStore = defineStore("yearLevel", () => {
    const { api, getAuthHeader } = useApiUrl();
    const states = useStates();

    const { success, error } = states;
    const { 
            yearLevels,
            paginationYearLevels,
            yearLevelsNotPaginated
        } = toRefs(states);

    // Get paginated year levels
    const getYearLevels = async (page = 1, filters = {}) => {
        try {
            const params = new URLSearchParams();
            params.append('page', page);
            
            if (filters.search) params.append('search', filters.search);

            const response = await axios.get(`${api}/yearlvls?${params.toString()}`, getAuthHeader());
            
            yearLevels.value = response.data.yearLevels.data || [];
            yearLevelsNotPaginated.value = response.data.yearLevelsNotPaginated || [];

            paginationYearLevels.value = {
                current_page: response.data.yearLevels.current_page,
                last_page: response.data.yearLevels.last_page,
                per_page: response.data.yearLevels.per_page,
                total: response.data.yearLevels.total,
            };

            // success(response.data.message || "Year levels fetched");
            console.log(yearLevelsNotPaginated.value);
        } catch (err) {
            error(err.response?.data?.message || "Failed to fetch year levels");
            console.log(err.response?.data?.message);
        }
    };

    // Add year level
    const addYearLevel = async (data) => {
        try {
            const response = await axios.post(`${api}/yearlvls`, data, getAuthHeader());

            success(response.data.message);
        } catch (err) {
            error(err.response?.data?.message || "Something went wrong");
            console.log(err.response?.data?.message);
        }
    };

    // Update year level
    const updateYearLevel = async (id, data) => {
        try {
            const response = await axios.put(`${api}/yearlvls/${id}`, data, getAuthHeader());

            success(response.data.message);
        } catch (err) {
            error(err.response?.data?.message);
            console.log(err.response?.data?.message);
        }
    };

    // Delete year level
    const deleteYearLevel = async (id) => {
        try {
            const response = await axios.delete(`${api}/yearlvls/${id}`, getAuthHeader());

            success(response.data.message);
        } catch (err) {
            error(err.response?.data?.message);
            console.log(err.response?.data?.message);
        }
    };

    return {
        getYearLevels,
        addYearLevel,
        updateYearLevel,
        deleteYearLevel,
    };
});

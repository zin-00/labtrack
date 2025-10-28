import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
import { useApiUrl } from '../../api/api';
import { useToast } from 'vue-toastification';


export const useYearLvlStore = defineStore('yearlvl', () => {
    const yearLevelsNotPaginated = ref([]);

    const { api, getAuthHeader } = useApiUrl();

    const fetchYearLevels = async () => {
        try {
            const { data } = await axios.get(`${api}/year-levels`, getAuthHeader());
            yearlvls.value = data.year_levels || [];
            console.log('Year Levels fetched:', yearlvls.value);
        } catch (error) {
            console.error('Error fetching year levels:', error);
        }
    };

    return {
        yearlvls,
        fetchYearLevels
    };
});
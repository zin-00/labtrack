import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
import { useApiUrl } from '../../api/api';

export const useLaboratoryStore = defineStore('laboratory', () => {
    const laboratories = ref([]);

    const { api, getAuthHeader } = useApiUrl();

    const fetchLaboratories= async () => {
        if(laboratories.value.length > 0){
            return;
        }

        try {
            const { data } = await axios.get(`${api}/laboratories`, getAuthHeader());
            laboratories.value = data.laboratories || [];
            console.log('Laboratories fetched:', laboratories.value);
        } catch (error) {
            console.error('Error fetching laboratories:', error);
        }
    }

    return {
        laboratories,
        fetchLaboratories
    };
});
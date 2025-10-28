import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
import { useApiUrl } from '../../api/api';
import { useToast } from 'vue-toastification';

export const useProgramStore = defineStore('program', () => {
  const programs = ref([]);
  const { api, getAuthHeader } = useApiUrl();

  const fetchPrograms = async () => {
    if (programs.value.length > 0) {
        return;
    }
    try {
      const { data } = await axios.get(`${api}/programs`, getAuthHeader());
      programs.value = data.programs || [];
      console.log('Programs fetched:', programs.value);
    } catch (error) {
      console.error('Error fetching programs:', error);
    }
  };

  return {
    programs,
    fetchPrograms
  };
});

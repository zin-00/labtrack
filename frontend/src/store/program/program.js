import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
import { useApiUrl } from '../../api/api';
import { useToast } from 'vue-toastification';

export const useProgramStore = defineStore('program', () => {
  const programs = ref([]);
  const { api, getAuthHeader } = useApiUrl();

  const paginated = ref({});

  const fetchPrograms = async () => {
    if (programs.value.length > 0) {
        return;
    }
    try {
      const { data } = await axios.get(`${api}/programs`, getAuthHeader());
      programs.value = data.programs || [];
      paginated.value = {
        total: data.meta?.total || programs.value.length,
        per_page: data.meta?.per_page || programs.value.length,
        current_page: data.meta?.current_page || 1,
        last_page: data.meta?.last_page || 1,
        from: data.meta?.from || 1,
        to: data.meta?.to || programs.value.length
      }
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

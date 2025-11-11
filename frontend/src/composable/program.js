import { defineStore } from 'pinia';
import { ref, toRefs } from 'vue';
import axios from 'axios';
import { useApiUrl } from '../api/api';
import { useToast } from './toastification/useToast';
import { useStates } from './states';

const toast = useToast();
const { api, getAuthHeader } = useApiUrl();



// Program functions
  export const useProgramStore = defineStore('program', () => {
    const states = useStates();
    const {
          programs,
          paginationPrograms,
          paginatedPrograms
        } = toRefs(states);
    const {
          success,
          error,
        } = states;


  const fetchPrograms = async (page = 1, filters = {}) => {
    try {
      const params = new URLSearchParams();
      params.append('page', page);

      if (filters.search) params.append('search', filters.search);

      const response = await axios.get(`${api}/programs?${params.toString()}`, getAuthHeader());

      // non-paginated list
      programs.value = response.data.programs || [];

      // paginated list
      const paginated = response.data.paginatedPrograms;
      paginatedPrograms.value = paginated.data || [];
      paginationPrograms.value = {
        current_page: paginated.current_page,
        last_page: paginated.last_page,
        per_page: paginated.per_page,
        total: paginated.total,
      };

      toast.success('Success', response.data.message);
    } catch (error) {
      programs.value = [];
      paginatedPrograms.value = [];
      toast.error('Error', "Failed to fetch programs");
      console.error("Error fetching programs:", error);
    }
  };


    const getProgram = async (page = 1) => {
      try {
        const response = await axios.get(`${api}/programs?page=${page}`, getAuthHeader());
        programs.value = response.data.programs.data || [];
        paginationPrograms.value = {
          current_page: response.data.programs.current_page,
          last_page: response.data.programs.last_page,
          per_page: response.data.programs.per_page,
          total: response.data.programs.total,
        }
        // success(response.data.message);
        console.log(programs.value);
        console.log(paginationPrograms.value);
      } catch (err) {
        error(err.response.data.message);
        console.error('Error fetching programs:', err);
      }
    }


    const addProgram = async (program) => {
      try {
        const response = await axios.post(`${api}/programs`, program, getAuthHeader());
        success(response.data.message);
        console.log(response.data.program);
      } catch (err) {
        error(err.response.data.message);
        console.error('Error adding program:', err);
      }
    }

    const updateProgram = async (id, program) => {
      try{
        const response = await axios.put(`${api}/programs/${id}`, program, getAuthHeader());
        success(response.data.message);
        console.log(response.data.program);
      }catch(err){
        error(err.response.data.message);
        console.error('Error updating program:', err);
      }
    }

    const deleteProgram = async (id) => {
        try{
          const response = await axios.delete(`${api}/programs/${id}`, getAuthHeader());
          success(response.data.message);
          console.log(response.data.program);
        }catch(err){
          error(err.response.data.message);
          console.error('Error deleting program:', err);
        }
      }



    return {
      programs,

      fetchPrograms,
      addProgram,
      updateProgram,
      deleteProgram,
      getProgram,

    };
  });
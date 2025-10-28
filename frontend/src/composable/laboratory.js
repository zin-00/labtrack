import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
import { useApiUrl } from '../api/api';
import { useToast } from 'vue-toastification';

const toast = useToast();
const { api, getAuthHeader } = useApiUrl();

export const useLaboratoryStore = defineStore('laboratory', () => {
  const laboratories = ref([]);
  const statusFilter = ref('all');
  const isLoading = ref(false);
  const isModalOpen = ref(false);
  const isImportModalOpen = ref(false);
  const searchQuery = ref('');
  const selectedLab = ref(null);
  const isEditMode = ref(false);
  const showDropdown = ref(null);

  const populateModal = ref(false);
  const unassignedComputers = ref([]);
  const selectedComputers = ref([]);
  const currentLabId = ref(null);

  const fetchLaboratories= async () => {
    if(laboratories.value.length > 0){
        return;
    }
    try {
      const response = await axios.get(`${api}/laboratories`, getAuthHeader());
      laboratories.value = response.data.laboratories || [];
        console.log(laboratories.value);
    
    } catch (error) {
      laboratories.value = [];
      toast.error('Failed to fetch labs');
      console.error('Error fetching labs:', error);
    }
  };

  function selectedLabFilter(lab) {
    if (selectedLab.value) {
      return lab.id === selectedLab.value.id;
    }
    return true;
  }
  const storeLaboratory = async (data) => {
    try{
        isLoading.value = true;
        const response = await axios.post(`${api}/laboratories`, data, getAuthHeader());
        laboratories.value.push(response.data.laboratory);
        toast.success('Laboratory added successfully');
        isModalOpen.value = false;
        isLoading.value = false;
    }catch(error){
        toast.error('Failed to add laboratory');
        console.error('Error storing laboratory:', error);
        isLoading.value = false;
    }
  }

  const updateLaboratory = async (id, data) => {
    try {
      const response = await axios.put(`${api}/laboratories/${id}`, data, getAuthHeader());
      const index = laboratories.value.findIndex(lab => lab.id === id);
      if (index !== -1) {
        laboratories.value[index] = response.data.laboratory;
      }
      toast.success('Laboratory updated successfully');
    } catch (error) {
      toast.error('Failed to update laboratory');
      console.error('Error updating laboratory:', error);
    }
  }

  const deleteLaboratory = async (id) => {
    try {
      await axios.delete(`${api}/laboratories/${id}`, getAuthHeader());
      laboratories.value = laboratories.value.filter(lab => lab.id !== id);
      toast.success('Laboratory deleted successfully');
    } catch (error) {
      toast.error('Failed to delete laboratory');
      console.error('Error deleting laboratory:', error);
    }
  }

  const fetchLabByname = async (name) => {
    try {
      const response = await axios.get(`${api}/laboratories/search?name=${name}`, getAuthHeader());
      laboratories.value = response.data.laboratories || [];
    } catch (error) {
      laboratories.value = [];
      toast.error('Failed to search laboratories');
      console.error('Error searching laboratories:', error);
    }
  }
  return {
    laboratories,
    isLoading,
    statusFilter,
    isModalOpen,
    isImportModalOpen,
    searchQuery,
    selectedLab,
    isEditMode,
    showDropdown,
    populateModal,
    unassignedComputers,
    selectedComputers,
    currentLabId,
    fetchLaboratories,
    storeLaboratory,
    updateLaboratory,
    deleteLaboratory,
    fetchLabByname,
    selectedLabFilter,
  
  };
});
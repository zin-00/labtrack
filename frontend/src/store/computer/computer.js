 import { reactive, ref, toRefs} from 'vue';
import { defineStore } from 'pinia';
import { useApiUrl } from '../api/api';
import { useToast } from 'vue-toastification';
import axios from 'axios';
import {useStates} from '../composable/states';

const { api, getAuthHeader } = useApiUrl();
 
 export const useComputerStore = defineStore('computer', () => {
 const states = useStates();
 const { 
          isLoading,
          computers,
          recentScans,
          modalState,
          isSubmitting,
        } = toRefs(states);
  const {
        success,
        error,
        } = states;
  const labs = ref([]);
  const errorMessage = ref("");

  const data = reactive({
    rfid_uid: '',
  });

  const fetchComputers = async () => {
    try {
      isLoading.value = true;
      const response = await axios.get(`${api}/computers?include=laboratory`, getAuthHeader());
      computers.value = response.data.computers || [];
    } catch (err) {
      computers.value = [];
      error('Failed to fetch computers');
      console.error('Error fetching computers:', err);
    } finally {
      isLoading.value = false;
    }
  };

  const fetchAllComputers = async () => {
    try {
      const response = await axios.get(`${api}/computers`, getAuthHeader());
      computers.value = response.data.computers || [];
    } catch (err) {
      computers.value = [];
      error('Failed to fetch computers');
      console.error('Error fetching computers:', err);
    }
  };


  const fetchLabs = async () => {
    try {
      const response = await axios.get(`${api}/laboratories`, getAuthHeader());
      labs.value = response.data.laboratories || [];
    } catch (err) {
      labs.value = [];
      error('Failed to fetch labs');
      console.error('Error fetching labs:', err);
    }
  };

  const fetchNoLabComputers = async () => {
    try{
        const response = await axios.get(`${api}/computers/null-lab`, getAuthHeader());
        computers.value = response.data.computers || [];
        console.log(computers.value);
    }catch(err) {
      error('Failed to fetch computers without labs');
      console.error('Error fetching computers without labs:', err);
    }
  }

  const storeComputer = async (data) => {
    try {
      const response = await axios.post(`${api}/computers`, data, getAuthHeader());
      success(response.data.message || 'Computer added successfully!');
    } catch (err) {
      error('Failed to add computer.');
      console.error('Error storing computer:', err);
    }
  };

  const updateComputer = async (id, data) => {
    try {
      await axios.put(`${api}/computers/update/${id}`, data, getAuthHeader());
      success('Computer updated successfully!');
    } catch (err) {
      error('Failed to update computer.');
      console.error('Error updating computer:', err);
    }
  };

  const assignLabToComputer = async (computerIds, labId) => {
    try {
        const response = await axios.post(`${api}/assign-laboratories`, {computer_ids: computerIds, laboratory_id: labId }, getAuthHeader());
        success(response.data.message || 'Lab assigned to computer successfully!');
        fetchNoLabComputers();
        
    } catch (err) {
      error('Failed to assign lab to computer.');
      console.error('Error assigning lab to computer:', err);
        
    }
  }

  const deleteComputer = async (id) => {
    try {
      await axios.delete(`${api}/computers/${id}`, getAuthHeader());
      success('Computer deleted successfully!');
    } catch (err) {
      error('Failed to delete computer.');
      console.error('Error deleting computer:', err);
    }
  };

  const unlockComputer = async (id, rfid_uid) => {
    try {
      const response = await axios.put(
        `${api}/computer/state/${id}`,
        { rfid_uid },
        getAuthHeader()
      );
      
      success(response.data.message || 'Computer unlocked successfully!');
      console.log(response.data.message);
      await fetchComputers();
      return true;
    } catch(err) {
      error(error.response?.data?.message || 'Failed to unlock computer.');
      console.error(err);
      return false;
    }
  }

const unlockByAdmin = async (id) => {
  try {
    isLoading.value = true;
    const response = await axios.patch(`${api}/admin/unlock/${id}`,{}, getAuthHeader());
    success(response.data.message)
  } catch (err) {
    console.log(err);
    error(err.response?.data?.message)
  }finally{
    isLoading.value = false;
  }
}

const unlockAssignedComputer = async (rfid_uid) => {
  try {
    isSubmitting.value = true;
    errorMessage.value = "";
    
    const response = await axios.post(
      `${api}/computer-unlock`, 
      { rfid_uid },
      getAuthHeader()
    );
    
    console.log('Full response:', response);
    console.log('Message:', response.data.message);
    console.log('Computers:', response.data.computers);
    console.log('Student:', response.data.student);
    
    success(response.data.message);
    
    // Add to recent scans with proper data
    if (response.data.computers && response.data.computers.length > 0) {
      response.data.computers.forEach(computer => {
        recentScans.value.unshift({
          id: computer.log_id || Date.now(),
          name: response.data.student?.name || "Unknown Student", 
          computerNumber: `PC-${computer.computer_number}`,
          ipAddress: computer.ip_address,
          timestamp: new Date().toLocaleString()
        });
      });
    }
    
  } catch (err) {
    console.error('Error details:', err);
    console.error('Response data:', err.response?.data);
    
    errorMessage.value = err.response?.data?.message || "An error occurred";
    error(err.response?.data?.message || "An error occurred");
  } finally {
    isSubmitting.value = false;
  }
}

const handleComputerEvent = (payload) => {
  const { action, computer } = payload;
  const index = computers.value.findIndex(c => c.id === computer.id);

  if (action === "update" && index !== -1) {
    computers.value[index] = computer;
  }

  if (action === "add" && index === -1) {
    computers.value.push(computer);
  }

  if (action === "delete" && index !== -1) {
    computers.value.splice(index, 1);
  }
};

const unlockComputersByLab = async (labId, rfid_uid) => {
  try {
    const response = await axios.post(
      `${api}/unlock-computers-by-lab/${labId}/${rfid_uid}`, 
      {},
      getAuthHeader()
    );
    success(response.data.message || 'Computers unlocked successfully!');
    console.log(response.data.message);
  } catch (err) {
    error(err.response?.data?.message || 'Failed to unlock computers.');
    console.error('Error unlocking computers by lab:', err);
  }
}


  return {
    // State
    computers,
    labs,
    isLoading,
    modalState,
    data,
    recentScans,
    isSubmitting,
    errorMessage,



    // Functions
    fetchComputers,
    fetchNoLabComputers,
    fetchLabs,
    storeComputer,
    updateComputer,
    deleteComputer,
    unlockComputer,
    unlockAssignedComputer,
    assignLabToComputer,
    handleComputerEvent,
    unlockComputersByLab,
    unlockByAdmin,
    fetchAllComputers
  };
});

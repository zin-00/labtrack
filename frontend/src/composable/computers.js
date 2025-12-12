 import { reactive, ref, toRefs} from 'vue';
import { defineStore } from 'pinia';
import { useApiUrl } from '../api/api';
import { useToast } from './toastification/useToast';
import axios from 'axios';
import {useStates} from '../composable/states';

const { api, getAuthHeader } = useApiUrl();

 export const useComputerStore = defineStore('computer', () => {
    const toast = useToast(); // Move inside store
 const states = useStates();
 const {
          isLoading,
          computers,
          recentScans,
          modalState,
          isSubmitting,
        } = toRefs(states);
  const labs = ref([]);
  const errorMessage = ref("");

  const data = reactive({
    rfid_uid: '',
  });

  const fetchComputers = async (filters = {}) => {
    try {
      isLoading.value = true;

      // Build query parameters
      const params = new URLSearchParams();
      if (filters.search) {
        params.append('search', filters.search);
      }
      if (filters.laboratory_id && filters.laboratory_id !== 'all') {
        params.append('laboratory_id', filters.laboratory_id);
      }
      if (filters.status && filters.status !== 'all') {
        params.append('status', filters.status);
      }

      const queryString = params.toString();
      const url = queryString ? `${api}/computers?include=laboratory&${queryString}` : `${api}/computers?include=laboratory`;

      const response = await axios.get(url, getAuthHeader());
      computers.value = response.data.computers || [];
    } catch (err) {
      computers.value = [];
      toast.error('Error', 'Failed to fetch computers');
      console.error('Error fetching computers:', err);
    } finally {
      isLoading.value = false;
    }
  };
    const fetchAllComputers = async () => {
    try {
      const response = await axios.get(`${api}/computers?include=laboratory`, getAuthHeader());
      computers.value = response.data.computers || [];
    } catch (err) {
      computers.value = [];
      toast.error('Error', 'Failed to fetch computers');
      console.error('Error fetching computers:', err);
    }
  };

  const fetchLabs = async () => {
    try {
      isLoading.value = true;
      const response = await axios.get(`${api}/laboratories`, getAuthHeader());
      labs.value = response.data.laboratories || [];
    } catch (err) {
      labs.value = [];
      toast.error('Error', 'Failed to fetch labs');
      console.error('Error fetching labs:', err);
    } finally {
      isLoading.value = false;
    }
  };

  const fetchNoLabComputers = async () => {
    try{
        const response = await axios.get(`${api}/computers/null-lab`, getAuthHeader());
        computers.value = response.data.computers || [];
        console.log(computers.value);
    }catch(err) {
      toast.error('Error', 'Failed to fetch computers without labs');
      console.error('Error fetching computers without labs:', err);
    }
  }

  const storeComputer = async (data) => {
    try {
      const response = await axios.post(`${api}/computers`, data, getAuthHeader());
      toast.success('Success', response.data.message || 'Computer added successfully!');
    } catch (err) {
      toast.error('Error', 'Failed to add computer.');
      console.error('Error storing computer:', err);
    }
  };

  const updateComputer = async (id, data) => {
    try {
      await axios.put(`${api}/computers/update/${id}`, data, getAuthHeader());
      toast.success('Success', 'Computer updated successfully!');
    } catch (err) {
      toast.error('Error', 'Failed to update computer.');
      console.error('Error updating computer:', err);
    }
  };

  const assignLabToComputer = async (computerIds, labId) => {
    try {
        const response = await axios.post(`${api}/assign-laboratories`, {computer_ids: computerIds, laboratory_id: labId }, getAuthHeader());
        success(response.data.message || 'Lab assigned to computer successfully!');
        fetchNoLabComputers();
        return true;
    } catch (err) {
      toast.error('Error', 'Failed to assign lab to computer.');
      console.error('Error assigning lab to computer:', err);
      return false;
    }
  }

  const unassignLabFromComputer = async (computerIds) => {
    try {
        const response = await axios.post(`${api}/unassign-laboratories`, {computer_ids: computerIds }, getAuthHeader());
        toast.success('Success', response.data.message || 'Lab unassigned from computer successfully!');
        return true;
    } catch (err) {
      toast.error('Error', 'Failed to unassign lab from computer.');
      console.error('Error unassigning lab from computer:', err);
      return false;
    }
  }

  const deleteComputer = async (id) => {
    try {
      await axios.delete(`${api}/computers/${id}`, getAuthHeader());
      toast.success('Success', 'Computer deleted successfully!');
    } catch (err) {
      toast.error('Error', 'Failed to delete computer.');
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

      toast.success('Success', response.data.message || 'Computer unlocked successfully!');
      console.log(response.data.message);
    } catch(err) {
      toast.error('Error', err.response?.data?.message || 'Failed to unlock computer.');
      console.error(err);
      return false;
    }
  }

const unlockByAdmin = async (id) => {
  try {
    isLoading.value = true;
    const response = await axios.patch(`${api}/admin/unlock/${id}`,{}, getAuthHeader());
    toast.success('Success', response.data.message);
  } catch (err) {
    console.log(err);
    toast.error('Error', err.response?.data?.message);
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

    toast.success('Success', response.data.message);

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
    toast.error('Error', err.response?.data?.message || "An error occurred");
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
    toast.success('Success', response.data.message || 'Computers unlocked successfully!');
    console.log(response.data.message);
  } catch (err) {
    toast.error('Error', err.response?.data?.message || 'Failed to unlock computers.');
    console.error('Error unlocking computers by lab:', err);
  }
}

const lockComputers = async (macAddresses) => {
  try {
    isLoading.value = true;
    const response = await axios.post(
      `${api}/computers/lock`,
      { mac_addresses: macAddresses },
      getAuthHeader()
    );
    toast.success('Success', response.data.message || 'Computers locked successfully!');
    return response.data.computers || [];
  } catch (err) {
    toast.error('Error', err.response?.data?.message || 'Failed to lock computers.');
    console.error('Error locking computers:', err);
    return [];
  } finally {
    isLoading.value = false;
  }
}

const unlockComputersBulk = async (macAddresses) => {
  try {
    isLoading.value = true;
    const response = await axios.post(
      `${api}/computers/unlock-bulk`,
      { mac_addresses: macAddresses },
      getAuthHeader()
    );
    toast.success('Success', response.data.message || 'Computers unlocked successfully!');
    return response.data.computers || [];
  } catch (err) {
    toast.error('Error', err.response?.data?.message || 'Failed to unlock computers.');
    console.error('Error unlocking computers:', err);
    return [];
  } finally {
    isLoading.value = false;
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
    unassignLabFromComputer,
    handleComputerEvent,
    unlockComputersByLab,
    unlockByAdmin,
    fetchAllComputers,
    lockComputers,
    unlockComputersBulk
  };
});

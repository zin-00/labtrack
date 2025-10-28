        import { defineStore, storeToRefs } from "pinia";
        import { useApiUrl } from "../api/api";
        import axios from "axios";
        import { useStates } from "../composable/states";
        import {ref, toRefs} from "vue";
        const {api , getAuthHeader} = useApiUrl();

        export const useAdminStore = defineStore('admin', () => {

            const states = useStates();    
            const {
                    admins,
                    isLoading,
                    modalState,
                    isConfirmationModalOpen,
                    selectedAdmin,
                    selectedStatus,
                    searchQuery,
                    isEditMode,
                    showDropdown,
                    statusFilter,
                    pagination,
                } = toRefs(states);
            
            const {
                    success,
                    error
            } = useStates();

            const storeAdmin = async (data) => {
                try {
                    const response = await axios.post(`${api}/admin/users`, data, getAuthHeader());
                    modalState.value = false;
                    if (response.data.success) {
                        admins.value.push(response.data.user);
                        success(response.data.message || 'Admin added successfully!');
                        return response.data;
                    } else {
                        throw new Error(response.data.message || 'Failed to add admin.');
                    }
                } catch(err) {
                    console.error('Error storing admin:', err);
                    error(err.response?.data?.message || 'Error storing admin');
                    throw err;
                }
            }

            const fetchAdmins = async (page = 1) => {
                try {
                    isLoading.value = true;
                    const response = await axios.get(`${api}/admin/users?page=${page}`, getAuthHeader());
                    admins.value = response.data.users.data || [];
                    pagination.value = {
                        current_page: response.data.users.current_page,
                        last_page: response.data.users.last_page,
                        per_page: response.data.users.per_page,
                        total: response.data.users.total
                    };
                    // success(response.data.message || 'Admins fetched successfully!');
                } catch(err) {
                    admins.value = [];
                    error(err.response?.data?.message || 'Failed to fetch admins');
                } finally {
                    isLoading.value = false;
                }
            }

            const updateAdmin = async (id, data) => {
                try{
                    await axios.put(`${api}/admin/users/${id}`, data, getAuthHeader());
                    success('Admin updated successfully!');
                    await fetchAdmins(pagination.value.currentPage);
                }catch(err){
                    error('Failed to update admin.');
                    console.error('Error updating admin:', err);
                }
            }

            const deleteAdmin = async (id) => {
                try{
                    await axios.delete(`${api}/admin/users/${id}`, getAuthHeader());
                    success('Admin deleted successfully!');
                    await fetchAdmins(pagination.value.currentPage);
                }catch(err){
                    error('Failed to delete admin.');
                    console.error('Error deleting admin:', err);
                }
            }
            return{

                // State
                admins,
                isLoading,
                modalState,
                isConfirmationModalOpen,
                selectedStatus,
                searchQuery,
                selectedAdmin,
                isEditMode,
                showDropdown,
                statusFilter,
                pagination,

                // Functions
                storeAdmin,
                fetchAdmins,
                updateAdmin,
                deleteAdmin,
            
            }


        });
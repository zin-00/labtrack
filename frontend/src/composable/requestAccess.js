import {ref} from 'vue';
import {defineStore} from 'pinia';
import axios from 'axios';
import {useApiUrl} from '../api/api';
import {useToast} from 'vue-toastification';

const toast = useToast();
const {api, getAuthHeader} = useApiUrl();

export const useRequestAccessStore = defineStore('requestAccess', () => {

    const requests = ref([]);
    const isLoading = ref(false);
    const selectedStatus = ref('all');
    const searchQuery = ref('');
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 7,
        total: 0
    });

    const fetchRequests = async (page = 1) => {

       try{
        isLoading.value = true;

        const response = await axios.get(`${api}/request-access?page=${page}`, getAuthHeader());
        requests.value = response.data.requestAccess.data || [];
        pagination.value = {
            current_page: response.data.requestAccess.current_page,
            last_page: response.data.requestAccess.last_page,
            per_page: response.data.requestAccess.per_page,
            total: response.data.requestAccess.total
        };

            console.log('Requests data:', requests.value);
            console.log('Pagination data:', pagination.value);

       }catch(error){
        requests.value = [];
        toast.error('Failed to fetch requests');
        console.error('Error fetching requests:', error);
       }finally{
        isLoading.value = false;
       }

    };

    const approveRequest = async (id) => {

        isLoading.value = true;

        try {
            const response = await axios.patch(`${api}/request-access/${id}/approve`, {} ,getAuthHeader());
            toast.success(response.data.message || 'Request approved successfully}');
            console.log(response.data.message);
            await fetchRequests();
        } catch (error) {
            toast.error('Failed to approve request.');
            console.error('Error approving request:', error)            
        }
    }

    const rejectRequest = async (id) => {
        isLoading.value = true;

        try {
            const response = await axios.patch(`${api}/request-access/${id}/reject`, {} ,getAuthHeader());
            toast.success(response.data.message || 'Request rejected');
            console.log(response.data.message);
            await fetchRequests();
        } catch (error) {
            toast.error('Failed to reject request.');
            console.error('Error rejecting request:', error)
        }
    }
    return{

        // State
        requests,
        isLoading,
        pagination,
        selectedStatus,
        searchQuery,


        // Functions
        fetchRequests,
        approveRequest,
        rejectRequest,

    }
});
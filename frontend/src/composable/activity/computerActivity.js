import {defineStore} from "pinia";
import { useStates } from "../states";
import { useApiUrl } from "../../api/api";
import axios from "axios";
import { useToast } from "../toastification/useToast.js";

import {ref, toRefs} from "vue";

export const useComputerActivityStore = defineStore('computerActivity', () => {
    const {api, getAuthHeader} = useApiUrl();
    const toast = useToast();
    const states = useStates();
    const {
        pagination,
        computerActivity,
        allActivity
        } = toRefs(states);
    const {
        success,
        error
        } = states;
    
    const getComputerActivity = async (page = 1) => {

        try {
            const response = await axios.get(`${api}/computer-activity?page=${page}`, getAuthHeader());
            allActivity.value = response.data.activity.data || [];
            computerActivity.value = allActivity.value;
            // pagination.value = {
            //     current_page: response.data.activity.current_page,
            //     last_page: response.data.activity.last_page,
            //     per_page: response.data.activity.per_page,
            //     total: response.data.activity.total
            // }
            console.log('Computer Activity data:', computerActivity.value);
            console.log('All Activity data:', allActivity.value);
            // console.log('Pagination data:', pagination.value);
            toast.success('Success', response.data.message);
        } catch (err) {
            computerActivity.value = [];
            toast.error('Error', err.response.data.message);
            console.error('Error fetching computer activity:', err);
        }
    }

    return {
        getComputerActivity,
    }
    
});
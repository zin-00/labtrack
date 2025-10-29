import { defineStore} from "pinia";
import { useApiUrl } from "../../../../api/api";
import { useStates } from "../../../states";
import axios from "axios";
import { toRefs } from "vue";

const { api, getAuthHeader } = useApiUrl();

export const useWorkstationStore = defineStore('workstation', () => {
    const states = useStates();
    const {
        success,
        error
    } = states;
    const {
        isLoading,
        assignedStudents,
        pagination
    } = toRefs(states);

    const getListAssignedStudents = async (page = 1, filters = {}) => {
        try {
            isLoading.value = true;
            const params = new URLSearchParams();
            params.append('page', page);
            
            if (filters.search) params.append('search', filters.search);
            if (filters.program) params.append('program', filters.program);
            if (filters.section) params.append('section', filters.section);
            if (filters.year_level) params.append('year_level', filters.year_level);
            if (filters.laboratory) params.append('laboratory', filters.laboratory);

            const response = await axios.get(`${api}/configurations?${params.toString()}`, getAuthHeader());
            assignedStudents.value = response.data.assigned_students.data || [];
             pagination.value = {
                current_page: response.data.assigned_students.current_page,
                last_page: response.data.assigned_students.last_page,
                per_page: response.data.assigned_students.per_page,
                total: response.data.assigned_students.total
            }

            isLoading.value = false;
            console.log('Assigned Students:', assignedStudents.value);
            console.log('Pagination:', pagination.value);
        } catch (err) {
            console.log('Error fetching assigned students:', err);
            error('Failed to fetch assigned students');
        }finally{
            isLoading.value = false;
        }
    }

    const unAssignStudent = async (id) => {
        try {
            isLoading.value = true;
            const response = await axios.delete(`${api}/configurations/${id}`, getAuthHeader());
            success(response.data.message || 'Student unassigned successfully!');
            console.log(response.data.message);
        } catch (err) {
            console.log('Error un-assigning student:', err);
            error('Failed to un-assign student');
        }finally{
            isLoading.value = false;
        }
    }

    return {
        getListAssignedStudents,
        unAssignStudent
    }
        
});
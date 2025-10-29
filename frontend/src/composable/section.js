import { defineStore, storeToRefs } from "pinia";
import { useApiUrl } from "../api/api";
import { useStates } from "./states";
import axios from "axios";
import { toRefs } from "vue";

const {api, getAuthHeader} = useApiUrl();


export const useSectionStore = defineStore("section", () => {
    const states = useStates();
    const {
            sections,
            paginationSections,
            secNotPaginated
    } = toRefs(states);

    const {
            success,
            error
    } = states;


    const getSections = async (page = 1, filters = {}) => {
        try{
            const params = new URLSearchParams();
            params.append('page', page);

            if (filters.search) params.append('search', filters.search);

            const response = await axios.get(`${api}/sections?${params.toString()}`,
                getAuthHeader()
            );

            sections.value = response.data.sections.data || [];
            secNotPaginated.value = response.data.secNotPaginated || [];

            paginationSections.value = {
                current_page: response.data.sections.current_page,
                last_page: response.data.sections.last_page,
                per_page: response.data.sections.per_page,
                total: response.data.sections.total
            };

            // success(response.data.message || 'Success')
            console.log("Response",response.data);
            console.log("Sections",secNotPaginated.value);
        }catch(err){
            error(err.response.data.message || 'Error')
            console.log("Error",err.response.data);
        }
    }

    const addSection = async (section) => {
        try{
            const response = await axios.post(`${api}/sections`,
                section,
                getAuthHeader()
            );

            success(response.data.message || 'Success')
            console.log("Response",response.data);
        }catch(err){
            error(err.response.data.message || 'Error')
            console.log("Error",err.response.data);
        }
    }

    const updateSection = async (id,section) => {
        try{
            const response = await axios.patch(`${api}/sections/${id}`,
                section,
                getAuthHeader()
            );

            success(response.data.message || 'Success')
            console.log("Response",response.data);
        }catch(err){
            error(err.response.data.message || 'Error')
            console.log("Error",err.response.data);
        }
    }


    const deleteSection = async(id) => {
        try{
            const response = await axios.delete(`${api}/sections/${id}`,
                getAuthHeader()
            );

            success(response.data.message || 'Success')
        }catch(err){
            error(err.response.data.message || 'Error')
            console.log("Error",err.response.data)
        }
    }

    return {
        // States
        sections,

        // Functions
        getSections,
        addSection,
        updateSection,
        deleteSection
    }
});
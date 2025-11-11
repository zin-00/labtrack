import { defineStore } from 'pinia';
import { toRefs } from 'vue';
import axios from 'axios';
import { useApiUrl } from '../../../api/api';
import {useStates} from '../../states';
import { useToast } from '../../toastification/useToast.js';

const { api, getAuthHeader } = useApiUrl();


// Students functions
export const useStudentStore = defineStore('student', () => {
  const states = useStates();
  const toast = useToast();
  const {
        students,
        pagination,
        isLoading
        } = toRefs(states);


  const storeStudent = async (data) => {
    try{
      await axios.post(`${api}/students`, data, getAuthHeader());
      toast.success('Success', 'Student added successfully!');
      console.log('Toast should be visible now');
    }catch(err){
      toast.error('Error', 'Failed to add student');
      console.error('Error storing student:', err);
    }
  }
  const fetchStudents = async (page = 1, filters = {}) => {
    try{
      isLoading.value = true;
      
      // Build query parameters
      const params = new URLSearchParams();
      params.append('page', page);
      
      if (filters.search) {
        params.append('search', filters.search);
      }
      if (filters.program_id && filters.program_id !== 'all') {
        params.append('program_id', filters.program_id);
      }
      if (filters.year_level_id && filters.year_level_id !== 'all') {
        params.append('year_level_id', filters.year_level_id);
      }
      if (filters.section_id && filters.section_id !== 'all') {
        params.append('section_id', filters.section_id);
      }
      if (filters.status && filters.status !== 'all') {
        params.append('status', filters.status);
      }
      
      const queryString = params.toString();
      const url = `${api}/students?${queryString}`;
      
      const response = await axios.get(url, getAuthHeader());
      students.value = response.data.students.data || [];
      pagination.value = {
        current_page: response.data.students.current_page,
        last_page: response.data.students.last_page,
        per_page: response.data.students.per_page,
        total: response.data.students.total
      }
      // const message = response.data.message || 'Success';
      // toast.success('Success', message, 3000);
    }catch(err){
      students.value = [];
      toast.error('Error', 'Failed to fetch students');
      console.error('Error fetching students:', err);
    }finally{
      isLoading.value = false;
    }
  }

  const updateStudent = async (id, data) => {
    try{
      const response = await axios.put(`${api}/students/${id}`, data, getAuthHeader());
      const message = response.data.message;
      toast.success('Success', message);
    }catch(err){
      toast.error('Error', 'Failed to update student');
      console.error('Error updating student:', err);
    }
  }

  const deleteStudent = async (id) => {
    try{
      await axios.delete(`${api}/students/${id}`, getAuthHeader());
      toast.success('Success', 'Student deleted successfully!');
    }catch(err){
      toast.error('Error', 'Failed to delete student');
      console.error('Error deleting student:', err);
    }
  }

  return{
    students,
    
    storeStudent,
    fetchStudents,
    updateStudent,
    deleteStudent,
  }

});

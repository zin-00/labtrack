import { defineStore } from 'pinia';
import { toRefs } from 'vue';
import axios from 'axios';
import { useApiUrl } from '../../../api/api';
import {useStates} from '../../states';

const { api, getAuthHeader } = useApiUrl();


// Students functions
export const useStudentStore = defineStore('student', () => {
  const states = useStates();
  const {
        success,
        error
        } = states;
  const {
        students,
        pagination,
        isLoading
        } = toRefs(states);


  const storeStudent = async (data) => {
    try{
      const response = await axios.post(`${api}/students`, data, getAuthHeader());
      success(response.data.message || 'Student added successfully!');
    }catch(err){
      error('Failed to add student.');
      console.error('Error storing student:', err);
    }
  }
  const fetchStudents = async (page = 1) => {
    try{
      isLoading.value = true;
      const response = await axios.get(`${api}/students?page=${page}`, getAuthHeader());
      students.value = response.data.students.data || [];
      pagination.value = {
        current_page: response.data.students.current_page,
        last_page: response.data.students.last_page,
        per_page: response.data.students.per_page,
        total: response.data.students.total
      }
      console.log('Students data:', students.value);
      console.log('Pagination data:', pagination.value);
      
    }catch(err){
      students.value = [];
      error('Failed to fetch students');
      console.error('Error fetching students:', err);
    }finally{
      isLoading.value = false;
    }
  }

  const updateStudent = async (id, data) => {
    try{
      await axios.put(`${api}/students/${id}`, data, getAuthHeader());
      success('Student updated successfully!');
      await fetchStudents();
    }catch(err){
      error('Failed to update student.');
      console.error('Error updating student:', err);
    }
  }

  const deleteStudent = async (id) => {
    try{
      await axios.delete(`${api}/students/${id}`, getAuthHeader());
      success('Student deleted successfully!');
      await fetchStudents();
    }catch(err){
      error('Failed to delete student.');
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

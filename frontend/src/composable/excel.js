import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
import * as XLSX from 'xlsx';  // Add this import
import { useToast } from 'vue-toastification';
import { useStudentStore } from './users/students/student';
import { useApiUrl } from '../api/api';

const toast = useToast();
const { api, getAuthHeader } = useApiUrl();

 // Excel Import function
  export const useExcelStore = defineStore('excel', () =>{
      const std = useStudentStore();
      const isImportModalOpen = ref(false);
      const selectedFile = ref(null);
      const isDragOver = ref(false);
      const fileInput = ref(null);
      const isLoading = ref(false);

      const handleFileUpload = (event) => {
        const file = event.target.files[0];

        if(file){
          selectedFile.value = file;
        }
      };

      const handleDrop = (event) => {
        isDragOver.value = false;
        const file = event.dataTransfer.files[0];
        if(file && file.name.endsWith('.xlsx') || file.name.endsWith('.xls')){
          selectedFile.value = file;
        }else{

        }
      };

      const removeFile = () => {
        selectedFile.value = null;
        if(fileInput.value){
          fileInput.value.value = '';
        
        }
      };

      const importStudents = async () => {
        if (!selectedFile.value) {
          toast.error('Please select a file first');
          return;
        }

        isLoading.value = true;

        try {
          const data = await readExcelFile(selectedFile.value);
          const students = parseExcelData(data);

          const response = await axios.post(
            `${api}/students/import`, 
            { students }, 
            getAuthHeader()
          );
          
          // Show detailed results
          if (response.data.imported_count > 0) {
            toast.success(`Imported ${response.data.imported_count} students successfully!`);
          }
          
          if (response.data.skipped_count > 0) {
            toast.warning(`Skipped ${response.data.skipped_count} students due to errors`);
            // Log errors to console for debugging
            console.error('Import errors:', response.data.errors);
          }

          isImportModalOpen.value = false;
          selectedFile.value = null;
          
          // Refresh student list
          std.fetchStudents();
        } catch (error) {
          if (error.response) {
            // Backend validation errors
            if (error.response.status === 422) {
              toast.error('Validation errors occurred during import');
              console.error('Validation errors:', error.response.data.errors);
            } else {
              toast.error(`Failed to import students: ${error.response.data.message}`);
            }
          } else {
            toast.error('Failed to import students. Please check console for details.');
            console.error('Import error:', error);
          }
        } finally {
          isLoading.value = false;
        }
      }

      const readExcelFile = async (file) => {
        return new Promise((resolve, reject) =>{
          const reader = new FileReader();

          reader.onload = (event) => {
            try{
              const data = new Uint8Array(event.target.result);
              const workbook = XLSX.read(data, {type: 'array'});
              const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
              const jsonData = XLSX.utils.sheet_to_json(firstSheet);
              resolve(jsonData);
            }catch(error){
              reject(error);
            
            }
          };

          reader.onerror = (error) => {
            reject(error);
          };

          reader.readAsArrayBuffer(file);
        });
      };

     const parseExcelData = (excelData) => {
      return excelData.map(row => ({
        student_id: String(row['Student_ID'] || row['student_id'] || row['Student ID'] || row['student id'] || '').trim(),
        rfid_uid: String(row['RFID_UID'] || row['rfid_uid'] || row['RFID UID'] || row['rfid uid'] || '').trim(),
        first_name: String(row['First_Name'] || row['first_name'] || row['First Name'] || row['first name'] || '').trim(),
        middle_name: String(row['Middle_Name'] || row['middle_name'] || row['Middle Name'] || row['middle name'] || '').trim(),
        last_name: String(row['Last_Name'] || row['last_name'] || row['Last Name'] || row['last name'] || '').trim(),
        email: String(
          row['Email'] || row['email'] || row['Email Address'] || row['email address'] || ''
        )
        .trim()
        .toLowerCase()
        .replace(/\s+/g, ''),
        year_level_id: String(row['Year_Level'] || row['year_level'] || row['Year Level'] || row['year level'] || '').trim(),

      })).filter(student => 
        student.student_id && 
        student.first_name && 
        student.last_name && 
        student.email &&
        student.year_level_id
      );
    };


      return{
        isImportModalOpen,
        selectedFile,
        isDragOver,
        fileInput,
        isLoading,
        handleFileUpload,
        handleDrop,
        removeFile,
        importStudents,
        readExcelFile,
        parseExcelData,
      }

  });
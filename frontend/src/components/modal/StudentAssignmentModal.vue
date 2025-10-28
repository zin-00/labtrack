<template>
  <Modal :show="show" @close="closeModal" max-width="3xl">
    <div class="bg-white rounded-lg max-h-[85vh] flex flex-col relative shadow-xl">
      <!-- Header -->
      <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
        <div>
          <h2 class="text-lg font-semibold text-gray-900">
            Assign Students to Computer {{ computer?.computer_number }}
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            Select students to assign to this computer
          </p>
          <p v-if="computer?.laboratory" class="text-xs text-blue-600 mt-1">
            Laboratory: {{ computer.laboratory.name }}
          </p>
          <p class="text-xs text-gray-500 mt-1">
            Only showing students not assigned to this laboratory
          </p>
        </div>
        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 text-xl p-1">
          &times;
        </button>
      </div>

      <!-- Filters -->
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <!-- Search -->
          <div>
            <input v-model="searchQuery" type="text" placeholder="Search by name or student ID..." 
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          
          <!-- Year Level Filter -->
          <div>
            <select v-model="selectedYearLevel" 
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="all">All Year Levels</option>
              <option value="1">1st Year</option>
              <option value="2">2nd Year</option>
              <option value="3">3rd Year</option>
              <option value="4">4th Year</option>
            </select>
          </div>
          
          <!-- Program Filter -->
          <div>
            <select v-model="selectedProgram" 
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="all">All Programs</option>
              <option v-for="program in programs" :key="program.id" :value="program.id">
                {{ program.program_code || program.code || program.program_name }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Students List -->
      <div class="flex-1 px-6 flex flex-col min-h-0">
        <div class="flex-1 overflow-y-auto border border-gray-200 rounded-lg">
          <div v-if="isLoading" class="text-center py-8">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500 mx-auto"></div>
            <p class="mt-2 text-gray-500 text-xs">Loading students...</p>
          </div>
          
          <div v-else-if="filteredStudents.length === 0" class="text-center py-8">
            <div class="w-8 h-8 mx-auto mb-2 text-gray-400">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
              </svg>
            </div>
            <p class="font-medium text-gray-900 text-sm mb-1">No students available</p>
            <p class="text-gray-500 text-xs">All students are already assigned to this laboratory</p>
          </div>

          <!-- Student List -->
          <div v-else class="divide-y divide-gray-200">
            <div v-for="student in filteredStudents" :key="student.id" 
              @click="toggleStudentSelection(student)"
              :class="[
                'px-4 py-3 transition-all cursor-pointer hover:bg-gray-50',
                selectedStudents.has(student.id) ? 'bg-blue-50 border-l-4 border-l-blue-500' : ''
              ]">
              <div class="flex items-center gap-4">
                <!-- Checkbox -->
                <div class="flex-shrink-0">
                  <input type="checkbox" :checked="selectedStudents.has(student.id)"
                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                    @click.stop="toggleStudentSelection(student)">
                </div>
                
                <!-- Student Info -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <h3 class="font-medium text-gray-900">
                      {{ student.first_name }} {{ student.middle_name || '' }} {{ student.last_name }}
                    </h3>
                    <span class="text-xs text-gray-500 font-mono bg-gray-100 px-2 py-1 rounded">
                      {{ student.student_id }}
                    </span>
                  </div>
                  
                  <div class="mt-1 flex items-center gap-3 text-xs text-gray-500">
                    <span>{{ getProgramCode(student.program_id) }}</span>
                    <span>•</span>
                    <span>Year {{ student.year_level }}</span>
                    <span>•</span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                      Available
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer Actions -->
      <div class="flex-shrink-0 flex justify-between items-center px-6 py-4 bg-gray-50 border-t border-gray-200">
        <div class="text-sm text-gray-600">
          {{ filteredStudents.length }} student{{ filteredStudents.length !== 1 ? 's' : '' }} available
          <span v-if="selectedCount > 0">• {{ selectedCount }} selected</span>
        </div>
        <div class="flex gap-3">
          <button @click="closeModal" 
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
            :disabled="isAssigning">
            Cancel
          </button>
          <button @click="assignStudents" 
            :disabled="!hasSelectedStudents || isAssigning"
            :class="[
              'px-4 py-2 text-sm font-medium rounded-md transition-colors flex items-center gap-2',
              hasSelectedStudents && !isAssigning ? 'bg-gray-900 text-white hover:bg-gray-800' : 'bg-gray-300 text-gray-500 cursor-not-allowed'
            ]">
            <div v-if="isAssigning" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
            {{ isAssigning ? 'Assigning...' : `Assign ${selectedCount} Student${selectedCount !== 1 ? 's' : ''}` }}
          </button>
        </div>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useToast } from 'vue-toastification';
import Modal from './Modal.vue';
import axios from 'axios';
import { useApiUrl } from '../../api/api';

const props = defineProps({
  show: Boolean,
  computer: Object
});

const emit = defineEmits(['close', 'assign']);

const toast = useToast();
const { api, getAuthHeader } = useApiUrl();

const students = ref([]);
const filteredStudents = ref([]);
const programs = ref([]);
const isLoading = ref(false);
const searchQuery = ref('');
const selectedYearLevel = ref('all');
const selectedProgram = ref('all');
const selectedStudents = ref(new Set());
const isAssigning = ref(false);

// Fetch programs
const fetchPrograms = async () => {
  try {
    const response = await axios.get(`${api}/programs`, getAuthHeader());
    programs.value = response.data.programs || [];
  } catch (error) {
    programs.value = [];
    toast.error('Failed to fetch programs');
    console.error('Error fetching programs:', error);
  }
};

// Get program code by ID
const getProgramCode = (programId) => {
  const program = programs.value.find(p => p.id === programId);
  return program ? (program.program_code || program.code || 'UNK') : 'UNK';
};

// Fetch unassigned students for this laboratory
const fetchUnassignedStudents = async () => {
  isLoading.value = true;
  selectedStudents.value.clear();
  
  try {
    console.log('Fetching unassigned students for computer:', props.computer);
    
    const response = await axios.get(`${api}/students/unassigned`, {
      params: {
        computer_id: props.computer?.id,
        year_level: selectedYearLevel.value !== 'all' ? selectedYearLevel.value : null,
        program: selectedProgram.value !== 'all' ? selectedProgram.value : null,
        search: searchQuery.value
      },
      ...getAuthHeader()
    });
    
    console.log('API response:', response.data);
    students.value = response.data.students || [];
    applyFilters();
  } catch (error) {
    console.error('Error fetching students:', error.response?.data || error.message);
    toast.error('Failed to fetch students: ' + (error.response?.data?.error || 'Unknown error'));
  } finally {
    isLoading.value = false;
  }
};
// Apply filters
const applyFilters = () => {
  filteredStudents.value = students.value.filter(student => {
    const matchesSearch = searchQuery.value === '' || 
      student.first_name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      student.last_name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      student.student_id?.toLowerCase().includes(searchQuery.value.toLowerCase());
    
    const matchesYearLevel = selectedYearLevel.value === 'all' || student.year_level == selectedYearLevel.value;
    const matchesProgram = selectedProgram.value === 'all' || student.program_id == selectedProgram.value;
    
    return matchesSearch && matchesYearLevel && matchesProgram;
  });
};

// Toggle student selection
const toggleStudentSelection = (student) => {
  if (selectedStudents.value.has(student.id)) {
    selectedStudents.value.delete(student.id);
  } else {
    selectedStudents.value.add(student.id);
  }
  selectedStudents.value = new Set(selectedStudents.value);
};

// Assign selected students
const assignStudents = async () => {
  if (selectedStudents.value.size === 0) {
    toast.warning('Please select at least one student');
    return;
  }
  
  isAssigning.value = true;
  try {
    const studentIds = Array.from(selectedStudents.value);
    
    const response = await axios.post(`${api}/computer/bulk-assign`, {
      computer_id: props.computer.id,
      student_ids: studentIds
    }, getAuthHeader());
    
    toast.success(`Successfully assigned ${studentIds.length} student(s) to Computer ${props.computer.computer_number}`);
    emit('assign', {
      computer: props.computer,
      students: studentIds.map(id => students.value.find(s => s.id === id))
    });
    closeModal();
  } catch (error) {
    if (error.response?.data?.message) {
      toast.error(error.response.data.message);
    } else {
      toast.error('Failed to assign students');
    }
    console.error('Assignment error:', error);
  } finally {
    isAssigning.value = false;
  }
};

// Close modal and reset state
const closeModal = () => {
  searchQuery.value = '';
  selectedYearLevel.value = 'all';
  selectedProgram.value = 'all';
  selectedStudents.value.clear();
  emit('close');
};

// Computed properties
const selectedCount = computed(() => selectedStudents.value.size);
const hasSelectedStudents = computed(() => selectedCount.value > 0);

// Watch for filter changes
watch([searchQuery, selectedYearLevel, selectedProgram], () => {
  applyFilters();
});

// Fetch data when modal opens or computer changes
watch(() => props.show, (show) => {
  if (show && props.computer) {
    fetchUnassignedStudents();
    fetchPrograms();
  }
});

// Also watch for computer changes
watch(() => props.computer, (newComputer) => {
  if (newComputer && props.show) {
    fetchUnassignedStudents();
  }
});

onMounted(() => {
  fetchPrograms();
});
</script>
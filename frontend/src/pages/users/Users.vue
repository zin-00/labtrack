<script setup>
import { ref, computed, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import Button from '../../components/button/Button.vue';
import { 
    TrashIcon, 
    PencilIcon, 
    DocumentMagnifyingGlassIcon, 
    FunnelIcon, 
    XMarkIcon,
    ArrowDownTrayIcon, 
    UserPlusIcon, 
    ArrowPathIcon
} from '@heroicons/vue/24/outline';
import Table from '../../components/table/Table.vue';
import { useToast } from '../../composable/toastification/useToast';
import { watch } from 'vue';
import Modal from '../../components/modal/Modal.vue';
import TextInput from '../../components/input/TextInput.vue';
import {useExcelStore} from '../../composable/excel';
import { useStudentStore } from '../../composable/users/students/student';
import { useProgramStore } from '../../composable/program';

const toast = useToast();

const router = useRouter();
const auth = useStudentStore();
const prog = useProgramStore();
const xl = useExcelStore();

// Reactive filter variables
const searchQuery = ref('');
const statusFilter = ref('');
const programFilter = ref('');
const yearLevelFilter = ref('');
const positionFilter = ref('');
const countryFilter = ref('');
const cityFilter = ref('');
const dateRangeStart = ref('');
const dateRangeEnd = ref('');
const showAdvancedFilters = ref(false);

// Modal state
const modalState = ref(false);
const isConfirmationModalOpen = ref(false);


const selectedUser = ref(null);

const student = auth.students;

const selectedStatus = ref('all');
const selectedProgram = ref('all');
const selectedYearLevel = ref('all');

const loading = ref(false);




// Sample data
const positions = ref([
    { id: 1, name: 'Student' },
    { id: 2, name: 'Faculty' },
    { id: 3, name: 'Staff' },
    { id: 4, name: 'Administrator' }
]);


const studentData = reactive({
        student_id: '',
        first_name: '',
        middle_name: '',
        last_name: '',
        email: '',
        rfid_uid: '',
        program_id: 1
});

const filteredStudents = computed(() => {
    return auth.students.filter((computer) => {
        const statusMatch = 
        selectedStatus.value === 'all' || computer.status === selectedStatus.value;
        const programMatch = 
        selectedProgram.value === 'all' || studentData.program_id === parseInt( selectedProgram.value);
        const yearLevelMatch = 
        selectedYearLevel.value === 'all' || computer.year_level === selectedYearLevel.value;
        return statusMatch && programMatch && yearLevelMatch;
    });
});

const handleView = (user) => {
    console.log('View user:', user);
    toast.success('User viewed successfully!');
};

const countries = ref(['Philippines', 'USA', 'Canada', 'Australia']);

const cities = computed(() => {
    if (!countryFilter.value) return [];
    
    const cityMap = {
        'Philippines': ['Manila', 'Cebu', 'Davao', 'Quezon City'],
        'USA': ['New York', 'Los Angeles', 'Chicago', 'Houston'],
        'Canada': ['Toronto', 'Vancouver', 'Montreal', 'Calgary'],
        'Australia': ['Sydney', 'Melbourne', 'Brisbane', 'Perth']
    };
    
    return cityMap[countryFilter.value] || [];
});

// Functions
const resetFilters = () => {
    searchQuery.value = '';
    statusFilter.value = '';
    programFilter.value = '';
    yearLevelFilter.value = '';
    positionFilter.value = '';
    countryFilter.value = '';
    cityFilter.value = '';
    dateRangeStart.value = '';
    dateRangeEnd.value = '';
    showAdvancedFilters.value = false;
};
const fetchStudents = (page = 1) => {
    try{
        auth.fetchStudents(page);
    }catch(error){
        toast.error('Failed to fetch students.');
        console.error(error);
    }
}
const saveStudent = () => {
    try{
      if(selectedUser.value){
        auth.updateStudent(selectedUser.value.id, studentData);
        modalState.value = false;
        clearForm();
      }else{
        auth.storeStudent(studentData);
        modalState.value = false;
      }
      fetchStudents();
    }catch(error){
        toast.error('Failed to add user.');
        console.error(error);
    }
};

const clearForm = () => {
    studentData.student_id = '';
    studentData.first_name = '';
    studentData.middle_name = '';
    studentData.last_name = '';
    studentData.email = '';
    studentData.rfid_uid = '';

}

const deleteStudent_func =  () => {
    auth.deleteStudent(selectedUser.value.id);
    isConfirmationModalOpen.value = false;
}
const editStudent = (student) => {
  selectedUser.value = student;
  Object.assign(studentData, {
    student_id: student.student_id?.toString() ?? '',
    first_name: student.first_name ?? '',
    middle_name: student.middle_name ?? '',
    last_name: student.last_name ?? '',
    email: student.email ?? '',
    rfid_uid: student.rfid_uid?.toString() ?? '',
  });
  modalState.value = true;
}; 

const openAddModal = () => {
    selectedUser.value = null;
    Object.assign(studentData, {
        student_id: '',
        first_name: '',
        middle_name: '',
        last_name: '',
        email: '',
        rfid_uid: '',
        program_id: prog.programs?.[0]?.id || 1

    });
    modalState.value = true;
}

const open_delete_modal = (student) => {
    selectedUser.value = student;
    isConfirmationModalOpen.value = true;
}


const refreshData = () => {
    // Refresh the page or reload data
    router.go(0);
};



watch(countryFilter, () => {
    cityFilter.value = '';
});

onMounted(() => {
    fetchStudents();
    prog.fetchPrograms();

});

</script>

<template>
    <AuthenticatedLayout>
        <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white">
                <h2 class="text-lg font-semibold mb-3">User Management</h2>
                
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <!-- Left: Search & Filters -->
                <div class="flex flex-wrap gap-2 items-center">
                    <!-- Search Box -->
                    <div class="relative">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search globally..."
                        class="w-64 border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                    >
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="absolute right-2 top-2 text-gray-400 hover:text-gray-600"
                    >
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                    </div>

                    <!-- Status Filter -->
                    <select
                        v-model="statusFilter"
                        class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                        >
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>

                    <!-- Program Filter -->
                    <select
                        v-model="selectedProgram"
                        class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                        >
                        <option value="all">All Programs</option>
                        <option 
                            v-for="program in prog.programs"
                            :key="program.id" 
                            :value="program.id">
                            
                        {{ program.program_code }}
                    </option>
                       
                    </select>

                    <!-- Year Level Filter -->
                    <select
                        v-model="yearLevelFilter"
                        class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                        >
                        <option value="">All Year Levels</option>
                        <option value="1st">1st Year</option>
                        <option value="2nd">2nd Year</option>
                        <option value="3rd">3rd Year</option>
                        <option value="4th">4th Year</option>
                    </select>
                </div>

                <!-- Right: Action Buttons -->
                <div class="flex gap-2">
                    <button
                        title="Refresh"
                        @click="refreshData"
                        class="p-2 text-white  bg-gray-800 hover:bg-gray-700  rounded-md transition duration-200"
                        >
                        <ArrowPathIcon class="h-5 w-5" />
                    </button>

                    <button
                        @click="openAddModal"
                        title="Add User"
                        class="p-2  text-white  bg-gray-800 hover:bg-gray-700 rounded-md transition duration-200"
                        >
                        <UserPlusIcon class="h-5 w-5" />
                    </button>

                    <button
                        @click="xl.isImportModalOpen = true"
                        title="Import Users"
                        class="p-2  text-white  bg-gray-800 hover:bg-gray-700 rounded-md transition duration-200"
                        >
                        <ArrowDownTrayIcon class="h-5 w-5" />
                    </button>
                </div>
                </div>

               
                <!-- User Table-->
                <div class="mt-4">
                    <Table 
                    :users="auth.students.data" 
                    :pagination="auth.students"
                    :loading="loading"
                    @edit="editStudent"
                    @delete="open_delete_modal"
                    @view="handleView"
                    @page-change="auth.fetchStudents"
                    />
                                    
                </div>
                <!-- Delete Confirmation Modal -->
                <Modal :show="isConfirmationModalOpen" @close="isConfirmationModalOpen = false">
                    <div class="bg-white p-6 rounded-2xl shadow-2xl w-full max-w-md mx-auto relative">
                        <div class="flex items-center gap-3 mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M12 19c3.866 0 7-3.134 7-7S15.866 5 12 5 5 8.134 5 12s3.134 7 7 7z"/>
                        </svg>
                        <h2 class="text-xl font-semibold text-gray-800">Confirm Deletion</h2>
                        </div>

                        <p class="text-gray-600 text-sm leading-relaxed">
                        Are you sure you want to delete this user? This action cannot be undone.
                        </p>

                        <div class="flex justify-end gap-3 mt-6">
                        <button
                            @click="isConfirmationModalOpen = false"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition"
                        >
                            Cancel
                        </button>

                        <button
                            @click="deleteStudent_func"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                        >
                            Yes, Delete
                        </button>
                        </div>
                    </div>
                </Modal>

                <!-- Import Modal -->
               <Modal :show="xl.isImportModalOpen" @close="xl.isImportModalOpen = false">
                    <div class="bg-white p-8 rounded-lg max-w-md mx-auto relative">
                        <h2 class="text-lg font-medium mb-6">Import Students</h2>
                        
                        <form @submit.prevent="xl.importStudents" class="space-y-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-2">
                            Excel file (.xlsx, .xls)
                            </label>
                            
                            <!-- Drag and Drop Area -->
                            <div
                            @drop="xl.handleDrop"
                            @dragover.prevent
                            @dragenter.prevent
                            @dragleave="xl.isDragOver = false"
                            @dragover="xl.isDragOver = true"
                            :class="[
                                'border-2 border-dashed rounded-lg p-8 text-center transition-colors',
                                xl.isDragOver ? 'border-gray-400 bg-gray-50' : 'border-gray-200',
                                xl.selectedFile ? 'bg-gray-50' : ''
                            ]"
                            >
                            <div v-if="!xl.selectedFile">
                                <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <p class="text-sm text-gray-600 mb-1">Drop your file here or</p>
                                <button
                                type="button"
                                @click="$refs.fileInput.click()"
                                class="text-sm text-black hover:underline"
                                >
                                browse files
                                </button>
                            </div>
                            
                            <div v-else class="flex items-center justify-between">
                                <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-sm text-gray-700">{{ xl.selectedFile.name }}</span>
                                </div>
                                <button
                                type="button"
                                @click="xl.removeFile"
                                class="text-sm text-gray-400 hover:text-gray-600"
                                >
                                Remove
                                </button>
                            </div>
                            </div>
                            
                            <!-- Hidden file input -->
                            <input
                            ref="fileInput"
                            type="file"
                            accept=".xlsx,.xls"
                            @change="xl.handleFileUpload"
                            class="hidden"
                            />
                        </div>
                        
                        <div class="flex gap-3 pt-2">
                            <button
                            type="button"
                            @click="xl.isImportModalOpen = false"
                            class="flex-1 px-4 py-2 text-sm text-gray-600 hover:text-gray-800"
                            >
                            Cancel
                            </button>
                            <button
                            type="submit"
                            :disabled="xl.isLoading"
                            class="flex-1 px-4 py-2 bg-black text-white text-sm rounded hover:bg-gray-800"
                            >
                                <span v-if="!xl.isLoading">Import</span>
                                <span v-else>Importing...</span>
                            </button>
                        </div>
                        </form>
                    </div>
                </Modal>



            <!-- Store and Edit User Modal -->
            <Modal :show="modalState" @close="modalState = false">
                <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-5xl mx-auto relative">
                    <h2 class="text-xl font-bold mb-4">
                    {{ selectedUser ? 'Edit Student' : 'Add New Student' }}
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Student ID</label>
                        <TextInput
                        v-model="studentData.student_id"
                        type="text"
                        class="w-full px-2 py-[7px] border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">RFID UID</label>
                        <TextInput
                        v-model="studentData.rfid_uid"
                        type="text"
                        class="w-full px-2 py-[7px] border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <TextInput
                        v-model="studentData.first_name"
                        type="text"
                        class="w-full px-2 py-[7px] border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                        <TextInput
                        v-model="studentData.middle_name"
                        type="text"
                        class="w-full px-2 py-[7px] border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <TextInput
                        v-model="studentData.last_name"
                        type="text"
                        class="w-full px-2 py-[7px] border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <TextInput
                        v-model="studentData.email"
                        type="email"
                        class="w-full px-2 py-[7px] border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Program</label>
                        <select
                        v-model="studentData.program_id"
                        class="w-full p-2 py-[7px] border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        >
                        <option disabled value="">-- Select Program --</option>
                        <!-- Debug: -->
                        <div v-if="!prog.programs.length">Loading programs...</div>
                        <option 
                            v-for="program in prog.programs" 
                            :key="program.id" 
                            :value="program.id"
                        >
                            {{ program.program_code }}
                        </option>
                        </select>
                    </div>

                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                    <button
                        @click="modalState = false"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition"
                    >
                        Cancel
                    </button>
                    <button
                        @click="saveStudent"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                    >
                        Save
                    </button>
                    </div>
                </div>
            </Modal>

            </div> 
    </AuthenticatedLayout>
</template>
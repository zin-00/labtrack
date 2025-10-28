<script setup>
import { ref, computed, reactive, onMounted, toRefs } from 'vue';
import { useRouter } from 'vue-router';
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import Button from '../../components/button/Button.vue';
import {LoopingRhombusesSpinner} from 'epic-spinners';
import { FolderDownIcon, FolderUpIcon,
        RefreshCcwIcon,
        UserRoundPlusIcon,
        XIcon,
    } from 'lucide-vue-next';
import { 
    TrashIcon, 
    PencilIcon, 
    EyeIcon
} from '@heroicons/vue/24/outline';
import Table from '../../components/table/Table.vue';
import { useToast } from 'vue-toastification';
import { watch } from 'vue';
import Modal from '../../components/modal/Modal.vue';
import TextInput from '../../components/input/TextInput.vue';
import {useExcelStore} from '../../composable/excel';
import { useStudentStore } from '../../composable/users/students/student';
import { useProgramStore } from '../../composable/program';
import { useStates } from '../../composable/states';
import { useSectionStore } from '../../composable/section';
import { useYearLevelStore } from '../../composable/yearlevel';
import LoaderSpinner from '../../components/spinner/LoaderSpinner.vue';

const lvl = useYearLevelStore();
const states = useStates();
const section = useSectionStore();
const auth = useStudentStore();

const {
    secNotPaginated,
    yearLevelsNotPaginated,
    modalState,
    programs,
    searchQuery,
    statusFilter,
    pagination,
    students,
    selectedUser,
    isConfirmationModalOpen,
    selectedYearLevel,
    selectedProgram,
    selectedStatus,
    selectedSection,
    isLoading,

    // Data Arrays

    } = toRefs(states);

const {
    getSections,
    } = section;

const {
    getYearLevels,
    } = lvl;
const {
    ModalState
    } = states;
const {
    fetchStudents,
    storeStudent,
    updateStudent,
    deleteStudent,
    } = auth;

const toast = useToast();
const router = useRouter();
const prog = useProgramStore();
const xl = useExcelStore();

const studentData = reactive({
        student_id: '',
        first_name: '',
        middle_name: '',
        last_name: '',
        email: '',
        rfid_uid: '',
        program_id: 1,
        year_level_id: 1,
        section_id: 1,
        status: '',
});

const filteredStudents = computed(() => {
    if (!students.value || !Array.isArray(students.value)) {
        return [];
    }

    return students.value.filter((student) => {
        const statusMatch =
            selectedStatus.value === 'all' || student.status === selectedStatus.value;
        const programMatch =
            selectedProgram.value === 'all' || student.program_id === parseInt(selectedProgram.value);
        const yearLevelMatch =
            selectedYearLevel.value === 'all' || student.year_level_id === parseInt(selectedYearLevel.value);
        const sectionMatch =
            selectedSection.value === 'all' || student.section_id === parseInt(selectedSection.value);
        const searchmatch =
            searchQuery.value === '' ||
            student.first_name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            student.last_name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            student.student_id?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            student.email?.toLowerCase().includes(searchQuery.value.toLowerCase());

        return statusMatch && programMatch && yearLevelMatch && searchmatch && sectionMatch;
    });
});

const handleView = (user) => {
    console.log('View user:', user);
    toast.success('User viewed successfully!');
};

const getStudents = async (page = 1) => {
    try{
        console.log('Fetching students...'); // Debug log
        await fetchStudents(page);
        console.log('Students fetched:', students.value); // Debug log
    }catch(error){
        toast.error('Failed to fetch students.');
        console.error(error);
    }
}

const saveStudent = async () => {
    try{
      if(selectedUser.value){
        await updateStudent(selectedUser.value.id, studentData);
        ModalState(false);
        clearForm();
      }else{
        await storeStudent(studentData);
        ModalState(false);
      }
      await fetchStudents();
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
    studentData.program_id = 1;
    studentData.status = '';
}

const deleteStudent_func = async () => {
    try {
        await deleteStudent(selectedUser.value.id);
        isConfirmationModalOpen.value = false;
    } catch (error) {
        console.error('Error deleting student:', error);
        toast.error('Failed to delete student.');
    }
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
    program_id: student.program_id ?? 1,
    status: student.status ?? 'active',
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
        program_id: prog.programs?.[0]?.id || 1,
        year_level_id: 1,
        section_id: 1,
        status: 'active',
    });
    modalState.value = true;
}

const open_delete_modal = (student) => {
    selectedUser.value = student;
    isConfirmationModalOpen.value = true;
}

const refreshData = () => {
    router.go(0);
};

// Add debugging watcher
watch(students, (newVal) => {
    console.log('Students changed:', newVal);
}, { deep: true });

onMounted(async () => {
    console.log('Component mounted, starting data fetch...');
    console.log('Filtered students:',filteredStudents.length);
    try {
        await getStudents();
        await getSections();
        await getYearLevels();
        await prog.fetchPrograms();
        console.log('All data fetched successfully');
    } catch (error) {
        console.error('Error in onMounted:', error);
    }
});
</script>
<template>
    <AuthenticatedLayout>
            <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white min-h-screen relative">
             <LoaderSpinner :is-loading="isLoading" subMessage="Please wait while we fetch your data" />
                <div>
                    <h2 class="text-2xl text-gray-900">Student Management</h2>
                    <p class="mt-1 text-xs text-gray-600">
                        Manage student records with full CRUD operations and bulk import via Excel file.
                    </p>
                </div>
                                
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pt-5">
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
                            <XIcon :stroke-width="1.50" class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Status Filter -->
                    <select
                        v-model="selectedStatus"
                        class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                        >
                        <option value="all">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="restricted">Restricted</option>
                    </select>

                    <!-- Program Filter -->
                    <select
                        v-model="selectedProgram"
                        class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                        >
                        <option value="all">All Programs</option>
                        <option 
                            v-for="program in programs"
                            :key="program.id" 
                            :value="program.id">
                        {{ program.program_code }}
                    </option>
                    </select>

                     <!-- Year Level Filter - FIX 3: Fixed the v-model binding -->
                    <select
                        v-model="selectedYearLevel"
                        class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                        >
                        <option value="all">All Year Levels</option>
                        <option 
                            v-for="yearLevel in yearLevelsNotPaginated"
                            :key="yearLevel.id" 
                            :value="yearLevel.id">
                        {{ yearLevel.name }}
                    </option>
                    </select>

                     <!-- Section Filter -->
                    <select
                        v-model="selectedSection"
                        class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                        >
                        <option value="all">All Section</option>
                        <option v-for="section in secNotPaginated"
                            :key="section.id" 
                            :value="section.id">
                        {{ section.name }}
                    </option>
                    </select>
                </div>

                <!-- Right: Action Buttons -->
                <div class="flex gap-2">
                    <Button
                        title="Refresh"
                        @click="refreshData"
                        class="p-2 text-white  bg-gray-800 hover:bg-gray-700  rounded-md transition duration-200"
                        >
                        <RefreshCcwIcon :stroke-width="1.50" class="h-5 w-5" />
                    </Button>

                    <Button
                        @click="openAddModal"
                        title="Add User"
                        class="p-2  text-white  bg-gray-800 hover:bg-gray-700 rounded-md transition duration-200"
                        >
                        <UserRoundPlusIcon :stroke-width="1.50" class="h-5 w-5" />
                    </Button>

                    <Button
                        @click="xl.isImportModalOpen = true"
                        title="Import Users"
                        class="p-2  text-white  bg-gray-800 hover:bg-gray-700 rounded-md transition duration-200"
                        >
                        <FolderDownIcon :stroke-width="1.50" class="h-5 w-5" />
                    </Button>
                    <Button class="p-2 text-white bg-gray-800 hover:bg-gray-700 rounded-md transition duration-200">
                        <FolderUpIcon :stroke-width="1.50" class="h-5 w-5" />
                    </Button>
                </div>
            </div>

          <div class="mt-4 relative min-h-64">
            
            <Table
                :users="filteredStudents" 
                :pagination="pagination"
                :loading="isLoading"
                @edit="editStudent"
                @delete="open_delete_modal"
                @view="handleView"
                @page-change="getStudents"
                >
                <template #header>
                    <tr>
                        <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">Student ID</th>
                        <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">Name</th>
                        <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">Email</th>
                        <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">Section</th>
                        <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">Level</th>
                        <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">Program</th>
                        <th class="px-3 py-2 text-xs font-medium text-gray-600 text-left cursor-pointer hover:bg-gray-100 transition-colors">Status</th>
                        <th class="px-3 py-2 text-xs font-medium text-gray-600 text-center w-20">Actions</th>
                    </tr>
                </template>
                <template #default>
                    <tr v-for="student in filteredStudents" :key="student.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-xs text-gray-900">{{ student.student_id }}</td>
                        <td class="px-6 py-4 text-xs text-gray-900">{{ student.first_name }} {{ student.last_name }}</td>
                        <td class="px-3 py-2 text-sm">
                            <a
                                v-if="student.email"
                                :href="`mailto:${student.email}`"
                                class="text-blue-600 hover:underline"
                            >
                                {{ student.email }}
                            </a>
                            <span v-else class="text-gray-400">—</span>
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-900">{{ student.section?.name }}</td>
                        <td class="px-6 py-4 text-xs text-gray-900">{{ student.year_level?.name }}</td>
                        <td class="px-6 py-4 text-xs text-gray-900">{{ student.program?.program_code }}</td>
                        <td class="px-6 py-4 text-xs text-gray-900">{{ student.status }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button @click="handleView(student)" class="text-gray-400 hover:text-gray-600">
                                    <EyeIcon class="w-4 h-4" />
                                </button>
                                <button @click="editStudent(student)" class="text-gray-400 hover:text-black">
                                    <PencilIcon class="w-4 h-4" />
                                </button>
                                <button @click="open_delete_modal(student)" class="text-gray-400 hover:text-gray-600">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
            </Table>        
            </div>
               <!-- Delete Confirmation Modal -->
                <Modal :show="isConfirmationModalOpen" @close="isConfirmationModalOpen = false">
                    <div class="relative inset-0 flex items-center justify-center p-4 z-50">
                        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-auto relative border border-gray-200">
                            <!-- Header -->
                            <div class="px-6 py-4 border-b border-gray-200">
                                <div class="flex items-center gap-4">
                                    <div class="flex-shrink-0 w-12 h-12 bg-red-50 border border-red-200 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 9v2m0 4h.01M12 19c3.866 0 7-3.134 7-7S15.866 5 12 5 5 8.134 5 12s3.134 7 7 7z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-semibold text-gray-900">Confirm Deletion</h2>
                                        <p class="text-sm text-gray-500">This action cannot be undone</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="px-6 py-4">
                                <p class="text-gray-700 leading-relaxed">
                                    Are you sure you want to delete this student? All associated data and records will be permanently removed from the system.
                                </p>
                            </div>

                            <!-- Footer -->
                            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                                <div class="flex justify-end gap-3">
                                    <button
                                        @click="isConfirmationModalOpen = false"
                                        class="px-4 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-all font-medium"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        @click="deleteStudent_func"
                                        class="px-4 py-2.5 bg-black text-white rounded-lg hover:bg-gray-900 transition-all font-medium shadow-sm"
                                    >
                                        Delete Student
                                    </button>
                                </div>
                            </div>
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
<Transition
    enter-active-class="ease-out duration-300"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="ease-in duration-200"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
>
    <div v-if="modalState" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="modalState" class="bg-white rounded-xl shadow-2xl w-full max-w-6xl relative border border-gray-200">
                <!-- Header -->
                <div class="px-8 py-5 border-b border-gray-200 bg-gray-50 rounded-t-xl">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-semibold text-gray-900">
                            {{ selectedUser ? 'Edit Student' : 'Add New Student' }}
                        </h2>
                        <button
                            @click="ModalState(false)"
                            class="p-2 hover:bg-gray-200 rounded-lg transition-colors text-gray-500 hover:text-gray-700"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Form Content -->
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <!-- Student ID -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Student ID</label>
                            <TextInput
                                v-model="studentData.student_id"
                                type="text"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-all bg-white text-gray-900"
                                placeholder="Enter student ID"
                            />
                        </div>

                        <!-- RFID UID -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">RFID UID</label>
                            <TextInput
                                v-model="studentData.rfid_uid"
                                type="text"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-all bg-white text-gray-900"
                                placeholder="Scan or enter RFID"
                            />
                        </div>

                        <!-- First Name -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">First Name</label>
                            <TextInput
                                v-model="studentData.first_name"
                                type="text"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-all bg-white text-gray-900"
                                placeholder="Enter first name"
                            />
                        </div>

                        <!-- Middle Name -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Middle Name</label>
                            <TextInput
                                v-model="studentData.middle_name"
                                type="text"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-all bg-white text-gray-900"
                                placeholder="Enter middle name (optional)"
                            />
                        </div>

                        <!-- Last Name -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Last Name</label>
                            <TextInput
                                v-model="studentData.last_name"
                                type="text"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-all bg-white text-gray-900"
                                placeholder="Enter last name"
                            />
                        </div>

                        <!-- Email -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <TextInput
                                v-model="studentData.email"
                                type="email"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-all bg-white text-gray-900"
                                placeholder="Enter email address"
                            />
                        </div>

                        <!-- Program -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Program</label>
                            <select
                                v-model="studentData.program_id"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-all bg-white text-gray-900"
                            >
                                <option disabled value="">-- Select Program --</option>
                                <option 
                                    v-for="program in prog.programs" 
                                    :key="program.id" 
                                    :value="program.id"
                                >
                                    {{ program.program_code }}
                                </option>
                            </select>
                            <div v-if="!prog.programs.length" class="text-sm text-gray-500 italic">
                                Loading programs...
                            </div>
                        </div>

                        <!-- Year Level -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Year Level</label>
                            <select
                                v-model="studentData.year_level"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-all bg-white text-gray-900"
                            >
                                <option disabled value="">-- Select Year Level --</option>
                                <option value="1st year">1st Year</option>
                                <option value="2nd year">2nd Year</option>
                                <option value="3rd year">3rd Year</option>
                                <option value="4th year">4th Year</option>
                            </select>
                        </div>

                        <!-- Section -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Section</label>
                            <select
                                v-model="studentData.section_id"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-all bg-white text-gray-900"
                            >
                                <option disabled value="">-- Select Section --</option>
                                <option 
                                    v-for="section in secNotPaginated" 
                                    :key="section.id" 
                                    :value="section.id"
                                >
                                    {{ section.name }}
                                </option>
                            </select>
                            <div v-if="!secNotPaginated.length" class="text-sm text-gray-500 italic">
                                Loading sections...
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select
                                v-model="studentData.status"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-black focus:border-black transition-all bg-white text-gray-900"
                            >
                                <option disabled value="">-- Select Status --</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="restricted">Restricted</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-8 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                    <div class="flex justify-end gap-3">
                        <button
                            @click="ModalState(false)"
                            class="px-6 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-all font-medium"
                        >
                            Cancel
                        </button>
                        <button
                            @click="saveStudent"
                            class="px-6 py-2.5 bg-black text-white rounded-lg hover:bg-gray-900 transition-all font-medium shadow-sm"
                        >
                            {{ selectedUser ? 'Update Student' : 'Save Student' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</Transition>
            </div> 
    </AuthenticatedLayout>
</template>
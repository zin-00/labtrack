<script setup>
import { useProgramStore } from '../../../composable/program';
import Table from '../../../components/table/Table.vue';
import { computed, onMounted, toRefs, ref, toRef } from 'vue';
import Modal from '../../../components/modal/Modal.vue';
import TextInput from '../../../components/input/TextInput.vue';
import InputLabel from '../../../components/input/InputLabel.vue';
import InputError from '../../../components/input/InputError.vue';
import AuthenticatedLayout from '../../../layouts/auth/AuthenticatedLayout.vue';
import dayjs from 'dayjs';
import { useStates } from '../../../composable/states';

import { 
    TrashIcon, 
    PencilIcon, 
    XMarkIcon,
    EyeIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';
import { 
    XIcon,
    PlusIcon
} from 'lucide-vue-next';

const program = useProgramStore();
const states = useStates();

const {
    paginatedPrograms,
    paginationPrograms,
    searchQuery,
    pagination,
    isLoading,
    modalState,
    selectedProgram,
    deleteModalState,
    itemToDelete
    } = toRefs(states);


const {
    fetchPrograms,
    addProgram,
    updateProgram,
    deleteProgram,
} = program;

const programData = ref({
    program_name: '',
    program_code: '',
    program_description: '',
    errors: {}
});

const filterPrograms = computed(() => {
    if (!paginatedPrograms.value || !Array.isArray(paginatedPrograms.value)) {
        return [];
    }
    return paginatedPrograms.value.filter((program) => {
        const searchLower = searchQuery.value.toLowerCase();
        return program.program_name?.toLowerCase().includes(searchLower) ||
               program.program_code?.toLowerCase().includes(searchLower) ||
               program.program_description?.toLowerCase().includes(searchLower);
    });
});

// Delete modal functions
const openDeleteModal = (program) => {
    itemToDelete.value = program;
    deleteProgram(program.id);
    deleteModalState.value = true;
};

const confirmDelete = async () => {
    try {
        await deleteProgram(itemToDelete.value.id);
        deleteModalState.value = false;
        itemToDelete.value = null;
        await getPrograms(); // Refresh the list
    } catch (error) {
        console.error('Error deleting program:', error);
    }
};

// Program modal functions
const openProgramModal = (program = null) => {
    selectedProgram.value = program;
    if (program) {
        programData.value = {
            program_name: program.program_name,
            program_code: program.program_code,
            program_description: program.program_description,
            errors: {}
        };
    } else {
        programData.value = {
            program_name: '',
            program_code: '',
            program_description: '',
            errors: {}
        };
    }
    modalState.value = true;
};

const saveProgram = async () => {
    try {
        const { errors, ...dataToSave } = programData.value;
        
        if (selectedProgram.value) {
            await updateProgram(selectedProgram.value.id, dataToSave);

        } else {
            await addProgram(dataToSave);
        }
        
        modalState.value = false;
        selectedProgram.value = null;
        await getPrograms();
    } catch (error) {
        console.error('Error saving program:', error);
        if (error.response?.data?.errors) {
            programData.value.errors = error.response.data.errors;
        }
    }
};

const editProgram = (program) => {
    openProgramModal(program);
};

const handleView = (program) => {
    console.log('Viewing program:', program);
    // Add your view logic here
};

onMounted(() => {
    fetchPrograms();
});
</script>

<template>
    <AuthenticatedLayout>
        <!-- Main Container with proper spacing -->
        <div class="min-h-screen bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                
                <!-- Header Section -->
                <div class="mb-8">
                    <h1 class="text-3xl font-light text-black mb-2">Programs Management</h1>
                    <p class="text-gray-500 text-sm">Manage academic programs with simplicity</p>
                </div>

                <!-- Control Panel -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8 justify-between">
                    
                    <!-- Header with Add Button -->
                    <div class="flex items-center justify-between mb-6">
                        <button
                            @click="openProgramModal()"
                            class="flex items-center gap-2 px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition-colors"
                        >
                            <PlusIcon class="w-4 h-4" />
                            Add Program
                        </button>
                    </div>

                    <!-- Search -->
                    <div class="flex items-center gap-4">
                        <div class="relative flex-1 max-w-md">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search programs..."
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:border-gray-400 focus:outline-none transition-colors"
                            />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600"
                            >
                                <XIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Programs Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    
                    <!-- Table Header -->
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-black">Programs</h2>
                    </div>

                    <!-- Table Component -->
                    <Table
                        :pagination="paginationPrograms"
                        :loading="isLoading"
                        :items="filterPrograms"
                        :mobileFields="['program_name', 'program_code', 'program_description', 'created_at']"
                        titleField="program_name"
                        @page-change="fetchPrograms"
                        @edit="editProgram"
                        @delete="openDeleteModal"
                        @view="handleView"
                    >
                        <template #header>
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                        </template>
                        <template #default>
                            <tr v-for="program in filterPrograms" :key="program.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ program.id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ program.program_code }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ program.program_description }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ dayjs(program.created_at).format("MMM D, YYYY") }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="handleView(program)" class="text-gray-400 hover:text-gray-600">
                                            <EyeIcon class="w-4 h-4" />
                                        </button>
                                        <button @click="editProgram(program)" class="text-gray-400 hover:text-black">
                                            <PencilIcon class="w-4 h-4" />
                                        </button>
                                        <button @click="openDeleteModal(program)" class="text-gray-400 hover:text-gray-600">
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </Table>
                </div>
            </div>
        </div>

        <!-- Program Modal -->
        <Modal :show="modalState" @close="modalState = false">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-auto relative">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-black">
                            {{ selectedProgram ? "Edit Program" : "Add Program" }}
                        </h3>
                        <button @click="modalState = false" class="text-gray-400 hover:text-gray-600">
                            <XIcon class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4">
                    <div>
                        <InputLabel for="program_name" value="Program Name" class="text-gray-700 text-sm font-medium" />
                        <TextInput
                            id="program_name"
                            v-model="programData.program_name"
                            placeholder="Enter program name"
                            class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-gray-400 focus:outline-none"
                        />
                        <InputError :message="programData.errors?.program_name" />
                    </div>

                    <div>
                        <InputLabel for="program_code" value="Program Code" class="text-gray-700 text-sm font-medium" />
                        <TextInput
                            id="program_code"
                            v-model="programData.program_code"
                            placeholder="e.g., BSIT, BSCS"
                            class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-gray-400 focus:outline-none"
                        />
                        <InputError :message="programData.errors?.program_code" />
                    </div>

                    <div>
                        <InputLabel for="program_description" value="Description" class="text-gray-700 text-sm font-medium" />
                        <textarea
                            id="program_description"
                            v-model="programData.program_description"
                            placeholder="Enter program description"
                            rows="3"
                            class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-gray-400 focus:outline-none resize-none"
                        ></textarea>
                        <InputError :message="programData.errors?.program_description" />
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button
                        @click="modalState = false"
                        class="px-4 py-2 text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 text-sm"
                    >
                        Cancel
                    </button>
                    <button
                        @click="saveProgram"
                        class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 text-sm"
                    >
                        {{ selectedProgram ? "Update" : "Create" }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="deleteModalState" @close="deleteModalState = false">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-auto relative">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                            <ExclamationTriangleIcon class="w-5 h-5 text-gray-600" />
                        </div>
                        <h3 class="text-lg font-medium text-black">Confirm Deletion</h3>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <p class="text-gray-600 text-sm mb-4">
                        Are you sure you want to delete this program?
                    </p>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="font-medium text-black text-sm">
                            {{ itemToDelete?.program_name }}
                        </p>
                        <p class="text-gray-500 text-xs mt-1">
                            Code: {{ itemToDelete?.program_code }}
                        </p>
                        <p class="text-gray-500 text-xs">
                            {{ itemToDelete?.program_description }}
                        </p>
                    </div>
                    <p class="text-gray-500 text-xs mt-3">This action cannot be undone.</p>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button
                        @click="deleteModalState = false"
                        class="px-4 py-2 text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 text-sm"
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirmDelete"
                        class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 text-sm"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
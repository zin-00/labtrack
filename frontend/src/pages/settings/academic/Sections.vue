<script setup>
import { useStates } from '../../../composable/states';
import { useSectionStore } from '../../../composable/section';
import { useYearLevelStore } from '../../../composable/yearlevel';
import Table from '../../../components/table/Table.vue';
import { computed, onMounted, toRefs, ref, watch } from 'vue';
import Modal from '../../../components/modal/Modal.vue';
import TextInput from '../../../components/input/TextInput.vue';
import InputLabel from '../../../components/input/InputLabel.vue';
import InputError from '../../../components/input/InputError.vue';
import AuthenticatedLayout from '../../../layouts/auth/AuthenticatedLayout.vue';
import dayjs from 'dayjs';
import { debounce } from 'lodash-es';

import { 
    TrashIcon, 
    PencilIcon, 
    XMarkIcon,
    ArrowDownTrayIcon, 
    UserPlusIcon, 
    ArrowPathIcon,
    EyeIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';
import { FolderDownIcon, FolderUpIcon,
        RefreshCcwIcon,
        UserRoundPlusIcon,
        XIcon,
        PlusIcon
    } from 'lucide-vue-next';

const states = useStates();
const sec = useSectionStore();
const lvl = useYearLevelStore();

const {
    getSections,
    addSection,
    updateSection,
    deleteSection,
} = sec;

const {
    sections,
} = toRefs(sec);

const {
    getYearLevels,
    addYearLevel,
    updateYearLevel,
    deleteYearLevel,
} = lvl;


const {
    searchQuery,
    isLoading,
    statusFilter,
    modalState,
    selectedSection,
    selectedYearLevel,
    yearLevelModalState,
    activeTable,
    SectionData,
    yearLevelData,
    deleteModalState,
    itemToDelete,
    deleteType,
    paginationSections,
    paginationYearLevels,
    yearLevels,
} = toRefs(states);

const {
    ModalState
} = states;

const filterSections = computed(() => {
    return sections.value || [];
});

const filterYearLevels = computed(() => {
    return yearLevels.value || [];
});

// Debounced filter functions
const applySectionFilters = debounce((page = 1) => {
    const filters = {
        search: searchQuery.value
    };
    getSections(page, filters);
}, 300);

const applyYearLevelFilters = debounce((page = 1) => {
    const filters = {
        search: searchQuery.value
    };
    getYearLevels(page, filters);
}, 300);

// Watch search and trigger backend request based on active table
watch(searchQuery, () => {
    if (activeTable.value === 'sections') {
        applySectionFilters(1);
    } else {
        applyYearLevelFilters(1);
    }
});

// Watch active table change and fetch data
watch(activeTable, (newValue) => {
    searchQuery.value = ''; // Clear search when switching tabs
    if (newValue === 'sections') {
        applySectionFilters(1);
    } else {
        applyYearLevelFilters(1);
    }
});

// Delete modal functions
const openDeleteModal = (item, type) => {
    itemToDelete.value = item;
    deleteType.value = type;
    deleteModalState.value = true;
};

const confirmDelete = async () => {
    if (deleteType.value === 'section') {
        await deleteSection(itemToDelete.value.id);
        applySectionFilters(paginationSections.value.current_page || 1);
    } else if (deleteType.value === 'yearlevel') {
        await deleteYearLevel(itemToDelete.value.id);
        applyYearLevelFilters(paginationYearLevels.value.current_page || 1);
    }
    deleteModalState.value = false;
    itemToDelete.value = null;
    deleteType.value = '';
};

// Year Level functions
const openYearLevelModal = (yearLevel = null) => {
    selectedYearLevel.value = yearLevel;
    if (yearLevel) {
        yearLevelData.value = {
            name: yearLevel.name,
            description: yearLevel.description,
            status: yearLevel.status,
            errors: {}
        };
    } else {
        yearLevelData.value = {
            name: '',
            description: '',
            status: '',
            errors: {}
        };
    }
    yearLevelModalState.value = true;
};

const saveYearLevel = async (YearLevelData) => {
    try{
        if(selectedYearLevel.value){
            await updateYearLevel(selectedYearLevel.value.id, YearLevelData);
        }else{
            await addYearLevel(YearLevelData);
        }
        applyYearLevelFilters(paginationYearLevels.value.current_page || 1);
    }catch(error){
        console.log(error);
    }finally{
        yearLevelModalState.value = false;
        selectedYearLevel.value = null;
    }
};

const editYearLevel = (yearLevel) => {
    openYearLevelModal(yearLevel);
};

const handleViewYearLevel = (yearLevel) => {
    console.log('Viewing year level:', yearLevel);
};

// Section functions
const openSectionModal = (section = null) => {
    selectedSection.value = section;
    if (section) {
        SectionData.value = {
            name: section.name,
            description: section.description,
            status: section.status,
            errors: {}
        };
    } else {
        SectionData.value = {
            name: '',
            description: '',
            status: '',
            errors: {}
        };
    }
    ModalState(true);
};

const editSection = (section) => {
    openSectionModal(section);
};

const handleView = (section) => {
    console.log('Viewing section:', section);
};

const saveSection = async (SectionData) => {
    try{
        if(selectedSection.value){
            await updateSection(selectedSection.value.id, SectionData);
        }else{
            await addSection(SectionData);
        }
        applySectionFilters(paginationSections.value.current_page || 1);
    }catch(error){
        console.log(error);
    }finally{
        ModalState(false);
        selectedSection.value = null;
    }
}

onMounted(() => {
    applySectionFilters(1);
    applyYearLevelFilters(1);
});
</script>

<template>
    <AuthenticatedLayout>
        <!-- Main Container with proper spacing -->
        <div class="min-h-screen bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                
                <!-- Header Section -->
                <div class="mb-8">
                    <h1 class="text-3xl font-light text-black mb-2">Academic Management</h1>
                    <p class="text-gray-500 text-sm">Manage sections and year levels with simplicity</p>
                </div>

                <!-- Control Panel -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
                    
                    <!-- Tab Navigation -->
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex bg-gray-100 rounded-lg p-1">
                            <button
                                @click="activeTable = 'sections'"
                                :class="[
                                    'px-4 py-2 text-sm font-medium rounded-md transition-all',
                                    activeTable === 'sections' 
                                        ? 'bg-white text-black shadow-sm' 
                                        : 'text-gray-600 hover:text-gray-900'
                                ]"
                            >
                                Sections
                            </button>
                            <button
                                @click="activeTable = 'yearlevels'"
                                :class="[
                                    'px-4 py-2 text-sm font-medium rounded-md transition-all',
                                    activeTable === 'yearlevels' 
                                        ? 'bg-white text-black shadow-sm' 
                                        : 'text-gray-600 hover:text-gray-900'
                                ]"
                            >
                                Year Levels
                            </button>
                        </div>

                        <button
                            @click="activeTable === 'sections' ? openSectionModal() : openYearLevelModal()"
                            class="flex items-center gap-2 px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition-colors"
                        >
                            <PlusIcon class="w-4 h-4" />
                            Add {{ activeTable === 'sections' ? 'Section' : 'Year Level' }}
                        </button>
                    </div>

                    <!-- Search and Filter -->
                    <div class="flex items-center gap-4">
                        <div class="relative flex-1 max-w-md">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search records..."
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

                        <select class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:border-gray-400 focus:outline-none">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- Single Table View -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <!-- Sections Table -->
                    <div v-if="activeTable === 'sections'">
                        <Table
                            :pagination="paginationSections"
                            :loading="isLoading"
                            :items="filterSections"
                            :mobileFields="['name', 'description', 'status', 'created_at']"
                            titleField="name"
                            @page-change="applySectionFilters"
                            @edit="editSection"
                            @delete="(item) => openDeleteModal(item, 'section')"
                            @view="handleView"
                        >
                            <template #header>
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                            </template>
                            <template #default>
                                <tr v-for="sec in filterSections" :key="sec.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-900">{{ sec.id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs font-medium text-black">{{ sec.name }}</td>
                                    <td class="px-6 py-4 text-xs text-gray-600">{{ sec.description }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="[
                                            'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                                            sec.status === 'active' ? 'bg-gray-100 text-black' : 'bg-gray-200 text-gray-600'
                                        ]">
                                            {{ sec.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ dayjs(sec.created_at).format("MMM D, YYYY") }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="handleView(sec)" class="text-gray-400 hover:text-gray-600">
                                                <EyeIcon class="w-4 h-4" />
                                            </button>
                                            <button @click="editSection(sec)" class="text-gray-400 hover:text-black">
                                                <PencilIcon class="w-4 h-4" />
                                            </button>
                                            <button @click="openDeleteModal(sec, 'section')" class="text-gray-400 hover:text-gray-600">
                                                <TrashIcon class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </Table>
                    </div>

                    <!-- Year Levels Table -->
                    <div v-if="activeTable === 'yearlevels'">
                        <Table
                            :pagination="paginationYearLevels"
                            :loading="isLoading"
                            :items="filterYearLevels"
                            :mobileFields="['name', 'description', 'status', 'created_at']"
                            titleField="name"
                            @page-change="applyYearLevelFilters"
                            @edit="editYearLevel"
                            @delete="(item) => openDeleteModal(item, 'yearlevel')"
                            @view="handleViewYearLevel"
                        >
                            <template #header>
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                            </template>
                            <template #default>
                                <tr v-for="lvl in filterYearLevels" :key="lvl.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ lvl.id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-black">{{ lvl.name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ lvl.description }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="[
                                            'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                                            lvl.status === 'active' ? 'bg-gray-100 text-black' : 'bg-gray-200 text-gray-600'
                                        ]">
                                            {{ lvl.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="handleViewYearLevel(lvl)" class="text-gray-400 hover:text-gray-600">
                                                <EyeIcon class="w-4 h-4" />
                                            </button>
                                            <button @click="editYearLevel(lvl)" class="text-gray-400 hover:text-black">
                                                <PencilIcon class="w-4 h-4" />
                                            </button>
                                            <button @click="openDeleteModal(lvl, 'yearlevel')" class="text-gray-400 hover:text-gray-600">
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
        </div>

        <!-- Minimalist Section Modal -->
        <Modal :show="modalState" @close="ModalState(false)">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-auto relative">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-black">
                            {{ selectedSection ? "Edit Section" : "Add Section" }}
                        </h3>
                        <button @click="ModalState(false)" class="text-gray-400 hover:text-gray-600">
                            <XIcon class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4">
                    <div>
                        <InputLabel for="name" value="Name" class="text-gray-700 text-sm font-medium" />
                        <TextInput
                            id="name"
                            v-model="SectionData.name"
                            placeholder="Enter section name"
                            class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-gray-400 focus:outline-none"
                        />
                        <InputError :message="SectionData.errors?.name" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Description" class="text-gray-700 text-sm font-medium" />
                        <TextInput
                            id="description"
                            v-model="SectionData.description"
                            placeholder="Enter description"
                            class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-gray-400 focus:outline-none"
                        />
                        <InputError :message="SectionData.errors?.description" />
                    </div>

                    <div>
                        <InputLabel for="status" value="Status" class="text-gray-700 text-sm font-medium" />
                        <select
                            id="status"
                            v-model="SectionData.status"
                            class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-gray-400 focus:outline-none"
                        >
                            <option value="">Select status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button
                        @click="ModalState(false)"
                        class="px-4 py-2 text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 text-sm"
                    >
                        Cancel
                    </button>
                    <button
                        @click="saveSection(SectionData)"
                        class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 text-sm"
                    >
                        {{ selectedSection ? "Update" : "Create" }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Minimalist Year Level Modal -->
        <Modal :show="yearLevelModalState" @close="yearLevelModalState = false">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-auto relative">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-black">
                            {{ selectedYearLevel ? "Edit Year Level" : "Add Year Level" }}
                        </h3>
                        <button @click="yearLevelModalState = false" class="text-gray-400 hover:text-gray-600">
                            <XIcon class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4">
                    <div>
                        <InputLabel for="level" value="Year Level" class="text-gray-700 text-sm font-medium" />
                        <TextInput
                            id="level"
                            v-model="yearLevelData.name"
                            placeholder="e.g., 1st Year"
                            class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-gray-400 focus:outline-none"
                        />
                        <InputError :message="yearLevelData.errors?.name" />
                    </div>

                    <div>
                        <InputLabel for="yearDescription" value="Description" class="text-gray-700 text-sm font-medium" />
                        <TextInput
                            id="yearDescription"
                            v-model="yearLevelData.description"
                            placeholder="Enter description"
                            class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-gray-400 focus:outline-none"
                        />
                        <InputError :message="yearLevelData.errors?.description" />
                    </div>

                    <div>
                        <InputLabel for="yearStatus" value="Status" class="text-gray-700 text-sm font-medium" />
                        <select
                            id="yearStatus"
                            v-model="yearLevelData.status"
                            class="mt-1 w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:border-gray-400 focus:outline-none"
                        >
                            <option value="">Select status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                    <button
                        @click="yearLevelModalState = false"
                        class="px-4 py-2 text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 text-sm"
                    >
                        Cancel
                    </button>
                    <button
                        @click="saveYearLevel(yearLevelData)"
                        class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 text-sm"
                    >
                        {{ selectedYearLevel ? "Update" : "Create" }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Minimalist Delete Modal -->
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
                        Are you sure you want to delete this {{ deleteType === 'section' ? 'section' : 'year level' }}?
                    </p>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="font-medium text-black text-sm">
                            {{ itemToDelete?.name }}
                        </p>
                        <p class="text-gray-500 text-xs mt-1">
                            {{ itemToDelete?.description }}
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
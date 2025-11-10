<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue';
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import { useToast } from 'vue-toastification';
import { ArrowPathIcon, PlusIcon, ArrowDownTrayIcon, XMarkIcon, EllipsisVerticalIcon } from '@heroicons/vue/24/outline';
import { useLaboratoryStore } from '../../composable/laboratory';
import { storeToRefs } from 'pinia';
import { useRouter } from 'vue-router';
import Modal from '../../components/modal/Modal.vue';
import { debounce } from 'lodash-es'
import Button from '../../components/button/Button.vue';
import { useComputerStore } from '../../composable/computers';
import LoaderSpinner from '../../components/spinner/LoaderSpinner.vue';
import Table from '../../components/table/Table.vue';
import axios from 'axios';
import { useApiUrl } from '../../api/api';



const router = useRouter();
const labStore = useLaboratoryStore();
const cstore = useComputerStore();
const toast = useToast();

const {
    laboratories,
    isLoading,
    statusFilter,
    selectedLab,
    isModalOpen,
    isEditMode,
    isImportModalOpen,
    showDropdown,
    addModal,
    searchQuery,
    populateModal,
} = storeToRefs(labStore);
const {
        computers
    } = storeToRefs(cstore);
const {
        fetchLaboratories,
        storeLaboratory,
        updateLaboratory,
        deleteLaboratory,
        selectedLabFilter
    } = labStore;

const { 
        fetchComputers,
        fetchNoLabComputers,
        assignLabToComputer,
        unassignLabFromComputer
    } = cstore;

// Local refs for computer assignment
const assignMode = ref('assign'); // 'assign' or 'unassign'
const selectedLabForUnassign = ref('all');
const assignedComputers = ref([]);
const selectedComputersForUnassign = ref([]);
const unassignedComputers = ref([]);
const selectedComputers = ref([]);
const currentLabId = ref(null);
const allComputers = ref([]);
const modalSearchQuery = ref(''); // Local search query for the modal

const props = defineProps({
    LabName: String,
});

const newLab = reactive({
    name: '',
    code: '',
    description: '',
    status: 'active',
});

const goToLaboratory = (lab) => {
    router.push({ name: 'computer', params: { lab: lab.id } });
};

// Debounced filter function for faster response
const debouncedFilter = debounce(() => {
    fetchLaboratories({
        search: searchQuery.value,
        status: statusFilter.value,
    });
}, 200);

// Watch for search query changes with debounce
watch(searchQuery, () => {
    debouncedFilter();
});

// Watch for status filter changes - apply immediately (no debounce for dropdown)
watch(statusFilter, () => {
    fetchLaboratories({
        search: searchQuery.value,
        status: statusFilter.value,
    });
});

const saveLaboratory = () => {
    try {
        if (selectedLab.value?.id) {
            updateLaboratory(selectedLab.value.id, newLab);
        } else {
            storeLaboratory(newLab);
        }
        // Refresh with current filters
        fetchLaboratories({
            search: searchQuery.value,
            status: statusFilter.value,
        });
        clearForm();
        isModalOpen.value = false;
        toast.success(selectedLab.value?.id ? 'Laboratory updated successfully' : 'Laboratory created successfully');
    } catch (error) {
        console.error('Error saving laboratory:', error);
        toast.error('Failed to save laboratory');
    }
};

const clearForm = () => {
    newLab.id = null;
    newLab.name = '';
    newLab.code = '';
    newLab.description = '';
    newLab.status = 'active';
};

const editlab = (laboratory) => {
    selectedLab.value = laboratory;
    Object.assign(newLab, {
        id: laboratory.id,
        name: laboratory.name,
        code: laboratory.code,
        description: laboratory.description,
        status: laboratory.status,
    });
    isModalOpen.value = true;
};

const openAddModal = () => {
    selectedLab.value = null;
    clearForm();
    isModalOpen.value = true;
};

const toggleDropdown = (id) => {
    showDropdown.value = showDropdown.value === id ? null : id;
};

const openPopulateModal = (labId) => {
    currentLabId.value = labId;
    populateModal.value = true;
    assignMode.value = 'assign';
    selectedComputers.value = [];
    selectedComputersForUnassign.value = [];
    modalSearchQuery.value = ''; // Initialize modal search query
    loadUnassignedComputers();
};

const switchToUnassignMode = () => {
    assignMode.value = 'unassign';
    selectedComputersForUnassign.value = [];
    modalSearchQuery.value = '';
    loadAssignedComputers();
};

const switchToAssignMode = () => {
    assignMode.value = 'assign';
    selectedComputers.value = [];
    modalSearchQuery.value = '';
    loadUnassignedComputers();
};

const toggleComputerSelection = (computerId) => {
    if (selectedComputers.value.includes(computerId)) {
        selectedComputers.value = selectedComputers.value.filter(id => id !== computerId);
    } else {
        selectedComputers.value.push(computerId);
    }
};

const toggleAllComputers = (event) => {
    if (event.target.checked) {
        selectedComputers.value = unassignedComputers.value.map(computer => computer.id);
    } else {
        selectedComputers.value = [];
    }
};

const loadUnassignedComputers = () => {
    fetchNoLabComputers();
    unassignedComputers.value = computers.value.filter(computer => !computer.laboratory_id);
};

const loadAssignedComputers = async () => {
    await fetchComputers({
        laboratory_id: currentLabId.value,
        search: modalSearchQuery.value
    });
    assignedComputers.value = computers.value;
};

const filteredAssignedComputers = computed(() => {
    if (!modalSearchQuery.value) {
        return assignedComputers.value;
    }
    const query = modalSearchQuery.value.toLowerCase();
    return assignedComputers.value.filter(computer => 
        computer.computer_number?.toLowerCase().includes(query) ||
        computer.ip_address?.toLowerCase().includes(query) ||
        computer.mac_address?.toLowerCase().includes(query)
    );
});

const assignComputers = async () => {
    try {
        if (selectedComputers.value.length === 0) {
            toast.error('Please select at least one computer');
            return;
        }

        const success = await assignLabToComputer(selectedComputers.value, currentLabId.value);
        
        if (success) {
            toast.success(`${selectedComputers.value.length} computer(s) assigned successfully`);
            selectedComputers.value = [];
            loadAllComputers(); // Refresh all computers for status display
        }

        populateModal.value = false;
        loadUnassignedComputers();
    } catch (error) {
        toast.error('Failed to assign computers');
        console.error(error);
    }
};

const unassignComputers = async () => {
    try {
        if (selectedComputersForUnassign.value.length === 0) {
            toast.error('Please select at least one computer to unassign');
            return;
        }

        const success = await unassignLabFromComputer(selectedComputersForUnassign.value);
        
        if (success) {
            toast.success(`${selectedComputersForUnassign.value.length} computer(s) unassigned successfully`);
            selectedComputersForUnassign.value = [];
            loadAllComputers(); // Refresh all computers for status display
            loadAssignedComputers();
        }
    } catch (error) {
        toast.error('Failed to unassign computers');
        console.error(error);
    }
};

const toggleComputerForUnassign = (computerId) => {
    if (selectedComputersForUnassign.value.includes(computerId)) {
        selectedComputersForUnassign.value = selectedComputersForUnassign.value.filter(id => id !== computerId);
    } else {
        selectedComputersForUnassign.value.push(computerId);
    }
};

const toggleAllForUnassign = (event) => {
    if (event.target.checked) {
        selectedComputersForUnassign.value = filteredAssignedComputers.value.map(computer => computer.id);
    } else {
        selectedComputersForUnassign.value = [];
    }
};

// Helper function to get online computers count for a lab
const getOnlineComputersCount = (labId) => {
    return allComputers.value.filter(c => c.laboratory_id === labId && c.is_online).length;
};

// Helper function to get total computers count for a lab
const getTotalComputersCount = (labId) => {
    return allComputers.value.filter(c => c.laboratory_id === labId).length;
};

// Helper function to check if any computer is online in a lab
const hasOnlineComputers = (labId) => {
    return allComputers.value.some(c => c.laboratory_id === labId && c.is_online);
};

// Load all computers for status tracking
const loadAllComputers = async () => {
    try {
        const { api, getAuthHeader } = useApiUrl();
        const response = await axios.get(`${api}/computers?include=laboratory`, getAuthHeader());
        allComputers.value = response.data.computers || [];
        console.log('All computers loaded:', allComputers.value.length);
        console.log('Computers with is_online=true:', allComputers.value.filter(c => c.is_online).length);
        
        // Log computers by lab
        laboratories.value.forEach(lab => {
            const labComputers = allComputers.value.filter(c => c.laboratory_id === lab.id);
            const onlineCount = labComputers.filter(c => c.is_online).length;
            console.log(`Lab "${lab.name}" (ID: ${lab.id}): ${labComputers.length} total, ${onlineCount} online`);
        });
    } catch (error) {
        console.error('Error loading all computers:', error);
        allComputers.value = [];
        toast.error('Failed to load computer status');
    }
};

onMounted(() => {
    fetchLaboratories({
        search: searchQuery.value,
        status: statusFilter.value,
    });
    loadAllComputers(); // Load all computers for status display
    loadUnassignedComputers();
});
</script>
<template>
    <AuthenticatedLayout>
        <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white min-h-screen relative">
            <LoaderSpinner :isLoading="isLoading" subMessage="Loading laboratories..." />
            
            <div v-show="!isLoading">
                <!-- Header Section -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <!-- Page Title -->
                        <div>
                            <h1 class="text-xl font-semibold text-gray-900">Laboratory Management</h1>
                            <p class="text-sm text-gray-600 mt-0.5">Manage and organize your computer laboratories</p>
                        </div>

                        <!-- Filters and Actions Row -->
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Search Input -->
                            <div class="w-64">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </div>
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search laboratories..."
                                        class="block w-full pl-9 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-200 focus:border-gray-400 text-sm transition-colors bg-white"
                                    />
                                    <button
                                        v-if="searchQuery"
                                        @click="searchQuery = ''"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                    >
                                        <XMarkIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <!-- Status Filter -->
                            <div class="w-40">
                                <select
                                    v-model="statusFilter"
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-200 focus:border-gray-400 text-sm bg-white transition-colors"
                                >
                                    <option value="all">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>

                            <!-- Results Count -->
                            <div class="text-xs text-gray-600 bg-gray-100 px-3 py-2 rounded-lg border border-gray-200">
                                {{ laboratories.length }} result{{ laboratories.length !== 1 ? 's' : '' }}
                            </div>

                            <!-- Add Button -->
                            <Button
                                @click="openAddModal"
                                class="inline-flex items-center gap-2 px-3 py-2 bg-gray-700 text-white text-xs font-medium rounded-lg hover:bg-gray-600 transition-colors shadow-sm"
                            >
                                <PlusIcon class="h-4 w-4" />
                                Add Laboratory
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Section -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Laboratory Cards Grid -->
                <div v-if="laboratories.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div
                        v-for="lab in laboratories"
                        :key="lab.id"
                        @click="goToLaboratory(lab)"
                        class="group relative bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md hover:border-gray-300 transition-all duration-200 cursor-pointer overflow-hidden"
                    >
                        <!-- Card Header -->
                        <div class="p-4 pb-3">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1">
                                    <h3 class="text-base font-semibold text-gray-900 group-hover:text-gray-700 transition-colors">
                                        {{ lab.name }}
                                    </h3>
                                    <p class="text-xs text-gray-600 mt-1 font-mono bg-gray-50 px-2 py-0.5 rounded inline-block border border-gray-200">
                                        {{ lab.code }}
                                    </p>
                                </div>

                                <!-- Dropdown Menu -->
                                <div class="relative">
                                    <button 
                                        @click.stop="toggleDropdown(lab.id)"
                                        class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-50 rounded-lg transition-colors"
                                    >
                                        <EllipsisVerticalIcon class="h-4 w-4" />
                                    </button>

                                    <div
                                        v-if="showDropdown === lab.id"
                                        class="absolute right-0 top-full mt-1 w-36 bg-white rounded-lg shadow-lg border border-gray-200 z-20 py-1"
                                    >
                                        <button 
                                            @click.stop="editlab(lab)"
                                            class="flex items-center w-full px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            Edit Lab
                                        </button>
                                        <button 
                                            @click.stop="deleteLaboratory(lab.id)"
                                            class="flex items-center w-full px-3 py-2 text-xs text-red-600 hover:bg-red-50 transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <p v-if="lab.description" class="text-xs text-gray-600 line-clamp-2 mb-3">
                                {{ lab.description }}
                            </p>
                            
                            <!-- Computer Stats -->
                            <div class="flex items-center gap-2 mt-3">
                                <!-- Total Computers -->
                                <div class="flex items-center gap-1.5 px-2 py-1 bg-gray-100 border border-gray-200 rounded-md">
                                    <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-xs font-medium text-gray-700">
                                        {{ getTotalComputersCount(lab.id) }} PCs
                                    </span>
                                </div>
                                
                                <!-- Online/Occupied Status -->
                                <div v-if="hasOnlineComputers(lab.id)" class="flex items-center gap-1.5 px-2 py-1 bg-green-50 border border-green-200 rounded-md">
                                    <div class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></div>
                                    <span class="text-xs font-medium text-green-700">
                                        {{ getOnlineComputersCount(lab.id) }} online
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <!-- Status Indicator -->
                                <div class="flex items-center">
                                    <div :class="{
                                        'w-2 h-2 rounded-full mr-1.5': true,
                                        'bg-emerald-500': lab.status === 'active',
                                        'bg-gray-400': lab.status === 'inactive',
                                        'bg-yellow-500': lab.status === 'maintenance'
                                    }"></div>
                                    <span class="text-xs font-medium text-gray-700 capitalize">
                                        {{ lab.status }}
                                    </span>
                                </div>
                                
                                <!-- Occupied Badge -->
                                <div v-if="hasOnlineComputers(lab.id)" class="flex items-center gap-1.5 px-2 py-1 bg-orange-50 border border-orange-200 rounded-md">
                                    <svg class="w-3 h-3 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-xs font-semibold text-orange-700 uppercase tracking-wide">
                                        Occupied
                                    </span>
                                </div>
                            </div>

                            <Button
                                @click.stop="openPopulateModal(lab.id)"
                                class="text-xs px-2.5 py-1.5 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition-colors font-medium"
                            >
                                Assign PCs
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-16 bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="max-w-md mx-auto px-4">
                        <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <h3 class="text-base font-medium text-gray-900 mb-1">No laboratories found</h3>
                        <p class="text-sm text-gray-500 mb-4">Get started by creating your first laboratory.</p>
                        <Button
                            @click="openAddModal"
                            class="inline-flex items-center gap-2 px-3 py-2 bg-gray-700 text-white text-xs font-medium rounded-lg hover:bg-gray-600 transition-colors"
                        >
                            <PlusIcon class="h-4 w-4" />
                            Add Laboratory
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Computer Assignment Modal -->
            <Modal :show="populateModal" @close="populateModal = false">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-6xl mx-auto relative overflow-hidden border border-gray-200">
                    <!-- Modal Header -->
                    <div class="px-4 py-3 border-b border-gray-100 bg-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900">Manage Laboratory Computers</h2>
                                <p class="text-gray-600 text-xs mt-0.5">
                                    {{ assignMode === 'assign' ? 'Select unassigned computers to add to this laboratory' : 'Select assigned computers to remove from this laboratory' }}
                                </p>
                            </div>
                            
                            <!-- Mode Toggle -->
                            <div class="flex gap-1 bg-gray-100 p-1 rounded-lg">
                                <button
                                    @click="switchToAssignMode"
                                    :class="[
                                        'px-3 py-1.5 text-xs font-medium rounded-md transition-all',
                                        assignMode === 'assign' 
                                            ? 'bg-white text-gray-900 shadow-sm' 
                                            : 'text-gray-600 hover:text-gray-900'
                                    ]"
                                >
                                    Assign Mode
                                </button>
                                <button
                                    @click="switchToUnassignMode"
                                    :class="[
                                        'px-3 py-1.5 text-xs font-medium rounded-md transition-all',
                                        assignMode === 'unassign' 
                                            ? 'bg-white text-gray-900 shadow-sm' 
                                            : 'text-gray-600 hover:text-gray-900'
                                    ]"
                                >
                                    Unassign Mode
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Content -->
                    <div class="px-4 py-4">
                        <!-- Assign Mode Content -->
                        <div v-if="assignMode === 'assign'">
                            <div v-if="unassignedComputers.length > 0" class="max-h-96 overflow-y-auto rounded-lg border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <input
                                                    type="checkbox"
                                                    @change="toggleAllComputers($event)"
                                                    class="h-3.5 w-3.5 text-gray-600 focus:ring-gray-300 border-gray-300 rounded"
                                                />
                                            </th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Computer #
                                            </th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                IP Address
                                            </th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                MAC Address
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="computer in unassignedComputers" :key="computer.id" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <input
                                                    type="checkbox"
                                                    :checked="selectedComputers.includes(computer.id)"
                                                    @change="toggleComputerSelection(computer.id)"
                                                    class="h-3.5 w-3.5 text-gray-600 focus:ring-gray-300 border-gray-300 rounded"
                                                />
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-xs font-medium text-gray-900">{{ computer.computer_number }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-xs text-gray-600 font-mono">{{ computer.ip_address }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-xs text-gray-600 font-mono">{{ computer.mac_address || 'N/A' }}</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div v-else class="text-center py-12 bg-gray-50 rounded-lg border border-gray-200">
                                <svg class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <h3 class="text-xs font-medium text-gray-900 mb-1">No unassigned computers</h3>
                                <p class="text-xs text-gray-500">All computers are currently assigned to laboratories.</p>
                            </div>
                        </div>

                        <!-- Unassign Mode Content -->
                        <div v-else>
                            <!-- Search Filter -->
                            <div class="mb-3">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </div>
                                    <input
                                        v-model="modalSearchQuery"
                                        type="text"
                                        placeholder="Search computers..."
                                        class="block w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-200 focus:border-gray-400 text-sm transition-colors bg-white"
                                    />
                                </div>
                            </div>

                            <div v-if="filteredAssignedComputers.length > 0" class="max-h-96 overflow-y-auto rounded-lg border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <input
                                                    type="checkbox"
                                                    @change="toggleAllForUnassign($event)"
                                                    :checked="selectedComputersForUnassign.length === filteredAssignedComputers.length && filteredAssignedComputers.length > 0"
                                                    class="h-3.5 w-3.5 text-gray-600 focus:ring-gray-300 border-gray-300 rounded"
                                                />
                                            </th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Computer #
                                            </th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                IP Address
                                            </th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                MAC Address
                                            </th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="computer in filteredAssignedComputers" :key="computer.id" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <input
                                                    type="checkbox"
                                                    :checked="selectedComputersForUnassign.includes(computer.id)"
                                                    @change="toggleComputerForUnassign(computer.id)"
                                                    class="h-3.5 w-3.5 text-gray-600 focus:ring-gray-300 border-gray-300 rounded"
                                                />
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-xs font-medium text-gray-900">{{ computer.computer_number }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-xs text-gray-600 font-mono">{{ computer.ip_address }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-xs text-gray-600 font-mono">{{ computer.mac_address || 'N/A' }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span :class="[
                                                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                                                    computer.status === 'active' ? 'bg-green-100 text-green-800' :
                                                    computer.status === 'inactive' ? 'bg-gray-100 text-gray-800' :
                                                    'bg-yellow-100 text-yellow-800'
                                                ]">
                                                    {{ computer.status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div v-else class="text-center py-12 bg-gray-50 rounded-lg border border-gray-200">
                                <svg class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <h3 class="text-xs font-medium text-gray-900 mb-1">No assigned computers found</h3>
                                <p class="text-xs text-gray-500">This laboratory has no computers assigned{{ modalSearchQuery ? ' matching your search' : '' }}.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                        <div v-if="assignMode === 'assign' && selectedComputers.length > 0" class="text-xs text-gray-600">
                            {{ selectedComputers.length }} computer{{ selectedComputers.length !== 1 ? 's' : '' }} selected
                        </div>
                        <div v-else-if="assignMode === 'unassign' && selectedComputersForUnassign.length > 0" class="text-xs text-gray-600">
                            {{ selectedComputersForUnassign.length }} computer{{ selectedComputersForUnassign.length !== 1 ? 's' : '' }} selected
                        </div>
                        <div v-else></div>
                        
                        <div class="flex gap-2">
                            <Button
                                @click="populateModal = false"
                                class="px-3 py-2 text-xs text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                Close
                            </Button>
                            <Button
                                v-if="assignMode === 'assign'"
                                @click="assignComputers"
                                :disabled="selectedComputers.length === 0"
                                class="px-4 py-2 text-xs bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Assign Selected ({{ selectedComputers.length }})
                            </Button>
                            <Button
                                v-else
                                @click="unassignComputers"
                                :disabled="selectedComputersForUnassign.length === 0"
                                class="px-4 py-2 text-xs bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Unassign Selected ({{ selectedComputersForUnassign.length }})
                            </Button>
                        </div>
                    </div>
                </div>
            </Modal>

            <!-- Add/Edit Laboratory Modal -->
            <Modal :show="isModalOpen" @close="isModalOpen = false">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-auto relative border border-gray-200">
                    <!-- Modal Header -->
                    <div class="px-4 py-3 border-b border-gray-100 bg-white">
                        <h2 class="text-base font-semibold text-gray-900">
                            {{ selectedLab ? 'Edit Laboratory' : 'Add Laboratory' }}
                        </h2>
                    </div>

                    <!-- Modal Content -->
                    <div class="px-4 py-4">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">Laboratory Name</label>
                                <input
                                    v-model="newLab.name"
                                    type="text"
                                    required
                                    placeholder="Enter laboratory name"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-gray-400 focus:ring-2 focus:ring-gray-200 bg-white transition"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">Laboratory Code</label>
                                <input
                                    v-model="newLab.code"
                                    type="text"
                                    required
                                    placeholder="LAB-001"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-gray-400 focus:ring-2 focus:ring-gray-200 bg-white transition font-mono"
                                />
                            </div>
                            
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">Description</label>
                                <textarea
                                    v-model="newLab.description"
                                    rows="3"
                                    placeholder="Enter description (optional)"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-gray-400 focus:ring-2 focus:ring-gray-200 bg-white transition resize-none"
                                ></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">Status</label>
                                <select
                                    v-model="newLab.status"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-gray-400 focus:ring-2 focus:ring-gray-200 bg-white transition"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal Footer -->
                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-100 rounded-b-xl flex justify-end gap-2">
                        <Button
                            type="button"
                            @click="isModalOpen = false"
                            class="px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                        >
                            Cancel
                        </Button>
                        <Button
                            @click="saveLaboratory"
                            type="submit"
                            class="px-4 py-2 text-xs font-medium text-white bg-gray-700 rounded-lg hover:bg-gray-600 transition"
                        >
                            Save
                        </Button>
                    </div>
                </div>
            </Modal>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
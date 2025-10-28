<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import { useToast } from 'vue-toastification';
import { ArrowPathIcon, PlusIcon, ArrowDownTrayIcon, XMarkIcon, EllipsisVerticalIcon } from '@heroicons/vue/24/outline';
import { useLaboratoryStore } from '../../composable/laboratory';
import { storeToRefs } from 'pinia';
import { useRouter } from 'vue-router';
import Modal from '../../components/modal/Modal.vue';
import { Ellipsis } from 'lucide-vue-next';
import Button from '../../components/button/Button.vue';
import { useComputerStore } from '../../composable/computers';

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
    unassignedComputers,
    selectedComputers,
    currentLabId,
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
        assignLabToComputer
    } = cstore;

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

const filterLaboratories = computed(() => {
    return (laboratories.value || []).filter((lab) => {
        const matchesQuery = lab.name?.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesStatus = statusFilter.value === 'all' || lab.status === statusFilter.value;
        return matchesQuery && matchesStatus;
    });
});

const saveLaboratory = () => {
    try {
        if (selectedLab.value?.id) {
            updateLaboratory(selectedLab.value.id, newLab);
        } else {
            storeLaboratory(newLab);
        }
        fetchLaboratories();
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
    selectedComputers.value = [];
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
        }

        populateModal.value = false;
        loadUnassignedComputers();
    } catch (error) {
        toast.error('Failed to assign computers');
        console.error(error);
    }
};

onMounted(() => {
    fetchLaboratories();
    loadUnassignedComputers();
});
</script>
<template>
    <AuthenticatedLayout>
         <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white">
            <!-- Header Section -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                    <div>
                        <h1 class="text-2xl text-gray-900">Laboratory Management</h1>
                        <p class="text-gray-600 mt-1">Manage and organize your computer laboratories</p>
                    </div>

                    <div class="flex items-center mt-4 sm:mt-0">
                        <Button
                            @click="openAddModal"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-black transition-all duration-200 shadow-sm"
                        >
                            <PlusIcon class="h-4 w-4" />
                            Add Laboratory
                        </Button>
                    </div>
                </div>

            <!-- Content Section -->
            <div class="max-w-7xl mx-auto px-6 py-8">
                <!-- Search and Filter Bar -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
                    <div class="flex flex-wrap items-center gap-4">
                        <!-- Search Input -->
                        <div class="flex-1 min-w-64">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search laboratories..."
                                    class="block w-full pl-10 pr-12 py-3 border border-gray-200 rounded-lg focus:ring-1 focus:ring-gray-300 focus:border-gray-400 text-sm transition-colors bg-gray-50 focus:bg-white"
                                />
                                <button
                                    v-if="searchQuery"
                                    @click="searchQuery = ''"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                >
                                    <XMarkIcon class="h-5 w-5" />
                                </button>
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div class="min-w-48">
                            <select
                                v-model="statusFilter"
                                class="block w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-1 focus:ring-gray-300 focus:border-gray-400 text-sm bg-gray-50 focus:bg-white transition-colors"
                            >
                                <option value="all">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>

                        <!-- Results Count -->
                        <div class="text-sm text-gray-500 bg-gray-100 px-3 py-2 rounded-lg">
                            {{ filterLaboratories.length }} result{{ filterLaboratories.length !== 1 ? 's' : '' }}
                        </div>
                    </div>
                </div>

                <!-- Laboratory Cards Grid -->
                <div v-if="filterLaboratories.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <div
                        v-for="lab in filterLaboratories"
                        :key="lab.id"
                        @click="goToLaboratory(lab)"
                        class="group relative bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md hover:border-gray-300 transition-all duration-200 cursor-pointer overflow-hidden"
                    >
                        <!-- Card Header -->
                        <div class="p-6 pb-4">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 group-hover:text-gray-600 transition-colors">
                                        {{ lab.name }}
                                    </h3>
                                    <p class="text-sm text-gray-600 mt-1 font-mono bg-gray-100 px-2 py-1 rounded inline-block">
                                        {{ lab.code }}
                                    </p>
                                </div>

                                <!-- Dropdown Menu -->
                                <div class="relative">
                                    <button 
                                        @click.stop="toggleDropdown(lab.id)"
                                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                                    >
                                        <EllipsisVerticalIcon class="h-5 w-5" />
                                    </button>

                                    <div
                                        v-if="showDropdown === lab.id"
                                        class="absolute right-0 top-full mt-1 w-40 bg-white rounded-lg shadow-lg border border-gray-200 z-20 py-2"
                                    >
                                        <button 
                                            @click.stop="editlab(lab)"
                                            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                                        >
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            Edit Lab
                                        </button>
                                        <button 
                                            @click.stop="deleteLaboratory(lab.id)"
                                            class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
                                        >
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <p v-if="lab.description" class="text-sm text-gray-600 line-clamp-2 mb-4">
                                {{ lab.description }}
                            </p>
                        </div>

                        <!-- Card Footer -->
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex items-center">
                                    <div :class="{
                                        'w-2.5 h-2.5 rounded-full mr-2': true,
                                        'bg-emerald-500': lab.status === 'active',
                                        'bg-gray-400': lab.status === 'inactive',
                                        'bg-yellow-500': lab.status === 'maintenance'
                                    }"></div>
                                    <span class="text-xs font-medium text-gray-700 capitalize">
                                        {{ lab.status }}
                                    </span>
                                </div>
                            </div>

                            <Button
                                @click.stop="openPopulateModal(lab.id)"
                                class="text-xs px-3 py-1.5 bg-gray-900 text-white rounded-md hover:bg-black transition-colors font-medium"
                            >
                                Assign PCs
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-16">
                    <div class="max-w-md mx-auto">
                        <svg class="mx-auto h-16 w-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No laboratories found</h3>
                        <p class="text-gray-500 mb-6">Get started by creating your first laboratory.</p>
                        <Button
                            @click="openAddModal"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-black transition-colors"
                        >
                            <PlusIcon class="h-4 w-4" />
                            Add Laboratory
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Computer Assignment Modal -->
            <Modal :show="populateModal" @close="populateModal = false">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl mx-auto relative overflow-hidden">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="text-xl font-semibold text-gray-900">Assign Computers to Laboratory</h2>
                        <p class="text-gray-600 text-sm mt-1">Select unassigned computers to add to this laboratory</p>
                    </div>

                    <!-- Modal Content -->
                    <div class="px-6 py-5">
                        <div v-if="unassignedComputers.length > 0" class="max-h-96 overflow-y-auto rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <input
                                                type="checkbox"
                                                @change="toggleAllComputers($event)"
                                                class="h-4 w-4 text-gray-600 focus:ring-gray-300 border-gray-300 rounded"
                                            />
                                        </th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Computer #
                                        </th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            IP Address
                                        </th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            MAC Address
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="computer in unassignedComputers" :key="computer.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input
                                                type="checkbox"
                                                :checked="selectedComputers.includes(computer.id)"
                                                @change="toggleComputerSelection(computer.id)"
                                                class="h-4 w-4 text-gray-600 focus:ring-gray-300 border-gray-300 rounded"
                                            />
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ computer.computer_number }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-600 font-mono">{{ computer.ip_address }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-600 font-mono">{{ computer.mac_address || 'N/A' }}</div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <h3 class="text-sm font-medium text-gray-900 mb-1">No unassigned computers</h3>
                            <p class="text-sm text-gray-500">All computers are currently assigned to laboratories.</p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                        <div v-if="selectedComputers.length > 0" class="text-sm text-gray-600">
                            {{ selectedComputers.length }} computer{{ selectedComputers.length !== 1 ? 's' : '' }} selected
                        </div>
                        <div v-else></div>
                        
                        <div class="flex gap-3">
                            <Button
                                @click="populateModal = false"
                                class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                Cancel
                            </Button>
                            <Button
                                @click="assignComputers"
                                :disabled="selectedComputers.length === 0"
                                class="px-6 py-2 text-sm bg-gray-900 text-white rounded-lg hover:bg-black transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Assign Selected ({{ selectedComputers.length }})
                            </Button>
                        </div>
                    </div>
                </div>
            </Modal>

            <!-- Add/Edit Laboratory Modal -->
            <Modal :show="isModalOpen" @close="isModalOpen = false">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-auto relative">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="text-xl font-semibold text-gray-900">
                            {{ selectedLab ? 'Edit Laboratory' : 'Add Laboratory' }}
                        </h2>
                    </div>

                    <!-- Modal Content -->
                    <div class="px-6 py-5">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Laboratory Name</label>
                                <input
                                    v-model="newLab.name"
                                    type="text"
                                    required
                                    placeholder="Enter laboratory name"
                                    class="w-full px-3 py-2 border border-gray-200 rounded-md text-sm focus:border-gray-400 focus:ring-1 focus:ring-gray-200 bg-gray-50 focus:bg-white transition"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Laboratory Code</label>
                                <input
                                    v-model="newLab.code"
                                    type="text"
                                    required
                                    placeholder="LAB-001"
                                    class="w-full px-3 py-2 border border-gray-200 rounded-md text-sm focus:border-gray-400 focus:ring-1 focus:ring-gray-200 bg-gray-50 focus:bg-white transition font-mono"
                                />
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea
                                    v-model="newLab.description"
                                    rows="3"
                                    placeholder="Enter description (optional)"
                                    class="w-full px-3 py-2 border border-gray-200 rounded-md text-sm focus:border-gray-400 focus:ring-1 focus:ring-gray-200 bg-gray-50 focus:bg-white transition resize-none"
                                ></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select
                                    v-model="newLab.status"
                                    class="w-full px-3 py-2 border border-gray-200 rounded-md text-sm focus:border-gray-400 focus:ring-1 focus:ring-gray-200 bg-gray-50 focus:bg-white transition"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal Footer -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                        <Button
                            type="button"
                            @click="isModalOpen = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition"
                        >
                            Cancel
                        </Button>
                        <Button
                            @click="saveLaboratory"
                            type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-md hover:bg-black transition"
                        >
                            Save
                        </Button>
                    </div>
                </div>
            </Modal>
        </div>
    </AuthenticatedLayout>
</template>
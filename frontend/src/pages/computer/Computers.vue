<script setup>

import { ref, computed, reactive, onMounted, onUnmounted, watch, nextTick, toRefs } from 'vue';
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import { useToast } from 'vue-toastification';
import { EllipsisVerticalIcon } from '@heroicons/vue/16/solid';
import TextInput from '../../components/input/TextInput.vue';
import Modal from '../../components/modal/Modal.vue';
import StudentAssignmentModal from '../../components/modal/StudentAssignmentModal.vue';
import { useComputerStore} from '../../composable/computers';
import { useStates } from '../../composable/states';
import { XIcon, TrashIcon } from 'lucide-vue-next';
import LoaderSpinner from '../../components/spinner/LoaderSpinner.vue';
import { debounce } from 'lodash-es';
const toast = useToast();

// Stores
const states = useStates();
const func = useComputerStore();
const form = reactive({ rfid_uid: '' });
const rfidInputRef = ref(null);
const newComputer = reactive({
  computer_number: '',
  ip_address: '',
  mac_address: '',
  laboratory_id: 1,
  status: 'active',
  is_online: false,
  is_lock: false
});

const {
      computers,
      selectedLab,
      selectedStatus,
      selectedComputer,
      selectedComputerForAssignment,
      modalState,
      isLoading,
      isSuccess,
      showAssignmentModal,
      saveModal,
      isDropdownOpen,
      isSubmitting,
      hasError,
      errorMessage,
      successMessage,
      searchQuery
      } = toRefs(states);

// Delete modal state
const showDeleteModal = ref(false);
const computerToDelete = ref(null);

const {
    fetchComputers,
    fetchLabs,
    storeComputer,
    updateComputer,
    deleteComputer,
    unlockComputer,
    unlockByAdmin
      } = func;

// Open assignment modal
const openAssignmentModal = (computer) => {
  selectedComputerForAssignment.value = computer;
  showAssignmentModal.value = true;
  isDropdownOpen.value = null;
};

// Close assignment modal
const closeAssignmentModal = () => {
  showAssignmentModal.value = false;
  selectedComputerForAssignment.value = null;
};

// Handle successful student assignment
const handleStudentAssignment = (data) => {
  applyFilters(); // Refresh with current filters
  
  if (data.students) {
    toast.success(`Successfully assigned ${data.students.length} student(s) to Computer ${data.computer.computer_number}`);
  } else if (data.student) {
    toast.success(`Assigned ${data.student.first_name} ${data.student.last_name} to Computer ${data.computer.computer_number}`);
  }
};

// Remove client-side filtering - now handled by backend
const filteredComputers = computed(() => {
  return computers.value;
});

// Debounced function to apply filters
const applyFilters = debounce(() => {
  fetchComputers({
    search: searchQuery.value,
    laboratory_id: selectedLab.value,
    status: selectedStatus.value
  });
}, 300);

// Watch for filter changes
watch([searchQuery, selectedLab, selectedStatus], () => {
  applyFilters();
});

// Clear all filters
const clearFilters = () => {
  selectedLab.value = 'all';
  selectedStatus.value = 'all';
  searchQuery.value = '';
};

const openModal = (computer) => {
  selectedComputer.value = computer;
  modalState.value = true;
  hasError.value = false;
  isSuccess.value = false;
  form.tag = '';
};

const closeModal = () => {
  if (isSubmitting.value) return;
  modalState.value = false;
  selectedComputer.value = null;
  form.tag = '';
};

const toggleDropdown = (id) => {
  isDropdownOpen.value = isDropdownOpen.value === id ? null : id;
};

const openAddComputerModal = () => {
  selectedComputer.value = null;
  Object.assign(newComputer, {
    computer_number: '',
    ip_address: '',
    laboratory_id: func.labs?.[0]?.id || 1,
    status: 'active'
  });
  saveModal.value = true;
};

const saveComputer = async () => {
  try {
    if (selectedComputer.value) {
      await updateComputer(selectedComputer.value.id, newComputer);
    } else {
      await storeComputer(newComputer);
    }
    applyFilters(); // Refresh with current filters
    saveModal.value = false;
  } catch (error) {
    toast.error('Failed to save computer.');
    console.error(error);
  }
};

const editComputer = (computer) => {
  selectedComputer.value = computer;
  Object.assign(newComputer, {
    computer_number: computer.computer_number?.toString() ?? '',
    ip_address: computer.ip_address ?? '',
    mac_address: computer.mac_address ?? '',
    laboratory_id: computer.laboratory_id ?? func.labs?.[0]?.id ?? 1,
    status: computer.status ?? 'active',
    is_online: computer.is_online ?? false,
    is_lock: computer.is_lock ?? false
  });
  saveModal.value = true;
  isDropdownOpen.value = null;
};

const openDeleteModal = (computer) => {
  computerToDelete.value = computer;
  showDeleteModal.value = true;
  isDropdownOpen.value = false;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  computerToDelete.value = null;
  isDropdownOpen.value = false;
};

const confirmDelete = async () => {
  if (computerToDelete.value) {
    try {
      await deleteComputer(computerToDelete.value.id);
      toast.success('Computer deleted successfully');
      closeDeleteModal();
      applyFilters();
    } catch (error) {
      toast.error('Failed to delete computer');
      console.error(error);
    }
  }
};

const deleteComputer_func = async (computer) => {
  openDeleteModal(computer);
};

const clearError = () => {
  hasError.value = false;
  errorMessage.value = '';
};

const unlock_function = async () => {
  if (!form.rfid_uid) {
    hasError.value = true;
    errorMessage.value = 'Please enter a tag.';
    toast.error(errorMessage.value);
    return;
  }
  
  isSubmitting.value = true;
  
  try {
    const success = await unlockComputer(selectedComputer.value.id, form.rfid_uid);
    
    if (success) {
      isSuccess.value = true;
      successMessage.value = 'Computer unlocked successfully!';
      setTimeout(() => {
        isSubmitting.value = false;
        closeModal();
      }, 1200);
    } else {
      isSubmitting.value = false;
    }
  } catch (error) {
    hasError.value = true;
    errorMessage.value = 'Failed to unlock computer.';
    isSubmitting.value = false;
  }
};
const handleComputerEvent = ({ action, computer }) => {
  const index = func.computers.value.findIndex(c => c.id === computer.id);

  if (action === "update" && index !== -1) {
    func.computers.value.splice(index, 1, { ...computer });
  }

  if (action === "add" && index === -1) {
    func.computers.value.splice(computers.value.length, 0, { ...computer });
  }

  if (action === "delete" && index !== -1) {
    func.computers.value.splice(index, 1);
  }
};


const EventListener = () => {
 if (!window.Echo) {
    console.log('Echo is not available');
    return;
  }

  window.Echo.channel('computers')
    .listen('.ComputerEvent', (e) => {
      console.log("📡 Computer update received:", e.computer);
      applyFilters(); // Refresh with current filters
      // handleComputerEvent(e);
    });
}
onMounted(async () => {
  EventListener();
  applyFilters(); // Initial load with filters
  fetchLabs();
  
  window.Echo.channel('test-event')
    .listen('.TestEvent', (e) => {
        console.log("📡 Event received:", e.message);
        alert(e.message);
    });
  
});


watch(modalState, async (newVal) => {
  if (newVal) {
    await nextTick();
    rfidInputRef.value?.focus();
  } else {
    form.tag = '';
  }
});
</script>

<style scoped>
@keyframes loading {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}
</style>

<template>
  <AuthenticatedLayout>
    <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white min-h-screen relative">
      <!-- Enhanced Loading Overlay - Fixed Position -->
        <LoaderSpinner :is-loading="isLoading" subMessage="Please wait while we fetch your data" />

      <div>
        <h2 class="text-2xl text-gray-900">Computer Management</h2>
         <p class="mt-1 text-sm text-gray-600">Manage computers, unlock remotely, and perform CRUD.</p>
      </div>

      <!-- Filters + Add -->
      <div class="flex flex-col lg:flex-row gap-4 justify-between items-start lg:items-end mb-6 mt-5">
        <!-- Filters -->
        <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-end flex-1">

          <!-- Search Filter-->
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
          <!-- Lab -->
          <div class="w-full sm:w-auto min-w-[200px]">
            <select
              v-model="selectedLab"
              class="w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
            >
              <option value="all">All Laboratories</option>
              <option v-for="lab in func.labs || []" :key="lab.id" :value="lab.id">
                {{ lab.name }}
              </option>
            </select>
          </div>

          <!-- Status -->
          <div class="w-full sm:w-auto min-w-[160px]">
            <select
              v-model="selectedStatus"
              class="w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
            >
              <option value="all">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="maintenance">Maintenance</option>
            </select>
          </div>

          <!-- Clear Filters -->
          <div class="w-ful sm-w-auto min-w-auto">
            <button
              @click="clearFilters"
              class="px-3 py-2 text-xs text-gray-600 hover:text-gray-800 transition-colors">
              Clear Filters
            </button>
          </div>
        </div>

        <!-- Add -->
        <div class="w-full sm:w-auto">
          <button
            @click="openAddComputerModal"
            class="w-full sm:w-auto px-4 py-2 bg-gray-700 text-white rounded-md hover:bg-gray-600 transition flex items-center justify-center gap-2 text-sm"
          >
           
            <span>Add Computer</span>
          </button>
        </div>
      </div>

      <!-- Divider -->
      <hr class="border-gray-200 mb-6" />

      <!-- Computer Cards Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <div
          v-for="computer in filteredComputers"
          :key="computer.id"
          class="relative group"
        >
          <!-- Menu -->
          <button
            @click.stop="toggleDropdown(computer.id)"
            class="absolute top-3 right-3 p-1.5 hover:bg-gray-100 rounded-md text-gray-400 hover:text-gray-700 transition-colors opacity-0 group-hover:opacity-100 z-20"
          >
            <EllipsisVerticalIcon class="h-4 w-4" />
          </button>

          <div
            v-if="isDropdownOpen === computer.id"
            class="absolute right-0 top-10 w-40 bg-white rounded-lg shadow-xl border border-gray-200 z-30 py-1 text-sm"
          >
            <button @click.stop="openAssignmentModal(computer)" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-50 transition-colors">
              Assign Student
            </button>
            <button @click.stop="openModal(computer)" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-50 transition-colors">
              Unlock by RFID
            </button>
            <button @click.stop="unlockByAdmin(computer.id)"
            class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-50 transition-colors"
            >
              Unlock directly
            </button>
            <button @click.stop="editComputer(computer)" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-50 transition-colors">
              Edit
            </button>
            <button @click.stop="deleteComputer_func(computer)" class="block w-full text-left px-4 py-2 text-red-600 hover:bg-red-50 transition-colors">
              Delete
            </button>
          </div>

          <!-- Computer Card - Click to assign students -->
          <button
            @click="openAssignmentModal(computer)"
            class="w-full p-4 bg-white border-2 border-gray-200 rounded-lg hover:border-gray-400 hover:shadow-lg transition-all duration-200 text-left"
          >
            <div class="flex items-center justify-between mb-3">
              <span class="text-2xl font-bold text-gray-900 group-hover:text-gray-700 transition-colors">
                PC {{ computer.computer_number }}
              </span>
              <div class="flex items-center gap-2">
                <div 
                  class="w-2 h-2 rounded-full"
                  :class="computer.is_online ? 'bg-green-500 animate-pulse' : 'bg-gray-300'"
                ></div>
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium border"
                  :class="computer.is_lock 
                    ? 'bg-red-50 text-red-700 border-red-200' 
                    : 'bg-green-50 text-green-700 border-green-200'"
                >
                  {{ computer.is_lock ? 'Locked' : 'Unlocked' }}
                </span>
              </div>
            </div>
            
            <div class="space-y-2">
              <p class="text-sm text-gray-600">
                <span class="font-medium text-gray-900">Lab:</span> {{ func.labs?.find(l => l.id === computer.laboratory_id)?.name || 'N/A' }}
              </p>
              <p class="text-sm text-gray-600 font-mono">
                <span class="font-medium text-gray-900">IP:</span> {{ computer.ip_address || 'N/A' }}
              </p>
              <p class="text-sm text-gray-600 font-mono">
              </p>
              
              <!-- Assigned students -->
              <div v-if="computer.assigned_students_count" class="pt-1">
                <span class="inline-flex items-center px-2 py-1 bg-blue-50 border border-blue-100 rounded-md text-blue-700 text-xs font-medium">
                  👥 {{ computer.assigned_students_count }} student{{ computer.assigned_students_count > 1 ? 's' : '' }}
                </span>
              </div>
              
              <div class="mt-2">
                <span
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                  :class="{
                    'bg-green-100 text-green-800': computer.status === 'active',
                    'bg-red-100 text-red-800': computer.status === 'inactive',
                    'bg-yellow-100 text-yellow-800': computer.status === 'maintenance'
                  }"
                >
                  {{ computer.status }}
                </span>
              </div>
            </div>
          </button>
        </div>
      </div>

      <!-- Unlock Modal -->
      <Modal :show="modalState" @close="closeModal" :closeable="!isSubmitting">
        <div class="bg-white p-6 rounded-xl shadow-xl w-full max-w-md mx-auto relative border border-gray-100">
          <!-- Header -->
          <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center text-gray-700">
                <!-- lock icon -->
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 11V7a4 4 0 10-8 0v4M5 11h6m4 0h2a2 2 0 012 2v6a2 2 0 01-2 2H7a2 2 0 01-2-2v-6a2 2 0 012-2h2"
                  />
                </svg>
              </div>
              <h2 class="text-lg font-semibold text-gray-900">Scan RFID Card</h2>
            </div>
            <button
              v-if="!isSubmitting"
              @click="closeModal"
              class="text-gray-400 hover:text-gray-600 p-1 rounded-md hover:bg-gray-100 transition"
              aria-label="Close"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Computer info -->
          <div class="mb-6 p-3 bg-gray-50 rounded-lg border border-gray-200">
            <p class="text-xs text-gray-600">Computer to unlock:</p>
            <p class="text-base font-semibold text-gray-900">
              Computer {{ selectedComputer?.computer_number ?? 'N/A' }}
            </p>
          </div>

          <!-- RFID input -->
          <div class="mb-4">
            <label for="rfid_input" class="block text-sm font-medium text-gray-700 mb-2">
              RFID Tag
            </label>
            <div class="relative">
              <TextInput
                ref="rfidInputRef"
                id="rfid_input"
                type="password"
                placeholder="Scan card..."
                v-model="form.rfid_uid"
                class="w-full text-center text-lg px-4 py-3 border-2 rounded-lg transition-colors bg-white"
                :class="{
                  'border-gray-300 focus:border-gray-500 focus:ring-gray-500': !hasError && !isSuccess,
                  'border-red-500 focus:border-red-500 focus:ring-red-500': hasError,
                  'border-green-500 focus:border-green-500 focus:ring-green-500': isSuccess,
                  'cursor-not-allowed opacity-50': isSubmitting
                }"
                maxlength="10"
                :disabled="isSubmitting"
                autocomplete="off"
                @keyup.enter="unlock_function"
                @input="clearError"
              />

              <!-- Spinner -->
              <div
                v-if="isSubmitting"
                class="absolute right-3 top-1/2 -translate-y-1/2"
              >
                <div class="animate-spin rounded-full h-5 w-5 border-2 border-gray-400 border-t-transparent"></div>
              </div>

              <!-- Success check -->
              <div
                v-else-if="isSuccess"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-green-600"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </div>
            </div>

            <!-- Feedback -->
            <p v-if="hasError" class="mt-2 text-sm text-red-600 flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4m0 4h.01M21 12A9 9 0 113 12a9 9 0 0118 0z" />
              </svg>
              {{ errorMessage }}
            </p>
            <p v-if="isSuccess" class="mt-2 text-sm text-green-600 flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M5 13l4 4L19 7" />
              </svg>
              {{ successMessage }}
            </p>
          </div>

          <!-- Tag length -->
          <p v-if="form.tag" class="mt-2 text-xs text-gray-500 text-center">
            Tag length: {{ form.rfid_uid.length }}/10
          </p>

          <!-- Actions -->
          <div class="flex justify-end gap-2 mt-6">
            <button
              v-if="!isSubmitting"
              @click="closeModal"
              class="px-4 py-2 text-sm bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
            >
              Cancel
            </button>
            <button
              :disabled="isSubmitting"
              @click="unlock_function"
              class="px-4 py-2 text-sm bg-gray-700 text-white rounded-lg hover:bg-gray-600 disabled:opacity-50 transition"
            >
              {{ isSubmitting ? 'Unlocking…' : 'Unlock' }}
            </button>
          </div>
        </div>
      </Modal>

    <!-- Add / Edit Computer Modal -->
<Modal :show="saveModal" @close="saveModal = false">
  <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-auto relative border border-gray-100">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-100">
      <h2 class="text-xl font-semibold text-gray-900">
        {{ selectedComputer ? 'Edit Computer' : 'Add Computer' }}
      </h2>
    </div>

    <!-- Form Content -->
    <div class="px-6 py-5 space-y-4">
      <!-- Computer Number -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Computer Number</label>
        <input
          v-model="newComputer.computer_number"
          type="text"
          placeholder="PC-001"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:border-gray-500 focus:ring-1 focus:ring-gray-500 bg-white transition"
        >
      </div>

      <!-- IP Address -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">IP Address</label>
        <input
          v-model="newComputer.ip_address"
          type="text"
          placeholder="192.168.1.100"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:border-gray-500 focus:ring-1 focus:ring-gray-500 bg-white transition font-mono"
        >
      </div>

      <!-- MAC Address -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">MAC Address</label>
        <input
          v-model="newComputer.mac_address"
          type="text"
          placeholder="00:1B:44:11:3A:B7"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:border-gray-500 focus:ring-1 focus:ring-gray-500 bg-white transition font-mono"
        >
      </div>

      <!-- Laboratory -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Laboratory</label>
        <select
          v-model="newComputer.laboratory_id"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:border-gray-500 focus:ring-1 focus:ring-gray-500 bg-white transition"
        >
          <option value="" disabled>Select laboratory</option>
          <option v-if="(func.labs?.length ?? 0) === 0" disabled>No labs available</option>
          <option v-for="lab in func.labs || []" :key="lab.id" :value="lab.id">
            {{ lab.name }}
          </option>
        </select>
      </div>

      <!-- Status -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
        <select
          v-model="newComputer.status"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:border-gray-500 focus:ring-1 focus:ring-gray-500 bg-white transition"
        >
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="maintenance">Maintenance</option>
        </select>
      </div>

      <!-- Status Checkboxes -->
      <div class="grid grid-cols-2 gap-4 pt-2">
        <div class="flex items-center">
          <input
            id="is_online"
            v-model="newComputer.is_online"
            type="checkbox"
            class="h-4 w-4 text-gray-600 focus:ring-gray-500 border-gray-300 rounded"
          >
          <label for="is_online" class="ml-2 text-sm text-gray-700">Online</label>
        </div>

        <div class="flex items-center">
          <input
            id="is_lock"
            v-model="newComputer.is_lock"
            type="checkbox"
            class="h-4 w-4 text-gray-600 focus:ring-gray-500 border-gray-300 rounded"
          >
          <label for="is_lock" class="ml-2 text-sm text-gray-700">Locked</label>
        </div>
      </div>
    </div>

    <!-- Footer Actions -->
    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl flex justify-end gap-3">
      <button
        @click="saveModal = false"
        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
      >
        Cancel
      </button>
      <button
        @click="saveComputer"
        class="px-4 py-2 text-sm font-medium text-white bg-gray-700 rounded-lg hover:bg-gray-600 transition"
      >
        Save
      </button>
    </div>
  </div>
</Modal>

        <!--Student Assignment Modal -->
      <StudentAssignmentModal
        :show="showAssignmentModal"
        :computer="selectedComputerForAssignment"
        @close="closeAssignmentModal"
        @assign="handleStudentAssignment"
      />

      <!-- Delete Confirmation Modal -->
      <Modal :show="showDeleteModal" @close="closeDeleteModal" max-width="md">
        <div class="relative bg-white p-6 rounded-lg">
          <!-- Icon -->
          <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-gray-100 rounded-full">
            <TrashIcon class="w-6 h-6 text-gray-600" />
          </div>

          <!-- Modal header -->
          <div class="text-center mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
              Delete Computer
            </h3>
            <p class="text-sm text-gray-600">
              Are you sure you want to delete this computer? This action cannot be undone.
            </p>
          </div>

          <!-- Computer info -->
          <div v-if="computerToDelete" class="bg-gray-50 rounded-md p-4 mb-6">
            <div class="flex items-start gap-3">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900">
                  Computer {{ computerToDelete.computer_number }}
                </p>
                <p class="text-xs text-gray-600 mt-1">
                  IP: {{ computerToDelete.ip_address }}
                </p>
                <p class="text-xs text-gray-600 mt-1">
                  Lab: {{ func.labs?.find(l => l.id === computerToDelete.laboratory_id)?.name || 'N/A' }}
                </p>
                <div class="mt-2">
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                    :class="{
                      'bg-green-100 text-green-800': computerToDelete.status === 'active',
                      'bg-red-100 text-red-800': computerToDelete.status === 'inactive',
                      'bg-yellow-100 text-yellow-800': computerToDelete.status === 'maintenance'
                    }"
                  >
                    {{ computerToDelete.status }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal footer -->
          <div class="flex gap-3">
            <button
              @click="closeDeleteModal"
              class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 transition-colors"
            >
              Cancel
            </button>
            <button
              @click="confirmDelete"
              class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-gray-900 rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors"
            >
              Delete Computer
            </button>
          </div>
        </div>
      </Modal>
    </div>
  </AuthenticatedLayout>
</template>
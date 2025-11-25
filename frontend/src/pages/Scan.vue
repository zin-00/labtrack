<script setup>
import TextInput from '../components/input/TextInput.vue';
import { onMounted, ref, toRefs, watch, nextTick } from "vue";
import { useComputerStore } from '../composable/computers';
import { storeToRefs } from 'pinia';
import { useComputerLogStore } from '../composable/computerLog';
import { useLaboratoryStore } from '../composable/laboratory';
import Modal from '../components/modal/Modal.vue';
import axios from 'axios';
import { useApiUrl } from '../api/api';
import { useReportsStore } from '../store/reports/reports';
import { useRealTimeEvents } from '../composable/events/EventsListener';

const { api} = useApiUrl();


const comp = useComputerStore();
const compLog = useComputerLogStore();
const lab = useLaboratoryStore();
const { submitReport } = useReportsStore();
const { register, handleEventUpdate } = useRealTimeEvents();
const { fetchLaboratories } = lab;
const { unlockComputersByLab, fetchAllComputers, unlockComputer} = comp;
const { fetchRecentScans } = compLog;
const { latestScan } = toRefs(compLog);

const {
  isSubmitting,
  errorMessage,
  data,
  computers
} = storeToRefs(comp);
const { laboratories, selectedLab } = toRefs(lab);

// Emergency Mode States
const emergencyMode = ref(false);
const emergencySearchQuery = ref('');
const emergencyLabFilter = ref('');
const showUnlockModal = ref(false);
const selectedComputerForUnlock = ref(null);
const emergencyRfidInput = ref('');
// const computers = ref([]);
const emergencyModalInput = ref(null);

// Report Modal States
const showReportModal = ref(false);
const reportRfidUid = ref('');
const reportDescription = ref('');
const reportModalInput = ref(null);

const sendRequest = async (rfid_uid) => {
  if(!selectedLab.value){
    errorMessage.value = 'Please select a laboratory first.';
    return;
  }

  try{
    await unlockComputersByLab(selectedLab.value, rfid_uid);
    data.value.rfid_uid = '';
  }catch(err){
    errorMessage.value = err.message;
  }
}

const handleReportSubmit = async (uid, description) => {
  try {
    await submitReport(uid, description);
    showReportModal.value = false;
    reportRfidUid.value = '';
    reportDescription.value = '';
  } catch (err) {
    errorMessage.value = err.message;
  }finally{
    showReportModal.value = false;
  }
};

watch(() => data.value.rfid_uid, (newValue) => {
  if (newValue && newValue.trimEnd().length === 10) {
    sendRequest(newValue.trim());
  }
});

// Emergency Mode Watcher - Auto-unlock when RFID scanned in modal
watch(() => emergencyRfidInput.value, async (newValue) => {
  if (newValue && newValue.trimEnd().length === 10 && selectedComputerForUnlock.value) {
    try {
      await unlockComputer(selectedComputerForUnlock.value.id, newValue.trim());
      emergencyRfidInput.value = '';
      showUnlockModal.value = false;
      selectedComputerForUnlock.value = null;
      errorMessage.value = '';
    } catch (err) {
      errorMessage.value = err.message;
    }
  }
});

// Watch modal state to focus input
watch(showUnlockModal, async (newValue) => {
  if (newValue) {
    await nextTick();
    emergencyModalInput.value?.focus();
  }
});

// Filtered computers for emergency mode
const filteredComputers = () => {
  if (!computers.value.length) return [];
  
  return computers.value.filter(computer => {
    const matchesSearch = !emergencySearchQuery.value || 
                         computer.computer_number.toString().includes(emergencySearchQuery.value) ||
                         computer.ip_address.toLowerCase().includes(emergencySearchQuery.value.toLowerCase()) ||
                         computer.laboratory?.name.toLowerCase().includes(emergencySearchQuery.value.toLowerCase());
    const matchesLab = !emergencyLabFilter.value || computer.laboratory_id == emergencyLabFilter.value;
    return matchesSearch && matchesLab;
  });
};

const openUnlockModal = (computer) => {
  selectedComputerForUnlock.value = computer;
  showUnlockModal.value = true;
  emergencyRfidInput.value = '';
  errorMessage.value = '';
};

const closeUnlockModal = () => {
  showUnlockModal.value = false;
  selectedComputerForUnlock.value = null;
  emergencyRfidInput.value = '';
  errorMessage.value = '';
};

const openReportModal = () => {
  showReportModal.value = true;
  reportRfidUid.value = '';
  reportDescription.value = '';
  errorMessage.value = '';
  nextTick(() => {
    reportModalInput.value?.focus();
  });
};

const closeReportModal = () => {
  showReportModal.value = false;
  reportRfidUid.value = '';
  reportDescription.value = '';
  errorMessage.value = '';
};

const openRfidInfoModal = (scan) => {
  selectedScanForInfo.value = scan;
  showRfidInfoModal.value = true;
};

const closeRfidInfoModal = () => {
  showRfidInfoModal.value = false;
  selectedScanForInfo.value = null;
};


// const initializeEcho = () => {
//   if (!window.Echo) {
//     console.log('Echo is not defined')
//   }
//   if (window.Echo) {
//     let echoChannel = window.Echo.channel('computer');
//     echoChannel.listen('ComputerUnlockRequested', (e) => {
//       console.log('Event received:', e.message);
//       console.log(e);
//       if (e.studentId) {
//         latestScan.value = e.studentId;
//       }
//     })
//   }
// }

const sendEmergencyUnlock = async (rfid_uid) => {
  if (!selectedComputerForUnlock.value) return;

  try {
    await unlockComputer(selectedComputerForUnlock.value.id, rfid_uid);
    emergencyRfidInput.value = '';
    showUnlockModal.value = false;
    selectedComputerForUnlock.value = null;
    errorMessage.value = '';
  } catch (err) {
    errorMessage.value = err.message;
  }
};

const EventListener = () => {
  if(window.Echo){
    window.Echo.channel('main-channel')
    .listen('.MainEvent', (e) => {
      switch(e.type) {
        case 'computer':
          // alert('Received Computer event', e.data);
          const computerIndex = computers.value.findIndex(c => c.id === e.data.id);
          if(computerIndex !== -1){
            computers.value[computerIndex] = {
              ...computers.value[computerIndex],
              ...e.data
            };
            computers.value.splice(computerIndex, 1, computers.value[computerIndex]); // Trigger reactivity update
          }else{
            computers.value.push(e.data);
          }
          break;

        case 'RecentScan':
          // alert('Received RecentScan event', e.data);
          const scanIndex = latestScan.value.findIndex(c => c.id === e.data.id);
          if(scanIndex !== -1){
            latestScan.value[scanIndex] = {
              ...latestScan.value[scanIndex],
              ...e.data
            };
            latestScan.value.splice(scanIndex, 1, latestScan.value[scanIndex]); // Trigger reactivity update
          }else{
            latestScan.value.push(e.data);
          }
          break;

        default:
          console.warn('Unknown event type:', e.type);
      }
    })
  }
}

onMounted(() => {
    // initializeEcho();
  EventListener();
  fetchRecentScans();
  fetchLaboratories();
  fetchAllComputers();
});
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded flex items-center justify-center">
              <img src="../assets/LABTrack.png" alt="LABTrack Logo" class="h-8 w-auto">
            </div>
            <p class="font-['Orbitron'] text-lg font-bold text-gray-900">
              LAB<span class="text-gray-600">TRACK</span>
            </p>
          </div>
          
          <div class="flex items-center space-x-4">
            <!-- Report Button -->
            <button
              @click="openReportModal"
              class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Report
            </button>

            <!-- Emergency Mode Toggle -->
            <div class="flex items-center space-x-3 px-4 py-2 bg-gray-100 rounded-lg">
              <span :class="['text-sm font-medium transition-colors', emergencyMode ? 'text-gray-400' : 'text-gray-900']">
                Normal
              </span>
              <button 
                @click="emergencyMode = !emergencyMode"
                :class="[
                  'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2',
                  emergencyMode ? 'bg-red-600 focus:ring-red-500' : 'bg-gray-300 focus:ring-gray-400'
                ]"
              >
                <span 
                  :class="[
                    'inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm',
                    emergencyMode ? 'translate-x-6' : 'translate-x-1'
                  ]"
                ></span>
              </button>
              <span :class="['text-sm font-medium transition-colors', emergencyMode ? 'text-red-600' : 'text-gray-400']">
                Emergency
              </span>
            </div>

            <!-- Laboratory Filter - Always visible -->
            <div v-if="!emergencyMode" class="relative">
              <select v-model="selectedLab" 
                class="pl-10 pr-8 py-2.5 border border-gray-300 rounded-lg appearance-none bg-white text-gray-700 focus:ring-2 focus:ring-gray-400 focus:border-gray-400">
                <option disabled value="">Select Laboratory</option>
                <option v-for="lab in laboratories" :key="lab.id" :value="lab.id">
                  {{ lab.name }} - {{ lab.code }}
                </option>
              </select>
              <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-6 0H5m2 0h4M9 7h6m-6 4h6m-6 4h6" />
                </svg>
              </div>
            </div>
            
            <div v-else class="relative">
              <select v-model="emergencyLabFilter" 
                class="pl-10 pr-8 py-2.5 border border-gray-300 rounded-lg appearance-none bg-white text-gray-700 focus:ring-2 focus:ring-gray-400 focus:border-gray-400">
                <option disabled value="">Select Laboratory</option>
                <option value="">All Laboratories</option>
                <option v-for="lab in laboratories" :key="lab.id" :value="lab.id">
                  {{ lab.name }} - {{ lab.code }}
                </option>
              </select>
              <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-6 0H5m2 0h4M9 7h6m-6 4h6m-6 4h6" />
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Normal Mode -->
        <div v-if="!emergencyMode" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          
          <!-- Scanner Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
              <div class="flex items-center">
                <div class="p-2 bg-gray-900 rounded-lg mr-3">
                  <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                  </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">RFID Scanner</h2>
              </div>
            </div>

            <div class="p-6">
              <div class="text-center mb-6">
                <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                  <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" />
                  </svg>
                </div>
                <p class="text-sm text-gray-600 mb-2">Scan your RFID tag to unlock a computer</p>
              </div>

              <div class="mb-6">
                <label for="rfid_input" class="block text-sm font-medium text-gray-700 mb-2">RFID Tag</label>
                <TextInput
                  id="rfid_input"
                  type="password"
                  placeholder="Waiting for scan..."
                  class="w-full text-center text-lg px-6 py-4 border-2 border-gray-300 rounded-lg focus:border-gray-400 focus:ring-2 focus:ring-gray-200 transition-colors font-mono"
                  maxlength="10"
                  @keyup.enter="sendRequest(data.rfid_uid)"
                  v-model="data.rfid_uid"
                  autofocus
                  :disabled="isSubmitting"
                  autocomplete="off"
                />
                <p class="text-xs text-gray-500 mt-2 text-center">Tag length: {{ data.rfid_uid.length }}/10</p>
              </div>

              <!-- Status Indicators -->
              <div v-if="isSubmitting" class="flex items-center justify-center p-4 bg-gray-100 rounded-lg mb-4">
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-gray-900 mr-3"></div>
                <span class="text-gray-900 font-medium">Processing scan...</span>
              </div>

              <div v-if="errorMessage" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span class="text-red-700">{{ errorMessage }}</span>
                </div>
              </div>

              <div class="bg-gray-50 rounded-lg p-4 mt-6">
                <div class="flex items-center text-sm text-gray-600">
                  <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>Place your RFID tag near the scanner</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Scans Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
              <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Recent Scans</h2>
                <span class="bg-gray-100 text-gray-700 text-xs font-medium px-2.5 py-0.5 rounded-full">
                  {{ latestScan.length }} scans
                </span>
              </div>
            </div>

            <div class="p-6">
              <div v-if="latestScan.length > 0" class="space-y-3 max-h-96 overflow-y-auto">
                <div 
                  v-for="(scan, index) in latestScan" 
                  :key="scan.id"
                  :class="[
                    'flex items-center p-4 rounded-lg border transition-all duration-200',
                    index === 0 ? 'bg-gray-50 border-gray-300' : 'bg-white border-gray-200 hover:bg-gray-50'
                  ]"
                >
                  <!-- Avatar -->
                  <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-gray-900 text-white flex items-center justify-center text-lg font-bold">
                      {{ scan.student?.first_name ? scan.student.first_name.charAt(0) : '?' }}
                    </div>
                  </div>

                  <!-- Details -->
                  <div class="ml-4 flex-1 min-w-0">
                    <h3 class="text-sm font-semibold text-gray-900 truncate">
                      {{ scan.student?.first_name }} {{ scan.student?.last_name }}
                    </h3>
                    <div class="flex flex-wrap items-center gap-2 mt-1">
                      <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700">
                        PC {{ scan.computer?.computer_number }}
                      </span>
                      <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700">
                        {{ scan.computer?.laboratory?.name }}
                      </span>
                      <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700">
                        {{ scan.computer?.ip_address }}
                      </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">{{ scan.timestamp }}</p>
                  </div>

                  <!-- Status Indicator -->
                  <div v-if="index === 0" class="flex-shrink-0 ml-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-900 text-white">
                      Latest
                    </span>
                  </div>
                </div>
              </div>

              <div v-else class="text-center py-12">
                <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                  <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-1">No scans yet</h3>
                <p class="text-gray-500">Scan an RFID tag to see activity here</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Emergency Mode -->
        <div v-else>
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
              <div class="flex items-center">
                <div class="p-2 bg-red-600 rounded-lg mr-3">
                  <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">Emergency Mode - Select Computer</h2>
              </div>
            </div>

            <div class="p-6">
              <!-- Filters -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <!-- Search Filter -->
                <div class="col-span-1">
                  <label for="emergency-search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                  <div class="relative">
                    <input
                      id="emergency-search"
                      v-model="emergencySearchQuery"
                      type="text"
                      placeholder="PC number, IP, or Lab..."
                      class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-400 focus:border-gray-400"
                    />
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                      <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                      </svg>
                    </div>
                  </div>
                </div>

                <!-- Results Count -->
                <div class="col-span-1 flex items-end">
                  <div class="w-full px-4 py-2.5 bg-gray-50 rounded-lg border border-gray-300">
                    <p class="text-sm font-medium text-gray-700">
                      Found: <span class="text-gray-900 font-bold">{{ filteredComputers().length }}</span> computer(s)
                    </p>
                  </div>
                </div>
              </div>

              <!-- Computers Grid -->
              <div v-if="filteredComputers().length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 max-h-[500px] overflow-y-auto pr-2">
                <button
                  v-for="computer in filteredComputers()"
                  :key="computer.id"
                  @click="openUnlockModal(computer)"
                  class="group p-4 bg-white border-2 border-gray-200 rounded-lg hover:border-gray-400 hover:shadow-md transition-all duration-200 text-left"
                >
                  <div class="flex items-center justify-between mb-3">
                    <span class="text-2xl font-bold text-gray-900 group-hover:text-gray-700 transition-colors">
                      PC {{ computer.computer_number }}
                    </span>
                    <svg class="h-6 w-6 text-gray-400 group-hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <div class="space-y-2">
                    <p class="text-sm text-gray-600">
                      <span class="font-medium text-gray-900">Lab:</span> {{ computer.laboratory?.name || 'N/A' }}
                    </p>
                    <p class="text-sm text-gray-600 font-mono">
                      <span class="font-medium text-gray-900">IP:</span> {{ computer.ip_address || 'N/A' }}
                    </p>
                    <div class="mt-2 flex items-center gap-2">
                      <span :class="[
                        'inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-medium',
                        computer.is_online ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                      ]">
                        <span :class="[
                          'w-1.5 h-1.5 rounded-full',
                          computer.is_online ? 'bg-green-500' : 'bg-red-500'
                        ]"></span>
                        {{ computer.is_online ? 'Online' : 'Offline' }}
                      </span>
                    </div>
                  </div>
                </button>
              </div>

              <div v-else class="text-center py-12">
                <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                  <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-1">No computers found</h3>
                <p class="text-gray-500">Try adjusting your search or laboratory filters</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Unlock Modal -->
    <Modal :show="showUnlockModal" @close="closeUnlockModal" max-width="md">
      <div class="relative bg-white rounded-lg shadow-xl overflow-hidden">
        <!-- Modal Body -->
        <div class="p-8 relative">
          <!-- Close Button - Top Right -->
          <button
            @click="closeUnlockModal"
            class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <!-- Computer Number - Large and Centered -->
          <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-4">
              <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
              </svg>
            </div>
            <h2 v-if="selectedComputerForUnlock" class="text-4xl font-bold text-gray-900 mb-2">
              PC {{ selectedComputerForUnlock.computer_number }}
            </h2>
            <p class="text-sm text-gray-500">{{ selectedComputerForUnlock?.laboratory?.name }}</p>
          </div>

          <!-- RFID Input - Clean and Minimal -->
          <div class="mb-6">
            <TextInput
              id="emergency-rfid-input"
              ref="emergencyModalInput"
              type="password"
              placeholder="Scan RFID..."
              class="w-full text-center text-2xl px-6 py-5 border-2 border-gray-300 rounded-lg focus:border-gray-400 focus:ring-2 focus:ring-gray-200 transition-colors font-mono tracking-widest"
              maxlength="10"
              @keyup.enter="sendEmergencyUnlock(emergencyRfidInput)"
              v-model="emergencyRfidInput"
              autofocus
              autocomplete="off"
            />
            <p class="text-xs text-gray-400 mt-2 text-center font-mono">{{ emergencyRfidInput.length }}/10</p>
          </div>

          <!-- Status Messages - Minimal -->
          <div v-if="isSubmitting" class="flex items-center justify-center p-3 bg-gray-50 rounded-lg mb-4">
            <div class="animate-spin rounded-full h-4 w-4 border-2 border-gray-300 border-t-gray-900 mr-2"></div>
            <span class="text-sm text-gray-700">Processing...</span>
          </div>

          <div v-if="errorMessage" class="text-center p-3 bg-red-50 rounded-lg mb-4">
            <span class="text-sm text-red-700">{{ errorMessage }}</span>
          </div>
        </div>
      </div>
    </Modal>

    <!-- Report Modal -->
    <Modal :show="showReportModal" @close="closeReportModal" max-width="md">
      <div class="bg-white/95 backdrop-blur-md rounded-lg shadow-xl overflow-hidden">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Submit Report</h3>
            </div>
            <button
              @click="closeReportModal"
              class="text-gray-400 hover:text-gray-600 transition-colors"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Modal Content -->
        <div class="p-6 space-y-5">
          <!-- RFID Input -->
          <div>
            <label for="report-rfid" class="block text-sm font-medium text-gray-700 mb-2">
              RFID UID <span class="text-red-500">*</span>
            </label>
            <TextInput
              id="report-rfid"
              ref="reportModalInput"
              type="password"
              placeholder="Scan your RFID tag..."
              class="w-full text-center text-lg px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-gray-400 focus:ring-2 focus:ring-gray-200 transition-colors font-mono tracking-wider"
              maxlength="10"
              v-model="reportRfidUid"
              autocomplete="off"
            />
            <p class="text-xs text-gray-500 mt-1.5 text-center font-mono">{{ reportRfidUid.length }}/10 characters</p>
          </div>

          <!-- Description Input -->
          <div>
            <label for="report-description" class="block text-sm font-medium text-gray-700 mb-2">
              Description <span class="text-red-500">*</span>
            </label>
            <textarea
              id="report-description"
              v-model="reportDescription"
              rows="4"
              placeholder="Enter your report description..."
              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-gray-400 focus:ring-2 focus:ring-gray-200 transition-colors resize-none text-sm"
            ></textarea>
            <p class="text-xs text-gray-500 mt-1.5">{{ reportDescription.length }} characters</p>
          </div>

          <!-- Error Message -->
          <div v-if="errorMessage" class="bg-red-50 border border-red-200 rounded-lg p-3">
            <div class="flex items-center">
              <svg class="h-5 w-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="text-sm text-red-700">{{ errorMessage }}</span>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="flex gap-3 pt-2">
            <button
              @click="closeReportModal"
              class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Cancel
            </button>
            <button
              @click="handleReportSubmit(reportRfidUid, reportDescription)"
              :disabled="isSubmitting || !reportRfidUid || !reportDescription"
              class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
            >
              <span v-if="!isSubmitting">Submit Report</span>
              <span v-else class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Submitting...
              </span>
            </button>
          </div>
        </div>
      </div>
    </Modal>
  </div>
</template>
<script setup>
import TextInput from '../components/input/TextInput.vue';
import { onMounted, ref, toRefs, watch } from "vue";
import { useComputerStore } from '../composable/computers';
import { storeToRefs } from 'pinia';
import { useComputerLogStore } from '../composable/computerLog';
import { useLaboratoryStore } from '../composable/laboratory';

const comp = useComputerStore();
const compLog = useComputerLogStore();
const lab = useLaboratoryStore();

const { fetchLaboratories } = lab;
const { unlockComputersByLab } = comp;
const { fetchRecentScans } = compLog;
const { latestScan } = toRefs(compLog);

const {
  isSubmitting,
  errorMessage,
  data
} = storeToRefs(comp);
const { laboratories, selectedLab } = toRefs(lab);

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

watch(() => data.value.rfid_uid, (newValue) => {
  if (newValue && newValue.trimEnd().length === 10) {
    sendRequest(newValue.trim());
  }
});                                                                                                                                                     

const initializeEcho = () => {
  if (!window.Echo) {
    console.log('Echo is not defined')
  }
  if (window.Echo) {
    let echoChannel = window.Echo.channel('computer');
    echoChannel.listen('ComputerUnlockRequested', (e) => {
      console.log('Event received:', e.message);
      console.log(e);
      if (e.studentId) {
        latestScan.value = e.studentId;
      }
    })
  }
}

onMounted(() => {
  initializeEcho();
  fetchRecentScans();
  fetchLaboratories();
});
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex flex-col">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded flex items-center justify-center">
              <img src="../assets/LABTrack.png" alt="LABTrack Logo" class="h-8 w-auto">
            </div>
            <p class="font-['Orbitron'] text-lg font-bold text-gray-900">
              LAB<span class="text-emerald-500">TRACK</span>
            </p>
          </div>
          
          <div class="flex items-center space-x-4">
            <div class="relative">
              <select v-model="selectedLab" 
                class="pl-10 pr-8 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 appearance-none bg-white text-gray-700">
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
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          
          <!-- Scanner Card -->
          <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
              <div class="flex items-center">
                <div class="p-2 bg-black rounded-lg mr-3">
                  <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                  </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900">RFID Scanner</h2>
              </div>
            </div>

            <div class="p-6">
              <div class="text-center mb-6">
                <div class="mx-auto w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mb-4">
                  <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" />
                  </svg>
                </div>
                <p class="text-gray-600 mb-2">Scan your RFID tag to unlock a computer</p>
              </div>

              <div class="mb-6">
                <label for="rfid_input" class="block text-sm font-medium text-gray-700 mb-2">RFID Tag</label>
                <TextInput
                  id="rfid_input"
                  type="password"
                  placeholder="Waiting for scan..."
                  class="w-full text-center text-lg px-6 py-4 border-2 border-gray-300 rounded-xl focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-colors font-mono"
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
              <div v-if="isSubmitting" class="flex items-center justify-center p-4 bg-blue-50 rounded-lg mb-4">
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600 mr-3"></div>
                <span class="text-blue-700 font-medium">Processing scan...</span>
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
          <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
              <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-900">Recent Scans</h2>
                <span class="bg-emerald-100 text-emerald-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                  {{ latestScan.length }} scans
                </span>
              </div>
            </div>

            <div class="p-6">
              <div v-if="latestScan.length > 0" class="space-y-4 max-h-96 overflow-y-auto">
                <div 
                  v-for="(scan, index) in latestScan" 
                  :key="scan.id"
                  :class="[
                    'flex items-center p-4 rounded-lg border transition-all duration-200',
                    index === 0 ? 'bg-emerald-50 border-emerald-200 shadow-sm' : 'bg-gray-50 border-gray-200 hover:bg-white hover:shadow-sm'
                  ]"
                >
                  <!-- Avatar -->
                  <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-emerald-500 to-emerald-600 text-white flex items-center justify-center text-lg font-bold shadow-sm">
                      {{ scan.student?.first_name ? scan.student.first_name.charAt(0) : '?' }}
                    </div>
                  </div>

                  <!-- Details -->
                  <div class="ml-4 flex-1 min-w-0">
                    <h3 class="text-sm font-semibold text-gray-900 truncate">
                      {{ scan.student?.first_name }} {{ scan.student?.last_name }}
                    </h3>
                    <div class="flex flex-wrap items-center gap-2 mt-1">
                      <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        PC {{ scan.computer?.computer_number }}
                      </span>
                      <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        {{ scan.computer?.laboratory?.name }}
                      </span>
                      <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        {{ scan.computer?.ip_address }}
                      </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">{{ scan.timestamp }}</p>
                  </div>

                  <!-- Status Indicator -->
                  <div v-if="index === 0" class="flex-shrink-0 ml-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 animate-pulse">
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
      </div>
    </main>
  </div>
</template>
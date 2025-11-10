<script setup>
import { toRefs, onMounted, ref, watch } from 'vue';
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import { useStates } from '../../composable/states';
import Table from '../../components/table/Table.vue';
import Modal from '../../components/modal/Modal.vue';
import { XMarkIcon, ArrowDownTrayIcon, FunnelIcon } from '@heroicons/vue/24/outline';
import { useBrowserActivityStore } from '../../composable/activity/browserActivity';
import dayjs from 'dayjs';
import LoaderSpinner from '../../components/spinner/LoaderSpinner.vue';
import { debounce } from 'lodash-es';

const states = useStates();
const brows = useBrowserActivityStore();
const {
        searchQuery,
        pagination,
        isLoading,
        browserActivity,
        dateFrom,
        dateTo,
      } = toRefs(states);
const {
        getBrowserActivity,
      } = brows;

// Modal state
const showDetailsModal = ref(false);
const modalTitle = ref('');
const modalUrl = ref('');
const modalBrowser = ref('');
const modalDuration = ref('');
const modalDateTime = ref('');

// Filter state
const showFilters = ref(false);

// Debounced filter function
const applyFilters = debounce(() => {
    getBrowserActivity(1, {
        search: searchQuery.value,
        dateFrom: dateFrom.value,
        dateTo: dateTo.value,
    });
}, 300);

// Watch filters
watch([searchQuery, dateFrom, dateTo], () => {
    applyFilters();
});

const openDetailsModal = (activity) => {
    modalTitle.value = activity.title;
    modalUrl.value = activity.url;
    modalBrowser.value = activity.browser_name;
    modalDuration.value = activity.duration;
    modalDateTime.value = dayjs(activity.created_at).format('MMM D, YYYY h:mm A');
    showDetailsModal.value = true;
};

const handlePageChange = (page) => {
    getBrowserActivity(page, {
        search: searchQuery.value,
        dateFrom: dateFrom.value,
        dateTo: dateTo.value,
    });
};

const exportToPDF = async () => {
    try {
        const { jsPDF } = await import('jspdf');
        const { autoTable } = await import('jspdf-autotable');

        const doc = new jsPDF();
        const pageWidth = doc.internal.pageSize.getWidth();
        
        // Title
        doc.setFontSize(16);
        doc.text('Browser Activity Report', pageWidth / 2, 10, { align: 'center' });
        
        // Export date
        doc.setFontSize(10);
        doc.text(`Generated: ${dayjs().format('MMM D, YYYY h:mm A')}`, pageWidth / 2, 18, { align: 'center' });

        // Prepare table data
        const tableData = browserActivity.value.map((activity) => [
            activity.ip_address || 'N/A',
            activity.computer_id || 'N/A',
            activity.browser_name || 'N/A',
            activity.title || 'N/A',
            activity.url || 'N/A',
            activity.duration + ' seconds' || 'N/A',
            dayjs(activity.created_at).format('MMM D, YYYY h:mm A'),
        ]);

        // Generate table
        autoTable(doc, {
            head: [['IP Address', 'Work Station', 'Browser', 'Title', 'URL', 'Duration', 'Date & Time']],
            body: tableData,
            startY: 25,
            styles: { fontSize: 8, cellPadding: 3 },
            headStyles: { fillColor: [66, 66, 66], textColor: 255, fontStyle: 'bold' },
            alternateRowStyles: { fillColor: [245, 245, 245] },
            margin: { left: 5, right: 5 },
            didDrawPage: (data) => {
                const pageCount = doc.internal.pages.length - 1;
                doc.setFontSize(8);
                doc.text(`Page ${data.pageNumber}`, pageWidth / 2, doc.internal.pageSize.getHeight() - 10, { align: 'center' });
            }
        });

        doc.save(`browser-activity-${dayjs().format('YYYY-MM-DD-HHmmss')}.pdf`);
    } catch (error) {
        console.error('Error exporting to PDF:', error);
        alert('Error exporting to PDF');
    }
};

const exportToDocx = async () => {
    try {
        const { Document, Packer, Table, TableRow, TableCell, Paragraph, TextRun, AlignmentType, BorderStyle } = await import('docx');

        // Prepare table rows
        const headerCells = ['IP Address', 'Work Station', 'Browser', 'Title', 'URL', 'Duration', 'Date & Time']
            .map(header => new TableCell({
                children: [new Paragraph({
                    text: header,
                    bold: true,
                    color: 'FFFFFF'
                })],
                shading: { fill: '424242', type: 'clear' }
            }));

        const bodyRows = browserActivity.value.map(activity => new TableRow({
            children: [
                new TableCell({ children: [new Paragraph(activity.ip_address || 'N/A')] }),
                new TableCell({ children: [new Paragraph(activity.computer_id || 'N/A')] }),
                new TableCell({ children: [new Paragraph(activity.browser_name || 'N/A')] }),
                new TableCell({ children: [new Paragraph(activity.title || 'N/A')] }),
                new TableCell({ children: [new Paragraph(activity.url || 'N/A')] }),
                new TableCell({ children: [new Paragraph(activity.duration + ' seconds' || 'N/A')] }),
                new TableCell({ children: [new Paragraph(dayjs(activity.created_at).format('MMM D, YYYY h:mm A'))] })
            ]
        }));

        const table = new Table({
            rows: [new TableRow({ children: headerCells }), ...bodyRows],
            width: { size: 100, type: 'pct' }
        });

        const doc = new Document({
            sections: [{
                children: [
                    new Paragraph({
                        text: 'Browser Activity Report',
                        size: 28,
                        bold: true,
                        alignment: AlignmentType.CENTER,
                        spacing: { after: 200 }
                    }),
                    new Paragraph({
                        text: `Generated: ${dayjs().format('MMM D, YYYY h:mm A')}`,
                        alignment: AlignmentType.CENTER,
                        spacing: { after: 400 }
                    }),
                    table
                ]
            }]
        });

        Packer.toBlob(doc).then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `browser-activity-${dayjs().format('YYYY-MM-DD-HHmmss')}.docx`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        });
    } catch (error) {
        console.error('Error exporting to DOCX:', error);
        alert('Error exporting to DOCX');
    }
};

onMounted(async () => {
getBrowserActivity();
});
</script>

<template>
  <AuthenticatedLayout>
    <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-gray-50 min-h-screen relative">
      <LoaderSpinner :isLoading="isLoading" subMessage="Loading browser activity..." />
      
      <!-- Header -->
      <div class="mb-4">
        <h2 class="text-xl text-gray-900">Browser Activity</h2>
        <p class="text-xs text-gray-600">Monitor computer usage and browser events</p>
      </div>

      <!-- Single Row: Filters and Actions -->
      <div class="bg-white rounded-lg border border-gray-200 p-3 mb-4">
        <div class="flex flex-wrap items-center gap-2">
          <!-- Filter Toggle Button -->
          <button
            @click="showFilters = !showFilters"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors"
            :class="showFilters ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
          >
            <FunnelIcon class="w-3.5 h-3.5" />
            Filters
          </button>

          <!-- Date Filters (Inline when expanded) -->
          <template v-if="showFilters">
            <input
              type="date"
              v-model="dateFrom"
              class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs focus:border-gray-400 focus:outline-none"
              placeholder="From"
            />
            <span class="text-gray-400 text-xs">to</span>
            <input
              type="date"
              v-model="dateTo"
              class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs focus:border-gray-400 focus:outline-none"
              placeholder="To"
            />
          </template>

          <!-- Search -->
          <div class="relative flex-1 min-w-[200px]">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search browser, title, URL, IP..."
              class="w-full px-3 py-1.5 pr-8 border border-gray-200 rounded-lg text-xs focus:border-gray-400 focus:outline-none transition-colors"
            />
            <button
              v-if="searchQuery"
              @click="searchQuery = ''"
              class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            >
              <XMarkIcon class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- Results Count and Actions -->
          <div class="flex items-center gap-2 ml-auto">
            <span class="text-xs text-gray-600">{{ pagination.total || 0 }} activities</span>
            <button
              @click="exportToPDF"
              :disabled="!browserActivity || browserActivity.length === 0"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
            >
              <ArrowDownTrayIcon class="w-3.5 h-3.5" />
              PDF
            </button>
            <button
              @click="exportToDocx"
              :disabled="!browserActivity || browserActivity.length === 0"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
            >
              <ArrowDownTrayIcon class="w-3.5 h-3.5" />
              DOCX
            </button>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <Table
          :data="browserActivity"
          :is-loading="isLoading"
          :pagination="pagination"
          @page-change="handlePageChange">
          
          <template #header>
            <tr>
              <th class="px-4 py-2 text-xs font-medium text-gray-600 text-left">IP Address</th>
              <th class="px-4 py-2 text-xs font-medium text-gray-600 text-left">User</th>
              <th class="px-4 py-2 text-xs font-medium text-gray-600 text-left">Work Station</th>
              <th class="px-4 py-2 text-xs font-medium text-gray-600 text-left">Browser</th>
              <th class="px-4 py-2 text-xs font-medium text-gray-600 text-left">Title</th>
              <th class="px-4 py-2 text-xs font-medium text-gray-600 text-left">URL</th>
              <th class="px-4 py-2 text-xs font-medium text-gray-600 text-left">Duration</th>
              <th class="px-4 py-2 text-xs font-medium text-gray-600 text-left">Date & Time</th>
            </tr>
          </template>
          
          <template #default>
            <tr v-for="activity in browserActivity" :key="activity.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-4 py-2.5 text-xs text-gray-900">{{ activity.ip_address }}</td>
              <td class="px-4 py-2.5 text-xs text-gray-900">{{ activity.student_id }}</td>
              <td class="px-4 py-2.5 text-xs text-gray-900">{{ activity.computer_id }}</td>
              <td class="px-4 py-2.5 text-xs text-gray-900">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium bg-gray-100 text-gray-700">
                  {{ activity.browser_name }}
                </span>
              </td>
              <td class="px-4 py-2.5 text-xs text-gray-900 max-w-[200px]">
                <button 
                  @click="openDetailsModal(activity)"
                  class="text-blue-600 hover:text-blue-800 hover:underline cursor-pointer truncate block w-full text-left"
                  :title="activity.title"
                >
                  {{ activity.title }}
                </button>
              </td>
              <td class="px-4 py-2.5 text-xs text-gray-900 max-w-[250px]">
                <button 
                  @click="openDetailsModal(activity)"
                  class="text-blue-600 hover:text-blue-800 hover:underline cursor-pointer truncate block w-full text-left"
                  :title="activity.url"
                >
                  {{ activity.url }}
                </button>
              </td>
              <td class="px-4 py-2.5 text-xs text-gray-900 whitespace-nowrap">{{ activity.duration }} sec</td>
              <td class="px-4 py-2.5 text-xs text-gray-900 whitespace-nowrap">{{ dayjs(activity.created_at).format("MMM D, YYYY h:mm A") }}</td>
            </tr>
          </template>
        </Table>
      </div>

      <!-- Mobile View -->
      <div class="sm:hidden space-y-4 mt-4">
        <div
          v-for="activity in browserActivity"
          :key="activity.id"
          class="bg-white rounded-lg border border-gray-200 p-4"
        >
          <div class="flex items-start justify-between mb-3">
            <div>
              <h3 class="text-sm font-medium text-gray-900">{{ activity.ip_address }}</h3>
              <p class="text-xs text-gray-600 mt-0.5">Station: {{ activity.computer_id }}</p>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium bg-gray-100 text-gray-700">
              {{ activity.browser_name }}
            </span>
          </div>
          
          <div class="space-y-2">
            <div class="text-xs">
              <span class="text-gray-600">Title:</span>
              <p class="text-gray-900 mt-1 truncate">{{ activity.title }}</p>
            </div>
            <div class="text-xs">
              <span class="text-gray-600">URL:</span>
              <p class="text-blue-600 mt-1 truncate">{{ activity.url }}</p>
            </div>
            <div class="flex justify-between text-xs">
              <span class="text-gray-600">Duration:</span>
              <span class="text-gray-900">{{ activity.duration }} sec</span>
            </div>
            <div class="flex justify-between text-xs">
              <span class="text-gray-600">Date:</span>
              <span class="text-gray-900">{{ dayjs(activity.created_at).format("MMM D, YYYY h:mm A") }}</span>
            </div>
            <button
              @click="openDetailsModal(activity)"
              class="w-full mt-3 px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
            >
              View Details
            </button>
          </div>
        </div>
      </div>

      <!-- Details Modal -->
      <Modal :show="showDetailsModal" @close="showDetailsModal = false">
        <div class="bg-white/95 backdrop-blur-md rounded-lg shadow-xl w-full max-w-2xl mx-auto relative">
          <!-- Modal Header -->
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Page Details</h3>
          </div>

          <!-- Modal Content -->
          <div class="px-6 py-4 space-y-4 max-h-96 overflow-y-auto">
            <!-- Title Section -->
            <div>
              <label class="text-xs font-semibold text-gray-600 uppercase">Title</label>
              <p class="mt-2 text-xs text-gray-900 bg-gray-50 p-3 rounded-lg break-words">{{ modalTitle || 'N/A' }}</p>
            </div>

            <!-- URL Section -->
            <div>
              <label class="text-xs font-semibold text-gray-600 uppercase">URL</label>
              <a 
                :href="modalUrl" 
                target="_blank"
                class="mt-2 text-xs text-blue-600 hover:text-blue-800 hover:underline bg-gray-50 p-3 rounded-lg block break-all"
              >
                {{ modalUrl || 'N/A' }}
              </a>
            </div>

            <!-- Browser Section -->
            <div>
              <label class="text-xs font-semibold text-gray-600 uppercase">Browser</label>
              <p class="mt-2 text-xs text-gray-900 bg-gray-50 p-3 rounded-lg">{{ modalBrowser || 'N/A' }}</p>
            </div>

            <!-- Duration Section -->
            <div>
              <label class="text-xs font-semibold text-gray-600 uppercase">Duration</label>
              <p class="mt-2 text-xs text-gray-900 bg-gray-50 p-3 rounded-lg">{{ modalDuration }} seconds</p>
            </div>

            <!-- Date & Time Section -->
            <div>
              <label class="text-xs font-semibold text-gray-600 uppercase">Date & Time</label>
              <p class="mt-2 text-xs text-gray-900 bg-gray-50 p-3 rounded-lg">{{ modalDateTime || 'N/A' }}</p>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
            <button
              @click="showDetailsModal = false"
              class="px-4 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Close
            </button>
          </div>
        </div>
      </Modal>
    </div>
  </AuthenticatedLayout>
</template>
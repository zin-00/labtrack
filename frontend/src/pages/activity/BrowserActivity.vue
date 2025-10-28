<script setup>
import { toRefs, onMounted, computed, ref } from 'vue';
import AuthenticatedLayout from '../../layouts/auth/AuthenticatedLayout.vue';
import { useStates } from '../../composable/states';
import Table from '../../components/table/Table.vue';
import Modal from '../../components/modal/Modal.vue';
import { XMarkIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/solid';
import { useBrowserActivityStore } from '../../composable/activity/browserActivity';
import dayjs from 'dayjs';

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

const FilteredActivity = computed(() => {
    if(!browserActivity.value || !Array.isArray(browserActivity.value)){
      return [];
    }

    return browserActivity.value.filter((act) => {
        const matchesSearch =
          !searchQuery.value ||
          act.browser_name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
          act.title?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
          act.url?.toLowerCase().includes(searchQuery.value.toLowerCase());

        const createdAt = new Date(act.created_at);
        const from = dateFrom.value ? new Date(dateFrom.value) : null;
        const to = dateTo.value ? new Date(dateTo.value) : null;

        const matchesDate =
          (!from || createdAt >= from) && (!to || createdAt <= to);

        return matchesSearch && matchesDate;
      });
});

const openDetailsModal = (activity) => {
    modalTitle.value = activity.title;
    modalUrl.value = activity.url;
    modalBrowser.value = activity.browser_name;
    modalDuration.value = activity.duration;
    modalDateTime.value = dayjs(activity.created_at).format('MMM D, YYYY h:mm A');
    showDetailsModal.value = true;
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
        const tableData = FilteredActivity.value.map((activity) => [
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

        const bodyRows = FilteredActivity.value.map(activity => new TableRow({
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
    <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
          <h2 class="text-2xl text-gray-900">Browser Activity</h2>
          <p class="mt-1 text-xs text-gray-600">Monitor computer usage and system events</p>
        </div>
      </div>

      <!-- Filters Panel And Actions -->
      <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row gap-2 justify-between">
        <!-- Search and Date Filter -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
          <!-- Search -->
          <div class="flex items-center gap-4">
            <div class="relative flex-1 max-w-md">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search programs..."
                class="w-full px-4 py-2 border border-gray-200 rounded-lg text-[10px] focus:border-gray-400 focus:outline-none transition-colors"
              />
              <button
                v-if="searchQuery"
                @click="searchQuery = ''"
                class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600"
              >
                <XMarkIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
          
          <!-- Date Filter -->
          <div class="flex items-center gap-2">
            <input
              type="date"
              v-model="dateFrom"
              class="px-3 py-2 border border-gray-200 rounded-lg text-[10px] focus:border-gray-400 focus:outline-none"
            />
            <span class="text-gray-500 text-[10px]">to</span>
            <input
              type="date"
              v-model="dateTo"
              class="px-3 py-2 border border-gray-200 rounded-lg text-[10px] focus:border-gray-400 focus:outline-none"
            />
          </div>
        </div>

        <!-- Export Buttons -->
        <div class="flex gap-2">
          <button
            @click="exportToPDF"
            :disabled="FilteredActivity.length === 0"
            class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-[10px] font-medium rounded-lg hover:bg-red-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
          >
            <ArrowDownTrayIcon class="w-4 h-4" />
            Export PDF
          </button>
          <button
            @click="exportToDocx"
            :disabled="FilteredActivity.length === 0"
            class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-[10px] font-medium rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
          >
            <ArrowDownTrayIcon class="w-4 h-4" />
            Export DOCX
          </button>
        </div>
      </div>

      <!-- Table with correct skeleton columns that match the header structure -->
      <Table
        :data="FilteredActivity"
        :is-loading="isLoading"
        :pagination="pagination"
        :mobile-fields="['ip_address', 'computer_id', 'browser_name', 'title', 'url', 'duration', 'created_at']"
        @page-change="getBrowserActivity">
        
        <template #header>
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Work Station</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Browser</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
            </tr>
          </thead>
        </template>
        
        <template #default>
          <tr v-for="activity in FilteredActivity" :key="activity.id" class="bg-white even:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-[10px] text-gray-900">{{ activity.ip_address }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-[10px] text-gray-900">{{ activity.computer_id }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-[10px] text-gray-900">{{ activity.browser_name }}</td>
            <td class="px-6 py-4 text-[10px] text-gray-900">
              <button 
                @click="openDetailsModal(activity)"
                class="text-blue-600 hover:text-blue-800 hover:underline cursor-pointer truncate max-w-[150px] block"
                :title="activity.title"
              >
                {{ activity.title }}
              </button>
            </td>
            <td class="px-6 py-4 text-[10px] text-gray-900">
              <button 
                @click="openDetailsModal(activity)"
                class="text-blue-600 hover:text-blue-800 hover:underline cursor-pointer truncate max-w-[200px] block"
                :title="activity.url"
              >
                {{ activity.url }}
              </button>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-[10px] text-gray-900">{{ activity.duration }} seconds</td>
            <td class="px-6 py-4 whitespace-nowrap text-[10px] text-gray-900">{{ dayjs(activity.created_at).format("MMM D, YYYY h:mm A") }}</td>
          </tr>
        </template>
      </Table>

      <!-- Details Modal -->
      <Modal :show="showDetailsModal" @close="showDetailsModal = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-auto relative">
          <!-- Modal Header -->
          <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-medium text-gray-900">Page Details</h3>
          </div>

          <!-- Modal Content -->
          <div class="px-6 py-4 space-y-4 max-h-96 overflow-y-auto">
            <!-- Title Section -->
            <div>
              <label class="text-xs font-semibold text-gray-600 uppercase">Title</label>
              <p class="mt-2 text-[10px] text-gray-900 bg-gray-50 p-3 rounded-lg break-words">{{ modalTitle || 'N/A' }}</p>
            </div>

            <!-- URL Section -->
            <div>
              <label class="text-xs font-semibold text-gray-600 uppercase">URL</label>
              <a 
                :href="modalUrl" 
                target="_blank"
                class="mt-2 text-[10px] text-blue-600 hover:text-blue-800 hover:underline bg-gray-50 p-3 rounded-lg block break-all"
              >
                {{ modalUrl || 'N/A' }}
              </a>
            </div>

            <!-- Browser Section -->
            <div>
              <label class="text-xs font-semibold text-gray-600 uppercase">Browser</label>
              <p class="mt-2 text-[10px] text-gray-900 bg-gray-50 p-3 rounded-lg">{{ modalBrowser || 'N/A' }}</p>
            </div>

            <!-- Duration Section -->
            <div>
              <label class="text-xs font-semibold text-gray-600 uppercase">Duration</label>
              <p class="mt-2 text-[10px] text-gray-900 bg-gray-50 p-3 rounded-lg">{{ modalDuration }} seconds</p>
            </div>

            <!-- Date & Time Section -->
            <div>
              <label class="text-xs font-semibold text-gray-600 uppercase">Date & Time</label>
              <p class="mt-2 text-[10px] text-gray-900 bg-gray-50 p-3 rounded-lg">{{ modalDateTime || 'N/A' }}</p>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
            <button
              @click="showDetailsModal = false"
              class="px-4 py-2 text-[10px] font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition"
            >
              Close
            </button>
          </div>
        </div>
      </Modal>
    </div>
  </AuthenticatedLayout>
</template>
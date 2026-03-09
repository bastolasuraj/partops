<template>
  <div>
    <div class="mb-8 slide-up">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Report Builder</h1>
          <p class="text-gray-600">Generate user, supplier, transaction, and parts reports with PDF/XLSX export.</p>
        </div>
        <div class="text-sm text-gray-500">
          Last generated:
          <span class="font-medium text-gray-700">{{ report ? formatDateTime(report.generated_at) : '-' }}</span>
        </div>
      </div>
    </div>

    <div class="card slide-up mb-6 overflow-visible">
      <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-8 gap-4">
          <div class="form-group md:col-span-3 lg:col-span-2">
            <label class="form-label">Date Range</label>
            <DateRangePicker
              :model-value="{ start: filters.dateFrom, end: filters.dateTo }"
              @update:model-value="(val) => { filters.dateFrom = val.start; filters.dateTo = val.end }"
            />
          </div>

          <div class="form-group md:col-span-3 lg:col-span-2">
            <label class="form-label">Quick Range</label>
            <div class="flex gap-2 flex-wrap">
              <button class="btn-outline text-sm !px-3 !py-2" @click="setLast30Days">Last 30 Days</button>
              <button class="btn-outline text-sm !px-3 !py-2" @click="setThisMonth">This Month</button>
              <button class="btn-outline text-sm !px-3 !py-2" @click="clearDateRange">All Time</button>
            </div>
          </div>

          <div class="form-group md:col-span-3 lg:col-span-2">
            <label class="form-label">Report Type</label>
            <div class="flex gap-2 flex-wrap">
              <button
                v-for="option in reportTypeOptions"
                :key="option.value"
                type="button"
                class="text-xs !px-3 !py-2"
                :class="selectedReportType === option.value ? 'btn-primary' : 'btn-outline'"
                @click="selectedReportType = option.value"
              >
                {{ option.label }}
              </button>
            </div>
          </div>

          <div class="form-group md:col-span-3 lg:col-span-2">
            <label class="form-label">Actions</label>
            <div class="flex gap-2 flex-wrap items-center">
              <button class="btn-primary text-sm !px-3 !py-2" :disabled="loading" @click="fetchReport">
                {{ generateLabel }}
              </button>
              <button class="btn-outline text-sm !px-3 !py-2" :disabled="!report || exportingXlsx" @click="downloadXlsx">
                {{ downloadXlsxLabel }}
              </button>
              <button class="btn-outline text-sm !px-3 !py-2" :disabled="!report || exportingPdf" @click="downloadPdf">
                {{ downloadPdfLabel }}
              </button>
            </div>
            <p class="text-xs text-gray-500 mt-2">Each download is also archived on the server.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="card slide-up mb-6">
      <div class="card-header">
        <h2 class="text-xl font-semibold text-gray-800">Archived Reports</h2>
        <div class="flex flex-wrap items-center gap-2">
          <button
            v-for="option in reportTypeOptions"
            :key="`archive-${option.value}`"
            type="button"
            class="text-xs !px-3 !py-2"
            :class="archiveScopeFilter === option.value ? 'btn-primary' : 'btn-outline'"
            @click="setArchiveScope(option.value)"
          >
            {{ option.label }}
          </button>
          <button
            class="btn-outline text-xs !px-3 !py-2"
            :disabled="archiveLoading"
            @click="fetchArchiveList"
          >
            {{ archiveLoading ? 'Refreshing...' : 'Refresh' }}
          </button>
        </div>
      </div>
      <div class="card-body pt-0">
        <p class="text-xs text-gray-500 mb-3">
          Showing {{ archiveRecords.length }} archived files
          <span class="font-medium text-gray-700">({{ prettyArchiveScope(archiveScopeFilter) }})</span>
        </p>

        <div v-if="archiveLoading" class="text-center py-8">
          <div class="spinner mx-auto mb-3"></div>
          <p class="text-sm text-gray-500">Loading archived reports...</p>
        </div>

        <div v-else-if="archiveError" class="bg-red-50 border border-red-100 rounded-lg p-4 text-sm text-red-700">
          {{ archiveError }}
        </div>

        <div v-else-if="archiveRecords.length === 0" class="text-center py-8 text-sm text-gray-500">
          No archived reports found yet.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="data-table min-w-[1200px]">
            <thead>
              <tr>
                <th>Archived At</th>
                <th>Scope</th>
                <th>Format</th>
                <th>Date Range</th>
                <th>Size</th>
                <th>Saved By</th>
                <th>File Name</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in archiveRecords" :key="row.archive_relative_path">
                <td>{{ formatDateTime(row.archived_at) }}</td>
                <td>
                  <span class="badge badge-info">{{ prettyArchiveScope(row.report_scope) }}</span>
                </td>
                <td>{{ row.mime_type === 'application/pdf' ? 'PDF' : 'XLSX' }}</td>
                <td>{{ row.date_from || 'Beginning' }} to {{ row.date_to || 'Now' }}</td>
                <td>{{ formatFileSize(row.size_bytes) }}</td>
                <td>{{ row.user?.display_name || row.user?.username || 'System' }}</td>
                <td class="font-mono text-xs">{{ row.original_file_name || row.archive_file_name }}</td>
                <td>
                  <button
                    class="btn-outline text-xs !px-3 !py-1.5"
                    :disabled="!row.available || archiveDownloadingPath === row.archive_relative_path"
                    @click="downloadArchivedCopy(row)"
                  >
                    {{
                      archiveDownloadingPath === row.archive_relative_path
                        ? 'Downloading...'
                        : row.available
                          ? 'Download'
                          : 'Missing'
                    }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-if="loading" class="card">
      <div class="card-body text-center py-12">
        <div class="spinner mx-auto mb-4"></div>
        <p class="text-gray-500">Building report...</p>
      </div>
    </div>

    <div v-else-if="report" class="space-y-6">
      <div class="card slide-up">
        <div class="card-body py-4">
          <div class="flex flex-wrap gap-2 items-center text-sm">
            <span class="badge badge-info">Scope: {{ selectedReportTypeLabel }}</span>
            <span class="badge badge-info">Date Range: {{ selectedRangeLabel }}</span>
            <span class="badge badge-success">Archive: Enabled</span>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 slide-up">
        <div v-if="showUsers" class="card">
          <div class="card-body">
            <p class="text-sm text-gray-500 font-medium">Users in Report</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ userTotals.user_count }}</p>
            <p class="text-xs text-gray-500 mt-2">
              Not returned cost: <span class="font-medium text-orange-600">{{ formatCurrency(userTotals.not_returned_cost) }}</span>
            </p>
          </div>
        </div>
        <div v-if="showSuppliers" class="card">
          <div class="card-body">
            <p class="text-sm text-gray-500 font-medium">Suppliers in Report</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ supplierTotals.supplier_count }}</p>
            <p class="text-xs text-gray-500 mt-2">
              Net supplier cost: <span class="font-medium text-blue-700">{{ formatCurrency(supplierTotals.net_cost) }}</span>
            </p>
          </div>
        </div>
        <div v-if="showTransactions" class="card">
          <div class="card-body">
            <p class="text-sm text-gray-500 font-medium">Transactions</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ transactionOverall.transaction_count }}</p>
            <p class="text-xs text-gray-500 mt-2">
              Movement qty: <span class="font-medium text-gray-700">{{ formatInt(transactionOverall.absolute_qty) }}</span>
            </p>
          </div>
        </div>
        <div v-if="showParts" class="card">
          <div class="card-body">
            <p class="text-sm text-gray-500 font-medium">Closing Inventory Cost</p>
            <p class="text-2xl font-bold text-emerald-700 mt-1">{{ formatCurrency(closingInventoryCost) }}</p>
            <p class="text-xs text-gray-500 mt-2">
              Opening: <span class="font-medium text-gray-700">{{ formatCurrency(openingInventoryCost) }}</span>
              ({{ formatInt(openingInventoryUnits) }} units)
            </p>
            <p class="text-xs text-gray-500 mt-1">
              Change:
              <span :class="inventoryCostChange >= 0 ? 'font-medium text-emerald-700' : 'font-medium text-orange-700'">
                {{ formatCurrency(inventoryCostChange) }}
              </span>
            </p>
          </div>
        </div>
      </div>

      <div v-if="!hasVisibleRows" class="card slide-up">
        <div class="card-body text-center py-12">
          <p class="text-lg font-semibold text-gray-800">No data for the selected filters</p>
          <p class="text-sm text-gray-500 mt-2">
            Try a different date range or choose another report type.
          </p>
        </div>
      </div>

      <template v-else>
      <section v-if="showUsers" class="card slide-up">
        <div class="card-header">
          <h2 class="text-xl font-semibold text-gray-800">User Summary</h2>
          <div class="text-sm text-gray-500">{{ userSummary.length }} users</div>
        </div>
        <div class="overflow-x-auto">
          <table class="data-table min-w-[900px]">
            <thead>
              <tr>
                <th>User</th>
                <th>Taken Qty</th>
                <th>Taken Cost</th>
                <th>Returned Qty</th>
                <th>Returned Cost</th>
                <th>Not Returned Qty</th>
                <th>Not Returned Cost</th>
                <th>Transactions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in userSummary" :key="`user-${row.user_name}`">
                <td>{{ row.user_name }}</td>
                <td>{{ formatInt(row.taken_qty) }}</td>
                <td>{{ formatCurrency(row.taken_cost) }}</td>
                <td>{{ formatInt(row.returned_qty) }}</td>
                <td>{{ formatCurrency(row.returned_cost) }}</td>
                <td class="font-medium text-orange-700">{{ formatInt(row.not_returned_qty) }}</td>
                <td class="font-medium text-orange-700">{{ formatCurrency(row.not_returned_cost) }}</td>
                <td>{{ formatInt(row.transaction_count) }}</td>
              </tr>
              <tr class="bg-gray-50 font-semibold text-gray-800">
                <td>Overall</td>
                <td>{{ formatInt(userTotals.taken_qty) }}</td>
                <td>{{ formatCurrency(userTotals.taken_cost) }}</td>
                <td>{{ formatInt(userTotals.returned_qty) }}</td>
                <td>{{ formatCurrency(userTotals.returned_cost) }}</td>
                <td>{{ formatInt(userTotals.not_returned_qty) }}</td>
                <td>{{ formatCurrency(userTotals.not_returned_cost) }}</td>
                <td>{{ formatInt(userTotals.transaction_count) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-if="showUsers" class="card slide-up">
        <div class="card-header">
          <h2 class="text-xl font-semibold text-gray-800">User Movement by Part</h2>
          <div class="text-sm text-gray-500">{{ userMovement.length }} records</div>
        </div>
        <div class="overflow-x-auto">
          <table class="data-table min-w-[1100px]">
            <thead>
              <tr>
                <th>User</th>
                <th>Fowler PN</th>
                <th>Supplier PN</th>
                <th>Part</th>
                <th>Taken Qty</th>
                <th>Returned Qty</th>
                <th>Not Returned Qty</th>
                <th>Taken Cost</th>
                <th>Returned Cost</th>
                <th>Not Returned Cost</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in userMovement" :key="`movement-${row.user_name}-${row.part_id}`">
                <td>{{ row.user_name }}</td>
                <td class="font-mono">{{ row.fowler_part_number }}</td>
                <td class="font-mono">{{ row.supplier_part_number || '-' }}</td>
                <td>{{ row.part_name }}</td>
                <td>{{ formatInt(row.taken_qty) }}</td>
                <td>{{ formatInt(row.returned_qty) }}</td>
                <td class="font-medium text-orange-700">{{ formatInt(row.not_returned_qty) }}</td>
                <td>{{ formatCurrency(row.taken_cost) }}</td>
                <td>{{ formatCurrency(row.returned_cost) }}</td>
                <td class="font-medium text-orange-700">{{ formatCurrency(row.not_returned_cost) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-if="showSuppliers" class="card slide-up">
        <div class="card-header">
          <h2 class="text-xl font-semibold text-gray-800">Supplier Summary</h2>
          <div class="text-sm text-gray-500">{{ supplierSummary.length }} suppliers</div>
        </div>
        <div class="overflow-x-auto">
          <table class="data-table min-w-[900px]">
            <thead>
              <tr>
                <th>Supplier</th>
                <th>Incoming Qty</th>
                <th>Incoming Cost</th>
                <th>Returned Qty</th>
                <th>Returned Cost</th>
                <th>Net Qty</th>
                <th>Net Cost</th>
                <th>Transactions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in supplierSummary" :key="`supplier-${row.supplier_id}`">
                <td>{{ row.supplier_name }}</td>
                <td>{{ formatInt(row.incoming_qty) }}</td>
                <td>{{ formatCurrency(row.incoming_cost) }}</td>
                <td>{{ formatInt(row.returned_qty) }}</td>
                <td>{{ formatCurrency(row.returned_cost) }}</td>
                <td>{{ formatInt(row.net_qty) }}</td>
                <td class="font-medium text-blue-700">{{ formatCurrency(row.net_cost) }}</td>
                <td>{{ formatInt(row.transaction_count) }}</td>
              </tr>
              <tr class="bg-gray-50 font-semibold text-gray-800">
                <td>Overall</td>
                <td>{{ formatInt(supplierTotals.incoming_qty) }}</td>
                <td>{{ formatCurrency(supplierTotals.incoming_cost) }}</td>
                <td>{{ formatInt(supplierTotals.returned_qty) }}</td>
                <td>{{ formatCurrency(supplierTotals.returned_cost) }}</td>
                <td>{{ formatInt(supplierTotals.net_qty) }}</td>
                <td>{{ formatCurrency(supplierTotals.net_cost) }}</td>
                <td>{{ formatInt(supplierTotals.transaction_count) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-if="showSuppliers" class="card slide-up">
        <div class="card-header">
          <h2 class="text-xl font-semibold text-gray-800">Items Returned to Supplier</h2>
          <div class="text-sm text-gray-500">{{ supplierReturnedItems.length }} rows</div>
        </div>
        <div class="overflow-x-auto">
          <table class="data-table min-w-[1000px]">
            <thead>
              <tr>
                <th>Date</th>
                <th>Supplier</th>
                <th>Fowler PN</th>
                <th>Supplier PN</th>
                <th>Part</th>
                <th>Qty</th>
                <th>Unit Cost</th>
                <th>Total Cost</th>
                <th>User</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, idx) in supplierReturnedItems" :key="`returned-${idx}`">
                <td>{{ formatDateTime(row.created_at) }}</td>
                <td>{{ row.supplier_name }}</td>
                <td class="font-mono">{{ row.fowler_part_number }}</td>
                <td class="font-mono">{{ row.supplier_part_number || '-' }}</td>
                <td>{{ row.part_name }}</td>
                <td>{{ formatInt(row.quantity) }}</td>
                <td>{{ formatCurrency(row.unit_price) }}</td>
                <td>{{ formatCurrency(row.total_cost) }}</td>
                <td>{{ row.created_by || 'System' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-if="showTransactions" class="card slide-up">
        <div class="card-header">
          <h2 class="text-xl font-semibold text-gray-800">Transaction Matrix</h2>
          <div class="text-sm text-gray-500">{{ transactionMatrix.length }} grouped rows</div>
        </div>
        <div class="overflow-x-auto">
          <table class="data-table min-w-[760px]">
            <thead>
              <tr>
                <th>Type</th>
                <th>Reference</th>
                <th>Transactions</th>
                <th>Signed Qty</th>
                <th>Absolute Qty</th>
                <th>Total Cost</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, idx) in transactionMatrix" :key="`txn-${idx}`">
                <td class="capitalize">{{ row.transaction_type }}</td>
                <td class="capitalize">{{ prettyReference(row.reference_type) }}</td>
                <td>{{ formatInt(row.transaction_count) }}</td>
                <td>{{ formatInt(row.signed_qty) }}</td>
                <td>{{ formatInt(row.absolute_qty) }}</td>
                <td>{{ formatCurrency(row.total_cost) }}</td>
              </tr>
              <tr class="bg-gray-50 font-semibold text-gray-800">
                <td>Overall</td>
                <td>-</td>
                <td>{{ formatInt(transactionOverall.transaction_count) }}</td>
                <td>-</td>
                <td>{{ formatInt(transactionOverall.absolute_qty) }}</td>
                <td>{{ formatCurrency(transactionOverall.total_cost) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-if="showParts" class="card slide-up">
        <div class="card-header">
          <h2 class="text-xl font-semibold text-gray-800">Parts Inventory Snapshot</h2>
          <div class="text-sm text-gray-500">{{ partInventory.length }} parts</div>
        </div>
        <div class="px-4 pb-3">
          <div class="flex flex-wrap gap-2 text-xs">
            <span class="badge badge-info">
              Opening: {{ formatCurrency(openingInventoryCost) }} ({{ formatInt(openingInventoryUnits) }} units)
            </span>
            <span class="badge badge-info">
              Closing: {{ formatCurrency(closingInventoryCost) }} ({{ formatInt(closingInventoryUnits) }} units)
            </span>
            <span class="badge" :class="inventoryCostChange >= 0 ? 'badge-success' : 'badge-warning'">
              Change: {{ formatCurrency(inventoryCostChange) }}
            </span>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="data-table min-w-[1000px]">
            <thead>
              <tr>
                <th>Fowler PN</th>
                <th>Supplier PN</th>
                <th>Part</th>
                <th>Supplier</th>
                <th>Stock</th>
                <th>Unit Price</th>
                <th>Inventory Cost</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in partInventory" :key="`inv-${row.part_id}`">
                <td class="font-mono">{{ row.fowler_part_number }}</td>
                <td class="font-mono">{{ row.supplier_part_number || '-' }}</td>
                <td>{{ row.part_name }}</td>
                <td>{{ row.supplier_name || '-' }}</td>
                <td>{{ formatInt(row.stock) }}</td>
                <td>{{ formatCurrency(row.unit_price) }}</td>
                <td class="font-medium text-emerald-700">{{ formatCurrency(row.inventory_cost) }}</td>
              </tr>
              <tr class="bg-gray-50 font-semibold text-gray-800">
                <td colspan="4">Overall</td>
                <td>{{ formatInt(partInventoryTotals.stock_units) }}</td>
                <td>-</td>
                <td>{{ formatCurrency(partInventoryTotals.inventory_cost) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-if="showParts" class="card slide-up">
        <div class="card-header">
          <h2 class="text-xl font-semibold text-gray-800">Part Movement</h2>
          <div class="text-sm text-gray-500">{{ partMovement.length }} parts</div>
        </div>
        <div class="overflow-x-auto">
          <table class="data-table min-w-[1200px]">
            <thead>
              <tr>
                <th>Fowler PN</th>
                <th>Supplier PN</th>
                <th>Part</th>
                <th>Incoming Qty</th>
                <th>Incoming Cost</th>
                <th>Outgoing Qty</th>
                <th>Outgoing Cost</th>
                <th>Return Qty</th>
                <th>Return Cost</th>
                <th>Net Qty</th>
                <th>Net Cost</th>
                <th>Movements</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in partMovement" :key="`mv-${row.part_id}`">
                <td class="font-mono">{{ row.fowler_part_number }}</td>
                <td class="font-mono">{{ row.supplier_part_number || '-' }}</td>
                <td>{{ row.part_name }}</td>
                <td>{{ formatInt(row.incoming_qty) }}</td>
                <td>{{ formatCurrency(row.incoming_cost) }}</td>
                <td>{{ formatInt(row.outgoing_qty) }}</td>
                <td>{{ formatCurrency(row.outgoing_cost) }}</td>
                <td>{{ formatInt(row.return_qty) }}</td>
                <td>{{ formatCurrency(row.return_cost) }}</td>
                <td>{{ formatInt(row.net_qty) }}</td>
                <td :class="row.net_cost >= 0 ? 'text-emerald-700 font-medium' : 'text-orange-700 font-medium'">
                  {{ formatCurrency(row.net_cost) }}
                </td>
                <td>{{ formatInt(row.movement_count) }}</td>
              </tr>
              <tr class="bg-gray-50 font-semibold text-gray-800">
                <td colspan="3">Overall</td>
                <td>{{ formatInt(partMovementTotals.incoming_qty) }}</td>
                <td>{{ formatCurrency(partMovementTotals.incoming_cost) }}</td>
                <td>{{ formatInt(partMovementTotals.outgoing_qty) }}</td>
                <td>{{ formatCurrency(partMovementTotals.outgoing_cost) }}</td>
                <td>{{ formatInt(partMovementTotals.return_qty) }}</td>
                <td>{{ formatCurrency(partMovementTotals.return_cost) }}</td>
                <td>{{ formatInt(partMovementTotals.net_qty) }}</td>
                <td>{{ formatCurrency(partMovementTotals.net_cost) }}</td>
                <td>{{ formatInt(partMovementTotals.movement_count) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useToast } from '@/composables/useToast'
import { reportsApi } from '@/services/api'
import DateRangePicker from '@/components/DateRangePicker.vue'
import * as XLSX from 'xlsx'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'

const { showToast } = useToast()

const loading = ref(false)
const exportingXlsx = ref(false)
const exportingPdf = ref(false)
const report = ref(null)
const selectedReportType = ref('all')
const archiveLoading = ref(false)
const archiveError = ref('')
const archiveScopeFilter = ref('all')
const archiveRecords = ref([])
const archiveDownloadingPath = ref('')

const filters = ref({
  dateFrom: '',
  dateTo: ''
})

const reportTypeOptions = [
  { value: 'all', label: 'All Reports' },
  { value: 'users', label: 'Users' },
  { value: 'suppliers', label: 'Suppliers' },
  { value: 'transactions', label: 'Transactions' },
  { value: 'parts', label: 'Parts' }
]

const prettyArchiveScope = (scope) => {
  return reportTypeOptions.find((item) => item.value === scope)?.label || scope || 'All Reports'
}

const selectedReportTypeLabel = computed(() => {
  return reportTypeOptions.find((item) => item.value === selectedReportType.value)?.label || 'Report'
})

const selectedRangeLabel = computed(() => {
  const from = report.value?.filters?.date_from || filters.value.dateFrom
  const to = report.value?.filters?.date_to || filters.value.dateTo

  if (!from && !to) {
    return 'All Time'
  }

  return `${from || 'Beginning'} to ${to || 'Now'}`
})

const showUsers = computed(() => selectedReportType.value === 'all' || selectedReportType.value === 'users')
const showSuppliers = computed(() => selectedReportType.value === 'all' || selectedReportType.value === 'suppliers')
const showTransactions = computed(() => selectedReportType.value === 'all' || selectedReportType.value === 'transactions')
const showParts = computed(() => selectedReportType.value === 'all' || selectedReportType.value === 'parts')

const generateLabel = computed(() => {
  if (loading.value) {
    return 'Generating...'
  }
  return selectedReportType.value === 'all' ? 'Generate All' : `Generate ${selectedReportTypeLabel.value}`
})

const downloadXlsxLabel = computed(() => {
  if (exportingXlsx.value) {
    return 'Preparing...'
  }
  return selectedReportType.value === 'all'
    ? 'Download XLSX (All)'
    : `Download XLSX (${selectedReportTypeLabel.value})`
})

const downloadPdfLabel = computed(() => {
  if (exportingPdf.value) {
    return 'Preparing...'
  }
  return selectedReportType.value === 'all'
    ? 'Download PDF (All)'
    : `Download PDF (${selectedReportTypeLabel.value})`
})

const userSummary = computed(() => report.value?.users?.summary || [])
const userMovement = computed(() => report.value?.users?.movement || [])
const userTotals = computed(() => report.value?.users?.totals || {})

const supplierSummary = computed(() => report.value?.suppliers?.summary || [])
const supplierReturnedItems = computed(() => report.value?.suppliers?.returned_items || [])
const supplierTotals = computed(() => report.value?.suppliers?.totals || {})

const transactionMatrix = computed(() => report.value?.transactions?.matrix || [])
const transactionOverall = computed(() => report.value?.transactions?.overall || {})

const partInventory = computed(() => report.value?.parts?.inventory || [])
const partMovement = computed(() => report.value?.parts?.movement || [])
const partInventoryTotals = computed(() => report.value?.parts?.totals?.inventory || {})
const partMovementTotals = computed(() => report.value?.parts?.totals?.movement || {})
const openingInventoryUnits = computed(() => Number(partInventoryTotals.value.opening_stock_units || 0))
const closingInventoryUnits = computed(() => Number(partInventoryTotals.value.closing_stock_units || 0))
const openingInventoryCost = computed(() => Number(partInventoryTotals.value.opening_inventory_cost || 0))
const closingInventoryCost = computed(() => Number(partInventoryTotals.value.closing_inventory_cost || 0))
const inventoryCostChange = computed(() => Number(partInventoryTotals.value.inventory_cost_change || 0))

const hasUsersRows = computed(() => userSummary.value.length > 0 || userMovement.value.length > 0)
const hasSuppliersRows = computed(() => supplierSummary.value.length > 0 || supplierReturnedItems.value.length > 0)
const hasTransactionsRows = computed(() => transactionMatrix.value.length > 0)
const hasPartsRows = computed(() => partInventory.value.length > 0 || partMovement.value.length > 0)

const hasVisibleRows = computed(() => {
  if (selectedReportType.value === 'users') {
    return hasUsersRows.value
  }

  if (selectedReportType.value === 'suppliers') {
    return hasSuppliersRows.value
  }

  if (selectedReportType.value === 'transactions') {
    return hasTransactionsRows.value
  }

  if (selectedReportType.value === 'parts') {
    return hasPartsRows.value
  }

  return hasUsersRows.value || hasSuppliersRows.value || hasTransactionsRows.value || hasPartsRows.value
})

const getParams = () => {
  const params = {}
  if (filters.value.dateFrom) {
    params.date_from = filters.value.dateFrom
  }
  if (filters.value.dateTo) {
    params.date_to = filters.value.dateTo
  }
  return params
}

const fetchReport = async () => {
  try {
    loading.value = true
    const response = await reportsApi.getSummary(getParams())
    report.value = response.data || null
  } catch (error) {
    showToast('Error', error.response?.data?.message || 'Failed to generate report', 'error')
  } finally {
    loading.value = false
  }
}

const fetchArchiveList = async () => {
  try {
    archiveLoading.value = true
    archiveError.value = ''
    const response = await reportsApi.getArchiveList({
      scope: archiveScopeFilter.value,
      limit: 200
    })
    archiveRecords.value = response.data?.records || []
  } catch (error) {
    archiveError.value = error?.response?.data?.message || 'Failed to load archived reports'
  } finally {
    archiveLoading.value = false
  }
}

const setArchiveScope = (scope) => {
  archiveScopeFilter.value = scope
  fetchArchiveList()
}

const toYmd = (date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const setLast30Days = () => {
  const end = new Date()
  const start = new Date()
  start.setDate(start.getDate() - 29)
  filters.value.dateFrom = toYmd(start)
  filters.value.dateTo = toYmd(end)
}

const setThisMonth = () => {
  const now = new Date()
  const start = new Date(now.getFullYear(), now.getMonth(), 1)
  filters.value.dateFrom = toYmd(start)
  filters.value.dateTo = toYmd(now)
}

const clearDateRange = () => {
  filters.value.dateFrom = ''
  filters.value.dateTo = ''
}

const prettyReference = (type) => {
  const labels = {
    vendor: 'Vendor',
    supplier: 'Supplier',
    work_order: 'Work Order',
    unit: 'Unit',
    technician: 'Technician',
    adjustment: 'Adjustment',
    unknown: 'Unknown'
  }
  return labels[type] || type || '-'
}

const formatInt = (value) => {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(amount)
}

const formatCurrency = (value) => {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

const formatFileSize = (value) => {
  const bytes = Number(value || 0)
  if (!Number.isFinite(bytes) || bytes <= 0) {
    return '0 B'
  }

  if (bytes < 1024) {
    return `${bytes} B`
  }
  if (bytes < 1024 * 1024) {
    return `${(bytes / 1024).toFixed(1)} KB`
  }

  return `${(bytes / (1024 * 1024)).toFixed(2)} MB`
}

const formatDateTime = (value) => {
  if (!value) {
    return '-'
  }
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return value
  }
  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const createFileStamp = () => {
  const now = new Date()
  const yyyy = now.getFullYear()
  const mm = String(now.getMonth() + 1).padStart(2, '0')
  const dd = String(now.getDate()).padStart(2, '0')
  const hh = String(now.getHours()).padStart(2, '0')
  const min = String(now.getMinutes()).padStart(2, '0')
  return `${yyyy}${mm}${dd}_${hh}${min}`
}

const resolveApiErrorMessage = async (error, fallbackMessage) => {
  const payload = error?.response?.data
  if (payload instanceof Blob) {
    try {
      const text = await payload.text()
      const parsed = JSON.parse(text)
      if (parsed?.message) {
        return parsed.message
      }
    } catch (_ignored) {
      // noop
    }
  }

  return error?.response?.data?.message || error?.message || fallbackMessage
}

const downloadArchivedCopy = async (row) => {
  if (!row?.archive_relative_path || !row.available) {
    return
  }

  try {
    archiveDownloadingPath.value = row.archive_relative_path
    const blob = await reportsApi.downloadArchive({ path: row.archive_relative_path })

    const extension = row.mime_type === 'application/pdf' ? 'pdf' : 'xlsx'
    const fallbackName = `pam_reports_archive_${createFileStamp()}.${extension}`
    const fileName = row.original_file_name || row.archive_file_name || fallbackName
    const fileBlob = blob instanceof Blob ? blob : new Blob([blob], {
      type: row.mime_type || 'application/octet-stream'
    })

    const link = document.createElement('a')
    link.href = window.URL.createObjectURL(fileBlob)
    link.download = fileName
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(link.href)

    showToast('Success', `Downloaded archived report: ${fileName}`, 'success')
  } catch (error) {
    const message = await resolveApiErrorMessage(error, 'Failed to download archived report')
    showToast('Error', message, 'error')
  } finally {
    archiveDownloadingPath.value = ''
  }
}

const appendSheet = (workbook, name, headers, rows) => {
  const safeName = name.slice(0, 31)
  const sheet = XLSX.utils.aoa_to_sheet([headers, ...rows])
  XLSX.utils.book_append_sheet(workbook, sheet, safeName)
}

const archiveReportCopy = async (fileName, mimeType, blob) => {
  if (!report.value || !blob) {
    return {
      success: false,
      message: 'No report payload available for archive'
    }
  }

  try {
    const formData = new FormData()
    formData.append('file', blob, fileName)
    formData.append('file_name', fileName)
    formData.append('mime_type', mimeType)
    formData.append('report_scope', selectedReportType.value)
    formData.append('date_from', report.value.filters?.date_from || '')
    formData.append('date_to', report.value.filters?.date_to || '')
    await reportsApi.archiveDownload(formData)
    return { success: true, message: '' }
  } catch (error) {
    console.error('Failed to archive report copy', error)
    return {
      success: false,
      message: error?.response?.data?.message || error?.message || 'Archive request failed'
    }
  }
}

const downloadXlsx = async () => {
  if (!report.value) {
    return
  }

  try {
    exportingXlsx.value = true
    const wb = XLSX.utils.book_new()

    appendSheet(wb, 'Overview', ['Metric', 'Value'], [
      ['Generated At', report.value.generated_at || '-'],
      ['Date From', report.value.filters?.date_from || 'All Time'],
      ['Date To', report.value.filters?.date_to || 'All Time'],
      ['Report Scope', selectedReportTypeLabel.value],
      ['Users in Report', userTotals.value.user_count || 0],
      ['Suppliers in Report', supplierTotals.value.supplier_count || 0],
      ['Transactions', transactionOverall.value.transaction_count || 0],
      ['Opening Inventory Units', openingInventoryUnits.value],
      ['Opening Inventory Cost', openingInventoryCost.value],
      ['Closing Inventory Units', closingInventoryUnits.value],
      ['Closing Inventory Cost', closingInventoryCost.value],
      ['Inventory Cost Change', inventoryCostChange.value],
      ['Current Inventory Cost', partInventoryTotals.value.inventory_cost || 0]
    ])

    if (showUsers.value) {
      appendSheet(
        wb,
        'User Summary',
        ['User', 'Taken Qty', 'Taken Cost', 'Returned Qty', 'Returned Cost', 'Not Returned Qty', 'Not Returned Cost', 'Transactions'],
        userSummary.value.map((row) => [
          row.user_name,
          row.taken_qty,
          row.taken_cost,
          row.returned_qty,
          row.returned_cost,
          row.not_returned_qty,
          row.not_returned_cost,
          row.transaction_count
        ])
      )

      appendSheet(
        wb,
        'User Movement',
        ['User', 'Fowler PN', 'Supplier PN', 'Part', 'Taken Qty', 'Taken Cost', 'Returned Qty', 'Returned Cost', 'Not Returned Qty', 'Not Returned Cost'],
        userMovement.value.map((row) => [
          row.user_name,
          row.fowler_part_number,
          row.supplier_part_number,
          row.part_name,
          row.taken_qty,
          row.taken_cost,
          row.returned_qty,
          row.returned_cost,
          row.not_returned_qty,
          row.not_returned_cost
        ])
      )
    }

    if (showSuppliers.value) {
      appendSheet(
        wb,
        'Supplier Summary',
        ['Supplier', 'Incoming Qty', 'Incoming Cost', 'Returned Qty', 'Returned Cost', 'Net Qty', 'Net Cost', 'Transactions'],
        supplierSummary.value.map((row) => [
          row.supplier_name,
          row.incoming_qty,
          row.incoming_cost,
          row.returned_qty,
          row.returned_cost,
          row.net_qty,
          row.net_cost,
          row.transaction_count
        ])
      )

      appendSheet(
        wb,
        'Returned to Supplier',
        ['Date', 'Supplier', 'Fowler PN', 'Supplier PN', 'Part', 'Qty', 'Unit Cost', 'Total Cost', 'User'],
        supplierReturnedItems.value.map((row) => [
          row.created_at,
          row.supplier_name,
          row.fowler_part_number,
          row.supplier_part_number,
          row.part_name,
          row.quantity,
          row.unit_price,
          row.total_cost,
          row.created_by
        ])
      )
    }

    if (showTransactions.value) {
      appendSheet(
        wb,
        'Transaction Matrix',
        ['Transaction Type', 'Reference Type', 'Transactions', 'Signed Qty', 'Absolute Qty', 'Total Cost'],
        transactionMatrix.value.map((row) => [
          row.transaction_type,
          row.reference_type,
          row.transaction_count,
          row.signed_qty,
          row.absolute_qty,
          row.total_cost
        ])
      )
    }

    if (showParts.value) {
      appendSheet(
        wb,
        'Part Inventory',
        ['Fowler PN', 'Supplier PN', 'Part', 'Supplier', 'Stock', 'Unit Price', 'Inventory Cost'],
        partInventory.value.map((row) => [
          row.fowler_part_number,
          row.supplier_part_number,
          row.part_name,
          row.supplier_name,
          row.stock,
          row.unit_price,
          row.inventory_cost
        ])
      )

      appendSheet(
        wb,
        'Part Movement',
        ['Fowler PN', 'Supplier PN', 'Part', 'Incoming Qty', 'Incoming Cost', 'Outgoing Qty', 'Outgoing Cost', 'Return Qty', 'Return Cost', 'Net Qty', 'Net Cost', 'Movements'],
        partMovement.value.map((row) => [
          row.fowler_part_number,
          row.supplier_part_number,
          row.part_name,
          row.incoming_qty,
          row.incoming_cost,
          row.outgoing_qty,
          row.outgoing_cost,
          row.return_qty,
          row.return_cost,
          row.net_qty,
          row.net_cost,
          row.movement_count
        ])
      )
    }

    const fileName = `pam_reports_${selectedReportType.value}_${createFileStamp()}.xlsx`
    const workbookBuffer = XLSX.write(wb, { bookType: 'xlsx', type: 'array' })
    const workbookBlob = new Blob(
      [workbookBuffer],
      { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }
    )
    const archiveResult = await archiveReportCopy(
      fileName,
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      workbookBlob
    )

    XLSX.writeFile(wb, fileName)
    if (archiveResult.success) {
      fetchArchiveList()
      showToast('Success', `${selectedReportTypeLabel.value} XLSX downloaded and archived`, 'success')
    } else {
      showToast(
        'Warning',
        `${selectedReportTypeLabel.value} XLSX downloaded, but archive failed: ${archiveResult.message}`,
        'warning'
      )
    }
  } catch (error) {
    showToast('Error', 'Failed to create XLSX report', 'error')
  } finally {
    exportingXlsx.value = false
  }
}

const downloadPdf = async () => {
  if (!report.value) {
    return
  }

  try {
    exportingPdf.value = true
    const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' })
    let cursorY = 34

    const writeHeader = (title) => {
      if (cursorY > 520) {
        doc.addPage()
        cursorY = 34
      }
      doc.setFontSize(12)
      doc.text(title, 32, cursorY)
      cursorY += 8
    }

    const drawTable = (title, headers, rows, maxRows = 250) => {
      writeHeader(title)
      const trimmed = rows.slice(0, maxRows)
      autoTable(doc, {
        startY: cursorY + 6,
        head: [headers],
        body: trimmed,
        margin: { left: 32, right: 32 },
        styles: { fontSize: 8, cellPadding: 4 },
        headStyles: { fillColor: [30, 58, 138] }
      })
      cursorY = (doc.lastAutoTable?.finalY || cursorY) + 16
      if (rows.length > maxRows) {
        doc.setFontSize(9)
        doc.text(`Showing first ${maxRows} of ${rows.length} rows`, 32, cursorY)
        cursorY += 14
      }
    }

    doc.setFontSize(16)
    doc.text('PAM Report Builder', 32, cursorY)
    cursorY += 16
    doc.setFontSize(10)
    doc.text(`Generated: ${formatDateTime(report.value.generated_at)}`, 32, cursorY)
    cursorY += 12
    doc.text(
      `Range: ${report.value.filters?.date_from || 'All Time'} to ${report.value.filters?.date_to || 'All Time'}`,
      32,
      cursorY
    )
    cursorY += 18

    drawTable(
      'Overview',
      ['Metric', 'Value'],
      [
        ['Report Scope', selectedReportTypeLabel.value],
        ['Users in Report', String(userTotals.value.user_count || 0)],
        ['Suppliers in Report', String(supplierTotals.value.supplier_count || 0)],
        ['Transactions', String(transactionOverall.value.transaction_count || 0)],
        ['Opening Inventory Units', formatInt(openingInventoryUnits.value)],
        ['Opening Inventory Cost', formatCurrency(openingInventoryCost.value)],
        ['Closing Inventory Units', formatInt(closingInventoryUnits.value)],
        ['Closing Inventory Cost', formatCurrency(closingInventoryCost.value)],
        ['Inventory Cost Change', formatCurrency(inventoryCostChange.value)],
        ['Inventory Cost', formatCurrency(partInventoryTotals.value.inventory_cost || 0)]
      ]
    )

    if (showUsers.value) {
      drawTable(
        'User Summary',
        ['User', 'Taken Qty', 'Taken Cost', 'Returned Qty', 'Returned Cost', 'Not Returned Qty', 'Not Returned Cost', 'Transactions'],
        userSummary.value.map((row) => [
          row.user_name,
          formatInt(row.taken_qty),
          formatCurrency(row.taken_cost),
          formatInt(row.returned_qty),
          formatCurrency(row.returned_cost),
          formatInt(row.not_returned_qty),
          formatCurrency(row.not_returned_cost),
          formatInt(row.transaction_count)
        ])
      )

      drawTable(
        'User Movement',
        ['User', 'Fowler PN', 'Supplier PN', 'Part', 'Taken Qty', 'Returned Qty', 'Not Returned Qty', 'Taken Cost', 'Returned Cost', 'Not Returned Cost'],
        userMovement.value.map((row) => [
          row.user_name,
          row.fowler_part_number,
          row.supplier_part_number || '-',
          row.part_name,
          formatInt(row.taken_qty),
          formatInt(row.returned_qty),
          formatInt(row.not_returned_qty),
          formatCurrency(row.taken_cost),
          formatCurrency(row.returned_cost),
          formatCurrency(row.not_returned_cost)
        ])
      )
    }

    if (showSuppliers.value) {
      drawTable(
        'Supplier Summary',
        ['Supplier', 'Incoming Qty', 'Incoming Cost', 'Returned Qty', 'Returned Cost', 'Net Qty', 'Net Cost', 'Transactions'],
        supplierSummary.value.map((row) => [
          row.supplier_name,
          formatInt(row.incoming_qty),
          formatCurrency(row.incoming_cost),
          formatInt(row.returned_qty),
          formatCurrency(row.returned_cost),
          formatInt(row.net_qty),
          formatCurrency(row.net_cost),
          formatInt(row.transaction_count)
        ])
      )

      drawTable(
        'Items Returned to Supplier',
        ['Date', 'Supplier', 'Fowler PN', 'Supplier PN', 'Part', 'Qty', 'Unit Cost', 'Total Cost', 'User'],
        supplierReturnedItems.value.map((row) => [
          formatDateTime(row.created_at),
          row.supplier_name,
          row.fowler_part_number,
          row.supplier_part_number || '-',
          row.part_name,
          formatInt(row.quantity),
          formatCurrency(row.unit_price),
          formatCurrency(row.total_cost),
          row.created_by || 'System'
        ])
      )
    }

    if (showTransactions.value) {
      drawTable(
        'Transaction Matrix',
        ['Type', 'Reference', 'Transactions', 'Signed Qty', 'Absolute Qty', 'Total Cost'],
        transactionMatrix.value.map((row) => [
          row.transaction_type,
          prettyReference(row.reference_type),
          formatInt(row.transaction_count),
          formatInt(row.signed_qty),
          formatInt(row.absolute_qty),
          formatCurrency(row.total_cost)
        ])
      )
    }

    if (showParts.value) {
      drawTable(
        'Part Inventory',
        ['Fowler PN', 'Supplier PN', 'Part', 'Stock', 'Unit Price', 'Inventory Cost'],
        partInventory.value.map((row) => [
          row.fowler_part_number,
          row.supplier_part_number || '-',
          row.part_name,
          formatInt(row.stock),
          formatCurrency(row.unit_price),
          formatCurrency(row.inventory_cost)
        ])
      )

      drawTable(
        'Part Movement',
        ['Fowler PN', 'Part', 'Incoming Qty', 'Outgoing Qty', 'Return Qty', 'Net Qty', 'Net Cost', 'Movements'],
        partMovement.value.map((row) => [
          row.fowler_part_number,
          row.part_name,
          formatInt(row.incoming_qty),
          formatInt(row.outgoing_qty),
          formatInt(row.return_qty),
          formatInt(row.net_qty),
          formatCurrency(row.net_cost),
          formatInt(row.movement_count)
        ])
      )
    }

    const fileName = `pam_reports_${selectedReportType.value}_${createFileStamp()}.pdf`
    const pdfBuffer = doc.output('arraybuffer')
    const pdfBlob = new Blob([pdfBuffer], { type: 'application/pdf' })
    const archiveResult = await archiveReportCopy(fileName, 'application/pdf', pdfBlob)

    doc.save(fileName)
    if (archiveResult.success) {
      fetchArchiveList()
      showToast('Success', `${selectedReportTypeLabel.value} PDF downloaded and archived`, 'success')
    } else {
      showToast(
        'Warning',
        `${selectedReportTypeLabel.value} PDF downloaded, but archive failed: ${archiveResult.message}`,
        'warning'
      )
    }
  } catch (error) {
    showToast('Error', 'Failed to create PDF report', 'error')
  } finally {
    exportingPdf.value = false
  }
}

onMounted(() => {
  fetchReport()
  fetchArchiveList()
})
</script>


<template>
  <div class="admin-container">
    <AdminNavbar title="Manajemen Kontrak" :showBackButton="true">
      <template #controls>
        <div class="search-container">
          <input 
            type="text" 
            v-model="searchQuery" 
            placeholder="Cari kontrak..." 
            class="search-input"
            @input="debouncedSearch"
            @keyup.enter="handleSearch"
          />
        </div>
        <a :href="getRoute('admin.contracts.create')" class="admin-action-button">
          <font-awesome-icon icon="fa-solid fa-plus" />
          Tambah Kontrak Baru
        </a>
        <button @click="showCopyModal = true" class="admin-action-button secondary">
          <font-awesome-icon icon="fa-solid fa-copy" />
          Copy Contract
        </button>
      </template>
    </AdminNavbar>

    <!-- Statistics Cards -->
    <!-- <div v-if="statistics" class="admin-stats-grid mb-6">
      <div class="admin-stat-card">
        <div class="stat-icon bg-blue-100 text-blue-600">
          <font-awesome-icon icon="file-contract" />
        </div>
        <div class="stat-content">
          <div class="stat-label">Total Contracts</div>
          <div class="stat-value">{{ statistics.total_contracts || 0 }}</div>
        </div>
      </div>
      
      <div class="admin-stat-card">
        <div class="stat-icon bg-green-100 text-green-600">
          <font-awesome-icon icon="dollar-sign" />
        </div>
        <div class="stat-content">
          <div class="stat-label">Total Value (RP)</div>
          <div class="stat-value">{{ formatCurrency(statistics.total_value_rp || 0) }}</div>
        </div>
      </div>
      
      <div class="admin-stat-card">
        <div class="stat-icon bg-purple-100 text-purple-600">
          <font-awesome-icon icon="dollar-sign" />
        </div>
        <div class="stat-content">
          <div class="stat-label">Total Value (Non-RP)</div>
          <div class="stat-value">{{ formatCurrency(statistics.total_value_non_rp || 0) }}</div>
        </div>
      </div>
      
      <div class="admin-stat-card">
        <div class="stat-icon bg-orange-100 text-orange-600">
          <font-awesome-icon icon="building" />
        </div>
        <div class="stat-content">
          <div class="stat-label">Apps with Contracts</div>
          <div class="stat-value">{{ statistics.apps_with_contracts || 0 }}</div>
        </div>
      </div>
    </div> -->

    <div v-if="!props.contracts?.data" class="p-4 text-center">
      Loading...
    </div>

    <div v-else-if="props.contracts.data.length === 0" class="p-4 text-center">
      Kontrak tidak ditemukan.
    </div>

    <div v-else>
      <AdminTable
        :columns="columns"
        :items="props.contracts.data"
        v-model:sortBy="sortBy"
        v-model:sortDesc="sortDesc"
        :searchQuery="searchQuery"
        :pagination="props.contracts.meta"
        @page="navigateToPage"
      >
        <template #column:app_names="{ item }">
          <div class="app-names">
            {{ item.app_names || 'No Apps' }}
          </div>
        </template>
        
        <template #column:title="{ item }">
          <div class="contract-title">{{ item.title }}</div>
        </template>
        
        <template #column:currency_type="{ item }">
          {{ item.currency_type?.toUpperCase() }}
        </template>
        
        <template #column:actions="{ item }">
          <div class="flex justify-center gap-2">
            <a 
              :href="`/admin/contracts/${item.id}/edit`" 
              class="action-button edit-button"
              title="Edit Contract"
            >
              <font-awesome-icon icon="fa-solid fa-pencil" />
            </a>
            <button 
              @click="deleteContract(item.id)" 
              class="action-button delete-button"
              title="Delete Contract"
            >
              <font-awesome-icon icon="fa-solid fa-trash" />
            </button>
          </div>
        </template>
      </AdminTable>
    </div>

    <!-- Copy Contract Modal -->
    <div v-if="showCopyModal" class="modal-overlay" @click="closeCopyModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">
            <font-awesome-icon icon="fa-solid fa-copy" />
            Copy Contract
          </h3>
          <button @click="closeCopyModal" class="modal-close">
            <font-awesome-icon icon="fa-solid fa-times" />
          </button>
        </div>

        <div class="modal-body">
          <div class="copy-form">
            <div class="form-group">
              <label for="sourceContract" class="form-label">Pilih kontrak untuk disalin:</label>
              <select 
                id="sourceContract" 
                v-model="selectedSourceContract"
                class="form-select"
                :disabled="isCopying"
              >
                <option value="">Pilih kontrak...</option>
                <option 
                  v-for="contract in sortedContracts" 
                  :key="contract.id" 
                  :value="contract.id"
                >
                  {{ contract.title }} - {{ contract.first_app_name }} ({{ contract.contract_number }})
                </option>
              </select>
            </div>

            <div class="form-group">
              <label for="targetApps" class="form-label">Salin ke Aplikasi (pilih beberapa):</label>
              <select 
                id="targetApps" 
                v-model="selectedTargetApps"
                class="form-select"
                :disabled="isCopying"
                multiple
                size="6"
              >
                <option 
                  v-for="app in sortedAvailableApps" 
                  :key="app.app_id" 
                  :value="app.app_id"
                >
                  {{ app.app_name }}
                </option>
              </select>
              <div class="form-help">
                Tahan Ctrl (Windows) atau Cmd (Mac) untuk memilih beberapa aplikasi
              </div>
            </div>

            <div v-if="selectedSourceContract && selectedTargetApps.length > 0" class="copy-preview">
              <div class="preview-header">
                <font-awesome-icon icon="fa-solid fa-info-circle" />
                Copy Preview
              </div>
              <div class="preview-content">
                <p><strong>Dari:</strong> {{ getContractById(selectedSourceContract)?.first_app_name || 'Unknown' }}</p>
                <p><strong>Ke:</strong> {{ getSelectedAppNames() }}</p>
                <p><strong>Kontrak:</strong> {{ getContractById(selectedSourceContract)?.title }}</p>
                <p class="preview-note">
                  Kontrak baru akan dibuat dengan judul dan nomor kontrak yang sama, terkait dengan {{ selectedTargetApps.length }} aplikasi.
                </p>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="closeCopyModal" class="btn btn-cancel" :disabled="isCopying">
            Cancel
          </button>
          <button 
            @click="copyContract" 
            class="btn btn-primary"
            :disabled="!canCopy || isCopying"
          >
            <font-awesome-icon 
              :icon="isCopying ? 'fa-solid fa-spinner' : 'fa-solid fa-copy'" 
              :class="{ 'fa-spin': isCopying }"
            />
            {{ isCopying ? 'Copying...' : 'Copy Contract' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { useRoutes } from '@/composables/useRoutes';
import AdminNavbar from '@/components/Admin/AdminNavbar.vue';
import AdminTable from '@/components/Admin/AdminTable.vue';
import { useAdminTable } from '@/composables/useAdminTable';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

interface App {
  app_id: number;
  app_name: string;
  description?: string;
}

interface Contract {
  id: number;
  title: string;
  contract_number: string;
  currency_type: 'rp' | 'non_rp';
  contract_value_rp: string;
  contract_value_non_rp: string;
  lumpsum_value_rp: string;
  unit_value_rp: string;
  apps: App[];
  app_names: string;
  first_app_name: string;
}

interface Statistics {
  total_contracts: number;
  total_value_rp: string;
  total_value_non_rp: string;
  apps_with_contracts: number;
}

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface Props {
  contracts?: {
    data: Contract[];
    meta?: {
      links: PaginationLink[];
    };
  };
  statistics?: Statistics;
  error?: string;
}

const props = defineProps<Props>();

// Use composables
const { getRoute } = useRoutes();
const { searchQuery, sortBy, sortDesc, debouncedSearch, handleSearch, navigateToPage } = useAdminTable({
  defaultSortBy: 'title'
});

// Copy functionality
const showCopyModal = ref(false);
const selectedSourceContract = ref<number | string>('');
const selectedTargetApps = ref<number[]>([]);
const isCopying = ref(false);
const availableApps = ref<App[]>([]);

const columns = [
  { key: 'app_names', label: 'Applications', sortable: false },
  { key: 'title', label: 'Contract Title', sortable: true },
  { key: 'contract_number', label: 'Contract Number', sortable: true },
  { key: 'currency_type', label: 'Currency', sortable: true },
  { key: 'actions', label: 'Actions', centered: true }
];

const canCopy = computed(() => {
  return selectedSourceContract.value && selectedTargetApps.value.length > 0 && !isCopying.value;
});

// Computed properties for sorted dropdowns
const sortedContracts = computed(() => {
  if (!props.contracts?.data) return [];
  return [...props.contracts.data].sort((a, b) => a.title.localeCompare(b.title));
});

const sortedAvailableApps = computed(() => {
  return [...availableApps.value].sort((a, b) => a.app_name.localeCompare(b.app_name));
});

// Fetch available apps when component mounts
onMounted(async () => {
  try {
    // Make a request to get available apps
    const response = await fetch('/admin/contracts/apps');
    if (response.ok) {
      const data = await response.json();
      availableApps.value = data;
    }
  } catch (error) {
    console.error('Failed to fetch apps:', error);
  }
});

function getContractById(id: number | string): Contract | undefined {
  return props.contracts?.data?.find(contract => contract.id === Number(id));
}

function getAppById(id: number | string): App | undefined {
  return availableApps.value.find(app => app.app_id === Number(id));
}

function getSelectedAppNames(): string {
  const selectedApps = selectedTargetApps.value.map(id => 
    availableApps.value.find(app => app.app_id === id)?.app_name
  ).filter(Boolean);
  
  return selectedApps.join(', ') || 'No apps selected';
}

function closeCopyModal() {
  showCopyModal.value = false;
  selectedSourceContract.value = '';
  selectedTargetApps.value = [];
}

function copyContract() {
  if (!canCopy.value) return;

  isCopying.value = true;

  router.post('/admin/contracts/copy', {
    source_contract_id: Number(selectedSourceContract.value),
    target_app_ids: selectedTargetApps.value,
  }, {
    onSuccess: () => {
      isCopying.value = false;
      closeCopyModal();
      // Refresh the page data
      router.reload();
    },
    onError: (errors) => {
      isCopying.value = false;
      console.error('Failed to copy contract:', errors);
      alert('Failed to copy contract. Please try again.');
    }
  });
}

function deleteContract(contractId: number) {
  if (confirm('Are you sure you want to delete this contract?')) {
    router.delete(`/admin/contracts/${contractId}`, {
      onSuccess: () => {
        // Refresh the page data after successful deletion
        router.reload();
      },
      onError: (errors) => {
        console.error('Failed to delete contract:', errors);
        alert('Failed to delete contract. Please try again.');
      }
    });
  }
}
</script>

<style scoped>
@import '@/../css/admin.css';

.admin-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.admin-stat-card {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  width: 3rem;
  height: 3rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1f2937;
}

.bg-blue-100 {
  background-color: #dbeafe;
}

.text-blue-600 {
  color: #2563eb;
}

.bg-green-100 {
  background-color: #dcfce7;
}

.text-green-600 {
  color: #16a34a;
}

.bg-purple-100 {
  background-color: #f3e8ff;
}

.text-purple-600 {
  color: #9333ea;
}

.bg-orange-100 {
  background-color: #fed7aa;
}

.text-orange-600 {
  color: #ea580c;
}

.contract-title {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.4;
  max-height: 2.8em; /* 2 lines * 1.4 line-height */
}

.admin-action-button.secondary {
  background-color: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.admin-action-button.secondary:hover {
  background-color: #e5e7eb;
  color: #1f2937;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  max-width: 500px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-color);
  margin: 0;
}

.modal-close {
  background: none;
  border: none;
  color: #6b7280;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.modal-close:hover {
  background-color: #f3f4f6;
  color: #374151;
}

.modal-body {
  padding: 1.5rem;
}

.copy-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-color);
}

.form-select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.875rem;
  background-color: white;
  transition: all 0.2s ease;
}

.form-select[multiple] {
  min-height: 120px;
}

.form-help {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.25rem;
  font-style: italic;
}

.app-names {
  font-size: 0.875rem;
  line-height: 1.4;
}

.form-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-select:disabled {
  background-color: #f9fafb;
  color: #6b7280;
  cursor: not-allowed;
}

.copy-preview {
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 8px;
  padding: 1rem;
}

.preview-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #0369a1;
  margin-bottom: 0.75rem;
}

.preview-content p {
  margin: 0 0 0.5rem 0;
  font-size: 0.875rem;
  color: #374151;
}

.preview-note {
  color: #6b7280;
  font-style: italic;
  margin-top: 0.75rem !important;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-cancel {
  background-color: #f9fafb;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-cancel:hover:not(:disabled) {
  background-color: #f3f4f6;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: var(--primary-color-dark, #1e40af);
}

@media (max-width: 640px) {
  .modal-overlay {
    padding: 0.5rem;
  }
  
  .modal-content {
    max-height: 95vh;
  }
  
  .modal-header,
  .modal-body,
  .modal-footer {
    padding: 1rem;
  }
  
  .modal-footer {
    flex-direction: column;
  }
  
  .btn {
    justify-content: center;
  }
}
</style>

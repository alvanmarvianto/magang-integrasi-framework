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
        <template #column:app_name="{ item }">
          {{ item.app_name }}
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
  </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useRoutes } from '@/composables/useRoutes';
import AdminNavbar from '@/components/Admin/AdminNavbar.vue';
import AdminTable from '@/components/Admin/AdminTable.vue';
import { useAdminTable } from '@/composables/useAdminTable';

interface Contract {
  id: number;
  app_id: number;
  app_name: string;
  title: string;
  contract_number: string;
  currency_type: 'rp' | 'non_rp';
  contract_value_rp: string;
  contract_value_non_rp: string;
  lumpsum_value_rp: string;
  unit_value_rp: string;
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
  defaultSortBy: 'app_name'
});

const columns = [
  { key: 'app_name', label: 'Application', sortable: true },
  { key: 'title', label: 'Contract Title', sortable: true },
  { key: 'contract_number', label: 'Contract Number', sortable: true },
  { key: 'currency_type', label: 'Currency', sortable: true },
  { key: 'actions', label: 'Actions', centered: true }
];

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
</style>

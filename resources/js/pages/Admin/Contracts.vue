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
    <AdminStatsGrid 
      v-if="props.statistics" 
      :statistics="props.statistics" 
      type="contracts" 
    />

    <AdminLoadingState 
      :loading="!props.contracts?.data"
      :empty="props.contracts?.data?.length === 0"
      emptyText="Kontrak tidak ditemukan."
      emptyIcon="fa-solid fa-file-contract"
    >
      <AdminTable
        :columns="columns"
        :items="props.contracts?.data || []"
        v-model:sortBy="sortBy"
        v-model:sortDesc="sortDesc"
        :searchQuery="searchQuery"
        :pagination="props.contracts?.meta"
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
          <AdminActionButtons
            :item="item"
            editRoute="/admin/contracts/:id/edit"
            @delete="handleDeleteContract"
          />
        </template>
      </AdminTable>
    </AdminLoadingState>

    <!-- Delete Confirmation Modal -->
    <ConfirmDeleteModal
      :show="deleteState.show"
      title="Delete Contract"
      :message="deleteState.options.confirmMessage || 'Are you sure you want to delete this contract?'"
      @confirm="confirmDelete"
      @cancel="hideDeleteConfirmation"
    />
  </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useRoutes } from '@/composables/useRoutes';
import AdminNavbar from '@/components/Admin/AdminNavbar.vue';
import AdminTable from '@/components/Admin/AdminTable.vue';
import AdminActionButtons from '@/components/Admin/AdminActionButtons.vue';
import AdminLoadingState from '@/components/Admin/AdminLoadingState.vue';
import AdminStatsGrid from '@/components/Admin/AdminStatsGrid.vue';
import ConfirmDeleteModal from '@/components/Admin/ConfirmDeleteModal.vue';
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
const { 
  searchQuery, 
  sortBy, 
  sortDesc, 
  debouncedSearch, 
  handleSearch, 
  navigateToPage,
  deleteState,
  showDeleteConfirmation,
  hideDeleteConfirmation,
  confirmDelete
} = useAdminTable({
  defaultSortBy: 'title'
});

const columns = [
  { key: 'app_names', label: 'Applications', sortable: false },
  { key: 'title', label: 'Contract Title', sortable: true },
  { key: 'contract_number', label: 'Contract Number', sortable: true },
  { key: 'currency_type', label: 'Currency', sortable: true },
  { key: 'actions', label: 'Actions', centered: true }
];

function handleDeleteContract(contract: Contract) {
  showDeleteConfirmation(contract, {
    url: `/admin/contracts/${contract.id}`,
    confirmMessage: `Are you sure you want to delete the contract "${contract.title}"?`
  });
}
</script>

<style scoped>
@import '@/../css/admin.css';

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

.app-names {
  font-size: 0.875rem;
  line-height: 1.4;
}
</style>

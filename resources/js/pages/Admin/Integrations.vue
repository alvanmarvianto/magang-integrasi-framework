<template>
  <div class="admin-container">
    <AdminNavbar title="Manajemen Integrasi" :showBackButton="true">
      <template #controls>
        <div class="search-container">
          <input 
            type="text" 
            v-model="searchQuery" 
            placeholder="Cari integrasi..." 
            class="search-input"
            @input="debouncedSearch"
            @keyup.enter="handleSearch"
          />
        </div>
        <a :href="getRoute('admin.integrations.create')" class="admin-action-button">
          <font-awesome-icon icon="fa-solid fa-plus" />
          Tambah Integrasi Baru
        </a>
      </template>
    </AdminNavbar>

    <!-- Statistics Cards -->
    <AdminStatsGrid 
      v-if="props.statistics" 
      :statistics="props.statistics" 
      type="integrations" 
    />

    <AdminLoadingState 
      :loading="!props.integrations?.data"
      :empty="props.integrations?.data?.length === 0"
      emptyText="Integrasi tidak ditemukan."
      emptyIcon="fa-solid fa-share-nodes"
    >
      <AdminTable
        :columns="columns"
        :items="props.integrations?.data || []"
        v-model:sortBy="sortBy"
        v-model:sortDesc="sortDesc"
        :searchQuery="searchQuery"
        :pagination="props.integrations?.meta"
        @page="navigateToPage"
      >
        <template #column:source_app_name="{ item }">
          {{ item.source_app.app_name }}
        </template>
        
        <template #column:target_app_name="{ item }">
          {{ item.target_app.app_name }}
        </template>
        
        <template #column:connection_type_name="{ item }">
          {{ item.connection_type.type_name }}
        </template>
        
        <template #column:actions="{ item }">
          <AdminActionButtons
            :item="item"
            editRoute="/admin/integrations/:id/edit"
            editField="integration_id"
            @delete="handleDeleteIntegration"
          />
        </template>
      </AdminTable>
    </AdminLoadingState>

    <!-- Delete Confirmation Modal -->
    <ConfirmDeleteModal
      :show="deleteState.show"
      title="Delete Integration"
      :message="deleteState.options.confirmMessage || 'Are you sure you want to delete this integration?'"
      @confirm="confirmDelete"
      @cancel="hideDeleteConfirmation"
    />
  </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useRoutes } from '@/composables/useRoutes';
import AdminNavbar from '@/components/Admin/AdminNavbar.vue';
import AdminTable from '@/components/Admin/AdminTable.vue';
import AdminActionButtons from '@/components/Admin/AdminActionButtons.vue';
import AdminLoadingState from '@/components/Admin/AdminLoadingState.vue';
import ConfirmDeleteModal from '@/components/Admin/ConfirmDeleteModal.vue';
import AdminStatsGrid from '@/components/Admin/AdminStatsGrid.vue';
import { useAdminTable } from '@/composables/useAdminTable';

const { getRoute } = useRoutes();

interface Integration {
  integration_id: number;
  source_app: {
    app_id: number;
    app_name: string;
  };
  target_app: {
    app_id: number;
    app_name: string;
  };
  connection_type: {
    connection_type_id: number;
    type_name: string;
  };
}

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface Props {
  integrations: {
    data: Integration[];
    meta: {
      current_page: number;
      from: number;
      last_page: number;
      links: PaginationLink[];
      per_page: number;
      to: number;
      total: number;
    };
  };
  statistics?: {
    total_integrations: number;
    integrations_by_connection_type: Record<string, number>;
    unique_apps_with_integrations: number;
    unique_connection_types: number;
    most_integrated_apps: Array<{
      app_name: string;
      integration_count: number;
    }>;
  };
}

const props = defineProps<Props>();

// Use the admin table composable with delete functionality
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
  defaultSortBy: 'source_app_name'
});

const columns = [
  { key: 'source_app_name', label: 'Source App', sortable: true },
  { key: 'target_app_name', label: 'Target App', sortable: true },
  { key: 'connection_type_name', label: 'Connection Type', sortable: true },
  { key: 'actions', label: 'Actions', centered: true }
];

function handleDeleteIntegration(integration: Integration) {
  showDeleteConfirmation(
    integration,
    {
      url: `/admin/integrations/${integration.integration_id}`,
      confirmMessage: `Apakah anda yakin ingin menghapus koneksi antara ${integration.source_app.app_name} dan ${integration.target_app.app_name}?`
    }
  );
}
</script>

<style scoped>
@import '@/../css/admin.css';
</style>

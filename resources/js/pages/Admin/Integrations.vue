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

    <div v-if="!props.integrations?.data" class="p-4 text-center">
      Loading...
    </div>

    <div v-else-if="props.integrations.data.length === 0" class="p-4 text-center">
      Tidak ada integrasi.
    </div>

    <div v-else class="admin-table-container">
      <AdminTable
        :columns="columns"
        :items="props.integrations.data"
        v-model:sortBy="sortBy"
        v-model:sortDesc="sortDesc"
        :searchQuery="searchQuery"
        :pagination="props.integrations.meta"
        @page="navigateToPage"
      >
        <template #column:source_app_name="{ item }">
          {{ item.source_app.app_name }}
        </template>
        
        <template #column:target_app_name="{ item }">
          {{ item.target_app.app_name }}
        </template>
        
        <template #column:connection_type_name="{ item }">
          {{ formatConnectionTypes(item.connections || []) }}
        </template>
        
        <template #column:actions="{ item }">
          <div class="flex justify-center gap-2">
            <a 
              :href="`/admin/integrations/${item.integration_id}/edit`"
              class="action-button edit-button"
              title="Edit"
            >
              <font-awesome-icon icon="fa-solid fa-pencil" />
            </a>
            <button
              class="action-button delete-button"
              @click="confirmDelete(item)"
              title="Delete"
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
import { useNotification } from '@/composables/useNotification';

const { getRoute } = useRoutes();
const { showSuccess, showError, showConfirm } = useNotification();

interface IntegrationConnectionDTO {
  connection_type_id: number;
  source_inbound?: string | null;
  source_outbound?: string | null;
  target_inbound?: string | null;
  target_outbound?: string | null;
  connection_type?: { connection_type_id: number; type_name: string; color?: string } | null;
}

interface Integration {
  integration_id: number;
  source_app: { app_id: number; app_name: string };
  target_app: { app_id: number; app_name: string };
  connections: IntegrationConnectionDTO[];
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
}

const props = defineProps<Props>();

function formatConnectionTypes(connections: IntegrationConnectionDTO[]): string {
  const names = connections
    .map((c: IntegrationConnectionDTO) => c.connection_type?.type_name)
    .filter((n): n is string => Boolean(n));
  return names.length ? names.join(', ') : '-';
}

// Use the admin table composable
const { searchQuery, sortBy, sortDesc, debouncedSearch, handleSearch, navigateToPage } = useAdminTable({
  defaultSortBy: 'source_app_name'
});

const columns = [
  { key: 'source_app_name', label: 'Source App', sortable: true },
  { key: 'target_app_name', label: 'Target App', sortable: true },
  { key: 'connection_type_name', label: 'Connection Type', sortable: true },
  { key: 'actions', label: 'Actions', centered: true }
];

function confirmDelete(integration: Integration) {
  showConfirm(`Apakah anda yakin ingin menghapus semua koneksi antara ${integration.source_app.app_name} dan ${integration.target_app.app_name}?`)
    .then((confirmed) => {
      if (confirmed) {
        router.delete(`/admin/integrations/${integration.integration_id}`, {
          onSuccess: () => {
            showSuccess('Integrasi berhasil dihapus');
          },
          onError: (errors) => {
            const errorMessage = typeof errors === 'object' && errors !== null 
              ? Object.values(errors).flat().join(', ')
              : 'Gagal menghapus integrasi';
            showError(errorMessage);
          },
        });
      }
    });
}
</script>

<style scoped>
@import '@/../css/admin.css';
</style>

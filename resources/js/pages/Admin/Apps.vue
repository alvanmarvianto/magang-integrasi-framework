<template>
  <div class="admin-container">
    <AdminNavbar title="Manajemen Aplikasi" :showBackButton="true">
      <template #controls>
        <div class="search-container">
          <input 
            type="text" 
            v-model="searchQuery" 
            placeholder="Cari aplikasi..." 
            class="search-input"
            @input="debouncedSearch"
            @keyup.enter="handleSearch"
          />
        </div>
        <a :href="getRoute('admin.apps.create')" class="admin-action-button">
          <font-awesome-icon icon="fa-solid fa-plus" />
          Tambah Aplikasi Baru
        </a>
      </template>
    </AdminNavbar>

    <!-- Statistics Cards -->
    <AdminStatsGrid 
      v-if="props.statistics" 
      :statistics="props.statistics" 
      type="apps" 
    />

    <AdminLoadingState 
      :loading="!props.apps?.data"
      :empty="props.apps?.data?.length === 0"
      emptyText="Aplikasi tidak ditemukan."
      emptyIcon="fa-solid fa-desktop"
    >
      <AdminTable
        :columns="columns"
        :items="props.apps?.data || []"
        v-model:sortBy="sortBy"
        v-model:sortDesc="sortDesc"
        :searchQuery="searchQuery"
        :pagination="props.apps?.meta"
        @page="navigateToPage"
      >
        <template #column:app_name="{ item }">
          {{ item.app_name }}
        </template>
        
        <template #column:stream="{ item }">
          {{ item.stream_name || '-' }}
        </template>
        
        <template #column:actions="{ item }">
          <AdminActionButtons
            :item="item"
            editRoute="/admin/apps/:id/edit"
            editField="app_id"
            @delete="handleDeleteApp"
          />
        </template>
      </AdminTable>
    </AdminLoadingState>

    <!-- Delete Confirmation Modal -->
    <ConfirmDeleteModal
      :show="deleteState.show"
      title="Delete Application"
      :message="deleteState.options.confirmMessage || 'Are you sure you want to delete this application?'"
      @confirm="confirmDelete"
      @cancel="hideDeleteConfirmation"
    />
  </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useRoutes } from '@/composables/useRoutes';
import AdminNavbar from '@/components/Admin/AdminNavbar.vue';
import AdminTable from '@/components/Admin/AdminTable.vue';
import AdminActionButtons from '@/components/Admin/AdminActionButtons.vue';
import AdminLoadingState from '@/components/Admin/AdminLoadingState.vue';
import AdminStatsGrid from '@/components/Admin/AdminStatsGrid.vue';
import ConfirmDeleteModal from '@/components/Admin/ConfirmDeleteModal.vue';
import { useAdminTable } from '@/composables/useAdminTable';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

interface Stream {
  data: {
    stream_id: number;
    stream_name: string;
    description: string | null;
  };
}

interface App {
  app_id: number;
  app_name: string;
  description: string | null;
  stream?: {
    stream_id: number;
    stream_name: string;
  };
  stream_id: number;
  stream_name: string | null;
  app_type: string | null;
  stratification: string | null;
  vendors: Array<{ name: string; version: string | null; }>;
  operating_systems: Array<{ name: string; version: string | null; }>;
  databases: Array<{ name: string; version: string | null; }>;
  programming_languages: Array<{ name: string; version: string | null; }>;
  frameworks: Array<{ name: string; version: string | null; }>;
  middlewares: Array<{ name: string; version: string | null; }>;
  third_parties: Array<{ name: string; version: string | null; }>;
  platforms: Array<{ name: string; version: string | null; }>;
}

interface Statistics {
  total_apps: number;
  apps_by_type: Record<string, number>;
  apps_by_stratification: Record<string, number>;
  apps_by_stream: Record<string, number>;
  apps_with_description: number;
  apps_without_description: number;
}

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface Props {
  apps?: {
    data: App[];
    meta?: {
      links: PaginationLink[];
    };
  };
  streams?: Stream[];
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
  defaultSortBy: 'app_name'
});

const columns = [
  { key: 'app_name', label: 'Nama', sortable: true },
  { key: 'stream', label: 'Stream', sortable: true },
  { key: 'actions', label: 'Aksi', centered: true }
];

function handleDeleteApp(app: App) {
  showDeleteConfirmation(app, {
    url: `/admin/apps/${app.app_id}`,
    confirmMessage: `Are you sure you want to delete the application "${app.app_name}"?`
  });
}
</script>

<style scoped>
@import '@/../css/admin.css';
</style> 
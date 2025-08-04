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

    <div v-if="!props.apps?.data" class="p-4 text-center">
      Loading...
    </div>

    <div v-else-if="props.apps.data.length === 0" class="p-4 text-center">
      No applications found.
    </div>

    <div v-else>
      <AdminTable
        :columns="columns"
        :items="props.apps.data"
        v-model:sortBy="sortBy"
        v-model:sortDesc="sortDesc"
        :searchQuery="searchQuery"
        :pagination="props.apps.meta"
        @page="navigateToPage"
      >
        <template #column:app_name="{ item }">
          {{ item.app_name }}
        </template>
        
        <template #column:stream="{ item }">
          {{ item.stream_name || '-' }}
        </template>
        
        <template #column:actions="{ item }">
          <div class="flex justify-center gap-2">
            <a 
              :href="`/admin/apps/${item.app_id}/edit`" 
              class="action-button edit-button"
              title="Edit Aplikasi"
            >
              <font-awesome-icon icon="fa-solid fa-pencil" />
            </a>
            <button 
              @click="deleteApp(item.app_id)" 
              class="action-button delete-button"
              title="Hapus Aplikasi"
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
}

const props = defineProps<Props>();

// Use composables
const { getRoute } = useRoutes();
const { searchQuery, sortBy, sortDesc, debouncedSearch, handleSearch, navigateToPage } = useAdminTable({
  defaultSortBy: 'app_name'
});

const columns = [
  { key: 'app_name', label: 'Nama', sortable: true },
  { key: 'stream', label: 'Stream', sortable: true },
  { key: 'actions', label: 'Aksi', centered: true }
];

function deleteApp(appId: number) {
  if (confirm('Apakah anda yakin ingin menghapus aplikasi ini?')) {
    router.delete(`/admin/apps/${appId}`);
  }
}
</script>

<style scoped>
@import '@/../css/admin.css';
</style> 
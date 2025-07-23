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
            @input="handleSearch"
          />
        </div>
        <a href="/admin/integrations/create" class="admin-action-button">
          <font-awesome-icon icon="fa-solid fa-plus" />
          Tambah Integrasi
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
          {{ item.connection_type.type_name }}
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
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminNavbar from '@/components/Admin/AdminNavbar.vue';
import AdminTable from '@/components/Admin/AdminTable.vue';

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
}

const props = defineProps<Props>();
const searchQuery = ref('');
const sortBy = ref('source_app_name');
const sortDesc = ref(false);

const columns = [
  { key: 'source_app_name', label: 'Source App', sortable: true },
  { key: 'target_app_name', label: 'Target App', sortable: true },
  { key: 'connection_type_name', label: 'Connection Type', sortable: true },
  { key: 'actions', label: 'Actions', centered: true }
];

// Watch for sort changes and trigger server request
watch([sortBy, sortDesc], () => {
  updateData();
}, { deep: true });

function updateData() {
  const params = new URLSearchParams();
  
  if (searchQuery.value) {
    params.set('search', searchQuery.value);
  }
  
  if (sortBy.value !== 'source_app_name') {
    params.set('sort_by', sortBy.value);
  }
  
  if (sortDesc.value) {
    params.set('sort_desc', '1');
  }

  router.get(
    window.location.pathname + (params.toString() ? '?' + params.toString() : ''),
    {},
    { preserveState: true, preserveScroll: true }
  );
}

function handleSearch() {
  updateData();
}

function confirmDelete(integration: Integration) {
  if (confirm(`Are you sure you want to delete the integration between ${integration.source_app.app_name} and ${integration.target_app.app_name}?`)) {
    router.delete(`/admin/integrations/${integration.integration_id}`);
  }
}

function navigateToPage(url: string) {
  router.get(url);
}
</script>

<style scoped>
@import '@/../css/admin.css';
</style>

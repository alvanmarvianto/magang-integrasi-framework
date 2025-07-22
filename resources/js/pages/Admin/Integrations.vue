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
      <table class="admin-table">
        <thead>
          <tr>
            <th>Source App</th>
            <th>Target App</th>
            <th>Connection Type</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="integration in filteredIntegrations" :key="integration.integration_id">
            <td>{{ integration.source_app.app_name }}</td>
            <td>{{ integration.target_app.app_name }}</td>
            <td>{{ integration.connection_type.type_name }}</td>
            <td>
              <div class="flex justify-center gap-2">
                <a 
                  :href="`/admin/integrations/${integration.integration_id}/edit`"
                  class="action-button edit-button"
                  title="Edit"
                >
                  <font-awesome-icon icon="fa-solid fa-pencil" />
                </a>
                <button
                  class="action-button delete-button"
                  @click="confirmDelete(integration)"
                  title="Delete"
                >
                  <font-awesome-icon icon="fa-solid fa-trash" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="props.integrations.meta?.links" class="admin-pagination">
        <div class="flex gap-2">
          <button
            v-for="link in props.integrations.meta.links"
            :key="link.label"
            class="admin-pagination-button"
            :class="{ active: link.active }"
            :disabled="!link.url"
            @click="link.url && navigateToPage(link.url)"
            v-html="link.label"
          ></button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminNavbar from '@/components/Admin/AdminNavbar.vue';

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

const filteredIntegrations = computed(() => {
  if (!props.integrations?.data) {
    return [];
  }

  let filtered = props.integrations.data;
  
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(integration => 
      integration.source_app.app_name.toLowerCase().includes(query)
    );
  }

  return filtered;
});

function handleSearch() {
  router.get(
    window.location.pathname,
    { search: searchQuery.value },
    { preserveState: true }
  );
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

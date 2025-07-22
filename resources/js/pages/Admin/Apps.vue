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
            @input="handleSearch"
          />
        </div>
        <a href="/admin/apps/create" class="admin-action-button">
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

    <div v-else class="admin-table-container">
      <table class="admin-table">
        <thead>
          <tr>
            <th @click="toggleSort('name')" class="sortable" :class="{ 'sorted': sortBy === 'name' }">
              Nama
              <font-awesome-icon 
                :icon="getSortIcon('name')" 
                class="sort-icon"
              />
            </th>
            <th @click="toggleSort('stream')" class="sortable" :class="{ 'sorted': sortBy === 'stream' }">
              Stream
              <font-awesome-icon 
                :icon="getSortIcon('stream')" 
                class="sort-icon"
              />
            </th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="app in sortedAndFilteredApps" :key="app.app_id">
                        <td>{{ app.app_name }}</td>
            <td>{{ getStreamName(app.stream_id) }}</td>
            <td>
              <div class="flex justify-center gap-2">
                <a 
                  :href="`/admin/apps/${app.app_id}/edit`" 
                  class="action-button edit-button"
                  title="Edit Aplikasi"
                >
                  <font-awesome-icon icon="fa-solid fa-pencil" />
                </a>
                <button 
                  @click="deleteApp(app.app_id)" 
                  class="action-button delete-button"
                  title="Hapus Aplikasi"
                >
                  <font-awesome-icon icon="fa-solid fa-trash" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="props.apps.meta?.links" class="admin-pagination">
        <div class="flex gap-2">
          <button
            v-for="link in props.apps.meta.links"
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
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminNavbar from '@/components/Admin/AdminNavbar.vue';

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
const searchQuery = ref('');
const sortBy = ref('name');
const sortDesc = ref(false);

function getStreamName(streamId: number): string {
  if (!props.streams) return '-';
  const stream = props.streams.find(s => s.data?.stream_id === streamId);
  return stream?.data?.stream_name ?? '-';
}

onMounted(() => {
  console.log('Props received:', props);
  console.log('Apps:', props.apps?.data);
  console.log('Streams:', props.streams);
});

const sortedAndFilteredApps = computed(() => {
  if (!props.apps?.data) {
    return [];
  }

  let filteredApps = props.apps.data;
  
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filteredApps = filteredApps.filter(app => 
      app.app_name.toLowerCase().includes(query)
    );
  }

  return [...filteredApps].sort((a, b) => {
    if (sortBy.value === 'name') {
      return sortDesc.value
        ? b.app_name.toLowerCase().localeCompare(a.app_name.toLowerCase())
        : a.app_name.toLowerCase().localeCompare(b.app_name.toLowerCase());
    } else if (sortBy.value === 'stream') {
      const streamA = getStreamName(a.stream_id).toLowerCase();
      const streamB = getStreamName(b.stream_id).toLowerCase();
      return sortDesc.value
        ? streamB.localeCompare(streamA)
        : streamA.localeCompare(streamB);
    }
    return 0;
  });
});

function handleSearch() {
  router.get(
    window.location.pathname,
    { search: searchQuery.value },
    { preserveState: true }
  );
}

function getSortIcon(column: string) {
  if (sortBy.value !== column) {
    return 'fa-solid fa-sort';
  }
  return sortDesc.value ? 'fa-solid fa-sort-down' : 'fa-solid fa-sort-up';
}

function toggleSort(column: string) {
  if (sortBy.value === column) {
    sortDesc.value = !sortDesc.value;
  } else {
    sortBy.value = column;
    sortDesc.value = false;
  }
}

function deleteApp(appId: number) {
  if (confirm('Apakah anda yakin ingin menghapus aplikasi ini?')) {
    router.delete(`/admin/apps/${appId}`);
  }
}

function navigateToPage(url: string) {
  router.get(url);
}
</script>

<style scoped>
@import '@/../css/admin.css';

.admin-controls {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.search-container {
  flex: 1;
  max-width: 300px;
}

.search-input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid var(--border-color);
  border-radius: 4px;
  font-size: 14px;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px var(--primary-color-light);
}

.sortable {
  cursor: pointer;
  user-select: none;
  position: relative;
  padding-right: 1.5rem;
}

.sort-icon {
  font-size: 0.8em;
  margin-left: 0.5rem;
  opacity: 0.5;
}

.sorted .sort-icon {
  opacity: 1;
}
</style> 
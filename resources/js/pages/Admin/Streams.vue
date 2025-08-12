<template>
  <div class="admin-container">
    <AdminNavbar title="Manajemen Stream" :showBackButton="true">
      <template #controls>
        <div class="search-container">
          <input 
            type="text" 
            v-model="searchQuery" 
            placeholder="Cari stream..." 
            class="search-input"
            @input="debouncedSearch"
            @keyup.enter="handleSearch"
          />
        </div>
        <button 
          @click="toggleSortMode"
          :class="[
            'mode-toggle-button',
            isSortMode ? 'mode-toggle-active' : 'mode-toggle-inactive'
          ]"
        >
          <font-awesome-icon :icon="isSortMode ? 'fa-solid fa-arrows-alt' : 'fa-solid fa-list'" />
          {{ isSortMode ? 'Mode Sortir' : 'Mode Normal' }}
        </button>
        <a :href="getRoute('admin.streams.create')" class="admin-action-button">
          <font-awesome-icon icon="fa-solid fa-plus" />
          Tambah Stream Baru
        </a>
      </template>
    </AdminNavbar>

    <div v-if="!props.streams?.data" class="p-4 text-center">
      Loading...
    </div>

    <div v-else-if="props.streams.data.length === 0" class="p-4 text-center">
      No streams found.
    </div>

    <div v-else>
      <!-- Sort Mode Notice -->
      <div v-if="isSortMode" class="sort-mode-notice">
        <font-awesome-icon icon="fa-solid fa-info-circle" class="mr-2" />
        Mode sortir aktif - Drag dan drop untuk mengubah urutan stream
      </div>

      <!-- Table for Normal Mode -->
      <AdminTable
        v-if="!isSortMode"
        :columns="columns"
        :items="sortedStreams"
        :searchQuery="searchQuery"
        :pagination="props.streams.meta"
        @page="navigateToPage"
      >
        <template #column:nomor="{ item }">
          <div class="flex justify-center">
            <span v-if="item.is_allowed_for_diagram && item.sort_order">
              {{ item.sort_order }}
            </span>
            <span v-else class="text-gray-400">-</span>
          </div>
        </template>

        <template #column:stream_name="{ item }">
          <div class="flex items-center gap-2">
            <div 
              v-if="item.color" 
              class="w-4 h-4 rounded-full border border-gray-300"
              :style="{ backgroundColor: item.color }"
            ></div>
            <span class="font-medium">{{ item.stream_name }}</span>
          </div>
        </template>
        
        <template #column:description="{ item }">
          <span class="text-gray-600">{{ item.description || '-' }}</span>
        </template>

        <template #column:apps_count="{ item }">
          <div class="flex justify-center">
            <span class="badge badge-outline">
              {{ item.apps_count || 0 }}
            </span>
          </div>
        </template>
        
        <template #column:actions="{ item }">
          <div class="flex justify-center gap-2">
            <a 
              :href="`/admin/streams/${item.stream_id}/edit`" 
              class="action-button edit-button"
              title="Edit Stream"
            >
              <font-awesome-icon icon="fa-solid fa-pencil" />
            </a>
            <button 
              @click="deleteStream(item.stream_id)" 
              class="action-button delete-button"
              title="Hapus Stream"
              :disabled="item.apps_count > 0"
            >
              <font-awesome-icon icon="fa-solid fa-trash" />
            </button>
          </div>
        </template>
      </AdminTable>

      <!-- Sortable Table for Sort Mode -->
      <div v-else class="sort-table-container">
        <div class="sort-table-header">
          <div class="sort-header-cell">Drag</div>
          <div class="sort-header-cell">No.</div>
          <div class="sort-header-cell">Stream</div>
          <div class="sort-header-cell">Aksi</div>
        </div>
        <draggable
          v-model="sortableStreams"
          @end="onSortEnd"
          item-key="stream_id"
          handle=".drag-handle"
          class="sort-table-body"
        >
          <template #item="{ element, index }">
            <div class="sort-table-row">
              <div class="sort-cell drag-cell">
                <div class="drag-handle">
                  <font-awesome-icon icon="fa-solid fa-grip-vertical" />
                </div>
              </div>
              <div class="sort-cell nomor-cell">
                <span>{{ index + 1 }}</span>
              </div>
              <div class="sort-cell stream-cell">
                <div class="flex items-center gap-2">
                  <div 
                    v-if="element.color" 
                    class="w-4 h-4 rounded-full border border-gray-300"
                    :style="{ backgroundColor: element.color }"
                  ></div>
                  <span class="font-medium">{{ element.stream_name }}</span>
                </div>
              </div>
              <div class="sort-cell actions-cell">
                <a 
                  :href="`/admin/streams/${element.stream_id}/edit`" 
                  class="action-button edit-button"
                  title="Edit Stream"
                >
                  <font-awesome-icon icon="fa-solid fa-pencil" />
                </a>
              </div>
            </div>
          </template>
        </draggable>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useRoutes } from '@/composables/useRoutes';
import AdminNavbar from '@/components/Admin/AdminNavbar.vue';
import AdminTable from '@/components/Admin/AdminTable.vue';
import { useAdminTable } from '@/composables/useAdminTable';
// @ts-ignore
import draggable from 'vuedraggable';

interface Stream {
  stream_id: number;
  stream_name: string;
  description: string | null;
  is_allowed_for_diagram: boolean;
  sort_order: number | null;
  color: string | null;
  apps_count: number;
}

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface Props {
  streams?: {
    data: Stream[];
    meta?: {
      links: PaginationLink[];
    };
  };
}

const props = defineProps<Props>();

// Use composables
const { getRoute } = useRoutes();
const { searchQuery, debouncedSearch, handleSearch, navigateToPage } = useAdminTable({});

// Sort mode toggle
const isSortMode = ref(false);
const sortableStreams = ref<Stream[]>([]);

// Computed property to sort streams by nomor (sort_order) ascending
const sortedStreams = computed(() => {
  if (!props.streams?.data) return [];
  
  return [...props.streams.data].sort((a, b) => {
    // Allowed streams with sort_order come first, sorted by sort_order
    if (a.is_allowed_for_diagram && a.sort_order && b.is_allowed_for_diagram && b.sort_order) {
      return a.sort_order - b.sort_order;
    }
    // Allowed streams with sort_order come before allowed without sort_order
    if (a.is_allowed_for_diagram && a.sort_order && b.is_allowed_for_diagram && !b.sort_order) {
      return -1;
    }
    if (a.is_allowed_for_diagram && !a.sort_order && b.is_allowed_for_diagram && b.sort_order) {
      return 1;
    }
    // Allowed streams come before not allowed streams
    if (a.is_allowed_for_diagram && !b.is_allowed_for_diagram) {
      return -1;
    }
    if (!a.is_allowed_for_diagram && b.is_allowed_for_diagram) {
      return 1;
    }
    // Within same category, sort by stream_name
    return a.stream_name.localeCompare(b.stream_name);
  });
});

// Initialize sortable streams
watch(() => props.streams?.data, (newData) => {
  if (newData) {
    // Filter only allowed streams for sorting and sort by sort_order
    sortableStreams.value = [...newData]
      .filter(stream => stream.is_allowed_for_diagram)
      .sort((a, b) => (a.sort_order || 999) - (b.sort_order || 999));
  }
}, { immediate: true });

const columns = [
  { key: 'nomor', label: 'No.', centered: true },
  { key: 'stream_name', label: 'Nama Stream' },
  { key: 'description', label: 'Deskripsi' },
  { key: 'apps_count', label: 'Aplikasi', centered: true },
  { key: 'actions', label: 'Aksi', centered: true }
];

function toggleSortMode() {
  isSortMode.value = !isSortMode.value;
  if (isSortMode.value && props.streams?.data) {
    // Refresh sortable streams when entering sort mode
    sortableStreams.value = [...props.streams.data]
      .filter(stream => stream.is_allowed_for_diagram)
      .sort((a, b) => (a.sort_order || 999) - (b.sort_order || 999));
  }
}

function onSortEnd() {
  // Update sort order based on new positions
  const updates = sortableStreams.value.map((stream, index) => ({
    stream_id: stream.stream_id,
    sort_order: index + 1
  }));

  // Send update to backend
  router.patch('/admin/streams/bulk-update-sort', {
    updates: updates
  }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      // Update local data
      sortableStreams.value.forEach((stream, index) => {
        stream.sort_order = index + 1;
      });
    }
  });
}

function deleteStream(streamId: number) {
  if (confirm('Apakah anda yakin ingin menghapus stream ini?')) {
    router.delete(`/admin/streams/${streamId}`);
  }
}
</script>

<style scoped>
@import '@/../css/admin.css';

.sort-mode-notice {
  background-color: #eff6ff;
  border: 1px solid #3b82f6;
  color: #1e40af;
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  font-size: 0.875rem;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
}

.status-badge-allowed {
  background-color: #dcfce7;
  color: #166534;
}

.status-badge-disabled {
  background-color: #fef2f2;
  color: #dc2626;
}

.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.125rem 0.625rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
}

.badge-info {
  background-color: #dbeafe;
  color: #1e40af;
}

.badge-outline {
  border: 1px solid #d1d5db;
  color: #374151;
  background-color: white;
}

.nomor-cell {
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>

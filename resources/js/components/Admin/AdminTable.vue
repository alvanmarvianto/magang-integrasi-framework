<template>
  <div class="admin-table-container">
    <table class="admin-table">
      <thead>
        <tr>
          <th 
            v-for="column in columns" 
            :key="column.key"
            :class="{ 
              'sortable': column.sortable,
              'sorted': column.sortable && sortBy === column.key,
              'text-center': column.centered 
            }"
            @click="column.sortable && toggleSort(column.key)"
          >
            {{ column.label }}
            <font-awesome-icon 
              v-if="column.sortable"
              :icon="getSortIcon(column.key)" 
              class="sort-icon"
            />
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in items" :key="item.id || item.app_id || item.stream_id">
          <td 
            v-for="column in columns" 
            :key="column.key"
            :class="{ 'text-center': column.centered }"
          >
            <slot :name="`column:${column.key}`" :item="item">
              {{ item[column.key] }}
            </slot>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="pagination?.links" class="admin-pagination">
      <div class="flex gap-2">
        <button
          v-for="link in pagination.links"
          :key="link.label"
          class="admin-pagination-button"
          :class="{ active: link.active }"
          :disabled="!link.url"
          @click="link.url && handlePageClick(link.url)"
          v-html="link.label"
        ></button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">

interface Column {
  key: string;
  label: string;
  sortable?: boolean;
  centered?: boolean;
}

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface Props {
  columns: Column[];
  items: any[];
  sortBy?: string;
  sortDesc?: boolean;
  searchQuery?: string;
  pagination?: {
    links: PaginationLink[];
  };
}

const props = withDefaults(defineProps<Props>(), {
  sortBy: undefined,
  sortDesc: false,
  searchQuery: '',
});

const emit = defineEmits<{
  (e: 'update:sortBy', value: string): void;
  (e: 'update:sortDesc', value: boolean): void;
  (e: 'page', url: string): void;
}>();

function getSortIcon(column: string) {
  if (props.sortBy !== column) {
    return 'fa-solid fa-sort';
  }
  return props.sortDesc ? 'fa-solid fa-sort-down' : 'fa-solid fa-sort-up';
}

function toggleSort(column: string) {
  if (props.sortBy === column) {
    emit('update:sortDesc', !props.sortDesc);
  } else {
    emit('update:sortBy', column);
    emit('update:sortDesc', false);
  }
}

function handlePageClick(url: string) {
  // Parse the URL to extract the page parameter
  const urlObj = new URL(url, window.location.origin);
  const page = urlObj.searchParams.get('page');
  
  // Build new URL with current search and sort parameters
  const params = new URLSearchParams();
  
  if (page) {
    params.set('page', page);
  }
  
  if (props.searchQuery) {
    params.set('search', props.searchQuery);
  }
  
  if (props.sortBy) {
    params.set('sort_by', props.sortBy);
  }
  
  if (props.sortDesc) {
    params.set('sort_desc', '1');
  }

  const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
  emit('page', newUrl);
}
</script>

<style scoped>
@import '@/../css/admin.css';

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

.admin-pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background-color: var(--bg-alt);
  border-top: 1px solid var(--border-color);
}

.admin-pagination-button {
  padding: 0.5rem 1rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  background-color: white;
  color: var(--text-color);
  font-size: 0.875rem;
  transition: all var(--transition-fast);
}

.admin-pagination-button:hover:not(:disabled) {
  background-color: var(--bg-hover);
}

.admin-pagination-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.admin-pagination-button.active {
  background-color: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}
</style>

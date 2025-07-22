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
        <tr v-for="item in sortedItems" :key="item.id">
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
          @click="link.url &amp;&amp; $emit('page', link.url)"
          v-html="link.label"
        ></button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

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
  pagination?: {
    links: PaginationLink[];
  };
}

const props = withDefaults(defineProps<Props>(), {
  sortBy: undefined,
  sortDesc: false,
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

const sortedItems = computed(() => {
  if (!props.sortBy) return props.items;

  return [...props.items].sort((a, b) => {
    const aVal = a[props.sortBy!]?.toString().toLowerCase() ?? '';
    const bVal = b[props.sortBy!]?.toString().toLowerCase() ?? '';
    
    return props.sortDesc
      ? bVal.localeCompare(aVal)
      : aVal.localeCompare(bVal);
  });
});
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
</style>

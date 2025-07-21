<template>
  <div class="admin-container">
    <div class="admin-header">
      <h1 class="admin-title">Application Management</h1>
      <a href="/admin/apps/create" class="admin-action-button">
        <font-awesome-icon icon="fa-solid fa-plus" />
        Add New Application
      </a>
    </div>

    <div class="admin-table-container">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Stream</th>
            <th>Type</th>
            <th>Stratification</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="app in apps.data" :key="app.app_id">
            <td>{{ app.app_name }}</td>
            <td>{{ app.stream?.stream_name }}</td>
            <td class="capitalize">{{ app.app_type }}</td>
            <td class="capitalize">{{ app.stratification }}</td>
            <td>
              <div class="flex justify-center gap-2">
                <a 
                  :href="`/admin/apps/${app.app_id}/edit`" 
                  class="action-button edit-button"
                  title="Edit Application"
                >
                  <font-awesome-icon icon="fa-solid fa-pencil" />
                </a>
                <button 
                  @click="deleteApp(app.app_id)" 
                  class="action-button delete-button"
                  title="Delete Application"
                >
                  <font-awesome-icon icon="fa-solid fa-trash" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="admin-pagination">
        <div class="flex gap-2">
          <button
            v-for="link in apps.links"
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
import { router } from '@inertiajs/vue3';

interface App {
  app_id: number;
  app_name: string;
  app_type: string;
  stratification: string;
  stream?: {
    stream_name: string;
  };
}

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface Props {
  apps: {
    data: App[];
    links: PaginationLink[];
  };
}

const props = defineProps<Props>();

function deleteApp(appId: number) {
  if (confirm('Are you sure you want to delete this application?')) {
    router.delete(`/admin/apps/${appId}`);
  }
}

function navigateToPage(url: string) {
  router.get(url);
}
</script>

<style scoped>
@import '../../../css/admin.css';

.capitalize {
  text-transform: capitalize;
}

.action-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  transition: all var(--transition-fast);
  font-size: 14px;
  color: var(--primary-color);
}

.edit-button {
  border-radius: 4px;
}

.edit-button:hover {
  background-color: var(--primary-color-light);
}

.delete-button:hover {
  opacity: 0.8;
}

/* Update the gap between buttons */
.flex.justify-center.gap-2 {
  display: inline-flex;
  gap: 8px;
}
</style> 
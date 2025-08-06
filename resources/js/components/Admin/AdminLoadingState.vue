<template>
  <div class="admin-loading-state">
    <div v-if="loading" class="loading-content">
      <div class="loading-spinner"></div>
      <p>{{ loadingText }}</p>
    </div>
    
    <div v-else-if="empty" class="empty-content">
      <div class="empty-icon">
        <font-awesome-icon :icon="emptyIcon" />
      </div>
      <p>{{ emptyText }}</p>
    </div>
    
    <slot v-else />
  </div>
</template>

<script setup lang="ts">
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

interface Props {
  loading?: boolean;
  empty?: boolean;
  loadingText?: string;
  emptyText?: string;
  emptyIcon?: string;
}

withDefaults(defineProps<Props>(), {
  loading: false,
  empty: false,
  loadingText: 'Loading...',
  emptyText: 'No data found.',
  emptyIcon: 'fa-solid fa-inbox'
});
</script>

<style scoped>
.admin-loading-state {
  min-height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.loading-content,
.empty-content {
  text-align: center;
  color: #6b7280;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #f3f4f6;
  border-top: 3px solid var(--primary-color, #4f46e5);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

.empty-icon {
  font-size: 3rem;
  color: #d1d5db;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

p {
  margin: 0;
  font-size: 0.875rem;
}
</style>

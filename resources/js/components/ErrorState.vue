<template>
  <div class="error-state">
    <div class="error-content">
      <font-awesome-icon :icon="iconName" :class="['error-icon', iconClass]" />
      <h2>{{ props.title }}</h2>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useRoutes } from '../composables/useRoutes';

interface Props {
  title?: string;
  showBackButton?: boolean;
  backRoute?: string;
  backRouteParams?: Record<string, any>;
  app?: {
    app_id: number;
    app_name: string;
  } | null;
}

const props = withDefaults(defineProps<Props>(), {
  showBackButton: true,
  title: 'Tidak ada data tersedia',
});

const { visitRoute } = useRoutes();

// Computed properties for dynamic content based on type
const iconName = computed(() => {
  return 'fa-solid fa-circle-info';
});

const iconClass = computed(() => {
  return 'error-icon-muted';
});

function handleBack() {
  if (props.backRoute) {
    visitRoute(props.backRoute, props.backRouteParams);
  } else if (props.app) {
    visitRoute('technology.app', { app_id: props.app.app_id });
  } else {
    visitRoute('index');
  }
}
</script>

<style scoped>
.error-state {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 80vh;
  padding: var(--spacing-8);
}

.error-content {
  text-align: center;
  color: var(--text-muted);
  max-width: 500px;
}

.error-icon {
  font-size: 2rem;
  margin-bottom: var(--spacing-4);
  opacity: 0.7;
}

.error-icon-muted {
  color: var(--text-muted);
  opacity: 0.6;
  background: rgba(156, 163, 175, 0.1);
  border-radius: 50%;
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto var(--spacing-4) auto;
}

.error-content h2 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-muted);
  margin: 0 0 var(--spacing-6) 0;
  opacity: 0.8;
}

.error-actions {
  margin-top: var(--spacing-6);
}

.back-button {
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-2);
  padding: var(--spacing-3) var(--spacing-4);
  background: var(--primary-color);
  color: white;
  border: none;
  border-radius: var(--radius);
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.back-button:hover {
  background: var(--primary-color-dark);
}

/* Responsive */
@media (max-width: 768px) {
  .error-state {
    padding: var(--spacing-4);
  }
  
  .error-icon {
    font-size: 1.5rem;
  }
  
  .error-content h2 {
    font-size: 1.25rem;
  }
  
  .error-content p {
    font-size: 0.875rem;
  }
}
</style>

<template>
  <div class="detail-section">
    <h3 class="section-title">
      <font-awesome-icon :icon="icon" />
      {{ title }}
    </h3>
    <div class="detail-items">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

interface Props {
  title: string;
  icon: string;
}

defineProps<Props>();
</script>

<style scoped>
.detail-section {
  background: white;
  border-radius: var(--radius-md);
  padding: var(--spacing-6);
  box-shadow: var(--shadow);
}

.section-title {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--text-color);
  margin: 0 0 var(--spacing-4) 0;
}

.detail-items {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-3);
}
</style>

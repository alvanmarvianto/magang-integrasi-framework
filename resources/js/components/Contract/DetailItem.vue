<template>
  <div class="detail-item">
    <span class="detail-label">{{ label }}:</span>
    <div class="detail-value" :class="valueClass">
      <slot>{{ value }}</slot>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  label: string;
  value?: string | number;
  valueClass?: string;
}

defineProps<Props>();
</script>

<style scoped>
.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-2) 0;
}

.detail-label {
  font-weight: 500;
  color: var(--text-muted);
  font-size: 0.875rem;
}

.detail-value {
  font-weight: 600;
  color: var(--text-color);
  font-size: 0.875rem;
}
</style>

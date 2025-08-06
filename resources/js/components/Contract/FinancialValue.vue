<template>
  <span class="financial-value">{{ formattedValue }}</span>
</template>

<script setup lang="ts">
interface Props {
  value: string | number;
  isRupiah?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  isRupiah: true,
});

const formattedValue = computed(() => {
  const numValue = typeof props.value === 'string' ? parseFloat(props.value) : props.value;
  if (isNaN(numValue)) return 'N/A';
  
  if (props.isRupiah) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(numValue);
  }
  
  return new Intl.NumberFormat('en-US', {
    style: 'decimal',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(numValue);
});
</script>

<script lang="ts">
import { computed } from 'vue';
</script>

<style scoped>
.financial-value {
  color: var(--success-color);
  font-family: 'Courier New', monospace;
}
</style>

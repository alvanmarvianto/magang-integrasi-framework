<template>
  <div class="legend">
    <h3>{{ title }}</h3>
    <ul>
      <li v-for="item in items" :key="item.label">
        <span 
          class="legend-key" 
          :class="getLegendClass(item)"
        ></span>
        {{ item.label }}
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
interface LegendItem {
  label: string;
  type: 'circle' | 'line';
  class?: string;
}

interface Props {
  title: string;
  items: LegendItem[];
}

defineProps<Props>();

function getLegendClass(item: LegendItem) {
  const classes: Record<string, boolean> = {
    circle: item.type === 'circle',
    line: item.type === 'line',
  };
  
  if (item.class) {
    classes[item.class] = true;
  }
  
  return classes;
}
</script>

<style scoped>
/* Legend styles are in components.css */
</style>

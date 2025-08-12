<template>
  <div class="legend">
    <h3>{{ title }}</h3>
    <ul>
      <li v-for="item in items" :key="item.label">
        <span 
          class="legend-key" 
          :class="getLegendClass(item)"
          :style="getLegendStyle(item)"
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
  color?: string;
  isAllowed?: boolean;
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

function getLegendStyle(item: LegendItem) {
  if (item.color) {
    if (item.type === 'circle') {
      return {
        backgroundColor: item.color,
        borderColor: item.color
      };
    } else if (item.type === 'line') {
      return {
        backgroundColor: item.color
      };
    }
  }
  return {};
}
</script>

<style scoped>
/* Legend */
.legend {
  margin-top: 20px;
}

.legend h3 {
  margin-bottom: 10px;
}

.legend ul {
  list-style: none;
  padding: 0;
}

.legend li {
  margin-bottom: 5px;
  display: flex;
  align-items: center;
}

.legend-key {
  display: inline-block;
  margin-right: 10px;
}

.legend-key.circle {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 1px solid #000;
}

.legend-key.line {
  width: 20px;
  height: 2px;
}
</style>

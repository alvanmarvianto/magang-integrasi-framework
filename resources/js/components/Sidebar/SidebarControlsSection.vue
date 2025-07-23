<template>
  <div class="controls-section">
    <h3>{{ title }}</h3>
    <button
      v-for="control in controls"
      :key="control.label"
      @click="control.onClick"
      class="control-button"
    >
      <FontAwesomeIcon :icon="control.icon" />
      {{ control.label }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

interface Control {
  label: string;
  icon: string;
  onClick: () => void;
}

interface Props {
  title: string;
  controls: Control[];
}

defineProps<Props>();
</script>

<style scoped>
/* Controls section styles are in components.css */
</style>

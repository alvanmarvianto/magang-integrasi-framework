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
/* Controls Section */
.controls-section {
  margin-bottom: 20px;
  padding-bottom: 20px;
  border-bottom: 1px solid rgba(221, 221, 221, 0.2);
}

.controls-section h3 {
  margin: 0 0 15px 0;
  font-size: 1.2em;
  font-weight: 600;
  color: var(--text-color-light);
}

.control-button {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 15px;
  text-decoration: none;
  border-radius: 8px;
  background-color: rgba(255, 255, 255, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: all 0.3s ease;
  color: var(--text-color-light);
  font-weight: 500;
  backdrop-filter: blur(10px);
  cursor: pointer;
  width: 100%;
  margin-bottom: 8px;
  font-size: 0.95em;
}

.control-button:hover {
  background-color: rgba(255, 255, 255, 0.5);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  color: var(--text-color-light);
}

.control-button i {
  font-size: 1.1em;
  width: 20px;
  text-align: center;
  color: var(--primary-color);
}

.control-button span {
  font-size: 0.95em;
}
</style>

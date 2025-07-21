<template>
  <div class="tech-section">
    <div class="tech-section-header">
      <h3 class="tech-section-title">{{ title }}</h3>
      <button
        type="button"
        @click="$emit('add')"
        class="tech-section-add"
      >
        <font-awesome-icon icon="fa-solid fa-plus" /> Add
      </button>
    </div>

    <div v-if="items.length === 0" class="tech-section-empty">
      Tidak ada item yang ditambahkan
    </div>

    <div v-else class="tech-section-items">
      <div
        v-for="(item, index) in items"
        :key="index"
        class="tech-section-item"
      >
        <select
          v-model="item.name"
          class="tech-section-select"
          required
        >
          <option value="">Pilih {{ title }}</option>
          <option v-for="name in availableItems" :key="name" :value="name">
            {{ name }}
          </option>
        </select>

        <input
          v-model="item.version"
          type="text"
          placeholder="Version/Deskripsi (opsional)"
          class="tech-section-input"
        >

        <button
          type="button"
          @click="$emit('remove', index)"
          class="tech-section-remove"
        >
          <font-awesome-icon icon="fa-solid fa-trash" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

interface TechItem {
  name: string;
  version?: string;
}

defineProps<{
  title: string;
  items: TechItem[];
  availableItems: string[];
}>();

defineEmits<{
  (e: 'add'): void;
  (e: 'remove', index: number): void;
}>();
</script>

<style scoped>
@import '../../../css/components.css';

.tech-section {
  margin-top: 2rem;
}

.tech-section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.tech-section-title {
  font-size: 1rem;
  font-weight: 500;
  color: var(--text-color);
}

.tech-section-add {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
  color: var(--primary-color);
  background-color: var(--bg-alt);
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  transition: all var(--transition-fast);
}

.tech-section-add:hover {
  background-color: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.tech-section-empty {
  padding: 1rem;
  text-align: center;
  color: var(--text-muted);
  background-color: var(--bg-alt);
  border-radius: var(--radius);
  font-size: 0.875rem;
}

.tech-section-items {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.tech-section-item {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 0.75rem;
  align-items: start;
}

.tech-section-select,
.tech-section-input {
  padding: 0.5rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  font-size: 0.875rem;
  background-color: white;
}

.tech-section-select:focus,
.tech-section-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px var(--primary-color-light);
}

.tech-section-remove {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  color: var(--danger-color);
  background-color: var(--bg-alt);
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  transition: all var(--transition-fast);
}

.tech-section-remove:hover {
  background-color: var(--danger-color);
  color: white;
  border-color: var(--danger-color);
}
</style> 
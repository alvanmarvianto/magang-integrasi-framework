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
  background-color: white;
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  padding: 1.25rem;
  margin-bottom: 1.5rem;
  display: flex;
  flex-direction: column;
  min-height: 0; /* Required for Firefox */
}

.tech-section:last-child {
  margin-bottom: 0;
}

.tech-section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  position: sticky;
  top: 0;
  background: white;
  z-index: var(--z-10);
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--border-color);
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
  padding: 0.375rem 0.75rem;
  color: var(--primary-color);
  border-radius: var(--radius);
  background-color: var(--bg-alt);
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  transition: all var(--transition-fast);
  transition: all var(--transition-fast);
  cursor: pointer;
  border: none;
}

.tech-section-add:hover {
  background-color: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
  opacity: 0.9;
}
.tech-section-add i {
  font-size: 0.75rem;
}

.tech-section-empty {
  padding: 1rem;
  text-align: center;
  color: var(--text-muted);
  background-color: var(--bg-alt);
  border-radius: var(--radius);
  font-size: 0.875rem;
  padding: 2rem;
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.tech-section-items {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  overflow-y: auto;
  flex: 1;
  padding: 0.5rem;
  margin: -0.5rem;
}

.tech-section-item {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 0.75rem;
  align-items: start;
  padding: 0.75rem;
  background-color: var(--bg-alt);
  border-radius: var(--radius);
  transition: background-color var(--transition-fast);
}

.tech-section-item:hover {
  background-color: var(--bg-hover);
}

.tech-section-select,
.tech-section-input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  font-size: 0.875rem;
  background-color: white;
  transition: all var(--transition-fast);
}

.tech-section-select:focus,
.tech-section-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px var(--primary-color-light);
}

.tech-section-select::placeholder,
.tech-section-input::placeholder {
  color: var(--text-muted);
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
  cursor: pointer;
  border: none;
}

.tech-section-remove:hover {
  background-color: var(--danger-color);
  color: white;
  border-color: var(--danger-color);
}

.tech-section-remove i {
  font-size: 0.875rem;
}
</style> 
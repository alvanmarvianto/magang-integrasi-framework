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
          <option value="">Select {{ title }}</option>
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
</style> 
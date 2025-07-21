<template>
  <div class="tech-section">
    <div class="tech-section-header">
      <h3 class="tech-section-title">{{ title }}</h3>
      <button
        type="button"
        @click="$emit('add')"
        class="tech-section-add"
      >
        <i class="fas fa-plus"></i> Add
      </button>
    </div>

    <div v-if="items.length === 0" class="tech-section-empty">
      No items added
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
          placeholder="Version (optional)"
          class="tech-section-input"
        >

        <button
          type="button"
          @click="$emit('remove', index)"
          class="tech-section-remove"
        >
          <i class="fas fa-trash"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
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
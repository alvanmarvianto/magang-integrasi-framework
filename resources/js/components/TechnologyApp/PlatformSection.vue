<template>
  <div v-if="technology?.platform?.length" class="content-section platform-content">
    <div class="content-items">
      <a
        v-for="item in technology.platform" 
        :key="item.name"
        class="content-item"
        :data-tooltip="item.version ? `${item.name} - ${item.version}` : item.name"
        :href="item.version ? `/technology/platform/${item.name}` : undefined"
        target="_blank"
        rel="noopener"
      >
        {{ item.name }}
      </a>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  technology: any;
}>();
</script>


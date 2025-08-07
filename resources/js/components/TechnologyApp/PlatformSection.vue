<template>
  <div v-if="technology?.platform?.length" class="content-section platform-content">
    <div class="content-items">
      <a
        v-for="item in technology.platform" 
        :key="item.name"
        class="content-item"
        :data-tooltip="item.version ? `${item.name} ${item.version}` : item.name"
        :href="`/technology/platform/${item.name}`"
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

<style scoped>
.platform-content .content-item {
  background: linear-gradient(135deg, rgba(255, 120, 110, 0.9), rgba(255, 100, 90, 0.9)) !important;
  color: #333 !important;
}
</style>
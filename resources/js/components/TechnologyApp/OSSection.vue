<template>
  <div v-if="technology?.os?.length" class="content-section os-content">
    <div class="content-items">
      <a
        v-for="item in technology.os" 
        :key="item.name"
        class="content-item"
        :data-tooltip="item.version ? `${item.name} ${item.version}` : item.name"
        :href="`/technology/os/${item.name}`"
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
.os-content .content-item {
  background: linear-gradient(135deg, rgba(255, 225, 220, 0.9), rgba(255, 215, 210, 0.9)) !important;
  color: #333 !important;
}
</style>
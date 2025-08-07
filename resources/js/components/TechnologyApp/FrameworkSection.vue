<template>
  <div v-if="technology?.framework?.length" class="content-section framework-content">
    <div class="content-items">
      <a
        v-for="item in technology.framework"
        :key="item.name"
        class="content-item"
        :data-tooltip="item.version ? `${item.name} ${item.version}` : item.name"
        :href="`/technology/framework/${item.name}`"
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
.framework-content .content-item {
  background: linear-gradient(135deg, rgba(255, 180, 170, 0.9), rgba(255, 160, 150, 0.9)) !important;
  color: #333 !important;
}
</style>
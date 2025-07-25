<template>
  <div v-if="technology.app_type" class="content-section app-type-content">
    <div class="content-items">
      <a
        class="content-item"
        :href="`/technology/app_type/${technology.app_type}`"
        target="_blank"
        rel="noopener"
      >
        {{ technology.app_type }}
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
.app-type-content .content-item {
  background: linear-gradient(135deg, rgba(255, 248, 245, 0.9), rgba(255, 245, 240, 0.9)) !important;
  color: #333 !important;
  font-weight: 600 !important;
  text-transform: uppercase;
  letter-spacing: 1px;
}
</style>
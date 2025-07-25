<template>
  <div v-if="technology.stratification" class="content-section stratification-content">
    <div class="content-items">
      <a
        class="content-item"
        :href="`/technology/stratification/${technology.stratification}`"
        target="_blank"
        rel="noopener"
      >
        {{ technology.stratification }}
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
.stratification-content .content-item {
  background: linear-gradient(135deg, rgba(255, 235, 230, 0.9), rgba(255, 225, 220, 0.9)) !important;
  color: #333 !important;
  font-weight: 600 !important;
  text-transform: uppercase;
  letter-spacing: 1px;
}
</style>

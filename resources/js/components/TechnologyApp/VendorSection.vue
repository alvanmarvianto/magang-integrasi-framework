<template>
  <div v-if="technology?.vendor?.length" class="content-section vendor-content">
    <div class="content-items">
      <a
        v-for="item in technology.vendor" 
        :key="item.name"
        class="content-item"
        :href="`/technology/vendor/${item.name}`"
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
.vendor-content .content-item {
  background: linear-gradient(135deg, rgba(255, 245, 240, 0.9), rgba(255, 240, 235, 0.9)) !important;
  color: #333 !important;
}
</style>
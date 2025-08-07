<template>
  <div v-if="technology?.third_party?.length" class="content-section third-party-content">
    <div class="content-items">
      <a
        v-for="item in technology.third_party" 
        :key="item.name"
        class="content-item"
        :data-tooltip="item.version ? `${item.name} ${item.version}` : item.name"
        :href="`/technology/third-party/${item.name}`"
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
.third-party-content .content-item {
  background: linear-gradient(135deg, rgba(255, 140, 130, 0.9), rgba(255, 120, 110, 0.9)) !important;
  color: #333 !important;
}
</style>
<template>
  <div v-if="technology?.language?.length" class="content-section language-content">
    <div class="content-items">
      <a
        v-for="item in technology.language" 
        :key="item.name"
        class="content-item"
        :data-tooltip="item.version ? `${item.name} - ${item.version}` : item.name"
        :href="`/technology/language/${item.name}`"
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
.language-content .content-item {
  background: linear-gradient(135deg, rgba(255, 200, 190, 0.9), rgba(255, 180, 170, 0.9)) !important;
  color: #333 !important;
}
</style>
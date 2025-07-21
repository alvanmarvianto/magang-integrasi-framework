<template>
  <div v-if="technology?.middleware?.length" class="content-section middleware-content">
    <div class="content-items">
      <a
        v-for="item in technology.middleware" 
        :key="item.name"
        class="content-item"
        :data-tooltip="item.version ? `${item.name} - ${item.version}` : item.name"
        :href="item.version ? `/technology/middleware/${item.name}` : undefined"
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


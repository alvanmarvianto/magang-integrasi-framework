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

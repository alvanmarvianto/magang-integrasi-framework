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

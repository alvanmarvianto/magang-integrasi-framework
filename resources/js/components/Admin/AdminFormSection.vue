<template>
  <div class="admin-form-section">
    <h2 class="admin-form-title">{{ title }}</h2>
    <slot />
  </div>
</template>

<script setup lang="ts">
defineProps<{ title: string }>();
</script>

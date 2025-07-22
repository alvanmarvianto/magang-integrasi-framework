<template>
  <div class="admin-form-field">
    <label :for="id" class="admin-form-label">{{ label }}</label>
    <slot />
  </div>
</template>

<script setup lang="ts">
defineProps<{ label: string; id?: string }>();
</script>

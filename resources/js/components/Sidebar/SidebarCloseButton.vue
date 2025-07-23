<template>
  <button id="sidebar-close" @click="$emit('close')">
    <FontAwesomeIcon icon="fa-solid fa-times" />
  </button>
</template>

<script setup lang="ts">
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineEmits<{
  close: [];
}>();
</script>

<style scoped>
/* Close button styles are in app.css */
</style>

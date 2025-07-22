<template>
  <form class="admin-form" @submit.prevent="$emit('submit')">
    <slot />
  </form>
</template>

<script setup lang="ts">
defineEmits(['submit']);
</script>

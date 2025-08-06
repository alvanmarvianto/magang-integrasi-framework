<template>
  <div class="admin-form-field">
    <label :for="id" class="admin-form-label">
      {{ label }}
      <span v-if="required" class="required-asterisk">*</span>
    </label>
    <slot />
  </div>
</template>

<script setup lang="ts">
defineProps<{ label: string; id?: string; required?: boolean }>();
</script>

<style scoped>
.admin-form-field {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

.required-asterisk {
  color: #dc2626;
  margin-left: 0.25rem;
  font-weight: 600;
}
</style>

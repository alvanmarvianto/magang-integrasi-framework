<template>
  <aside id="sidebar">
    <SidebarCloseButton v-if="showCloseButton" @close="$emit('close')" />
    
    <header v-if="title">
      <h1 :style="titleStyle">
        <FontAwesomeIcon v-if="icon" :icon="icon" />
        {{ title }}
      </h1>
    </header>
    
    <div class="sidebar-content">
      <slot />
    </div>
  </aside>
</template>

<script setup lang="ts">
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import SidebarCloseButton from './SidebarCloseButton.vue';

interface Props {
  title?: string;
  icon?: string;
  titleStyle?: Record<string, string>;
  showCloseButton?: boolean;
}

withDefaults(defineProps<Props>(), {
  titleStyle: () => ({}),
  showCloseButton: false,
});

defineEmits<{
  close: [];
}>();
</script>

<style scoped>
/* Sidebar styles are imported from app.css */
</style>

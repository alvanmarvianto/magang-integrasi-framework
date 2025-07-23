<template>
  <div class="stream-links">
    <h3>{{ title }}</h3>
    <p v-if="description">{{ description }}</p>
    <div class="stream-buttons">
      <SidebarStreamLink
        v-for="link in links"
        :key="link.href"
        :icon="link.icon"
        :text="link.text"
        :href="link.href"
        :variant="link.variant"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import SidebarStreamLink from './SidebarStreamLink.vue';

interface StreamLink {
  icon: string;
  text: string;
  href: string;
  variant?: 'default' | 'admin' | 'vue-flow';
}

interface Props {
  title: string;
  description?: string;
  links: StreamLink[];
}

defineProps<Props>();
</script>

<style scoped>
/* Stream links styles are in components.css */
</style>

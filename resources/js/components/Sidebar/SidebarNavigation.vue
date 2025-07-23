<template>
  <div class="navigation">
    <SidebarNavLink
      v-for="link in links"
      :key="link.href || link.onClick"
      :icon="link.icon"
      :text="link.text"
      :href="link.href"
      :onClick="link.onClick"
    />
  </div>
</template>

<script setup lang="ts">
import SidebarNavLink from './SidebarNavLink.vue';

interface NavLink {
  icon: string;
  text: string;
  href?: string;
  onClick?: () => void;
}

interface Props {
  links: NavLink[];
}

defineProps<Props>();
</script>

<style scoped>
/* Navigation styles are in components.css */
</style>

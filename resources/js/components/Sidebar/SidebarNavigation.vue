<template>
  <div class="sidebar-navigation">
    <!-- Section with title and description -->
    <div v-if="title || description" class="nav-section">
      <h3 v-if="title">{{ title }}</h3>
      <p v-if="description">{{ description }}</p>
    </div>
    
    <!-- Navigation links -->
    <div v-if="links && links.length > 0" class="nav-links">
      <a 
        v-for="link in links"
        :key="link.href || link.text"
        :href="link.href"
        class="nav-link"
        :class="getLinkClass(link.variant)"
        @click.prevent="handleLinkClick(link)"
      >
        <FontAwesomeIcon v-if="link.icon" :icon="link.icon" />
        <span>{{ link.text }}</span>
      </a>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

interface NavLink {
  icon?: string;
  text: string;
  href?: string;
  onClick?: () => void;
  variant?: 'default' | 'admin' | 'vue-flow';
}

interface Props {
  title?: string;
  description?: string;
  links?: NavLink[];
  variant?: 'navigation' | 'group';
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'navigation',
});

function getLinkClass(variant?: string): string {
  switch (variant) {
    case 'admin':
      return 'admin-link';
    case 'vue-flow':
      return 'vue-flow-link';
    default:
      return 'stream-link';
  }
}

function handleLinkClick(link: NavLink) {
  if (link.onClick) {
    link.onClick();
  } else if (link.href) {
    window.location.href = link.href;
  }
}
</script>

<style scoped>
.sidebar-navigation {
  margin-bottom: 1rem;
}

.nav-section {
  margin-bottom: 1rem;
}

.nav-section h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary, #333);
}

.nav-section p {
  margin: 0 0 0.5rem 0;
  font-size: 0.9rem;
  color: var(--text-secondary, #666);
  line-height: 1.4;
}

.nav-links {
  display: flex;
  flex-direction: column;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  text-decoration: none;
  color: var(--text-primary, #333);
  border-radius: 0.5rem;
  transition: all 0.2s ease;
  cursor: pointer;
}

.nav-link:hover {
  background-color: var(--hover-bg, #f5f5f5);
  transform: translateX(4px);
}

.nav-link.stream-link:hover {
  transform: translateX(4px);
}

.nav-link.admin-link {
  background-color: var(--admin-bg, #fff3e0);
  border: 1px solid var(--admin-border, #ffb74d);
}

.nav-link.admin-link:hover {
  background-color: var(--admin-hover, #ffe0b2);
  transform: translateX(4px);
}

.nav-link.vue-flow-link {
  background-color: var(--vue-flow-bg, #f3e5f5);
  border: 1px solid var(--vue-flow-border, #ce93d8);
  color: var(--vue-flow-text, #7b1fa2);
}

.nav-link.vue-flow-link:hover {
  background-color: var(--vue-flow-hover, #e1bee7);
  transform: translateX(4px);
}

.nav-link span {
  font-weight: 500;
}

/* Navigation variant styles */
.nav-links.navigation .nav-link {
  background: none;
  border: none;
  padding: 0.5rem 0.75rem;
}

.nav-links.navigation .nav-link:hover {
  background-color: var(--nav-hover, #f8f9fa);
}
</style>

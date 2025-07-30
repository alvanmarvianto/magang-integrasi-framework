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
  margin-bottom: 8px;
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

/* Vue Flow specific styling */
.vue-flow-link {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.3), rgba(168, 85, 247, 0.3));
  border: 1px solid rgba(99, 102, 241, 0.4);
}

.vue-flow-link:hover {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.5), rgba(168, 85, 247, 0.5));
  border: 1px solid rgba(99, 102, 241, 0.6);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(99, 102, 241, 0.3);
}

.vue-flow-link i {
  color: #6366f1;
}

/* Admin Link specific styling */
.admin-link {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.3), rgba(220, 38, 38, 0.3));
  border: 1px solid rgba(239, 68, 68, 0.4);
}

.admin-link:hover {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.5), rgba(220, 38, 38, 0.5));
  border: 1px solid rgba(239, 68, 68, 0.6);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
}

.admin-link i {
  color: #ef4444;
}

/* Stream Links */
.stream-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 15px;
  text-decoration: none;
  border-radius: 8px;
  background-color: rgba(255, 255, 255, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: all 0.3s ease;
  color: var(--text-color-light);
  font-weight: 500;
  backdrop-filter: blur(10px);
}

.stream-link:hover {
  background-color: rgba(255, 255, 255, 0.5);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  color: var(--text-color-light);
}

.stream-link i {
  font-size: 1.1em;
  width: 20px;
  text-align: center;
  color: var(--primary-color);
}

.stream-link span {
  font-size: 0.95em;
}

@media (max-width: 768px) {
    .stream-link {
    padding: 10px 12px;
    font-size: 0.9em;
  }
  
  .stream-link i {
    font-size: 1em;
  }

  .nav-link {
    padding: 10px 12px;
    font-size: 0.9em;
  }

  .nav-link i {
    font-size: 1em;
  }
}
</style>

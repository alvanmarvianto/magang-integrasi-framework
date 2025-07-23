<template>
  <a 
    :href="href" 
    class="stream-link"
    :class="linkClass"
  >
    <span>{{ text }}</span>
  </a>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

interface Props {
  icon: string;
  text: string;
  href: string;
  variant?: 'default' | 'admin' | 'vue-flow';
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
});

const linkClass = computed(() => {
  switch (props.variant) {
    case 'admin':
      return 'admin-link';
    case 'vue-flow':
      return 'vue-flow-link';
    default:
      return '';
  }
});
</script>

<style scoped>
/* Stream link styles are in components.css */
</style>

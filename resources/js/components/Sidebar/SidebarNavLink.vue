<template>
  <a 
    v-if="href"
    :href="href"
    class="nav-link"
    @click.prevent="handleClick"
  >
    <FontAwesomeIcon :icon="icon" />
    <span>{{ text }}</span>
  </a>
  <a 
    v-else
    class="nav-link"
    @click.prevent="handleClick"
  >
    <FontAwesomeIcon :icon="icon" />
    <span>{{ text }}</span>
  </a>
</template>

<script setup lang="ts">
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

interface Props {
  icon: string;
  text: string;
  href?: string;
  onClick?: () => void;
}

const props = defineProps<Props>();

function handleClick() {
  if (props.onClick) {
    props.onClick();
  } else if (props.href) {
    window.location.href = props.href;
  }
}
</script>

<style scoped>
/* Nav link styles are in components.css */
</style>

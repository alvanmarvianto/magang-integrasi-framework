<template>
  <div v-if="technology.platform" class="content-section platform-content">
    <div class="content-items">
      <a
        v-for="item in technology.platform"
        :key="item.name"
        class="content-item"
        :href="`/technology/platform/${item.name}`"
        target="_blank"
        rel="noopener"
        style="cursor:pointer;"
      >
        {{ item.name }}<span v-if="item.version">&nbsp;{{ item.version }}</span>
      </a>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ technology: any }>()
</script>

<template>
  <div v-if="technology.middleware" class="content-section middleware-content">
    <div class="content-items">
      <a
        v-for="item in technology.middleware"
        :key="item.name"
        class="content-item"
        :href="`/technology/middleware/${item.name}`"
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

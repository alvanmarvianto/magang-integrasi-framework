<template>
  <div v-if="technology.os" class="content-section os-content">
    <div class="content-items">
      <a
        v-for="item in technology.os"
        :key="item.name"
        class="content-item"
        :href="`/technology/os/${item.name}`"
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

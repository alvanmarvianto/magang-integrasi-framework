<template>
  <div v-if="technology.third_party" class="content-section third-party-content">
    <div class="content-items">
      <a
        v-for="item in technology.third_party"
        :key="item.name"
        class="content-item"
        :href="`/technology/third_party/${item.name}`"
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

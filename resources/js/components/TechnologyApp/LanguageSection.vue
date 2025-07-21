<template>
  <div v-if="technology?.language?.length" class="content-section language-content">
    <div class="content-items">
      <a
        v-for="item in technology.language" 
        :key="item.name"
        class="content-item"
        :data-tooltip="item.version ? `${item.name} - ${item.version}` : item.name"
        :href="item.version ? `/technology/language/${item.name}` : undefined"
        target="_blank"
        rel="noopener"
      >
        {{ item.name }}
      </a>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  technology: any;
}>();
</script>


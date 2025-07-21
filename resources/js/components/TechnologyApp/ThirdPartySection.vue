<template>
  <div v-if="technology?.third_party?.length" class="content-section third-party-content">
    <div class="content-items">
      <a
        v-for="item in technology.third_party" 
        :key="item.name"
        class="content-item"
        :data-tooltip="item.version ? `${item.name} - ${item.version}` : item.name"
        :href="item.version ? `/technology/third_party/${item.name}` : undefined"
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


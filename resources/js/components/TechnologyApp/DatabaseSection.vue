<template>
  <div v-if="technology?.database?.length" class="content-section database-content">
    <div class="content-items">
      <a
        v-for="item in technology.database"
        :key="item.name"
        class="content-item"
        :data-tooltip="item.version ? `${item.name} - ${item.version}` : item.name"
        :href="`/technology/database/${item.name}`"
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
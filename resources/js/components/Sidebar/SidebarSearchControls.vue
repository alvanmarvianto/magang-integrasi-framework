<template>
  <div id="controls">
    <div class="control-group">
      <label for="search">Search:</label>
      <div class="search-wrapper">
        <input 
          :value="searchTerm" 
          @input="onSearchInput" 
          type="text" 
          id="search" 
          list="search-suggestions"
          placeholder="Cari Aplikasi..." 
        />
      </div>
      <datalist id="search-suggestions">
        <option v-for="name in uniqueNodeNames" :key="name" :value="name" />
      </datalist>
    </div>
  </div>
</template>

<script setup lang="ts">

interface Props {
  searchTerm: string;
  uniqueNodeNames: string[];
  onSearchInput: (event: Event) => void;
  clearSearch: () => void;
}

defineProps<Props>();
</script>

<style scoped>
.search-wrapper {
  position: relative;
}
</style>

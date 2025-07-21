<template>
    <div class="admin-header">
      <h1 class="admin-title">{{ title }}</h1>
      <div class="admin-header-controls">
        <!-- Additional controls slot before back button -->
        <slot name="controls"></slot>
        
        <!-- Back button -->
        <a 
          v-if="showBackButton" 
          :href="backUrl || '/admin'" 
          class="admin-back-button"
        >
          <font-awesome-icon icon="fa-solid fa-arrow-left" />
          Kembali
        </a>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  defineProps<{
    title: string;
    showBackButton?: boolean;
    backUrl?: string;
  }>();
  </script>
  
  <style scoped>
  .admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    border-radius: var(--radius);
    border-bottom: 1px solid var(--border-color);
    padding: 1rem 2rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }
  
  .admin-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-color);
    margin: 0;
  }
  
  .admin-header-controls {
    display: flex;
    gap: 1rem;
    align-items: center;
  }
  
  .admin-back-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background-color: var(--bg-alt);
    color: var(--text-color);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    font-size: 0.875rem;
    text-decoration: none;
    transition: all var(--transition-fast);
  }
  
  .admin-back-button:hover {
    background-color: var(--bg-hover);
  }
  </style> 
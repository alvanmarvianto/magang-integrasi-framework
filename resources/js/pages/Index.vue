<template>
  <div id="container">
    <aside id="sidebar">
      <button id="sidebar-close" @click="closeSidebar">
        <i class="fas fa-times"></i>
      </button>
      <header>
        <h1>
          <i class="fas fa-sitemap"></i>
          Aplikasi BI
        </h1>
      </header>
      <div class="sidebar-content">
        <div id="controls">
          <div class="control-group">
            <label for="search">Search:</label>
            <div class="search-wrapper">
              <input v-model="searchTerm" @input="onSearchInput" type="text" id="search" list="search-suggestions"
                placeholder="Cari Aplikasi..." />
              <i class="fas fa-times-circle" id="clear-search" @click="clearSearch"></i>
            </div>
            <datalist id="search-suggestions">
              <option v-for="name in uniqueNodeNames" :key="name" :value="name" />
            </datalist>
          </div>
        </div>
        <div class="stream-links">
          <h3>Intergrasi dalam Stream</h3>
          <p>Navigasi ke halaman integrasi stream tertentu:</p>
          <div class="stream-buttons">
            <a href="/integration/stream/sp" class="stream-link">
              <i class="fas fa-shield-alt"></i>
              <span>SP Stream</span>
            </a>
            <a href="/integration/stream/mi" class="stream-link">
              <i class="fas fa-chart-line"></i>
              <span>MI Stream</span>
            </a>
            <a href="/integration/stream/ssk" class="stream-link">
              <i class="fas fa-project-diagram"></i>
              <span>SSK Stream</span>
            </a>
            <a href="/integration/stream/moneter" class="stream-link">
              <i class="fas fa-coins"></i>
              <span>Moneter Stream</span>
            </a>
            <a href="/integration/stream/market" class="stream-link">
              <i class="fas fa-store"></i>
              <span>Market Stream</span>
            </a>
          </div>
        </div>
        
        <div class="stream-links">
          <h3>Visualisasi Diagram</h3>
          <div class="stream-buttons">
            <a href="diagram/stream/sp" class="stream-link">
              <i class="fas fa-bezier-curve"></i>
              <span>SP Stream</span>
            </a>
            <a href="/diagram/stream/mi" class="stream-link">
              <i class="fas fa-bezier-curve"></i>
              <span>MI Stream</span>
            </a>
            <a href="/diagram/stream/ssk" class="stream-link">
              <i class="fas fa-bezier-curve"></i>
              <span>SSK Stream</span>
            </a>
            <a href="/diagram/stream/moneter" class="stream-link">
              <i class="fas fa-bezier-curve"></i>
              <span>Moneter Stream</span>
            </a>
            <a href="/diagram/stream/market" class="stream-link">
              <i class="fas fa-bezier-curve"></i>
              <span>Market Stream</span>
            </a>
          </div>
        </div>

        <div class="stream-links">
          <h3>Admin - Layout Editor</h3>
          <div class="stream-buttons">
            <a href="/admin/stream/sp" class="stream-link admin-link">
              <i class="fas fa-edit"></i>
              <span>SP Admin</span>
            </a>
            <a href="/admin/stream/mi" class="stream-link admin-link">
              <i class="fas fa-edit"></i>
              <span>MI Admin</span>
            </a>
            <a href="/admin/stream/ssk" class="stream-link admin-link">
              <i class="fas fa-edit"></i>
              <span>SSK Admin</span>
            </a>
            <a href="/admin/stream/moneter" class="stream-link admin-link">
              <i class="fas fa-edit"></i>
              <span>Moneter Admin</span>
            </a>
            <a href="/admin/stream/market" class="stream-link admin-link">
              <i class="fas fa-edit"></i>
              <span>Market Admin</span>
            </a>
          </div>
        </div>
      </div>
    </aside>

    <main id="main-content">
      <div id="menu-toggle" v-show="isMobile && !visible" :class="{ active: visible }" @click.stop="toggleSidebar">
        <i class="fas fa-bars"></i>
      </div>
      <div id="loader" v-if="loading"></div>
      <div id="body"></div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { useSidebar } from '../composables/useSidebar';
import { useD3Tree } from '../composables/useD3Tree';

const props = defineProps<{ appData: any }>();

const { visible, isMobile, toggleSidebar, closeSidebar } = useSidebar();
const { loading, searchTerm, uniqueNodeNames, onSearchInput, clearSearch } = useD3Tree(props.appData);
</script>


<style scoped src="../../css/app.css"></style>
<template>
  <div id="container">
    <aside id="sidebar">
      <button id="sidebar-close" @click="closeSidebar">
        <i class="fas fa-times"></i>
      </button>
      <header>
        <h1>
          <i class="fas fa-project-diagram"></i>
          Integrasi Aplikasi {{ appName }}
        </h1>
      </header>
      <div class="sidebar-content">
        <div class="navigation">
          <a @click.prevent="$inertia.visit('/')" class="nav-link">
            <i class="fas fa-home"></i>
            <span>Halaman Utama</span>
          </a>
          <a @click.prevent="$inertia.visit(`/technology/${parentAppId}`)" class="nav-link">
            <i class="fas fa-project-diagram"></i>
            <span>Halaman Teknologi</span>
          </a>
        </div>
        <div class="legend">
          <h3>Node Legend</h3>
          <ul>
            <li><span class="legend-key circle sp"></span> Aplikasi SP</li>
            <li><span class="legend-key circle mi"></span> Aplikasi MI</li>
            <li>
              <span class="legend-key circle ssk-mon"></span> Aplikasi SSK &
              Moneter
            </li>
            <li>
              <span class="legend-key circle market"></span> Aplikasi Market
            </li>
            <li>
              <span class="legend-key circle internal"></span> Aplikasi
              Internal BI di luar DLDS
            </li>
            <li>
              <span class="legend-key circle external"></span>
              Aplikasi Eksternal BI
            </li>
            <li>
              <span class="legend-key circle middleware"></span> Middleware
            </li>
          </ul>
        </div>

        <div class="legend">
          <h3>Link Legend</h3>
          <ul>
            <li><span class="legend-key line direct"></span> Direct</li>
            <li><span class="legend-key line soa"></span> SOA</li>
            <li><span class="legend-key line sftp"></span> SFTP</li>
          </ul>
        </div>
      </div>
    </aside>

    <main id="main-content">
      <div id="menu-toggle" v-show="isMobile && !visible" :class="{ active: visible }" @click.stop="toggleSidebar">
        <i class="fas fa-bars"></i>
      </div>
      <!-- <div id="loader" vif="loading"></div> -->
      <div id="body"></div>
      <p id="error-message" style="display: none"></p>
    </main>
  </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { useSidebar } from '../composables/useSidebar';
import { useD3ForceAppIntegration } from '../composables/useD3ForceAppIntegration';

const props = defineProps<{
  integrationData: any;
  appName: string;
  streamName: string;
  parentAppId: number;
}>();

const { visible, isMobile, toggleSidebar, closeSidebar } = useSidebar();
useD3ForceAppIntegration(props.integrationData);
</script>

<style scoped>
@import '../../css/app.css';
</style>
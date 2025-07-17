<template>
  <div id="container">
    <aside id="sidebar">
      <header>
        <h1>Spesifikasi Teknologi</h1>
      </header>
      <div class="sidebar-content">
        <div class="navigation">
          <a @click.prevent="$inertia.visit('/')" class="nav-link">
            <i class="fas fa-home"></i>
            <span>Halaman Utama</span>
          </a>
          <a @click.prevent="$inertia.visit(`/integration/app/${app.app_id}`)" class="nav-link">
            <i class="fas fa-project-diagram"></i>
            <span>Integrasi App</span>
          </a>
        </div>
        
        <!-- App Type and Stratification in Sidebar -->
        <div v-if="technology" class="sidebar-tech-info">
          <div v-if="technology.app_type" class="sidebar-tech-item">
            <h4><i class="fas fa-cube"></i> Jenis Aplikasi</h4>
            <p>{{ technology.app_type }}</p>
          </div>
          
          <div v-if="technology.stratification" class="sidebar-tech-item">
            <h4><i class="fas fa-layer-group"></i> Stratifikasi</h4>
            <p>{{ technology.stratification }}</p>
          </div>
        </div>
      </div>
    </aside>

    <main id="main-content">
      <div id="menu-toggle" v-show="isMobile && !visible" :class="{ active: visible }" @click.stop="toggleSidebar">
        <i class="fas fa-bars"></i>
      </div>
      <div id="technology-container">
        <div class="tech-header">
          <h1>{{ appName }}</h1>
          <p v-if="streamName" class="stream-info">{{ appDescription }}</p>
        </div>

        <div v-if="!technology" class="no-data">
          <i class="fas fa-info-circle"></i>
          <p>Tidak ada data teknologi untuk aplikasi ini.</p>
        </div>

        <div v-else class="tech-main-layout">
          <!-- Left Side - Tech Stack Labels -->
          <div class="tech-stack-labels">
            <div v-if="technology.platform" class="stack-label platform-label">
              <h3><i class="fas fa-cloud"></i> PLATFORM</h3>
            </div>
            <div v-if="technology.framework" class="stack-label framework-label">
              <h3><i class="fas fa-tools"></i> FRAMEWORK</h3>
            </div>
            <div v-if="technology.middleware" class="stack-label middleware-label">
              <h3><i class="fas fa-exchange-alt"></i> MIDDLEWARE</h3>
            </div>
            <div v-if="technology.third_party" class="stack-label third-party-label">
              <h3><i class="fas fa-plug"></i> THIRD PARTY</h3>
            </div>
            <div v-if="technology.language" class="stack-label language-label">
              <h3><i class="fas fa-code"></i> LANGUAGE</h3>
            </div>
            <div v-if="technology.database" class="stack-label database-label">
              <h3><i class="fas fa-database"></i> DATABASE</h3>
            </div>
            <div v-if="technology.os" class="stack-label os-label">
              <h3><i class="fas fa-desktop"></i> OPERATING SYSTEM</h3>
            </div>
            <div v-if="technology.vendor" class="stack-label vendor-label">
              <h3><i class="fas fa-building"></i> VENDOR</h3>
            </div>
          </div>

          <!-- Right Side - Tech Content -->
          <div class="tech-content-area">
            <div v-if="technology.platform" class="content-section platform-content">
              <div class="content-items">
                <span v-for="item in technology.platform" :key="item" class="content-item">{{ item }}</span>
              </div>
            </div>
            <div v-if="technology.framework" class="content-section framework-content">
              <div class="content-items">
                <span v-for="item in technology.framework" :key="item" class="content-item">{{ item }}</span>
              </div>
            </div>
            <div v-if="technology.middleware" class="content-section middleware-content">
              <div class="content-items">
                <span v-for="item in technology.middleware" :key="item" class="content-item">{{ item }}</span>
              </div>
            </div>
            <div v-if="technology.third_party" class="content-section third-party-content">
              <div class="content-items">
                <span v-for="item in technology.third_party" :key="item" class="content-item">{{ item }}</span>
              </div>
            </div>
            <div v-if="technology.language" class="content-section language-content">
              <div class="content-items">
                <span v-for="item in technology.language" :key="item" class="content-item">{{ item }}</span>
              </div>
            </div>
            <div v-if="technology.database" class="content-section database-content">
              <div class="content-items">
                <span v-for="item in technology.database" :key="item" class="content-item">{{ item }}</span>
              </div>
            </div>
            <div v-if="technology.os" class="content-section os-content">
              <div class="content-items">
                <span v-for="item in technology.os" :key="item" class="content-item">{{ item }}</span>
              </div>
            </div>
            <div v-if="technology.vendor" class="content-section vendor-content">
              <div class="content-items">
                <span v-for="item in technology.vendor" :key="item" class="content-item">{{ item }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { useSidebar } from '../composables/useSidebar';

const props = defineProps<{
  app: any;
  technology: any;
  appName: string;
  appDescription: string;
  streamName: string;
}>()

const { visible, isMobile, toggleSidebar, closeSidebar } = useSidebar();
</script>

<style scoped>
@import '../../css/app.css';
</style>

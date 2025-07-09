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

        <div class="notes">
          <h3>Notes</h3>
          <p>Halaman ini menampilkan teknologi yang digunakan oleh aplikasi {{ appName }}.</p>
        </div>
      </div>
    </aside>

    <main id="main-content">
      <div id="menu-toggle" @click.stop="toggleSidebar">
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

        <div v-else class="tech-cards-container">
          <!-- Vendor Card -->
          <div v-if="technology.vendor" class="tech-card">
            <h3><i class="fas fa-building"></i> VENDOR</h3>
            <div class="tech-items">
              <span v-for="item in technology.vendor" :key="item" class="tech-item">{{ item }}</span>
            </div>
          </div>

          <!-- App Type Card -->
          <div v-if="technology.app_type" class="tech-card">
            <h3><i class="fas fa-cube"></i> APP TYPE</h3>
            <div class="tech-items">
              <span class="tech-item">{{ technology.app_type }}</span>
            </div>
          </div>

          <!-- Stratification Card -->
          <div v-if="technology.stratification" class="tech-card">
            <h3><i class="fas fa-layer-group"></i> STRATIFICATION</h3>
            <div class="tech-items">
              <span class="tech-item">{{ technology.stratification }}</span>
            </div>
          </div>

          <!-- OS Card -->
          <div v-if="technology.os" class="tech-card">
            <h3><i class="fas fa-desktop"></i> OPERATING SYSTEM</h3>
            <div class="tech-items">
              <span v-for="item in technology.os" :key="item" class="tech-item">{{ item }}</span>
            </div>
          </div>

          <!-- Database Card -->
          <div v-if="technology.database" class="tech-card">
            <h3><i class="fas fa-database"></i> DATABASE</h3>
            <div class="tech-items">
              <span v-for="item in technology.database" :key="item" class="tech-item">{{ item }}</span>
            </div>
          </div>

          <!-- Language Card -->
          <div v-if="technology.language" class="tech-card">
            <h3><i class="fas fa-code"></i> PROGRAMMING LANGUAGE</h3>
            <div class="tech-items">
              <span v-for="item in technology.language" :key="item" class="tech-item">{{ item }}</span>
            </div>
          </div>

          <!-- DRC Card -->
          <div v-if="technology.drc" class="tech-card">
            <h3><i class="fas fa-shield-alt"></i> DRC</h3>
            <div class="tech-items">
              <span v-for="item in technology.drc" :key="item" class="tech-item">{{ item }}</span>
            </div>
          </div>

          <!-- Failover Card -->
          <div v-if="technology.failover" class="tech-card">
            <h3><i class="fas fa-sync-alt"></i> FAILOVER</h3>
            <div class="tech-items">
              <span v-for="item in technology.failover" :key="item" class="tech-item">{{ item }}</span>
            </div>
          </div>

          <!-- Third Party Card -->
          <div v-if="technology.third_party" class="tech-card">
            <h3><i class="fas fa-plug"></i> THIRD PARTY</h3>
            <div class="tech-items">
              <span v-for="item in technology.third_party" :key="item" class="tech-item">{{ item }}</span>
            </div>
          </div>

          <!-- Middleware Card -->
          <div v-if="technology.middleware" class="tech-card">
            <h3><i class="fas fa-exchange-alt"></i> MIDDLEWARE</h3>
            <div class="tech-items">
              <span v-for="item in technology.middleware" :key="item" class="tech-item">{{ item }}</span>
            </div>
          </div>

          <!-- Framework Card -->
          <div v-if="technology.framework" class="tech-card">
            <h3><i class="fas fa-tools"></i> FRAMEWORK</h3>
            <div class="tech-items">
              <span v-for="item in technology.framework" :key="item" class="tech-item">{{ item }}</span>
            </div>
          </div>

          <!-- Platform Card -->
          <div v-if="technology.platform" class="tech-card">
            <h3><i class="fas fa-cloud"></i> PLATFORM</h3>
            <div class="tech-items">
              <span v-for="item in technology.platform" :key="item" class="tech-item">{{ item }}</span>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { ref, onMounted, onUnmounted } from 'vue'

const props = defineProps<{
  app: any;
  technology: any;
  appName: string;
  appDescription: string;
  streamName: string;
}>()

function toggleSidebar() {
  const sidebar = document.getElementById('sidebar')
  sidebar?.classList.toggle('visible')
}

// Close sidebar when clicking outside on mobile
function handleClickOutside(event: Event) {
  const sidebar = document.getElementById('sidebar')
  const menuToggle = document.getElementById('menu-toggle')
  
  if (sidebar && menuToggle && !sidebar.contains(event.target as Node) && !menuToggle.contains(event.target as Node)) {
    sidebar.classList.remove('visible')
  }
}

// Close sidebar on escape key
function handleEscapeKey(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    const sidebar = document.getElementById('sidebar')
    sidebar?.classList.remove('visible')
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  document.addEventListener('keydown', handleEscapeKey)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  document.removeEventListener('keydown', handleEscapeKey)
})
</script>

<style scoped>
@import '../../css/app.css';
</style>

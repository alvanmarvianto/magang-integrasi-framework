<template>
  <div id="container">
    <aside id="sidebar">
      <header>
        <h1 style="font-size: 1.75em;">Spesifikasi Teknologi</h1>
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

        <div v-if="!hasAnyTechnologyData" class="no-data-center">
          <i class="fas fa-info-circle fa-2x"></i>
          <p>Tidak ada data tersedia</p>
        </div>

        <div v-else class="tech-main-layout">
          <!-- Left Side - Tech Stack Labels -->
          <div class="tech-stack-labels">
            <div v-if="technology.app_type" class="stack-label app-type-label">
              <h3><i class="fas fa-cube"></i> JENIS APLIKASI</h3>
            </div>
            <div v-if="technology.stratification" class="stack-label stratification-label">
              <h3><i class="fas fa-layer-group"></i> STRATIFIKASI</h3>
            </div>
            <div v-if="technology.vendor?.length" class="stack-label vendor-label">
              <h3><i class="fas fa-building"></i> VENDOR</h3>
            </div>
            <div v-if="technology.os?.length" class="stack-label os-label">
              <h3><i class="fas fa-desktop"></i> OPERATING SYSTEM</h3>
            </div>
            <div v-if="technology.database?.length" class="stack-label database-label">
              <h3><i class="fas fa-database"></i> DATABASE</h3>
            </div>
            <div v-if="technology.language?.length" class="stack-label language-label">
              <h3><i class="fas fa-code"></i> LANGUAGE</h3>
            </div>
            <div v-if="technology.framework?.length" class="stack-label framework-label">
              <h3><i class="fas fa-tools"></i> FRAMEWORK</h3>
            </div>
            <div v-if="technology.middleware?.length" class="stack-label middleware-label">
              <h3><i class="fas fa-exchange-alt"></i> MIDDLEWARE</h3>
            </div>
            <div v-if="technology.third_party?.length" class="stack-label third-party-label">
              <h3><i class="fas fa-plug"></i> THIRD PARTY</h3>
            </div>
            <div v-if="technology.platform?.length" class="stack-label platform-label">
              <h3><i class="fas fa-cloud"></i> PLATFORM</h3>
            </div>
          </div>

          <!-- Right Side - Tech Content -->
          <div class="tech-content-area">
            <div v-if="technology.app_type" class="content-section app-type-content">
              <div class="content-items">
                <div class="content-item">{{ technology.app_type }}</div>
              </div>
            </div>
            <div v-if="technology.stratification" class="content-section stratification-content">
              <div class="content-items">
                <div class="content-item">{{ technology.stratification }}</div>
              </div>
            </div>
            <VendorSection :technology="technology" />
            <OSSection :technology="technology" />
            <DatabaseSection :technology="technology" />
            <LanguageSection :technology="technology" />
            <FrameworkSection :technology="technology" />
            <MiddlewareSection :technology="technology" />
            <ThirdPartySection :technology="technology" />
            <PlatformSection :technology="technology" />
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useSidebar } from '../composables/useSidebar';
import PlatformSection from '../components/TechnologyApp/PlatformSection.vue';
import FrameworkSection from '../components/TechnologyApp/FrameworkSection.vue';
import MiddlewareSection from '../components/TechnologyApp/MiddlewareSection.vue';
import ThirdPartySection from '../components/TechnologyApp/ThirdPartySection.vue';
import LanguageSection from '../components/TechnologyApp/LanguageSection.vue';
import DatabaseSection from '../components/TechnologyApp/DatabaseSection.vue';
import OSSection from '../components/TechnologyApp/OSSection.vue';
import VendorSection from '../components/TechnologyApp/VendorSection.vue';

const props = defineProps<{
  app: any;
  technology: any;
  appName: string;
  appDescription: string;
  streamName: string;
}>()

const { visible, isMobile, toggleSidebar, closeSidebar } = useSidebar();

const hasAnyTechnologyData = computed(() => {
  if (!props.technology) return false;
  
  return Boolean(
    props.technology.platform?.length ||
    props.technology.framework?.length ||
    props.technology.middleware?.length ||
    props.technology.third_party?.length ||
    props.technology.language?.length ||
    props.technology.database?.length ||
    props.technology.os?.length ||
    props.technology.vendor?.length ||
    props.technology.app_type ||
    props.technology.stratification
  );
});
</script>

<style scoped>
@import '../../css/app.css';

.content-item {
  text-decoration: none !important;
}

.no-data-center {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: calc(100vh - 200px); /* Adjust based on your header height */
  color: var(--text-muted);
  text-align: center;
  gap: 1rem;
}

.no-data-center i {
  margin-bottom: 0.5rem;
}

.no-data-center p {
  font-size: 1.2rem;
  margin: 0;
}
</style>

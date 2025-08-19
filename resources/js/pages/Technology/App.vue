<template>
  <div id="container">
    <Sidebar 
      title="Spesifikasi Teknologi" 
      :title-style="{ fontSize: '1.75em' }"
      icon="fa-solid fa-microchip"
    >
      <SidebarNavigation :links="navigationLinks" />
    </Sidebar>

    <main id="main-content">
      <div id="menu-toggle" v-show="!visible" :class="{ active: visible }" @click.stop="toggleSidebar">
        <FontAwesomeIcon icon="fa-solid fa-bars" />
      </div>
      <div id="technology-container">
        <ErrorState 
          v-if="error || !app"
          title="Aplikasi tidak ditemukan"
          :app="app"
        />
        
        <template v-else>
          <div class="tech-header">
            <h1>{{ appName }}</h1>
            <p v-if="streamName" class="stream-info">{{ appDescription }}</p>
          </div>

          <ErrorState 
            v-if="!hasAnyTechnologyData"
            :show-back-button="false"
          />

          <div v-else class="tech-main-layout">
          <!-- Left Side - Tech Stack Labels -->
          <div class="tech-stack-labels">
            <div v-if="technology.vendor?.length" class="stack-label vendor-label">
              <h3><FontAwesomeIcon icon="fa-solid fa-building" /> VENDOR</h3>
            </div>
            <div v-if="technology.app_type" class="stack-label app-type-label">
              <h3><FontAwesomeIcon icon="fa-solid fa-cube" /> JENIS APLIKASI</h3>
            </div>
            <div v-if="technology.stratification" class="stack-label stratification-label">
              <h3><FontAwesomeIcon icon="fa-solid fa-layer-group" /> STRATIFIKASI</h3>
            </div>
            <div v-if="technology.os?.length" class="stack-label os-label">
              <h3><FontAwesomeIcon icon="fa-solid fa-desktop" /> OPERATING SYSTEM</h3>
            </div>
            <div v-if="technology.database?.length" class="stack-label database-label">
              <h3><FontAwesomeIcon icon="fa-solid fa-database" /> DATABASE</h3>
            </div>
            <div v-if="technology.language?.length" class="stack-label language-label">
              <h3><FontAwesomeIcon icon="fa-solid fa-code" /> LANGUAGE</h3>
            </div>
            <div v-if="technology.framework?.length" class="stack-label framework-label">
              <h3><FontAwesomeIcon icon="fa-solid fa-tools" /> FRAMEWORK</h3>
            </div>
            <div v-if="technology.middleware?.length" class="stack-label middleware-label">
              <h3><FontAwesomeIcon icon="fa-solid fa-exchange-alt" /> MIDDLEWARE</h3>
            </div>
            <div v-if="technology.third_party?.length" class="stack-label third-party-label">
              <h3><FontAwesomeIcon icon="fa-solid fa-plug" /> THIRD PARTY</h3>
            </div>
            <div v-if="technology.platform?.length" class="stack-label platform-label">
              <h3><FontAwesomeIcon icon="fa-solid fa-cloud" /> PLATFORM</h3>
            </div>
          </div>

          <!-- Right Side - Tech Content -->
          <div class="tech-content-area">
            <VendorSection :technology="technology" />
            <AppTypeSection :technology="technology" />
            <StratificationSection :technology="technology" />
            <OSSection :technology="technology" />
            <DatabaseSection :technology="technology" />
            <LanguageSection :technology="technology" />
            <FrameworkSection :technology="technology" />
            <MiddlewareSection :technology="technology" />
            <ThirdPartySection :technology="technology" />
            <PlatformSection :technology="technology" />
          </div>
        </div>
        </template>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useSidebar } from '../../composables/useSidebar';
import { useNavigation } from '../../composables/useNavigation';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import Sidebar from '../../components/Sidebar/Sidebar.vue';
import SidebarNavigation from '../../components/Sidebar/SidebarNavigation.vue';
import PlatformSection from '../../components/TechnologyApp/PlatformSection.vue';
import FrameworkSection from '../../components/TechnologyApp/FrameworkSection.vue';
import AppTypeSection from '../../components/TechnologyApp/AppTypeSection.vue';
import StratificationSection from '../../components/TechnologyApp/StratificationSection.vue';
import MiddlewareSection from '../../components/TechnologyApp/MiddlewareSection.vue';
import ThirdPartySection from '../../components/TechnologyApp/ThirdPartySection.vue';
import LanguageSection from '../../components/TechnologyApp/LanguageSection.vue';
import DatabaseSection from '../../components/TechnologyApp/DatabaseSection.vue';
import OSSection from '../../components/TechnologyApp/OSSection.vue';
import VendorSection from '../../components/TechnologyApp/VendorSection.vue';
import ErrorState from '../../components/ErrorState.vue';

const props = defineProps<{
  app: any;
  technology: any;
  appName: string;
  appDescription: string;
  streamName: string;
  error?: string;
}>()

const { visible, isMobile, toggleSidebar, closeSidebar } = useSidebar();
const { createTechnologyNavigation } = useNavigation();

const navigationLinks = createTechnologyNavigation(props.app.app_id);

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
@import '../../../css/app.css';

.stream-info {
  margin: 0;
  color: var(--primary-color);
  font-size: 1.1rem;
  font-weight: 500;
  text-align: center;
}

.tech-stack-labels {
  flex: none;
}

.stack-label {
  border-radius: 0.5rem !important;
  margin-bottom: 0.5rem;
}

.content-section {
  border-radius: 0.5rem !important;
  border-left: 1px solid rgba(255, 255, 255, 0.3);
  margin-bottom: 0.5rem;
}

.content-items {
  grid-template-columns: 1fr !important;
}
.app-type-label {
  background: linear-gradient(135deg, rgba(255, 248, 245, 0.1), rgba(255, 245, 240, 0.1));
  backdrop-filter: blur(15px) saturate(180%);
  -webkit-backdrop-filter: blur(15px) saturate(180%);
}

.database-label {
  background: linear-gradient(135deg, rgba(255, 215, 210, 0.1), rgba(255, 200, 190, 0.1));
  backdrop-filter: blur(15px) saturate(180%);
  -webkit-backdrop-filter: blur(15px) saturate(180%);
}

.language-label {
  background: linear-gradient(135deg, rgba(255, 200, 190, 0.1), rgba(255, 180, 170, 0.1));
  backdrop-filter: blur(15px) saturate(180%);
  -webkit-backdrop-filter: blur(15px) saturate(180%);
}

.os-label {
  background: linear-gradient(135deg, rgba(255, 225, 220, 0.1), rgba(255, 215, 210, 0.1));
  backdrop-filter: blur(15px) saturate(180%);
  -webkit-backdrop-filter: blur(15px) saturate(180%);
}

.stratification-label {
  background: linear-gradient(135deg, rgba(255, 235, 230, 0.1), rgba(255, 225, 220, 0.1));
  backdrop-filter: blur(15px) saturate(180%);
  -webkit-backdrop-filter: blur(15px) saturate(180%);
}

.vendor-label {
  background: linear-gradient(135deg, rgba(255, 245, 240, 0.1), rgba(255, 240, 235, 0.1));
  backdrop-filter: blur(15px) saturate(180%);
  -webkit-backdrop-filter: blur(15px) saturate(180%);
}

.framework-label {
  background: linear-gradient(135deg, rgba(255, 180, 170, 0.1), rgba(255, 160, 150, 0.1));
  backdrop-filter: blur(15px) saturate(180%);
  -webkit-backdrop-filter: blur(15px) saturate(180%);
}

.middleware-label {
  background: linear-gradient(135deg, rgba(255, 160, 150, 0.1), rgba(255, 140, 130, 0.1));
  backdrop-filter: blur(15px) saturate(180%);
  -webkit-backdrop-filter: blur(15px) saturate(180%);
}

.third-party-label {
  background: linear-gradient(135deg, rgba(255, 140, 130, 0.1), rgba(255, 120, 110, 0.1));
  backdrop-filter: blur(15px) saturate(180%);
  -webkit-backdrop-filter: blur(15px) saturate(180%);
}

.platform-label {
  background: linear-gradient(135deg, rgba(255, 120, 110, 0.1), rgba(255, 100, 90, 0.1));
  backdrop-filter: blur(15px) saturate(180%);
  -webkit-backdrop-filter: blur(15px) saturate(180%);
}

.content-item {
  text-decoration: none !important;
}
</style>

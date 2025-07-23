<template>
  <div id="container">
    <Sidebar 
      :title="technologyType" 
      :title-style="{ fontSize: '1.75em' }"
      :icon="icon"
    >
      <SidebarNavigation :links="navigationLinks" />
    </Sidebar>

    <main id="main-content">
      <div id="menu-toggle" v-show="isMobile && !visible" :class="{ active: visible }" @click.stop="toggleSidebar">
        <FontAwesomeIcon icon="fa-solid fa-bars" />
      </div>
      <div id="technology-container">
        <div class="tech-header">
          <h1>
            {{ pageTitle }}
          </h1>
        </div>

        <div v-if="apps.length === 0" class="no-data-center">
          <FontAwesomeIcon icon="fa-solid fa-info-circle" class="fa-2x" />
          <p>Tidak ada aplikasi yang menggunakan {{ technologyName }}</p>
        </div>

        <div v-else class="apps-list">
          <div 
            v-for="app in apps" 
            :key="app.id" 
            class="app-card"
            @click="navigateToApp(app.id)"
          >
            <div class="app-card-header">
              <h3 class="app-name">{{ app.name }}</h3>
              <span v-if="app.stream?.name" class="stream-badge">
                {{ app.stream.name.toUpperCase() }}
              </span>
            </div>
            
            <div class="app-card-body">
              <p v-if="app.description" class="app-description">
                {{ app.description }}
              </p>
              
              <div class="technology-detail">
                <span class="tech-label">{{ technologyType }}:</span>
                <span class="tech-value">{{ app.technology_detail }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { useSidebar } from '../../composables/useSidebar';
import { router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import Sidebar from '../../components/Sidebar/Sidebar.vue';
import SidebarNavigation from '../../components/Sidebar/SidebarNavigation.vue';

interface App {
  id: number;
  name: string;
  description: string | null;
  version: string | null;
  stream: {
    id: number | null;
    name: string | null;
  } | null;
  technology_detail: string;
}

interface Props {
  apps: App[];
  technologyType: string;
  technologyName: string;
  pageTitle: string;
  icon: string;
}

const props = defineProps<Props>();

const { visible, isMobile, toggleSidebar } = useSidebar();

const navigationLinks = [
  {
    icon: 'fa-solid fa-home',
    text: 'Halaman Utama',
    onClick: () => router.visit('/'),
  },
];

function navigateToApp(appId: number) {
  router.visit(`/technology/${appId}`);
}
</script>

<style scoped>
@import '../../../css/app.css';
@import '../../../css/technology.css';
</style>

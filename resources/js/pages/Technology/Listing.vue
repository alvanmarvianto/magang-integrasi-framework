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
import { useRoutes } from '../../composables/useRoutes';
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
const { visitRoute } = useRoutes();

const navigationLinks = [
  {
    icon: 'fa-solid fa-home',
    text: 'Halaman Utama',
    onClick: () => visitRoute('index'),
  },
];

function navigateToApp(appId: number) {
  visitRoute('technology.app', { app_id: appId });
}
</script>

<style scoped>
@import '../../../css/app.css';
@import '../../../css/technology.css';

.apps-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
  padding: 1rem;
}

.app-card {
  background: #f0f8ff;
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 1.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.app-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  border-color: var(--primary-color);
}

.app-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.app-name {
  margin: 0;
  color: var(--text-primary);
  font-size: 1.3rem;
  font-weight: 600;
  flex: 1;
}

.stream-badge {
  background: var(--primary-color);
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  white-space: nowrap;
  margin-left: 1rem;
}

.app-card-body {
  margin-bottom: 0;
}

.app-description {
  color: var(--text-secondary);
  margin: 0 0 1rem 0;
  line-height: 1.5;
  font-size: 0.95rem;
}

.technology-detail {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: white;
  border-radius: 8px;
  border-left: 3px solid var(--primary-color);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.tech-label {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9rem;
}

.tech-value {
  color: var(--text-secondary);
  font-family: 'Courier New', monospace;
  font-size: 0.9rem;
}
</style>

<template>
  <div id="container">
    <Sidebar 
      title="Aplikasi BI-DLDS" 
      icon="fa-solid fa-sitemap"
      :show-close-button="true"
      @close="closeSidebar"
    >
      <SidebarSearchControls
        :search-term="searchTerm"
        :unique-node-names="uniqueNodeNames"
        :on-search-input="onSearchInput"
        :clear-search="clearSearch"
      />
      
      <SidebarNavigation
        title="Integrasi dalam Stream"
        :links="streamLinks"
        variant="group"
      />

      <SidebarNavigation
        title="Kontrak"
        :links="contractLinks"
        variant="group"
      />

      <SidebarNavigation
        title="Teknologi"
        :links="technologyLinks"
        variant="group"
      />

      <SidebarNavigation
        title="Admin"
        :links="adminLinks"
        variant="group"
      />
    </Sidebar>

    <main id="main-content">
      <div id="menu-toggle" v-show="!visible" :class="{ active: visible }" @click.stop="toggleSidebar">
        <FontAwesomeIcon icon="fa-solid fa-bars" />
      </div>
      <div id="loader" v-if="loading"></div>
      <div id="body"></div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useSidebar } from '../composables/useSidebar';
import { useD3Tree } from '../composables/useD3Tree';
import { useRoutes } from '../composables/useRoutes';
import Sidebar from '../components/Sidebar/Sidebar.vue';
import SidebarSearchControls from '../components/Sidebar/SidebarSearchControls.vue';
import SidebarNavigation from '../components/Sidebar/SidebarNavigation.vue';

const props = defineProps<{ appData: any }>();

const { visible, isMobile, toggleSidebar, closeSidebar } = useSidebar();
const { loading, searchTerm, uniqueNodeNames, onSearchInput, clearSearch } = useD3Tree(props.appData);
const { visitRoute, getRoute } = useRoutes();

const streamLinks = [
  { text: 'Stream SP', onClick: () => visitRoute('integrations.stream', { stream: 'sp' }) },
  { text: 'Stream MI', onClick: () => visitRoute('integrations.stream', { stream: 'mi' }) },
  { text: 'Stream SSK', onClick: () => visitRoute('integrations.stream', { stream: 'ssk' }) },
  { text: 'Stream Moneter', onClick: () => visitRoute('integrations.stream', { stream: 'moneter' }) },
  { text: 'Stream Market', onClick: () => visitRoute('integrations.stream', { stream: 'market' }) },
  { text: 'Stream Middleware', onClick: () => visitRoute('integrations.stream', { stream: 'middleware' }) },
];

const contractLinks = [
  { text: 'Semua Kontrak', onClick: () => visitRoute('contract.index'), variant: 'default' as const },
];

const technologyLinks = [
  { text: 'Spesifikasi Teknologi', href: getRoute('technology.index'), variant: 'default' as const },
];

const adminLinks = [
  { text: 'Halaman Admin', href: getRoute('admin.index'), variant: 'admin' as const },
];
</script>

<style scoped>
/* Loader */
#loader {
  position: absolute;
  left: 50%;
  top: 50%;
  z-index: 1;
  width: 60px;
  height: 60px;
  margin: -30px 0 0 -30px;
  border: 8px solid rgba(0, 0, 0, 0.1);
  border-radius: 50%;
  border-top-color: var(--primary-color);
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>

<style scoped src="../../css/app.css"></style>
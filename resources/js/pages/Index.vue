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
        title="Teknologi"
        :links="technologyLinks"
        variant="group"
      />

      <SidebarNavigation
        title="Back Office"
        :links="adminLinks"
        variant="group"
      />
    </Sidebar>

    <main id="main-content">
      <div id="menu-toggle" v-show="isMobile && !visible" :class="{ active: visible }" @click.stop="toggleSidebar">
        <FontAwesomeIcon icon="fa-solid fa-bars" />
      </div>
      <div id="loader" v-if="loading"></div>
      <div id="body"></div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useSidebar } from '../composables/useSidebar';
import { useD3Tree } from '../composables/useD3Tree';
import Sidebar from '../components/Sidebar/Sidebar.vue';
import SidebarSearchControls from '../components/Sidebar/SidebarSearchControls.vue';
import SidebarNavigation from '../components/Sidebar/SidebarNavigation.vue';

const props = defineProps<{ appData: any }>();

const { visible, isMobile, toggleSidebar, closeSidebar } = useSidebar();
const { loading, searchTerm, uniqueNodeNames, onSearchInput, clearSearch } = useD3Tree(props.appData);

const streamLinks = [
  { text: 'SP Stream', onClick: () => router.visit('/diagram/stream/sp') },
  { text: 'MI Stream', onClick: () => router.visit('/diagram/stream/mi') },
  { text: 'SSK Stream', onClick: () => router.visit('/diagram/stream/ssk') },
  { text: 'Moneter Stream', onClick: () => router.visit('/diagram/stream/moneter') },
  { text: 'Market Stream', onClick: () => router.visit('/diagram/stream/market') },
];

const technologyLinks = [
  { text: 'Daftar Teknologi', href: '/technology', variant: 'default' as const },
];

const adminLinks = [
  { text: 'Halaman Back Office', href: '/admin', variant: 'admin' as const },
];
</script>


<style scoped src="../../css/app.css"></style>
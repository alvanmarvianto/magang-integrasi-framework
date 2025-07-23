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
      
      <SidebarStreamLinks
        title="Intergrasi dalam Stream"
        description="Navigasi ke halaman integrasi stream tertentu:"
        :links="streamLinks"
      />

      <SidebarStreamLinks
        title="Back Office"
        :links="adminLinks"
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
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useSidebar } from '../composables/useSidebar';
import { useD3Tree } from '../composables/useD3Tree';
import Sidebar from '../components/Sidebar/Sidebar.vue';
import SidebarSearchControls from '../components/Sidebar/SidebarSearchControls.vue';
import SidebarStreamLinks from '../components/Sidebar/SidebarStreamLinks.vue';

const props = defineProps<{ appData: any }>();

const { visible, isMobile, toggleSidebar, closeSidebar } = useSidebar();
const { loading, searchTerm, uniqueNodeNames, onSearchInput, clearSearch } = useD3Tree(props.appData);

const streamLinks = [
  { icon: 'fa-solid fa-bezier-curve', text: 'SP Stream', href: '/diagram/stream/sp' },
  { icon: 'fa-solid fa-bezier-curve', text: 'MI Stream', href: '/diagram/stream/mi' },
  { icon: 'fa-solid fa-bezier-curve', text: 'SSK Stream', href: '/diagram/stream/ssk' },
  { icon: 'fa-solid fa-bezier-curve', text: 'Moneter Stream', href: '/diagram/stream/moneter' },
  { icon: 'fa-solid fa-bezier-curve', text: 'Market Stream', href: '/diagram/stream/market' },
];

const adminLinks = [
  { icon: 'fa-solid fa-edit', text: 'Halaman Back Office', href: '/admin', variant: 'admin' as const },
];
</script>


<style scoped src="../../css/app.css"></style>
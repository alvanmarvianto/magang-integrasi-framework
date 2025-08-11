<template>
  <div id="container">
    <Sidebar 
      :title="`Integrasi Aplikasi`" 
      icon="fa-solid fa-project-diagram"
      :show-close-button="true"
      @close="closeSidebar"
    >
      <SidebarNavigation :links="navigationLinks" />
      
      <SidebarLegend
        title="Tipe Node"
        :items="nodeTypeLegend"
      />

      <SidebarLegend
        title="Tipe Koneksi"
        :items="connectionTypeLegend"
      />
    </Sidebar>

    <main id="main-content">
      <div id="menu-toggle" v-show="!visible" :class="{ active: visible }" @click.stop="toggleSidebar">
        <FontAwesomeIcon icon="fa-solid fa-bars" />
      </div>
      
      <ErrorState 
        v-if="error"
        :title="error.includes('Application not found') ? 'Aplikasi tidak ditemukan' : 'Terjadi kesalahan'"
        :app="{ app_id: parentAppId, app_name: appName }"
      />
      
      <ErrorState 
        v-else-if="!integrationData || (Array.isArray(integrationData) && integrationData.length === 0)"
        :show-back-button="false"
      />
      
      <template v-else>
        <div id="body"></div>
        <p id="error-message" style="display: none"></p>
      </template>
    </main>
  </div>
</template>

<script setup lang="ts">
// @ts-nocheck
import { useSidebar } from '@/composables/useSidebar';
import { useD3ForceAppIntegration } from '@/composables/useD3ForceAppIntegration';
import { useRoutes } from '@/composables/useRoutes';
import { computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import Sidebar from '@/components/Sidebar/Sidebar.vue';
import SidebarNavigation from '@/components/Sidebar/SidebarNavigation.vue';
import SidebarLegend from '@/components/Sidebar/SidebarLegend.vue';
import ErrorState from '@/components/ErrorState.vue';

const props = defineProps<{
  integrationData: any;
  appName: string;
  streamName: string;
  parentAppId: number;
  error?: string;
}>();

const { visible, isMobile, toggleSidebar, closeSidebar } = useSidebar();
const { visitRoute } = useRoutes();

// Only initialize D3 if there's actual integration data and no error
if (!props.error && props.integrationData && (!Array.isArray(props.integrationData) || props.integrationData.length > 0)) {
  useD3ForceAppIntegration(props.integrationData);
}

const navigationLinks = [
  {
    icon: 'fa-solid fa-home',
    text: 'Halaman Utama',
    onClick: () => visitRoute('index'),
  },
  {
    icon: 'fa-solid fa-microchip',
    text: 'Halaman Teknologi',
    onClick: () => visitRoute('technology.app', { app_id: props.parentAppId }),
  },
  {
    icon: 'fa-solid fa-file-contract',
    text: 'Halaman Kontrak',
    onClick: () => visitRoute('contract.app', { app_id: props.parentAppId }),
  },
];

const nodeTypeLegend = [
  { label: 'Aplikasi SP', type: 'circle' as const, class: 'sp' },
  { label: 'Aplikasi MI', type: 'circle' as const, class: 'mi' },
  { label: 'Aplikasi SSK & Moneter', type: 'circle' as const, class: 'ssk-mon' },
  { label: 'Aplikasi Market', type: 'circle' as const, class: 'market' },
  { label: 'Aplikasi Internal BI di luar DLDS', type: 'circle' as const, class: 'internal' },
  { label: 'Aplikasi Eksternal BI', type: 'circle' as const, class: 'external' },
  { label: 'Middleware', type: 'circle' as const, class: 'middleware' },
];

const connectionTypeLegend = computed(() => {
  if (!props.integrationData || !props.integrationData.children) {
    return [
      { label: 'DIRECT', type: 'line' as const, class: 'direct' },
      { label: 'SOA', type: 'line' as const, class: 'soa' },
      { label: 'SFTP', type: 'line' as const, class: 'sftp' },
    ];
  }
  
  // Extract unique connection types from the integration data
  const uniqueConnectionTypes = new Map();
  
  const processNode = (node: any) => {
    if (node.children) {
      node.children.forEach((child: any) => {
        if (child.link && child.link_color) {
          uniqueConnectionTypes.set(child.link, {
            label: child.link.toUpperCase(),
            type: 'line' as const,
            class: child.link.toLowerCase(),
            color: child.link_color
          });
        }
        processNode(child);
      });
    }
  };
  
  processNode(props.integrationData);
  
  return Array.from(uniqueConnectionTypes.values());
});
</script>

<style scoped>
@import '@/../css/app.css';
</style>
<template>
  <div id="container">
    <Sidebar 
      :title="`Diagram - ${streamName.toUpperCase()} Stream`" 
      icon="fa-solid fa-bezier-curve"
      :show-close-button="true"
      @close="closeSidebar"
    >
      <SidebarNavigation :links="navigationLinks" />

      <SidebarControlsSection
        title="Controls"
        :controls="controls"
      />

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
      <div id="menu-toggle" v-show="isMobile && !visible" :class="{ active: visible }"
        @click.stop="toggleSidebar">
        <i class="fas fa-bars"></i>
      </div>
      
      <!-- Vue Flow Container -->
      <div id="body" class="vue-flow-wrapper">
        <VueFlow
          ref="vueFlowRef"
          :nodes="nodes"
          :edges="edges"
          :fit-view-on-init="false"
          :nodes-draggable="true"
          :pan-on-scroll="true"
          :pan-on-scroll-mode="PanOnScrollMode.Free"
          :zoom-on-scroll="true"
          :zoom-on-pinch="true"
          :zoom-on-double-click="true"
          :max-zoom="1.5"
          :min-zoom="0.5"
          :default-viewport="{ zoom: 1, x: 0, y: 0 }"
          class="vue-flow-container"
          @node-click="onNodeClick"
          @edge-click="onEdgeClick"
          @pane-click="onPaneClick"
          @node-drag-stop="onNodeDragStop"
        >
          <!-- Custom Node Types -->
          <template #node-stream="nodeProps">
            <StreamNest v-bind="nodeProps" :admin-mode="false" />
          </template>
          <template #node-app="nodeProps">
            <AppNode v-bind="nodeProps" :admin-mode="false" />
          </template>

          <!-- Controls -->
          <Controls
            :show-zoom="true"
            :show-fit-view="true"
            :show-interactive="true"
            position="bottom-right"
          />
          <Background :pattern="BackgroundVariant.Dots" />
        </VueFlow>

        <!-- Loading Overlay -->
        <div
          v-if="!isLayouted"
          class="loading-overlay"
        >
          <div class="loading-content">
            <div class="loading-spinner"></div>
            <p>Loading Diagram...</p>
          </div>
        </div>
      </div>
    </main>

    <!-- Edge Details Sidebar -->
    <EdgeDetailsSidebar
      :visible="showEdgeDetails"
      :edgeData="selectedEdgeData"
      :isAdmin="false"
      @close="closeEdgeDetails"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { VueFlow, PanOnScrollMode } from '@vue-flow/core';
import { Controls } from '@vue-flow/controls';
import { Background, BackgroundVariant } from '@vue-flow/background';
import { router } from '@inertiajs/vue3';
import StreamNest from '@/components/VueFlow/StreamNest.vue';
import AppNode from '@/components/VueFlow/AppNode.vue';
import { useSidebar } from '../composables/useSidebar';
import { useVueFlowUserView } from '../composables/useVueFlowUserView';
import Sidebar from '../components/Sidebar/Sidebar.vue';
import SidebarNavigation from '../components/Sidebar/SidebarNavigation.vue';
import SidebarControlsSection from '../components/Sidebar/SidebarControlsSection.vue';
import SidebarLegend from '../components/Sidebar/SidebarLegend.vue';
import EdgeDetailsSidebar from '../components/Sidebar/EdgeDetailsSidebar.vue';
import { 
  validateAndCleanNodes,
  initializeNodesWithLayout,
  initializeEdgesWithLayout,
  handleNodeClick,
  handleNodeDragStop,
  fitView as sharedFitView
} from '../composables/useVueFlowCommon';
import type { Node, Edge } from '@vue-flow/core';

// Add necessary CSS imports
import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';
import '@vue-flow/controls/dist/style.css';

// Props from Inertia
interface Props {
  streamName: string;
  nodes: Node[];
  edges: Edge[];
  savedLayout: {
    nodes_layout?: Record<string, any>
    edges_layout?: any[]
    stream_config?: Record<string, any>
  } | null
  streams: string[];
}

const props = defineProps<Props>();

// Use composables
const { visible, isMobile, toggleSidebar, closeSidebar } = useSidebar();
const {
  selectedEdgeId,
  getNodeColor,
  getEdgeColor,
  removeDuplicateEdges,
  applyAutomaticLayoutWithConstraints,
  updateEdgeStyles,
  handleEdgeClick,
  handlePaneClick,
} = useVueFlowUserView();

const navigationLinks = [
  {
    icon: 'fa-solid fa-home',
    text: 'Halaman Utama',
    onClick: () => router.visit('/'),
  },
];

const controls = [
  {
    label: 'Center View',
    icon: 'fa-solid fa-crosshairs',
    onClick: centerView,
  },
  {
    label: 'Reset Layout',
    icon: 'fa-solid fa-refresh',
    onClick: resetLayout,
  },
];

const nodeTypeLegend = [
  { label: 'Aplikasi SP', type: 'circle' as const, class: 'sp' },
  { label: 'Aplikasi MI', type: 'circle' as const, class: 'mi' },
  { label: 'Aplikasi SSK & Moneter', type: 'circle' as const, class: 'ssk-mon' },
  { label: 'Aplikasi Market', type: 'circle' as const, class: 'market' },
  { label: 'Aplikasi Internal BI di luar DLDS', type: 'circle' as const, class: 'internal' },
  { label: 'Aplikasi External BI', type: 'circle' as const, class: 'external' },
  { label: 'Middleware', type: 'circle' as const, class: 'middleware' },
];

const connectionTypeLegend = [
  { label: 'Direct', type: 'line' as const, class: 'direct' },
  { label: 'SOA', type: 'line' as const, class: 'soa' },
  { label: 'SFTP', type: 'line' as const, class: 'sftp' },
];

// Refs
const vueFlowRef = ref()
const isLayouted = ref(false)

// Reactive data
const nodes = ref<Node[]>([])
const edges = ref<Edge[]>([])

// Edge details sidebar
const showEdgeDetails = ref(false)
const selectedEdgeData = ref<any>(null)
// Initialize layout
onMounted(() => {
  initializeLayout()
})

function initializeLayout() {
  // Clean and validate input nodes
  const cleanedNodes = validateAndCleanNodes(props.nodes);
  
  // Initialize nodes with shared function
  nodes.value = initializeNodesWithLayout(
    cleanedNodes,
    props.savedLayout,
    false // User mode
  );
  
  // Apply automatic layout if no saved layout
  const hasSavedLayout = props.savedLayout?.nodes_layout && Object.keys(props.savedLayout.nodes_layout).length > 0;
  if (!hasSavedLayout) {
    applyAutomaticLayoutWithConstraints(nodes.value);
  }
  
  // Initialize edges with shared function
  edges.value = initializeEdgesWithLayout(
    props.edges,
    props.savedLayout,
    selectedEdgeId.value,
    false // User mode
  );

  // Apply layout and fit view
  setTimeout(() => {
    fitView();
    isLayouted.value = true;
  }, 100);
}

function onNodeClick(event: any) {
  handleNodeClick(event.node, false); // User mode
}

function onEdgeClick(event: any) {
  const clickedEdgeId = event.edge?.id;
  if (!clickedEdgeId) {
    return;
  }
  
  // Find the edge data
  const edge = edges.value.find(e => e.id === clickedEdgeId);
  if (edge && edge.data) {
    selectedEdgeData.value = edge.data;
    showEdgeDetails.value = true;
  }
  
  handleEdgeClick(clickedEdgeId);
  edges.value = updateEdgeStyles(edges.value);
}

function onPaneClick(event: any) {
  handlePaneClick();
  edges.value = updateEdgeStyles(edges.value);
  // Close edge details when clicking on pane
  if (showEdgeDetails.value) {
    closeEdgeDetails();
  }
}

function closeEdgeDetails() {
  showEdgeDetails.value = false;
  selectedEdgeData.value = null;
}

function onNodeDragStop(event: any) {
  handleNodeDragStop(event, nodes);
}

function fitView() {
  sharedFitView(vueFlowRef);
}

function centerView() {
  fitView();
}

function resetLayout() {
  initializeLayout();
}
</script>

<style scoped>
@import '../../css/app.css';
@import '../../css/vue-flow-integration.css';
</style>

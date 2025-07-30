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
          :pan-on-scroll="false"
          :pan-on-scroll-mode="PanOnScrollMode.Free"
          :zoom-on-scroll="false"
          :zoom-on-pinch="true"
          :zoom-on-double-click="true"
          :max-zoom="1.5"
          :min-zoom="0.5"
          :default-viewport="{ zoom: 1, x: 0, y: 0 }"
          :pan-on-drag="[0, 2]"
          class="vue-flow-container"
          @node-click="onNodeClick"
          @edge-click="onEdgeClick"
          @pane-click="onPaneClick"
          @node-drag-stop="onNodeDragStop"
          @wheel="onWheel"
          @contextmenu="onContextMenu"
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
    <DetailsSidebar
      :visible="showEdgeDetails"
      :edgeData="selectedEdgeData"
      :isAdmin="false"
      @close="closeEdgeDetails"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { VueFlow, PanOnScrollMode, useVueFlow } from '@vue-flow/core';
import { Controls } from '@vue-flow/controls';
import { Background, BackgroundVariant } from '@vue-flow/background';
import { useRoutes } from '@/composables/useRoutes';
import StreamNest from '@/components/VueFlow/StreamNest.vue';
import AppNode from '@/components/VueFlow/AppNode.vue';
import { useSidebar } from '@/composables/useSidebar';
import { useVueFlowUserView } from '@/composables/useVueFlowUserView';
import Sidebar from '@/components/Sidebar/Sidebar.vue';
import SidebarNavigation from '@/components/Sidebar/SidebarNavigation.vue';
import SidebarControlsSection from '@/components/Sidebar/SidebarControlsSection.vue';
import SidebarLegend from '@/components/Sidebar/SidebarLegend.vue';
import DetailsSidebar from '@/components/Sidebar/DetailsSidebar.vue';
import { 
  validateAndCleanNodes,
  initializeNodesWithLayout,
  initializeEdgesWithLayout,
  handleNodeClick,
  handleNodeDragStop,
  fitView as sharedFitView,
  createCustomWheelHandler,
  createCustomContextMenuHandler
} from '../../composables/useVueFlowCommon';
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
  allowedStreams: string[];
}

const props = defineProps<Props>();

// Use composables
const { visible, isMobile, toggleSidebar, closeSidebar } = useSidebar();
const { visitRoute } = useRoutes();
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

// Get VueFlow instance and functions for custom controls
const { zoomIn, zoomOut, setViewport, getViewport } = useVueFlow();

// Create shared wheel handler
const onWheel = createCustomWheelHandler(zoomIn, zoomOut, setViewport, getViewport);

// Create context menu handler to disable popup on empty space
const onContextMenu = createCustomContextMenuHandler();

const navigationLinks = [
  {
    icon: 'fa-solid fa-home',
    text: 'Halaman Utama',
    onClick: () => visitRoute('index'),
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
    false, // User mode
    props.allowedStreams // Pass allowed streams for click restriction
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
  handleNodeClick(event.node, false, props.allowedStreams); // User mode with allowed streams restriction
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
@import '@/../css/app.css';
@import '@/../css/vue-flow-integration.css';
/* Loading overlay */
.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(255, 255, 255, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.loading-content {
  text-align: center;
  color: var(--text-color);
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 15px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>

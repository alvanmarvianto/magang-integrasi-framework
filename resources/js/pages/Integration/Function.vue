<template>
  <div id="container">
    <!-- Error State -->
    <ErrorState
      v-if="props.error"
      title="Gagal memuat diagram modul"
      :show-back-button="true"
      back-button-text="Kembali ke Halaman Utama"
      back-route="index"
    />

    <!-- No Data State -->
    <ErrorState
      v-else-if="!props.nodes || props.nodes.length === 0"
      title="Tidak ada data modul"
      :show-back-button="true"
      back-button-text="Kembali ke Halaman Utama"
      back-route="index"
    />

    <!-- Normal Content -->
    <template v-else>
      <Sidebar 
        :title="`${appName}`" 
        icon="fa-solid fa-sitemap"
        :show-close-button="true"
        @close="closeSidebar"
      >
        <SidebarNavigation :links="navigationLinks" />

        <SidebarControlsSection
          title="Controls"
          :controls="controls"
        />

        <SidebarLegend
          title="Tipe Aplikasi"
          :items="nodeTypeLegend"
        />
      </Sidebar>

      <main id="main-content">
        <div id="menu-toggle" v-show="!visible" :class="{ active: visible }"
          @click.stop="toggleSidebar">
          <FontAwesomeIcon icon="fa-solid fa-bars" />
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
            <template #node-app="nodeProps">
              <AppNode v-bind="nodeProps" :admin-mode="false" />
            </template>
            <template #node-function="nodeProps">
              <StreamNest v-bind="nodeProps" :admin-mode="false" />
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
              <p>Loading Function Diagram...</p>
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
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { VueFlow, PanOnScrollMode, useVueFlow } from '@vue-flow/core';
import { Controls } from '@vue-flow/controls';
import { Background, BackgroundVariant } from '@vue-flow/background';
import { useRoutes } from '@/composables/useRoutes';
import AppNode from '@/components/VueFlow/AppNode.vue';
import FunctionNode from '@/components/VueFlow/FunctionNode.vue';
import { useSidebar } from '@/composables/useSidebar';
import { useVueFlowUserView } from '@/composables/useVueFlowUserView';
import { useNavigation } from '@/composables/useNavigation';
import Sidebar from '@/components/Sidebar/Sidebar.vue';
import SidebarNavigation from '@/components/Sidebar/SidebarNavigation.vue';
import SidebarControlsSection from '@/components/Sidebar/SidebarControlsSection.vue';
import SidebarLegend from '@/components/Sidebar/SidebarLegend.vue';
import DetailsSidebar from '@/components/Sidebar/DetailsSidebar.vue';
import ErrorState from '@/components/ErrorState.vue';
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
  appId: number;
  appName: string;
  streamName: string;
  isModule?: boolean;
  nodes: Node[];
  edges: Edge[];
  savedLayout: {
    nodes_layout?: Record<string, any>
    edges_layout?: any[]
    app_config?: Record<string, any>
  } | null
  allowedStreams: string[];
  config?: {
    node_types?: Array<{
      label: string;
      type: string;
      class: string;
      stream_name: string;
      color?: string;
    }>;
    total_nodes?: number;
    total_functions?: number;
    external_apps?: number;
  } | null;
  error?: string | null;
}

const props = defineProps<Props>();

// Use composables
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import StreamNest from '@/components/VueFlow/StreamNest.vue';
const { visible, isMobile, toggleSidebar, closeSidebar } = useSidebar();
const { createFunctionNavigation } = useNavigation();
const {
  selectedEdgeId,
  getNodeColor,
  getEdgeColor,
  removeDuplicateEdges,
  applyAutomaticLayoutWithConstraints,
  updateEdgeStyles,
  updateEdgeStylesWithSelection,
  handleEdgeClick,
  handlePaneClick,
  handleAppNodeClick,
  clearNodeHighlight,
  applyEdgeHighlightById,
} = useVueFlowUserView();

// Get VueFlow instance and functions for custom controls
const { zoomIn, zoomOut, setViewport, getViewport } = useVueFlow();

// Create shared wheel handler
const onWheel = createCustomWheelHandler(zoomIn, zoomOut, setViewport, getViewport);

// Create context menu handler to disable popup on empty space
const onContextMenu = createCustomContextMenuHandler();

const navigationLinks = createFunctionNavigation(props.appId, props.streamName, props.isModule ?? true);

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

const nodeTypeLegend = computed(() => {
  // Use backend-provided node types if available
  if (props.config?.node_types && props.config.node_types.length > 0) {
    return props.config.node_types.map(nodeType => ({
      label: (nodeType as any).stream_description || nodeType.label,
      type: 'circle' as const,
      class: nodeType.class,
      color: nodeType.color // Include color from backend
    }));
  }

  // Fallback: Extract from actual nodes if backend data not available
  if (!props.nodes || props.nodes.length === 0) {
    return [];
  }

  // Extract unique stream types from actual nodes
  const uniqueStreamTypes = new Map();
  
  props.nodes.forEach((node: any) => {
    if (node.data && node.data.stream_name && !node.data.is_parent_node) {
      const streamName: string = node.data.stream_name;
      const streamKey = streamName.toLowerCase();
      
      if (!uniqueStreamTypes.has(streamKey)) {
        uniqueStreamTypes.set(streamKey, {
          label: streamName,
          type: 'circle' as const,
          class: streamKey.replace(/\s+/g, '-'), // Convert spaces to hyphens for CSS class
        });
      }
    }
  });

  return Array.from(uniqueStreamTypes.values());
});

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
    props.allowedStreams, // Pass allowed streams for click restriction
    [] // TODO: Pass stream objects with colors from backend
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
    // Reset any highlight state after initial layout
    clearNodeHighlight(nodes, edges);
  }, 100);
}

function onNodeClick(event: any) {
  const node = event?.node;
  if (!node) return;

  // If an edge is selected (animated) or details sidebar is open, clear them first
  if (showEdgeDetails.value) {
    closeEdgeDetails();
  }
  if (selectedEdgeId.value) {
    handlePaneClick();
    edges.value = updateEdgeStylesWithSelection(edges.value);
    // Hard reset to ensure no lingering animations
    edges.value = edges.value.map((e: any) => ({ ...e, animated: false }));
  }

  // Handle different node types
  if (node.type === 'app') {
    // App nodes: delegate to composable for highlighting
    handleAppNodeClick(node, nodes, edges);
    // Ensure edges remain non-animated after applying node highlight
    edges.value = edges.value.map((e: any) => ({ ...e, animated: false }));
  } else if (node.type === 'function') {
    // Function nodes: clear highlight and keep default behavior (no navigation for user view)
    clearNodeHighlight(nodes, edges);
  } else {
    // Other node types: clear highlight and use default behavior
    clearNodeHighlight(nodes, edges);
    handleNodeClick(node, false, props.allowedStreams);
  }
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
  // Highlight only the edge and its source/target apps
  applyEdgeHighlightById(clickedEdgeId, nodes, edges);
  // Keep selection styles (e.g., animation/thickness)
  edges.value = updateEdgeStylesWithSelection(edges.value);
}

function onPaneClick(event: any) {
  handlePaneClick();
  edges.value = updateEdgeStylesWithSelection(edges.value);
  // Close edge details when clicking on pane
  if (showEdgeDetails.value) {
    closeEdgeDetails();
  }
  // Clear node-based highlight when clicking on empty pane
  clearNodeHighlight(nodes, edges);
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

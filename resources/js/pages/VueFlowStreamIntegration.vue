<template>
  <div id="container">
    <aside id="sidebar">
      <button id="sidebar-close" @click="closeSidebar">
        <i class="fas fa-times"></i>
      </button>
      <header>
        <h1>
          <i class="fas fa-bezier-curve"></i>
          Diagram - {{ streamName.toUpperCase() }} Stream
        </h1>
      </header>
      <div class="sidebar-content">
        <div class="navigation">
          <a @click.prevent="$inertia.visit('/')" class="nav-link">
            <i class="fas fa-home"></i>
            <span>Halaman Utama</span>
          </a>
        </div>

        <div class="controls-section">
          <h3>Controls</h3>
          <button
            @click="centerView"
            class="control-button"
          >
            <i class="fas fa-crosshairs"></i>
            Center View
          </button>
          <button
            @click="resetLayout"
            class="control-button"
          >
            <i class="fas fa-refresh"></i>
            Reset Layout
          </button>
        </div>

        <div class="legend">
          <h3>Tipe Node</h3>
          <ul>
            <li><span class="legend-key circle sp"></span>Aplikasi SP</li>
            <li><span class="legend-key circle mi"></span>Aplikasi MI</li>
            <li><span class="legend-key circle ssk-mon"></span>Aplikasi SSK & Moneter</li>
            <li><span class="legend-key circle market"></span>Aplikasi Market</li>
            <li><span class="legend-key circle internal"></span>Aplikasi Internal BI di luar DLDS</li>
            <li><span class="legend-key circle external"></span>Aplikasi External BI</li>
            <li><span class="legend-key circle middleware"></span> Middleware</li>
          </ul>
        </div>

        <div class="legend">
          <h3>Tipe Koneksi</h3>
          <ul>
            <li><span class="legend-key line direct"></span> Direct</li>
            <li><span class="legend-key line soa"></span> SOA</li>
            <li><span class="legend-key line sftp"></span> SFTP</li>
          </ul>
        </div>
      </div>
    </aside>

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
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { VueFlow, PanOnScrollMode } from '@vue-flow/core';
import { Controls } from '@vue-flow/controls';
import { Background, BackgroundVariant } from '@vue-flow/background';
import StreamNest from '@/components/VueFlow/StreamNest.vue';
import AppNode from '@/components/VueFlow/AppNode.vue';
import { useSidebar } from '../composables/useSidebar';
import { useVueFlowUserView } from '../composables/useVueFlowUserView';
import { 
  validateAndCleanNodes, 
  createStyledNode, 
  createStyledEdge,
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

// Refs
const vueFlowRef = ref()
const isLayouted = ref(false)

// Reactive data
const nodes = ref<Node[]>([])
const edges = ref<Edge[]>([])

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
    console.log('No edge ID found in click event');
    return;
  }
  
  console.log('Edge clicked:', clickedEdgeId);
  handleEdgeClick(clickedEdgeId);
  edges.value = updateEdgeStyles(edges.value);
}

function onPaneClick(event: any) {
  handlePaneClick();
  edges.value = updateEdgeStyles(edges.value);
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

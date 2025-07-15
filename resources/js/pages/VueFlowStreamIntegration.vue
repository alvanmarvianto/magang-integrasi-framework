<template>
  <div id="container">
    <aside id="sidebar">
      <button id="sidebar-close" @click="closeSidebar">
        <i class="fas fa-times"></i>
      </button>
      <header>
        <h1>
          <i class="fas fa-bezier-curve"></i>
          Vue Flow - {{ streamName.toUpperCase() }} Stream
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
            <li><span class="legend-key circle sp"></span> SP Apps</li>
            <li><span class="legend-key circle mi"></span> MI Apps</li>
            <li><span class="legend-key circle ssk-mon"></span> SSK & Moneter Apps</li>
            <li><span class="legend-key circle market"></span> Market Apps</li>
            <li><span class="legend-key circle internal"></span> Internal BI Apps</li>
            <li><span class="legend-key circle external"></span> External BI Apps</li>
            <li><span class="legend-key circle middleware"></span> Middleware</li>
          </ul>
        </div>

        <div class="legend">
          <h3>Connection Types</h3>
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
          v-model:nodes="nodes"
          v-model:edges="edges"
          :fit-view-on-init="true"
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
        >
          <!-- Custom Node Types -->
          <template #node-streamParent="nodeProps">
            <StreamParentNode v-bind="nodeProps" />
          </template>
          <template #node-custom="nodeProps">
            <CustomNode v-bind="nodeProps" />
          </template>

          <!-- Controls -->
          <Controls
            :show-zoom="true"
            :show-fit-view="true"
            :show-interactive="true"
            position="bottom-right"
          />
        </VueFlow>

        <!-- Loading Overlay -->
        <div
          v-if="!isLayouted"
          class="loading-overlay"
        >
          <div class="loading-content">
            <div class="loading-spinner"></div>
            <p>Loading Vue Flow diagram...</p>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { onMounted, computed, nextTick } from 'vue';
import { VueFlow, PanOnScrollMode } from '@vue-flow/core';
import { Controls } from '@vue-flow/controls';
import StreamParentNode from '../components/StreamParentNode.vue';
import CustomNode from '../components/CustomNode.vue';
import { useSidebar } from '../composables/useSidebar';
import { useVueFlowStreamIntegration } from '@/composables/useVueFlowStreamIntegration';
import type { AppNode, AppEdge } from '@/composables/useVueFlowStreamIntegration';

// Add necessary CSS imports
import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';
import '@vue-flow/controls/dist/style.css';

// Props from Inertia
interface Props {
  streamName: string;
  nodes: any[];
  edges: any[];
  streams: string[];
}

const props = defineProps<Props>();

// Use sidebar composable
const { visible, isMobile, toggleSidebar, closeSidebar } = useSidebar();

// Use the composable
const {
  nodes,
  edges,
  isLayouted,
  initializeLayout,
  onNodeClick,
  resetLayout,
  centerView,
} = useVueFlowStreamIntegration();

// Convert props to proper types
const convertedNodes = computed((): AppNode[] => {
  return props.nodes.map(node => ({
    ...node,
    data: {
      ...node.data,
      app_id: node.data.app_id || -1,
      stream_name: node.data.stream_name || '',
      is_home_stream: node.data.is_home_stream || false,
    }
  }));
});

const convertedEdges = computed((): AppEdge[] => {
  return props.edges.map(edge => ({
    ...edge,
    data: {
      ...edge.data,
      label: edge.data.label || 'Connection',
    }
  }));
});

// Initialize layout on mount
onMounted(async () => {
  // Wait for the DOM to be fully rendered
  await nextTick();
  
  // Minimal delay to ensure Vue Flow container is ready
  setTimeout(async () => {
    await initializeLayout(convertedNodes.value, convertedEdges.value, props.streamName);
  }, 50);
});
</script>

<style scoped>
@import '../../css/app.css';

.vue-flow-wrapper {
  width: 100%;
  height: 100%;
  position: relative;
}

.vue-flow-container {
  width: 100%;
  height: 100%;
}

/* Control buttons in sidebar */
.controls-section {
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid rgba(221, 221, 221, 0.2);
}

.control-button {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 10px 12px;
  margin-bottom: 8px;
  background-color: rgba(255, 255, 255, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 6px;
  color: var(--text-color-light);
  font-size: 0.9em;
  cursor: pointer;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
}

.control-button:hover {
  background-color: rgba(255, 255, 255, 0.5);
  transform: translateY(-1px);
}

.control-button i {
  color: var(--primary-color);
  width: 16px;
}

/* Legend styling for stream groups */
.legend-key.rect {
  display: inline-block;
  width: 20px;
  height: 12px;
  border-radius: 3px;
  margin-right: 8px;
  vertical-align: middle;
}

.legend-key.rect.home-stream {
  background-color: rgba(59, 130, 246, 0.2);
  border: 2px solid #3b82f6;
}

.legend-key.rect.other-stream {
  background-color: rgba(156, 163, 175, 0.2);
  border: 2px dashed #9ca3af;
}

.legend-key.rect.external-app {
  background-color: rgba(245, 158, 11, 0.2);
  border: 2px solid #f59e0b;
}

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

/* Custom Node Styles - White boxes with colored borders */
:deep(.vue-flow__node) {
  font-family: inherit;
  will-change: transform;
}

:deep(.vue-flow__node.dragging) {
  transition: none !important;
}

/* Stream parent node specific styles */
:deep(.vue-flow__node-streamParent) {
  cursor: move !important;
}

:deep(.vue-flow__node-streamParent.dragging) {
  transition: none !important;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
}

:deep(.vue-flow__node-default) {
  background: white !important;
  border-radius: 6px !important;
  padding: 8px 12px !important;
  min-width: 120px !important;
  min-height: 40px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  text-align: center !important;
  font-size: 12px !important;
  font-weight: 500 !important;
  color: #1c1c1e !important;
  cursor: pointer !important;
  transition: box-shadow 0.15s ease !important;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1) !important;
}

:deep(.vue-flow__node-default:hover) {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
}

/* Edge Styles */
:deep(.vue-flow__edge-path) {
  stroke: #6b7280;
  stroke-width: 2;
}

:deep(.vue-flow__edge:hover .vue-flow__edge-path) {
  stroke: #3b82f6;
  stroke-width: 3;
}

/* Controls Styling */
:deep(.vue-flow__controls) {
  background-color: white;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
}

:deep(.vue-flow__controls-button) {
  border: none;
  background-color: transparent;
}

:deep(.vue-flow__controls-button:hover) {
  background-color: #f3f4f6;
}

/* Custom Node Handle Styling */
:deep(.vue-flow__handle) {
  width: 10px;
  height: 10px;
  background: #555;
  border: 2px solid #fff;
  border-radius: 50%;
  opacity: 0;
  transition: opacity 0.2s ease;
}

:deep(.vue-flow__node:hover .vue-flow__handle) {
  opacity: 1;
}

:deep(.vue-flow__handle:hover) {
  background: #3b82f6;
  transform: scale(1.2);
}
</style>

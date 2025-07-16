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
            <p>Loading Vue Flow diagram...</p>
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
  // Check if we have saved layout data
  const hasSavedLayout = props.savedLayout?.nodes_layout && Object.keys(props.savedLayout.nodes_layout).length > 0
  
  // Ensure no duplicate nodes and validate node data
  const uniqueNodes = props.nodes.reduce((acc, node) => {
    if (!acc.find(n => n.id === node.id)) {
      // Ensure each node has required properties with complete structure
      const validNode = {
        id: node.id || `node-${acc.length}`,
        type: node.type || 'default',
        position: node.position || { x: 0, y: 0 },
        data: {
          label: node.data?.label || 'Unknown',
          lingkup: node.data?.lingkup || '',
          is_parent_node: node.data?.is_parent_node || false,
          is_home_stream: node.data?.is_home_stream || false,
          ...(node.data || {})
        },
        draggable: true,
        selectable: false,
        connectable: false,
        focusable: true,
        deletable: false,
        zIndex: node.zIndex || 0,
        ...(node.style && { style: node.style }),
        ...(node.class && { class: node.class }),
        ...(node.hidden !== undefined && { hidden: node.hidden }),
        ...(node.selected !== undefined && { selected: node.selected })
      }
      acc.push(validNode)
    } else {
      console.warn('Duplicate node found and removed:', node.id)
    }
    return acc
  }, [] as Node[])
  
  if (hasSavedLayout) {
    // Initialize nodes with saved positions
    nodes.value = uniqueNodes.map(node => {
      const savedNode = props.savedLayout?.nodes_layout?.[node.id]
      const nodeColors = getNodeColor(node.data?.lingkup || '')
      
      const newNode: Node = {
        id: node.id,
        type: node.data.is_parent_node ? 'stream' : 'app',
        position: savedNode?.position || { x: 0, y: 0 },
        data: node.data,
        draggable: true,
        selectable: false,
        connectable: false,
        focusable: true,
        deletable: false
      }
      
      if (node.data.is_parent_node) {
        // Stream node
        newNode.style = {
          cursor: 'grab',
          backgroundColor: 'rgba(59, 130, 246, 0.3)',
          border: '2px solid #3b82f6',
          borderRadius: '8px',
          width: savedNode?.dimensions?.width ? `${savedNode.dimensions.width}px` : '300px',
          height: savedNode?.dimensions?.height ? `${savedNode.dimensions.height}px` : '200px'
        }
        
        if (savedNode?.dimensions) {
          newNode.data = {
            ...newNode.data,
            dimensions: savedNode.dimensions
          }
        }
      } else {
        // App node
        newNode.style = {
          cursor: 'grab',
          width: '120px',
          height: '80px',
          backgroundColor: nodeColors.background,
          border: `2px solid ${nodeColors.border}`,
          borderRadius: '8px'
        }
      }
      
      return newNode
    })
  } else {
    // Auto-generate layout
    nodes.value = uniqueNodes.map(node => {
      const nodeColors = getNodeColor(node.data?.lingkup || '')
      
      const newNode: Node = {
        id: node.id,
        type: node.data.is_parent_node ? 'stream' : 'app',
        position: { x: 0, y: 0 },
        data: node.data,
        draggable: true,
        selectable: false,
        connectable: false,
        focusable: true,
        deletable: false
      }
      
      if (node.data.is_parent_node) {
        // Stream node
        newNode.style = {
          cursor: 'grab',
          backgroundColor: 'rgba(59, 130, 246, 0.3)',
          border: '2px solid #3b82f6',
          borderRadius: '8px',
          width: '300px',
          height: '200px'
        }
      } else {
        // App node
        newNode.style = {
          cursor: 'grab',
          width: '120px',
          height: '80px',
          backgroundColor: nodeColors.background,
          border: `2px solid ${nodeColors.border}`,
          borderRadius: '8px'
        }
      }
      
      return newNode
    })
    
    applyAutomaticLayoutWithConstraints(nodes.value)
  }
  
  // Initialize edges with saved handle information if available
  let edgesData = props.edges
  
  if (props.savedLayout?.edges_layout && props.savedLayout.edges_layout.length > 0) {
    console.log('Loading saved edges layout:', props.savedLayout.edges_layout)
    edgesData = props.savedLayout.edges_layout
  }
  
  edges.value = removeDuplicateEdges(edgesData).map(edge => {
    const edgeColor = getEdgeColor(edge.data?.connection_type || 'direct')
    const isSelected = selectedEdgeId.value === edge.id
    
    // For saved layouts, preserve all edge properties including handles
    if (props.savedLayout?.edges_layout && props.savedLayout.edges_layout.length > 0) {
      return {
        ...edge,
        type: 'smoothstep',
        updatable: false, // Disable edge updates in user view
        animated: isSelected,
        style: {
          stroke: edgeColor,
          strokeWidth: isSelected ? 4 : 2,
          ...(edge.style || {})
        },
        markerEnd: {
          type: 'arrowclosed',
          color: edgeColor,
        } as any,
        // Preserve saved handle positions
        sourceHandle: edge.sourceHandle,
        targetHandle: edge.targetHandle,
        data: {
          connection_type: edge.data?.connection_type || 'direct',
          ...edge.data
        }
      }
    } else {
      // For non-saved layouts, use default behavior
      return {
        ...edge,
        type: 'smoothstep',
        updatable: false, // Disable edge updates in user view
        animated: isSelected,
        style: {
          stroke: edgeColor,
          strokeWidth: isSelected ? 4 : 2,
          ...(edge.style || {})
        },
        markerEnd: {
          type: 'arrowclosed',
          color: edgeColor,
        } as any,
        sourceHandle: edge.sourceHandle || undefined,
        targetHandle: edge.targetHandle || undefined,
        data: {
          connection_type: edge.data?.connection_type || 'direct',
          ...edge.data
        }
      }
    }
  })

  // Apply layout and fit view
  setTimeout(() => {
    fitView()
    isLayouted.value = true
  }, 100)
}

function onNodeClick(event: any) {
  console.log('Node clicked:', event.node)
}

function onEdgeClick(event: any) {
  const clickedEdgeId = event.edge?.id
  if (!clickedEdgeId) {
    console.log('No edge ID found in click event')
    return
  }
  
  console.log('Edge clicked:', clickedEdgeId)
  handleEdgeClick(clickedEdgeId)
  edges.value = updateEdgeStyles(edges.value)
}

function onPaneClick(event: any) {
  handlePaneClick()
  edges.value = updateEdgeStyles(edges.value)
}

function onNodeDragStop(event: any) {
  const { node } = event
  const nodeIndex = nodes.value.findIndex(n => n.id === node.id)
  if (nodeIndex !== -1) {
    nodes.value[nodeIndex].position = node.position
  }
}

function fitView() {
  if (vueFlowRef.value) {
    vueFlowRef.value.fitView({ padding: 50 })
  }
}

function centerView() {
  fitView()
}

function resetLayout() {
  initializeLayout()
}
</script>

<style scoped>
@import '../../css/app.css';
@import '../../css/vue-flow-integration.css';
</style>

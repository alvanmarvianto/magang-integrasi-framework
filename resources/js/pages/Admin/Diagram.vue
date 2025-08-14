<template>
  <div class="admin-vue-flow-container">
    <!-- Error State -->
    <ErrorState
      v-if="props.error"
      type="error"
      title="Gagal Memuat Diagram Admin"
      :message="props.error"
      :show-back-button="true"
      back-button-text="Kembali ke Dashboard Admin"
      back-route="admin.dashboard"
    />

    <!-- No Data State -->
    <ErrorState
      v-else-if="!props.nodes || props.nodes.length === 0"
      type="no-data"
      title="Tidak Ada Data Diagram"
      message="Diagram untuk stream ini belum tersedia atau belum dikonfigurasi."
      :show-back-button="true"
      back-button-text="Kembali ke Dashboard Admin"
      back-route="admin.dashboard"
    />

    <!-- Normal Content -->
    <template v-else>
      <AdminNavbar :title="`Admin - ${streamName.toUpperCase()} Stream Layout`" :showBackButton="true">
        <template #controls>
          <!-- Layout changed indicator -->
          <div v-if="layoutChanged" class="status-indicator unsaved">
            <div class="indicator-dot"></div>
            Perubahan belum tersimpan
          </div>
          
          <!-- Saved indicator -->
          <div v-else class="status-indicator saved">
            <div class="indicator-dot saved-dot"></div>
            Tersimpan
          </div>
          
          <select v-model="selectedStream" @change="switchStream" class="stream-selector">
            <option v-for="stream in allowedStreams" :key="stream" :value="stream">
              {{ stream.toUpperCase() }}
            </option>
          </select>
          <button @click="saveLayout" :disabled="saving" class="save-btn">
            {{ saving ? 'Saving...' : 'Save Layout' }}
          </button>
          <button 
            @click="refreshLayout" 
            :disabled="refreshing" 
            class="refresh-btn"
            :class="{ 'has-unsaved-changes': layoutChanged }"
            :title="layoutChanged ? 'Peringatan: Ada perubahan yang belum disimpan!' : 'Refresh layout dan hapus data yang tidak valid'"
          >
            {{ refreshing ? 'Refreshing...' : 'Refresh Layout' }}
          </button>
          <button @click="resetLayout" class="reset-btn">Reset Layout</button>
        </template>
      </AdminNavbar>

      <!-- Vue Flow -->
      <div class="vue-flow-wrapper">
        <VueFlow
          ref="vueFlowRef"
          :key="vueFlowKey"
          :nodes="nodes"
          :edges="edges"
          :class="{ 'vue-flow': true, 'admin-mode': true }"
          @node-drag-stop="onNodeDragStop"
          @nodes-change="onNodesChange"
          @edges-change="onEdgesChange"
          @edge-update="onEdgeUpdate"
          @edge-click="onEdgeClick"
          @node-click="onNodeClick"
        @pane-click="onPaneClick"
        @wheel="onWheel"
        @contextmenu="onContextMenu"
        :fit-view-on-init="false"
        :zoom-on-scroll="false"
        :zoom-on-pinch="true"
        :pan-on-scroll="false"
        :pan-on-scroll-mode="PanOnScrollMode.Free"
        :pan-on-drag="[0, 2]"
        :selection-key-code="null"
        :multi-selection-key-code="null"
        :nodes-draggable="true"
        :nodes-connectable="true"
        :edges-updatable="true"
        :edges-focusable="true"
        :validate-connection="validateConnection"
        :connection-line-type="'smoothstep'"
        :delete-key-code="null"
      >
        <!-- Custom Node Types -->
        <template #node-stream="nodeProps">
          <StreamNest v-bind="nodeProps" :admin-mode="true" @resize="onStreamResize" />
        </template>

        <!-- Custom App Node with Handles -->
        <template #node-app="nodeProps">
          <AppNode v-bind="nodeProps" :admin-mode="true" />
        </template>

        <!-- Controls -->
        <Controls
          :show-zoom="true"
          :show-fit-view="true"
          :show-interactive="true"
          position="bottom-left"
        />
        <Background :pattern="BackgroundVariant.Dots" />
      </VueFlow>

      <!-- Details Sidebar for Admin -->
      <DetailsSidebar
        :visible="showDetails"
        :detailType="detailType"
        :edgeData="selectedEdgeData"
        :nodeData="selectedNodeData"
        :isAdmin="true"
        :offsetTop="'5rem'"
        @close="closeDetails"
      />
    </div>

    <!-- Status -->
    <div v-if="statusMessage" class="status-message" :class="statusType">
      {{ statusMessage }}
    </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { VueFlow, useVueFlow } from '@vue-flow/core'
import { Background, BackgroundVariant } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import { PanOnScrollMode } from '@vue-flow/core'
import { router, usePage } from '@inertiajs/vue3'
import StreamNest from '@/components/VueFlow/StreamNest.vue'
import AppNode from '@/components/VueFlow/AppNode.vue'
import AdminNavbar from '@/components/Admin/AdminNavbar.vue'
import EdgeDetailsSidebar from '@/components/Sidebar/EdgeDetailsSidebar.vue'
import DetailsSidebar from '@/components/Sidebar/DetailsSidebar.vue'
import ErrorState from '@/components/ErrorState.vue'
import { useStatusMessage } from '@/composables/useStatusMessage'
import { useAdminEdgeHandling } from '@/composables/useAdminEdgeHandling'
import { 
  removeDuplicateEdges,
  moveEdgeToTop,
  fitView as sharedFitView,
  initializeNodesWithLayout,
  applyAutomaticLayoutWithConstraints,
  validateAndCleanNodes,
  createCustomWheelHandler,
  createCustomContextMenuHandler
} from '@/composables/useVueFlowCommon'
import type { Node, Edge } from '@vue-flow/core'

// Add necessary CSS imports
import '@vue-flow/core/dist/style.css'
import '@vue-flow/core/dist/theme-default.css'
import '@vue-flow/controls/dist/style.css'

// Props
interface Props {
  streamName: string
  nodes: Node[]
  edges: Edge[]
  savedLayout: {
    nodes_layout?: Record<string, any>
    edges_layout?: any[]
    stream_config?: Record<string, any>
  } | null
  allowedStreams: string[]
  refreshData?: {
    nodes: Node[]
    edges: Edge[]
  }
  error?: string | null
}

const props = defineProps<Props>()

// Refs
const vueFlowRef = ref()
const saving = ref(false)
const refreshing = ref(false)
const selectedStream = ref(props.streamName)
const showDetails = ref(false)
const detailType = ref<'edge' | 'node'>('edge')
const selectedEdgeData = ref(null)
const selectedNodeData = ref(null)

// Reactive data
const nodes = ref<Node[]>([])
const edges = ref<Edge[]>([])
const layoutChanged = ref(false)
const vueFlowKey = ref(0) // Key to force VueFlow re-render
const isInitializing = ref(false) // Flag to prevent marking changes during initialization
// Use admin-specific edge handling; respect server flag to force black/no arrows
const forceNoArrow = !!(props.savedLayout as any)?.stream_config?.forceEdgeBlackNoArrow
const { handleEdgeClick, handlePaneClick, updateAdminEdgeStyles, updateAdminEdgeStylesWithSelection, initializeAdminEdges, selectedEdgeId } = useAdminEdgeHandling({
  disableMarkers: forceNoArrow,
  forceBlack: forceNoArrow,
})

// Get VueFlow instance and functions
const { zoomIn, zoomOut, setViewport, getViewport } = useVueFlow()

// Create shared wheel handler
const onWheel = createCustomWheelHandler(zoomIn, zoomOut, setViewport, getViewport)

// Create context menu handler to disable popup on empty space
const onContextMenu = createCustomContextMenuHandler()

// Add event listener for beforeunload
onMounted(() => {
  window.addEventListener('beforeunload', handleBeforeUnload)
})

// Remove event listener on component unmount
onUnmounted(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload)
})

// Handle beforeunload event
function handleBeforeUnload(e: BeforeUnloadEvent) {
  if (layoutChanged.value) {
    e.preventDefault()
    e.returnValue = 'Ada perubahan yang belum tersimpan. Anda yakin ingin meninggalkan halaman ini?'
    return e.returnValue
  }
}

// Use status messages
const { statusMessage, statusType, showStatus } = useStatusMessage()

// Get page props for flash messages
const page = usePage()

// Track original layout for reset
const originalLayout = ref<{
  nodes_layout?: Record<string, any>
  edges_layout?: any[]
  stream_config?: Record<string, any>
} | null>(null)

// Initialize layout
onMounted(() => {
  initializeLayout()
  
  // Check for flash messages
  if (page.props.success) {
    showStatus(page.props.success as string, 'success')
  }
  
  if (page.props.errors && typeof page.props.errors === 'object') {
    const errorMessages = Object.values(page.props.errors).flat()
    if (errorMessages.length > 0) {
      showStatus(errorMessages[0] as string, 'error')
    }
  }
})

// Watch for stream changes
watch(() => props.streamName, () => {
  selectedStream.value = props.streamName
  initializeLayout()
})

function initializeLayout() {
  // Set initializing flag to prevent change events from marking layout as changed
  isInitializing.value = true
  
  // Reset layout changed status to show "Tersimpan" initially
  layoutChanged.value = false
  
  // Store original layout
  originalLayout.value = props.savedLayout ? JSON.parse(JSON.stringify(props.savedLayout)) : null
  const cleanedNodes = validateAndCleanNodes(props.nodes);
  
  // Check if we have saved layout data
  const hasSavedLayout = props.savedLayout?.nodes_layout && Object.keys(props.savedLayout.nodes_layout).length > 0
  
  // Initialize nodes with shared function
  nodes.value = initializeNodesWithLayout(
    cleanedNodes,
    props.savedLayout,
    true // Admin mode
  )
  
  // Apply automatic layout if no saved layout
  if (!hasSavedLayout) {
    applyAutomaticLayoutWithConstraints(nodes.value)
  }
  
  // Initialize edges with admin-specific function
  edges.value = initializeAdminEdges(
    props.edges,
    props.savedLayout,
    removeDuplicateEdges
  )

  // Apply layout
  setTimeout(() => {
    fitView()
    // Clear initializing flag after a delay to allow all VueFlow events to settle
    setTimeout(() => {
      isInitializing.value = false
      // Ensure layout is marked as not changed after initialization
      layoutChanged.value = false
    }, 200)
  }, 100)
}

function fitView() {
  if (vueFlowRef.value) {
    vueFlowRef.value.fitView({ padding: 50 })
  }
}

function validateConnection(connection: any) {
  
  // Show user feedback about blocked connection
  showStatus('Tidak bisa membuat koneksi. Anda hanya dapat memperbarui koneksi yang ada.', 'error')
  
  return false // Always block new connections while allowing the drag interaction
}

function onNodeDragStop(event: any) {
  const { node } = event
  
  // Update the node position in our reactive array
  const nodeIndex = nodes.value.findIndex(n => n.id === node.id)
  if (nodeIndex !== -1) {
    nodes.value[nodeIndex].position = node.position
  }
  
  markLayoutChanged()
}

function onEdgeUpdate(params: any, newConnection?: any) {
  
  // VueFlow passes parameters differently - extract the actual data
  let oldEdge, connection
  
  if (params && typeof params === 'object') {
    // Check if params has edge and connection properties
    if (params.edge && params.connection) {
      oldEdge = params.edge
      connection = params.connection
    } else if (params.id) {
      // params is the oldEdge itself
      oldEdge = params
      connection = newConnection
    } else {
      console.error('onEdgeUpdate: Unexpected parameter structure', params)
      showStatus('Gagal memperbarui koneksi - parameter tidak valid', 'error')
      return
    }
  } else {
    console.error('onEdgeUpdate: Invalid parameters', { params, newConnection })
    showStatus('Gagal memperbarui koneksi - parameter tidak valid', 'error')
    return
  }
  
  
  // Guard against missing data
  if (!oldEdge || !connection) {
    console.error('onEdgeUpdate: oldEdge or connection is missing', { oldEdge, connection })
    return
  }
  
  // Check if the connection is only changing handles on the same nodes
  const sourceNodeChanged = connection.source !== oldEdge.source
  const targetNodeChanged = connection.target !== oldEdge.target
  
  if (sourceNodeChanged || targetNodeChanged) {
    showStatus('Tidak bisa menghubungkan ke node yang berbeda.', 'error')
    return
  }
  
  const edgeIndex = edges.value.findIndex(e => e.id === oldEdge.id)
  if (edgeIndex !== -1) {
    // Since source and target nodes haven't changed, keep the same edge ID
    const edgeId = oldEdge.id

    // Preserve original edge properties but update handle information
    const updatedEdge = {
      ...edges.value[edgeIndex],
      id: edgeId,
      source: connection.source, // Same as oldEdge.source
      target: connection.target, // Same as oldEdge.target
      sourceHandle: connection.sourceHandle || oldEdge.sourceHandle,
      targetHandle: connection.targetHandle || oldEdge.targetHandle,
    }

    // Update the edge in place first
    edges.value[edgeIndex] = updatedEdge
    
    // Move the updated edge to the top so it's easier to manipulate
    edges.value = moveEdgeToTop(edges.value, edgeId)

    // Apply proper styling
    edges.value = updateAdminEdgeStylesWithSelection(edges.value)

    // Force VueFlow to re-render the edge with new handle positions
    nextTick(() => {
      if (vueFlowRef.value) {
        try {
          vueFlowRef.value.updateEdge(oldEdge.id, updatedEdge)
        } catch (error) {
          vueFlowKey.value += 1 // Force re-render of VueFlow component
        }
      }
    })

    markLayoutChanged()
  } else {
    console.error('Edge not found for update:', oldEdge.id)
    showStatus('Gagal memperbarui koneksi', 'error')
  }
}

function onEdgeClick(event: any) {
  const clickedEdgeId = event.edge?.id
  if (!clickedEdgeId) {
    return
  }
  
  // Find the edge data for the sidebar
  const edge = edges.value.find(e => e.id === clickedEdgeId)
  if (edge && edge.data) {
    selectedEdgeData.value = edge.data
    selectedNodeData.value = null
    detailType.value = 'edge'
    showDetails.value = true
  }
  
  // Move the clicked edge to the top so it renders on top and is easier to manipulate
  edges.value = moveEdgeToTop(edges.value, clickedEdgeId)
  
  handleEdgeClick(clickedEdgeId)
  edges.value = updateAdminEdgeStylesWithSelection(edges.value)
  
  // Note: Clicking an edge does not mark layout as changed - only actual modifications do
}

function onNodeClick(event: any) {
  const clickedNodeId = event.node?.id
  if (!clickedNodeId) {
    return
  }
  
  // Find the node data for the sidebar
  const node = nodes.value.find(n => n.id === clickedNodeId)
  if (node && node.data && !node.data.is_parent_node) {
    // Only show details for app nodes, not stream parent nodes
    selectedNodeData.value = node.data
    selectedEdgeData.value = null
    detailType.value = 'node'
    showDetails.value = true
  }
  
  // Note: Clicking a node does not mark layout as changed - only actual modifications do
}

function closeDetails() {
  showDetails.value = false
  selectedEdgeData.value = null
  selectedNodeData.value = null
}

function onPaneClick(event: any) {
  handlePaneClick()
  edges.value = updateAdminEdgeStylesWithSelection(edges.value)
  // Close details when clicking on pane
  if (showDetails.value) {
    closeDetails()
  }
  
  // Note: Clicking the pane does not mark layout as changed - only actual modifications do
}

function onNodesChange(changes: any[]) {
  
  let hasLayoutChanges = false
  
  // Update our reactive nodes array
  changes.forEach(change => {
    if (change.type === 'position' && change.item) {
      const nodeIndex = nodes.value.findIndex(n => n.id === change.item.id)
      if (nodeIndex !== -1) {
        nodes.value[nodeIndex].position = change.item.position
        hasLayoutChanges = true // Position changes affect layout
      }
    } else if (change.type === 'dimensions' && change.item) {
      const nodeIndex = nodes.value.findIndex(n => n.id === change.item.id)
      if (nodeIndex !== -1) {
        nodes.value[nodeIndex].style = {
          ...nodes.value[nodeIndex].style,
          ...change.item.style
        }
        hasLayoutChanges = true // Dimension changes affect layout
      }
    }
    // Note: 'select' type changes are ignored as they don't affect layout
  })
  
  // Mark layout as changed only if there are actual layout changes and we're not initializing
  if (hasLayoutChanges && !isInitializing.value) {
    markLayoutChanged()
  }
}

function onEdgesChange(changes: any[]) {
  
  let hasLayoutChanges = false
  
  // Update our reactive edges array
  changes.forEach(change => {
    if (change.type === 'remove' && change.id) {
      const edgeIndex = edges.value.findIndex(e => e.id === change.id)
      if (edgeIndex !== -1) {
        edges.value.splice(edgeIndex, 1)
        hasLayoutChanges = true // Removing edges affects layout
      }
    } else if (change.type === 'add' && change.item) {
      // Handle edge additions
      const existingEdgeIndex = edges.value.findIndex(e => e.id === change.item.id)
      if (existingEdgeIndex === -1) {
        edges.value.push(change.item)
        hasLayoutChanges = true // Adding edges affects layout
      }
    } else if (change.type === 'update' && change.item) {
      // Handle edge updates
      const edgeIndex = edges.value.findIndex(e => e.id === change.item.id)
      if (edgeIndex !== -1) {
        edges.value.splice(edgeIndex, 1, change.item)
        hasLayoutChanges = true // Updating edges affects layout
      }
    }
    // Note: 'select' type changes are ignored as they don't affect layout
  })
  
  // Mark layout as changed only if there are actual layout changes and we're not initializing
  if (hasLayoutChanges && !isInitializing.value) {
    markLayoutChanged()
  }
}

function markLayoutChanged() {
  layoutChanged.value = true
}

async function saveLayout() {
  if (!layoutChanged.value) {
    showStatus('Tidak ada perubahan untuk disimpan', 'info')
    return
  }

  saving.value = true
  
  try {
    // Use our reactive nodes array directly
    const currentNodes = nodes.value
    
    // Prepare nodes layout
    const nodesLayout: Record<string, any> = {}
    currentNodes.forEach(node => {
      nodesLayout[node.id] = {
        position: node.position,
        style: node.style,
        parentNode: node.parentNode,
        extent: node.extent,
        ...(node.data.is_parent_node && {
          dimensions: {
            width: parseInt((node.style as any)?.width?.toString().replace('px', '') || '600'),
            height: parseInt((node.style as any)?.height?.toString().replace('px', '') || '400')
          }
        })
      }
    })

    // Prepare edges layout
    const edgesLayout = edges.value.map(edge => ({
      id: edge.id,
      source: edge.source,
      target: edge.target,
      sourceHandle: edge.sourceHandle,
      targetHandle: edge.targetHandle,
      type: edge.type,
      style: edge.style,
      data: edge.data
    }))

    // Prepare stream config
    const streamConfig = {
      lastUpdated: new Date().toISOString(),
      totalNodes: currentNodes.length,
      totalEdges: edges.value.length
    }

    // Save to backend
    router.post(`/admin/stream/${props.streamName}/layout`, {
      nodes_layout: nodesLayout,
      edges_layout: edgesLayout,
      stream_config: streamConfig
    }, {
      preserveState: true,
      onSuccess: () => {
        layoutChanged.value = false
        showStatus('Layout berhasil disimpan!', 'success')
      },
      onError: (errors) => {
        console.error('Save failed:', errors)
        showStatus('Gagal menyimpan layout', 'error')
      }
    })
  } catch (error) {
    console.error('Save error:', error)
    showStatus('Gagal menyimpan layout', 'error')
  } finally {
    saving.value = false
  }
}

async function refreshLayout() {
  // Check if there are unsaved changes
  if (layoutChanged.value) {
    const confirmRefresh = confirm(
      'Ada perubahan yang belum tersimpan. Menyegarkan layout akan menghilangkan semua perubahan yang belum disimpan. Apakah Anda yakin ingin melanjutkan?'
    )
    
    if (!confirmRefresh) {
      return // User cancelled the refresh
    }
  }

  refreshing.value = true
  
  try {
    // Simple navigation to refresh URL - backend will handle cleanup and redirect back
    window.location.href = `/admin/stream/${props.streamName}/refresh`
  } catch (error: any) {
    console.error('Refresh error:', error)
    showStatus('Gagal menyegarkan layout: ' + (error?.message || 'Unknown error'), 'error')
    refreshing.value = false
  }
}



function resetLayout() {
  if (originalLayout.value) {
    // Reset to saved layout
    initializeLayout()
    showStatus('Layout kembali ke semula', 'info')
  } else {
    // Reset to automatic layout with constraints
    applyAutomaticLayoutWithConstraints(nodes.value)
    fitView()
    showStatus('Layout kembali ke default', 'info')
    markLayoutChanged() // Mark as changed so user can save the new layout
  }
}

function switchStream() {
  if (selectedStream.value !== props.streamName) {
    if (layoutChanged.value) {
      // Reset the selector to current stream and show warning
      selectedStream.value = props.streamName
      showStatus('Simpan perubahan sebelum keluar dari halaman', 'error')
    } else {
      router.get(`/admin/stream/${selectedStream.value}`)
    }
  }
}

function onStreamResize(event: { width: number, height: number, position?: { x: number, y: number } }) {
  // Find the stream node and update its style
  const streamNodeIndex = nodes.value.findIndex(n => n.data.is_parent_node)
  if (streamNodeIndex !== -1) {
    const currentNode = nodes.value[streamNodeIndex]
    
    // Create updated node with new dimensions and position (if provided)
    const updatedNode = {
      ...currentNode,
      style: {
        ...currentNode.style,
        width: `${event.width}px`,
        height: `${event.height}px`
      },
      dimensions: {
        width: event.width,
        height: event.height
      }
    }
    
    // Update position if provided (for top/left resizing)
    if (event.position) {
      updatedNode.position = event.position
    }
    
    // Replace node to trigger reactivity
    nodes.value.splice(streamNodeIndex, 1, updatedNode)
    
    markLayoutChanged()
  } else {
    console.warn('Stream node not found for resize')
  }
}
</script>

<style scoped>
.admin-vue-flow-container {
  height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f8fafc;
}

.admin-header {
  background: white;
  border-bottom: 1px solid #e2e8f0;
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1a202c;
  margin: 0;
}

.header-controls {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.stream-selector {
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  background: white;
  font-size: 0.875rem;
}

.save-btn, .refresh-btn, .reset-btn {
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-weight: 500;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
}

.save-btn {
  background: #3b82f6;
  color: white;
  border: none;
}

.save-btn:hover:not(:disabled) {
  background: #2563eb;
}

.save-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.refresh-btn {
  background: #10b981;
  color: white;
  border: none;
  position: relative;
}

.refresh-btn:hover:not(:disabled) {
  background: #059669;
}

.refresh-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.reset-btn {
  background: #6b7280;
  color: white;
  border: none;
}

.reset-btn:hover {
  background: #4b5563;
}

.vue-flow-wrapper {
  flex: 1;
  position: relative;
}

.vue-flow.admin-mode {
  background: #f8fafc;
}

/* Stream parent node specific styles */
:deep(.vue-flow__node-streamParent) {
  cursor: move !important;
}

/* Admin mode specific overrides for stream nodes */
.vue-flow.admin-mode :deep(.vue-flow__node-stream) {
  z-index: -10 !important;
}

.vue-flow.admin-mode :deep(.vue-flow__node-app) {
  z-index: 10 !important;
}

/* Admin Vue Flow controls positioning */
.vue-flow.admin-mode :deep(.vue-flow__controls) {
  bottom: 1rem;
  left: 1rem;
  right: auto;
}

.status-message {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  padding: 0.75rem 1rem;
  border-radius: 0.375rem;
  font-weight: 500;
  font-size: 0.875rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  z-index: 1000;
}

.status-message.success {
  background: #dcfce7;
  color: #166534;
  border: 1px solid #bbf7d0;
}

.status-message.error {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
}

.status-message.info {
  background: #dbeafe;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
}

/* Status indicators */
.status-indicator {
  display: flex;
  align-items: center;
  font-size: 0.875rem;
  font-weight: 500;
  padding: 0.375rem 0.75rem;
  border-radius: 0.375rem;
  margin-right: 0.75rem;
}

.status-indicator.auto-saving {
  color: #d97706;
  background: #fef3c7;
  border: 1px solid #fde68a;
}

.status-indicator.unsaved {
  color: #ea580c;
  background: #fed7aa;
  border: 1px solid #fdba74;
}

.status-indicator.saved {
  color: #059669;
  background: #d1fae5;
  border: 1px solid #a7f3d0;
}

.status-indicator.connection-mode {
  color: #7c3aed;
  background: #ede9fe;
  border: 1px solid #ddd6fe;
}

.spinner {
  animation: spin 1s linear infinite;
  width: 1rem;
  height: 1rem;
  margin-right: 0.5rem;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.indicator-dot {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
  margin-right: 0.5rem;
  background: #ea580c;
}

.indicator-dot.saved-dot {
  background: #059669;
}

.indicator-dot.connection-dot {
  background: #7c3aed;
}
</style>

<style scoped>
@import '@/../css/vue-flow-integration.css';
</style>

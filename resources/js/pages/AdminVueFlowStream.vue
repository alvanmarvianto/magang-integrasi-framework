<template>
  <div class="admin-vue-flow-container">
    <!-- Header -->
    <div class="admin-header">
      <h1 class="title">Admin - {{ streamName.toUpperCase() }} Stream Layout</h1>
      <div class="header-controls">
        <!-- Auto-save status indicator -->
        <div v-if="autoSaveTimeout" class="status-indicator auto-saving">
          <svg class="spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Auto-saving...
        </div>
        
        <!-- Layout changed indicator -->
        <div v-else-if="layoutChanged" class="status-indicator unsaved">
          <div class="indicator-dot"></div>
          Belum tersimpan
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
        <button @click="resetLayout" class="reset-btn">Reset Layout</button>
      </div>
    </div>

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
        @edge-update-start="onEdgeUpdateStart"
        @edge-update-end="onEdgeUpdateEnd"
        @edge-click="onEdgeClick"
        @pane-click="onPaneClick"
        :fit-view-on-init="false"
        :zoom-on-scroll="true"
        :zoom-on-pinch="true"
        :pan-on-scroll="false"
        :pan-on-scroll-mode="PanOnScrollMode.Free"
        :pan-on-drag="[1, 2]"
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
        <Controls :show-fit-view="true" :show-zoom="true" />
        <Background :pattern="BackgroundVariant.Dots" />
      </VueFlow>
    </div>

    <!-- Status -->
    <div v-if="statusMessage" class="status-message" :class="statusType">
      {{ statusMessage }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { VueFlow } from '@vue-flow/core'
import { Background, BackgroundVariant } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import { PanOnScrollMode } from '@vue-flow/core'
import { router } from '@inertiajs/vue3'
import StreamNest from '@/components/VueFlow/StreamNest.vue'
import AppNode from '@/components/VueFlow/AppNode.vue'
import { useAutoSave } from '../composables/useAutoSave'
import { useStatusMessage } from '../composables/useStatusMessage'
import { useAdminEdgeHandling } from '../composables/useAdminEdgeHandling'
import { 
  removeDuplicateEdges,
  getNodeColor,
  getEdgeColor,
  handleNodeDragStop,
  fitView as sharedFitView,
  initializeNodesWithLayout,
  applyAutomaticLayoutWithConstraints
} from '../composables/useVueFlowCommon'
import type { Node, Edge } from '@vue-flow/core'

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
}

const props = defineProps<Props>()

// Refs
const vueFlowRef = ref()
const saving = ref(false)
const selectedStream = ref(props.streamName)

// Reactive data
const nodes = ref<Node[]>([])
const edges = ref<Edge[]>([])
const layoutChanged = ref(false)
const vueFlowKey = ref(0) // Key to force VueFlow re-render
// Use admin-specific edge handling
const { selectedEdgeId, handleEdgeClick, handlePaneClick, updateAdminEdgeStyles, initializeAdminEdges } = useAdminEdgeHandling()

// Use auto-save functionality
const { autoSaveTimeout, scheduleAutoSave, clearAutoSave } = useAutoSave()

// Use status messages
const { statusMessage, statusType, showStatus } = useStatusMessage()

// Track original layout for reset
const originalLayout = ref<{
  nodes_layout?: Record<string, any>
  edges_layout?: any[]
  stream_config?: Record<string, any>
} | null>(null)

// Initialize layout
onMounted(() => {
  initializeLayout()
})

// Watch for stream changes
watch(() => props.streamName, () => {
  selectedStream.value = props.streamName
  initializeLayout()
})

function initializeLayout() {
  // Store original layout
  originalLayout.value = props.savedLayout ? JSON.parse(JSON.stringify(props.savedLayout)) : null
  
  // Check if we have saved layout data
  const hasSavedLayout = props.savedLayout?.nodes_layout && Object.keys(props.savedLayout.nodes_layout).length > 0
  
  // Initialize nodes with shared function
  nodes.value = initializeNodesWithLayout(
    props.nodes,
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

  // Apply layout and auto-save if needed
  setTimeout(() => {
    if (!hasSavedLayout && nodes.value.length > 0) {
      // Schedule auto-save for generated layout (immediate, but still debounced)
      markLayoutChanged()
      scheduleAutoSave(autoSaveLayout)
    }
    fitView()
  }, 100)
  
  // Reset status indicators
  layoutChanged.value = false
  autoSaveTimeout.value = null
}

function applyAutomaticLayout() {
  // Use the shared layout function
  applyAutomaticLayoutWithConstraints(nodes.value)
}

function fitView() {
  if (vueFlowRef.value) {
    vueFlowRef.value.fitView({ padding: 50 })
  }
}

function validateConnection(connection: any) {
  // Allow dragging interaction but prevent actual connection creation
  // This enables the visual feedback while blocking new connections
  console.log('Connection attempt blocked (visual dragging allowed):', {
    source: connection.source,
    target: connection.target,
    sourceHandle: connection.sourceHandle,
    targetHandle: connection.targetHandle
  })
  
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
  scheduleAutoSave(autoSaveLayout) // Schedule debounced auto-save
}

function onEdgeUpdate(params: any, newConnection?: any) {
  // Handle edge updates when user drags edge to new connection
  console.log('onEdgeUpdate called with params:', params)
  console.log('newConnection:', newConnection)
  
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
  
  console.log('Extracted data:', {
    oldEdge: oldEdge ? {
      id: oldEdge.id,
      source: oldEdge.source,
      target: oldEdge.target,
      sourceHandle: oldEdge.sourceHandle,
      targetHandle: oldEdge.targetHandle
    } : null,
    connection: connection ? {
      source: connection.source,
      target: connection.target,
      sourceHandle: connection.sourceHandle,
      targetHandle: connection.targetHandle
    } : null
  })
  
  // Guard against missing data
  if (!oldEdge || !connection) {
    console.error('onEdgeUpdate: oldEdge or connection is missing', { oldEdge, connection })
    return
  }
  
  // Check if the connection is only changing handles on the same nodes
  const sourceNodeChanged = connection.source !== oldEdge.source
  const targetNodeChanged = connection.target !== oldEdge.target
  
  if (sourceNodeChanged || targetNodeChanged) {
    console.log('Edge update blocked: Cannot connect to different nodes', {
      oldSource: oldEdge.source,
      newSource: connection.source,
      oldTarget: oldEdge.target,
      newTarget: connection.target
    })
    showStatus('Tidak bisa menghubungkan ke node yang berbeda.', 'error')
    return
  }
  
  console.log('Edge update allowed: Only handle positions changed', {
    source: connection.source,
    target: connection.target,
    oldSourceHandle: oldEdge.sourceHandle,
    newSourceHandle: connection.sourceHandle,
    oldTargetHandle: oldEdge.targetHandle,
    newTargetHandle: connection.targetHandle
  })
  
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

    // Force reactivity by creating a new array reference
    const newEdges = [...edges.value]
    newEdges[edgeIndex] = updatedEdge
    edges.value = newEdges
    
    // Apply proper styling
    edges.value = updateAdminEdgeStyles(edges.value)
    
    // Force VueFlow to re-render the edge with new handle positions
    nextTick(() => {
      if (vueFlowRef.value) {
        try {
          vueFlowRef.value.updateEdge(oldEdge.id, updatedEdge)
        } catch (error) {
          console.log('VueFlow updateEdge method not available, forcing re-render')
          vueFlowKey.value += 1 // Force re-render of VueFlow component
        }
      }
    })
    
    console.log('Edge handle positions updated successfully:', {
      edgeId: edgeId,
      sourceNode: connection.source,
      targetNode: connection.target,
      oldSourceHandle: oldEdge.sourceHandle || 'default',
      newSourceHandle: connection.sourceHandle || 'default',
      oldTargetHandle: oldEdge.targetHandle || 'default',
      newTargetHandle: connection.targetHandle || 'default'
    })
    
    markLayoutChanged()
    scheduleAutoSave(autoSaveLayout)
  } else {
    console.error('Edge not found for update:', oldEdge.id)
    showStatus('Gagal memperbarui koneksi', 'error')
  }
}

function onEdgeUpdateStart(event: any) {
  // Handle start of edge update
  console.log('Edge update started:', {
    event: event,
    edgeId: event.edge?.id,
    source: event.edge?.source,
    target: event.edge?.target,
    sourceHandle: event.edge?.sourceHandle,
    targetHandle: event.edge?.targetHandle,
    eventType: typeof event,
    hasEdge: !!event.edge
  })
}

function onEdgeUpdateEnd(event: any) {
  // Handle end of edge update
  console.log('Edge update ended:', {
    event: event,
    edgeId: event.edge?.id,
    source: event.edge?.source,
    target: event.edge?.target,
    sourceHandle: event.edge?.sourceHandle,
    targetHandle: event.edge?.targetHandle,
    eventType: typeof event,
    hasEdge: !!event.edge
  })
}

function onEdgeClick(event: any) {
  const clickedEdgeId = event.edge?.id
  if (!clickedEdgeId) {
    return
  }
  
  handleEdgeClick(clickedEdgeId)
  edges.value = updateAdminEdgeStyles(edges.value)
}

function onPaneClick(event: any) {
  handlePaneClick()
  edges.value = updateAdminEdgeStyles(edges.value)
}

function onNodesChange(changes: any[]) {
  
  // Update our reactive nodes array
  changes.forEach(change => {
    if (change.type === 'position' && change.item) {
      const nodeIndex = nodes.value.findIndex(n => n.id === change.item.id)
      if (nodeIndex !== -1) {
        nodes.value[nodeIndex].position = change.item.position
      }
    } else if (change.type === 'dimensions' && change.item) {
      const nodeIndex = nodes.value.findIndex(n => n.id === change.item.id)
      if (nodeIndex !== -1) {
        nodes.value[nodeIndex].style = {
          ...nodes.value[nodeIndex].style,
          ...change.item.style
        }
      }
    }
  })
  
  // Mark layout as changed if there are actual changes
  if (changes.length > 0) {
    markLayoutChanged()
  }
}

function onEdgesChange(changes: any[]) {
  // Handle edge changes (deletions, updates, etc.)
  console.log('Edges changed:', changes)
  
  // Update our reactive edges array
  changes.forEach(change => {
    if (change.type === 'remove' && change.id) {
      const edgeIndex = edges.value.findIndex(e => e.id === change.id)
      if (edgeIndex !== -1) {
        edges.value.splice(edgeIndex, 1)
        console.log('Edge removed:', change.id)
      }
    } else if (change.type === 'add' && change.item) {
      // Handle edge additions
      const existingEdgeIndex = edges.value.findIndex(e => e.id === change.item.id)
      if (existingEdgeIndex === -1) {
        edges.value.push(change.item)
        console.log('Edge added:', change.item.id)
      }
    } else if (change.type === 'update' && change.item) {
      // Handle edge updates
      const edgeIndex = edges.value.findIndex(e => e.id === change.item.id)
      if (edgeIndex !== -1) {
        edges.value.splice(edgeIndex, 1, change.item)
        console.log('Edge updated:', change.item.id)
      }
    }
  })
  
  // Mark layout as changed if there are actual changes
  if (changes.length > 0) {
    markLayoutChanged()
    scheduleAutoSave(autoSaveLayout)
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

    console.log('Saving edges layout:', edgesLayout)

    // Prepare stream config
    const streamConfig = {
      lastUpdated: new Date().toISOString(),
      totalNodes: currentNodes.length,
      totalEdges: edges.value.length
    }

    // Save to backend
    await router.post(`/admin/stream/${props.streamName}/layout`, {
      nodes_layout: nodesLayout,
      edges_layout: edgesLayout,
      stream_config: streamConfig
    }, {
      preserveState: true,
      onSuccess: () => {
        layoutChanged.value = false
        autoSaveTimeout.value = null // Clear auto-save indicator
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

async function autoSaveLayout() {
  if (saving.value) {
    return
  }
  
  try {
    // Set auto-save status
    autoSaveTimeout.value = 1

    // Prepare nodes layout for auto-save
    const nodesLayout: Record<string, any> = {}
    nodes.value.forEach(node => {
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

    // Ensure we have at least one node layout
    if (Object.keys(nodesLayout).length === 0) {
      autoSaveTimeout.value = null
      return
    }

    // Prepare edges layout for auto-save
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

    console.log('Auto-saving edges layout:', edgesLayout)

    // Prepare stream config
    const streamConfig = {
      lastUpdated: new Date().toISOString(),
      totalNodes: nodes.value.length,
      totalEdges: edges.value.length,
      autoSaved: true
    }

    // Auto-save to backend
    saving.value = true
    await router.post(`/admin/stream/${props.streamName}/layout`, {
      nodes_layout: nodesLayout,
      edges_layout: edgesLayout,
      stream_config: streamConfig
    }, {
      preserveState: true,
      onSuccess: () => {
        originalLayout.value = { 
          nodes_layout: nodesLayout, 
          edges_layout: edgesLayout,
          stream_config: streamConfig 
        }
        layoutChanged.value = false // Mark as saved
        autoSaveTimeout.value = null // Clear auto-save indicator
        showStatus('Layout auto-saved', 'success')
      },
      onError: (errors) => {
        console.error('Auto-save failed:', errors)
        autoSaveTimeout.value = null // Clear auto-save indicator on error
        showStatus('Auto-save gagal', 'error')
      }
    })
  } catch (error) {
    console.error('Auto-save error:', error)
    autoSaveTimeout.value = null // Clear auto-save indicator on error
    showStatus('Auto-save error', 'error')
  } finally {
    saving.value = false
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

function onStreamResize(event: { width: number, height: number }) {
  // Find the stream node and update its style
  const streamNodeIndex = nodes.value.findIndex(n => n.data.is_parent_node)
  if (streamNodeIndex !== -1) {
    const currentNode = nodes.value[streamNodeIndex]
    
    // Create updated node with new dimensions
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
    
    // Replace node to trigger reactivity
    nodes.value.splice(streamNodeIndex, 1, updatedNode)
    
    markLayoutChanged()
    scheduleAutoSave(autoSaveLayout)
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

.save-btn, .reset-btn {
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

.reset-btn {
  background: #6b7280;
  color: white;
  border: none;
}

.reset-btn:hover {
  background: #4b5563;
}

.admin-instructions {
  background: #f0f9ff;
  border-left: 4px solid #0284c7;
  padding: 0.75rem 2rem;
  margin: 0;
  border-bottom: 1px solid #e2e8f0;
}

.admin-instructions p {
  margin: 0;
  font-size: 0.875rem;
  color: #0c4a6e;
}

.instructions {
  background: #e0f2fe;
  border-left: 4px solid #0284c7;
  padding: 1rem 2rem;
  margin: 0;
}

.instructions p {
  margin: 0.25rem 0;
  font-size: 0.875rem;
  color: #0c4a6e;
}

.vue-flow-wrapper {
  flex: 1;
  position: relative;
}

.vue-flow.admin-mode {
  background: #f8fafc;
}

/* Admin-specific Node Styles */

/* Stream parent node specific styles */
:deep(.vue-flow__node-streamParent) {
  cursor: move !important;
}

:deep(.vue-flow__node-streamParent.dragging) {
  transition: none !important;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
}

:deep(.vue-flow__node-default) {
  background: transparent !important;
  border: none !important;
  padding: 0 !important;
}

/* Admin-specific custom node styling */
:deep(.vue-flow__node-custom) {
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

:deep(.vue-flow__node-custom:hover) {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
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

<style>
@import '../../css/vue-flow-integration.css';
</style>

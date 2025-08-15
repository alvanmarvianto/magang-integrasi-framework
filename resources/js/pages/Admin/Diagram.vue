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
      <!-- Layout Navbar -->
      <LayoutNavbar
        :title="`Stream Diagram - ${streamName}`"
        :layout-changed="layoutChanged"
        :saving="saving"
        :refreshing="refreshing"
        :show-layout-selector="true"
        :show-refresh-button="true"
        :allowed-streams="allowedStreams"
        :function-apps="props.functionApps"
        :current-stream="selectedStream"
        :current-app-id="selectedAppId"
        @save="saveLayout"
        @refresh="refreshLayout"
        @reset="resetLayout"
        @stream-change="onStreamChange"
        @app-change="onAppChange"
      />

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
          <component :is="StreamNestComponent" v-bind="nodeProps" :admin-mode="true" @resize="onStreamResize" />
        </template>

        <!-- Custom App Node with Handles -->
        <template #node-app="nodeProps">
          <component :is="AppNodeComponent" v-bind="nodeProps" :admin-mode="true" />
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
import { ref, onMounted, watch, nextTick, markRaw } from 'vue'
import { VueFlow, useVueFlow } from '@vue-flow/core'
import { Background, BackgroundVariant } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import { PanOnScrollMode } from '@vue-flow/core'
import { router, usePage } from '@inertiajs/vue3'
import StreamNest from '@/components/VueFlow/StreamNest.vue'
import AppNode from '@/components/VueFlow/AppNode.vue'
import LayoutNavbar from '@/components/Admin/LayoutNavbar.vue'
import DetailsSidebar from '@/components/Sidebar/DetailsSidebar.vue'
import ErrorState from '@/components/ErrorState.vue'
import { useAdminLayout } from '@/composables/useAdminLayout'
import { 
  removeDuplicateEdges,
  moveEdgeToTop,
  applyAutomaticLayoutWithConstraints,
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
  functionApps?: { app_id: number; app_name: string }[]
  refreshData?: {
    nodes: Node[]
    edges: Edge[]
  }
  error?: string | null
}

const props = defineProps<Props>()

// Get page props for flash messages
const page = usePage()

// Use the shared admin layout composable
const {
  vueFlowRef,
  saving,
  showDetails,
  detailType,
  selectedEdgeData,
  selectedNodeData,
  nodes,
  edges,
  layoutChanged,
  vueFlowKey,
  statusMessage,
  statusType,
  showStatus,
  defaultEdgeOptions,
  onWheel,
  onContextMenu,
  onNodeDragStop,
  onEdgeUpdate: baseOnEdgeUpdate,
  onEdgeClick: baseOnEdgeClick,
  onNodeClick,
  onPaneClick: baseOnPaneClick,
  onNodesChange: baseOnNodesChange,
  onEdgesChange: baseOnEdgesChange,
  initializeLayout,
  fitView,
  validateConnection,
  resetLayout,
  closeDetails,
} = useAdminLayout({
  savedLayout: props.savedLayout,
  nodes: props.nodes,
  edges: props.edges,
  allowedStreams: props.allowedStreams,
  pageProps: page.props,
})

// Refs specific to stream diagram
const refreshing = ref(false)
const selectedStream = ref(props.streamName)
const selectedAppId = ref<number | ''>('')

// Components for template
const StreamNestComponent = markRaw(StreamNest)
const AppNodeComponent = markRaw(AppNode)

// Get VueFlow instance for stream-specific functionality
const { zoomIn, zoomOut, setViewport, getViewport } = useVueFlow()

// Initialize layout on mount
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

watch(() => props.functionApps, () => {
  // reset selection when function app list changes
  selectedAppId.value = ''
})

// Enhanced edge update for stream diagrams (includes more complex logic)
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

    layoutChanged.value = true
  } else {
    console.error('Edge not found for update:', oldEdge.id)
    showStatus('Gagal memperbarui koneksi', 'error')
  }
}

// Enhanced edge click for stream diagrams
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
  
  // Use base functionality
  baseOnEdgeClick(event)
}

// Enhanced pane click for stream diagrams
function onPaneClick(event: any) {
  baseOnPaneClick(event)
  
  // Close details when clicking on pane
  if (showDetails.value) {
    closeDetails()
  }
}

// Enhanced nodes change for stream diagrams
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
  
  // Mark layout as changed only if there are actual layout changes
  if (hasLayoutChanges) {
    layoutChanged.value = true
  }
}

// Enhanced edges change for stream diagrams
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
  
  // Mark layout as changed only if there are actual layout changes
  if (hasLayoutChanges) {
    layoutChanged.value = true
  }
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

async function switchApp() {
  // Navigate to app layout admin page (separate URL)
  if (selectedAppId.value === '') {
    return
  }

  if (layoutChanged.value) {
    showStatus('Simpan perubahan sebelum mengganti diagram', 'error')
    return
  }

  // Navigate to the app layout admin page
  router.visit(`/admin/app/${selectedAppId.value}/layout/admin`)
}

// Event handlers for LayoutNavbar
function onStreamChange(stream: string) {
  if (stream !== props.streamName) {
    if (layoutChanged.value) {
      // Reset the selector to current stream and show warning
      selectedStream.value = props.streamName
      showStatus('Simpan perubahan sebelum keluar dari halaman', 'error')
    } else {
      router.get(`/admin/stream/${stream}`)
    }
  }
}

function onAppChange(appId: number | string) {
  // Navigate to app layout admin page (separate URL)
  if (appId === '') {
    return
  }

  if (layoutChanged.value) {
    showStatus('Simpan perubahan sebelum mengganti diagram', 'error')
    return
  }

  // Navigate to the app layout admin page
  router.visit(`/admin/app/${appId}/layout/admin`)
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
    
    layoutChanged.value = true
  } else {
    console.warn('Stream node not found for resize')
  }
}
</script>

<style scoped>
/* Import shared admin layout styles */
@import '@/../css/admin-layout.css';
@import '@/../css/vue-flow-integration.css';
</style>

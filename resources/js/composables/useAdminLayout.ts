import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { useVueFlow } from '@vue-flow/core'
import { router } from '@inertiajs/vue3'
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

interface UseAdminLayoutOptions {
  savedLayout?: {
    nodes_layout?: Record<string, any>
    edges_layout?: any[]
    stream_config?: Record<string, any>
    app_config?: Record<string, any>
  } | null
  nodes: Node[]
  edges: Edge[]
  allowedStreams?: string[]
  pageProps?: any
  disableArrowMarkers?: boolean
}

export function useAdminLayout(options: UseAdminLayoutOptions) {
  const { savedLayout, nodes: initialNodes, edges: initialEdges, allowedStreams = [], pageProps, disableArrowMarkers = false } = options

  // Refs
  const vueFlowRef = ref()
  const saving = ref(false)
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

  // Use admin-specific edge handling
  const forceNoArrow = disableArrowMarkers || !!(savedLayout as any)?.stream_config?.forceEdgeBlackNoArrow || !!(savedLayout as any)?.app_config?.forceEdgeBlackNoArrow
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

  // Use status messages
  const { statusMessage, statusType, showStatus } = useStatusMessage()

  // Track original layout for reset
  const originalLayout = ref<{
    nodes_layout?: Record<string, any>
    edges_layout?: any[]
    stream_config?: Record<string, any>
    app_config?: Record<string, any>
  } | null>(null)

  // Default edge options
  const defaultEdgeOptions = {
    type: 'smoothstep',
    style: {
      stroke: '#374151',
      strokeWidth: 2,
    },
  }

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

  function initializeLayout() {
    // Set initializing flag to prevent change events from marking layout as changed
    isInitializing.value = true
    
    // Reset layout changed status to show "Tersimpan" initially
    layoutChanged.value = false
    
    // Store original layout
    originalLayout.value = savedLayout ? JSON.parse(JSON.stringify(savedLayout)) : null
    const cleanedNodes = validateAndCleanNodes(initialNodes);
    
    // Check if we have saved layout data
    const hasSavedLayout = savedLayout?.nodes_layout && Object.keys(savedLayout.nodes_layout).length > 0
    
    // Initialize nodes with shared function
    nodes.value = initializeNodesWithLayout(
      cleanedNodes,
      savedLayout,
      true, // Admin mode
      allowedStreams,
      pageProps?.config?.node_types || []
    )
    
    // Apply automatic layout if no saved layout
    if (!hasSavedLayout) {
      setTimeout(() => {
        applyAutomaticLayoutWithConstraints(nodes.value)
      }, 100)
    }
    
    // Initialize edges with admin-specific function
    edges.value = initializeAdminEdges(
      initialEdges,
      savedLayout,
      removeDuplicateEdges
    )

    // Apply layout
    setTimeout(() => {
      fitView()
      isInitializing.value = false
    }, 200)
  }

  function fitView() {
    if (vueFlowRef.value) {
      sharedFitView(vueFlowRef.value)
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
      nodes.value[nodeIndex].position = { ...node.position }
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
      showStatus('Edge tidak ditemukan', 'error')
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
      detailType.value = 'edge'
      showDetails.value = true
    }
    
    // Move the clicked edge to the top so it renders on top and is easier to manipulate
    edges.value = moveEdgeToTop(edges.value, clickedEdgeId)
    
    handleEdgeClick(clickedEdgeId)
    edges.value = updateAdminEdgeStylesWithSelection(edges.value)
  }

  function onNodeClick(event: any) {
    const clickedNodeId = event.node?.id
    if (!clickedNodeId) {
      return
    }
    
    // Find the node data for the sidebar
    const node = nodes.value.find(n => n.id === clickedNodeId)
    if (node && node.data && !node.data.is_parent_node) {
      selectedNodeData.value = node.data
      detailType.value = 'node'
      showDetails.value = true
    }
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
  }

  function onNodesChange(changes: any[]) {
    let hasLayoutChanges = false
    
    // Update our reactive nodes array
    changes.forEach(change => {
      if (change.type === 'position' && change.position) {
        const nodeIndex = nodes.value.findIndex(n => n.id === change.id)
        if (nodeIndex !== -1) {
          nodes.value[nodeIndex].position = change.position
          hasLayoutChanges = true
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
      if (change.type === 'add' || change.type === 'remove') {
        hasLayoutChanges = true
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

  function resetLayout() {
    if (originalLayout.value) {
      initializeLayout()
      showStatus('Layout kembali ke semula', 'info')
    } else {
      // Apply automatic layout
      applyAutomaticLayoutWithConstraints(nodes.value)
      setTimeout(() => fitView(), 100)
      showStatus('Layout otomatis diterapkan', 'info')
    }
  }

  return {
    // Refs
    vueFlowRef,
    saving,
    showDetails,
    detailType,
    selectedEdgeData,
    selectedNodeData,
    
    // Reactive data
    nodes,
    edges,
    layoutChanged,
    vueFlowKey,
    isInitializing,
    
    // Status
    statusMessage,
    statusType,
    showStatus,
    
    // VueFlow options
    defaultEdgeOptions,
    onWheel,
    onContextMenu,
    
    // Event handlers
    onNodeDragStop,
    onEdgeUpdate,
    onEdgeClick,
    onNodeClick,
    onPaneClick,
    onNodesChange,
    onEdgesChange,
    
    // Layout functions
    initializeLayout,
    fitView,
    validateConnection,
    markLayoutChanged,
    resetLayout,
    closeDetails,
  }
}

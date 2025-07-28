import { ref } from 'vue'
import type { Edge } from '@vue-flow/core'
import { getEdgeColor } from './useVueFlowCommon'

/**
 * Admin-specific edge handling composable
 * Handles edge selection, dragging endpoints, and admin-specific edge operations
 */
export function useAdminEdgeHandling() {
  const selectedEdgeId = ref<string | null>(null)

  /**
   * Handle edge click for admin mode - toggles selection
   */
  function handleEdgeClick(clickedEdgeId: string) {
    if (selectedEdgeId.value === clickedEdgeId) {
      selectedEdgeId.value = null
    } else {
      selectedEdgeId.value = clickedEdgeId
    }
  }

  /**
   * Handle pane click for admin mode - deselects edges
   */
  function handlePaneClick() {
    if (selectedEdgeId.value) {
      selectedEdgeId.value = null
    }
  }

  /**
   * Update edge styles for admin mode with selection and dragging capabilities
   */
  function updateAdminEdgeStyles(edges: Edge[]): Edge[] {
    return edges.map(edge => {
      const edgeColor = getEdgeColor(edge.data?.connection_type || 'direct', true) // Admin mode
      const isSelected = selectedEdgeId.value === edge.id
      const isBothWays = edge.data?.direction === 'both_ways'
      
      const styledEdge = {
        ...edge,
        type: 'smoothstep',
        updatable: true, // Enable endpoint dragging in admin mode
        animated: isSelected,
        style: {
          ...edge.style,
          stroke: edgeColor,
          strokeWidth: isSelected ? 4 : 2, // Keep consistent stroke width
          strokeDasharray: isSelected ? undefined : edge.style?.strokeDasharray,
        },
        markerEnd: {
          type: 'arrowclosed',
          color: edgeColor,
        } as any,
      }

      // Add arrow at the start for bidirectional connections
      if (isBothWays) {
        styledEdge.markerStart = {
          type: 'arrowclosed',
          color: edgeColor,
        } as any
      }

      return styledEdge
    })
  }

  /**
   * Initialize edges with admin-specific styling and capabilities
   */
  function initializeAdminEdges(
    inputEdges: Edge[],
    savedLayout: any,
    removeDuplicateEdges: (edges: Edge[]) => Edge[]
  ): Edge[] {
    let edgesData = inputEdges
    
    // Check if we have saved edge layout with handle information
    if (savedLayout?.edges_layout && savedLayout.edges_layout.length > 0) {
      edgesData = savedLayout.edges_layout
    }
    
    return removeDuplicateEdges(edgesData).map(edge => {
      const edgeColor = getEdgeColor(edge.data?.connection_type || 'direct', true)
      const isSelected = selectedEdgeId.value === edge.id
      const isBothWays = edge.data?.direction === 'both_ways'
      
      const styledEdge = {
        ...edge,
        type: 'smoothstep',
        updatable: true, // Enable endpoint dragging
        animated: isSelected,
        style: {
          stroke: edgeColor,
          strokeWidth: 2, // Keep consistent stroke width
          ...(edge.style || {})
        },
        markerEnd: {
          type: 'arrowclosed',
          color: edgeColor,
        } as any,
        // Preserve saved handle information
        sourceHandle: edge.sourceHandle || undefined,
        targetHandle: edge.targetHandle || undefined,
      }

      // Add arrow at the start for bidirectional connections
      if (isBothWays) {
        styledEdge.markerStart = {
          type: 'arrowclosed',
          color: edgeColor,
        } as any
      }

      return styledEdge
    })
  }

  return {
    selectedEdgeId,
    handleEdgeClick,
    handlePaneClick,
    updateAdminEdgeStyles,
    initializeAdminEdges,
  }
}

import { ref } from 'vue'
import type { Edge } from '@vue-flow/core'
import { getEdgeColor } from './useVueFlowCommon'

/**
 * Admin-specific edge handling composable
 * Handles edge selection, dragging endpoints, and admin-specific edge operations
 */
export function useAdminEdgeHandling(options?: { disableMarkers?: boolean; forceBlack?: boolean }) {
  const selectedEdgeId = ref<string | null>(null)
  const disableMarkers = !!options?.disableMarkers
  const forceBlack = !!options?.forceBlack

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
      // Preserve original color from backend or fall back to calculated color
      const originalColor = edge.style?.stroke
      const computed = getEdgeColor(edge.data?.connection_type || 'direct', edge.data?.color, true)
      const edgeColor = forceBlack ? '#000000' : (originalColor || computed)
      const isSelected = selectedEdgeId.value === edge.id
      const isBothWays = edge.data?.direction === 'both_ways'
      
      const styledEdge = {
        ...edge,
        type: 'smoothstep',
        updatable: true, // Enable endpoint dragging in admin mode
        animated: isSelected,
        style: {
          ...edge.style,
          stroke: edgeColor, // Use preserved or forced color
          strokeWidth: isSelected ? 4 : 2,
          strokeDasharray: isSelected ? undefined : edge.style?.strokeDasharray,
        },
        ...(disableMarkers ? {} : {
          markerEnd: {
            type: 'arrowclosed',
            color: edgeColor,
          } as any,
        }),
      }

      // Add arrow at the start for bidirectional connections
      if (isBothWays && !disableMarkers) {
        (styledEdge as any).markerStart = {
          type: 'arrowclosed',
          color: edgeColor,
        } as any
      }

      return styledEdge
    })
  }

  /**
   * Update edge styles with current selection for admin mode
   */
  function updateAdminEdgeStylesWithSelection(edges: Edge[]): Edge[] {
    return updateAdminEdgeStyles(edges)
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
      // Create a map of fresh edge data by edge ID
      const freshEdgeMap = new Map<string, Edge>();
      inputEdges.forEach(edge => {
        freshEdgeMap.set(edge.id, edge);
      });
      
      // Merge saved layout with fresh data
      edgesData = savedLayout.edges_layout.map((savedEdge: any) => {
        const freshEdge = freshEdgeMap.get(savedEdge.id);
        if (freshEdge) {
          // Use fresh data but preserve layout-specific properties
          return {
            ...freshEdge,
            sourceHandle: savedEdge.sourceHandle,
            targetHandle: savedEdge.targetHandle,
            style: savedEdge.style || freshEdge.style,
            // Preserve all fresh data and only override layout-specific properties
            data: {
              ...freshEdge.data, // Keep all backend data
              // Override only layout-specific properties if they exist in saved data
              ...(savedEdge.data && {
                // Prefer backend (fresh) connection metadata; fall back to saved only if backend missing
                connection_type: freshEdge.data?.connection_type || savedEdge.data.connection_type || 'direct',
                direction: freshEdge.data?.direction || savedEdge.data.direction || 'one_way',
                // Keep saved label/source/target names if they exist, but prefer backend names
                source_app_name: freshEdge.data?.sourceApp?.app_name || freshEdge.data?.source_app_name || savedEdge.data.source_app_name,
                target_app_name: freshEdge.data?.targetApp?.app_name || freshEdge.data?.target_app_name || savedEdge.data.target_app_name,
              })
            }
          };
        }
        // If no fresh data found, enhance saved edge with default values
        return {
          ...savedEdge,
          data: {
            ...savedEdge.data,
            connection_type: savedEdge.data?.connection_type || 'direct',
            direction: savedEdge.data?.direction || 'one_way',
            source_app_name: savedEdge.data?.source_app_name || 'Unknown App',
            target_app_name: savedEdge.data?.target_app_name || 'Unknown App',
          }
        };
      });
      
      // Add any new edges that weren't in the saved layout
      inputEdges.forEach(edge => {
        const existsInSaved = savedLayout.edges_layout.some((savedEdge: any) => savedEdge.id === edge.id);
        if (!existsInSaved) {
          edgesData.push(edge);
        }
      });
    }
    
    return removeDuplicateEdges(edgesData).map(edge => {
      // Preserve original color from backend or fall back to calculated color
      const originalColor = edge.style?.stroke
      const computed = getEdgeColor(edge.data?.connection_type || 'direct', edge.data?.color, true)
      const edgeColor = forceBlack ? '#000000' : (originalColor || computed)
      const isSelected = selectedEdgeId.value === edge.id
      const isBothWays = edge.data?.direction === 'both_ways'
      
      const styledEdge = {
        ...edge,
        type: 'smoothstep',
        updatable: true, // Enable endpoint dragging
        animated: isSelected,
        style: {
          stroke: edgeColor, // Use preserved or forced color
          strokeWidth: 2, // Keep consistent stroke width
          ...(edge.style || {})
        },
        ...(disableMarkers ? {} : {
          markerEnd: {
            type: 'arrowclosed',
            color: edgeColor,
          } as any,
        }),
        // Preserve saved handle information and ensure proper data
        sourceHandle: edge.sourceHandle || undefined,
        targetHandle: edge.targetHandle || undefined,
        data: {
          // Spread all existing data first to preserve backend fields
          ...edge.data,
          // Then override with defaults only if missing
          connection_type: edge.data?.connection_type || 'direct',
          direction: edge.data?.direction || 'one_way',
        }
      }

      // Add arrow at the start for bidirectional connections
      if (isBothWays && !disableMarkers) {
        (styledEdge as any).markerStart = {
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
    updateAdminEdgeStylesWithSelection,
    initializeAdminEdges,
  }
}

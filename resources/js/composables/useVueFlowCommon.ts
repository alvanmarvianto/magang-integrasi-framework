import { ref, Ref } from 'vue'
import type { Node, Edge } from '@vue-flow/core';

/**
 * Common Vue Flow utilities and functions
 * Shared between AdminVueFlowStream and VueFlowStreamIntegration
 */

// Layout constants
export const LAYOUT_CONSTANTS = {
  STREAM_PADDING: 20,
  STREAM_MIN_WIDTH: 300,
  STREAM_MIN_HEIGHT: 200,
  NODE_WIDTH: 120,
  NODE_HEIGHT: 80,
  NODES_PER_ROW: 2,
  NODE_SPACING_X: 150,
  NODE_SPACING_Y: 110,
} as const;

// Color schemes
const NODE_COLORS: { [key: string]: { background: string; border: string } } = {
  'sp': { background: '#f5f5f5', border: '#000000' },
  'mi': { background: '#fff5f5', border: '#ff0000' },
  'ssk': { background: '#fffef0', border: '#fbff00' },
  'ssk-mon': { background: '#fffef0', border: '#fbff00' },
  'moneter': { background: '#fffef0', border: '#fbff00' },
  'market': { background: '#fdf4ff', border: '#dd00ff' },
  'internal bi': { background: '#f0fff4', border: '#00ff48' },
  'external bi': { background: '#eff6ff', border: '#0a74da' },
  'middleware': { background: '#f0fffe', border: '#00ddff' },
};

/**
 * Get node color based on lingkup/stream
 */
export function getNodeColor(lingkupOrParams: string | { lingkup: string; allStreams?: any[] }, isAdminMode: boolean = false): { background: string; border: string } {
  // Handle both old signature (string) and new signature (object)
  let lingkup: string;
  
  if (typeof lingkupOrParams === 'string') {
    lingkup = lingkupOrParams;
  } else {
    lingkup = lingkupOrParams.lingkup || '';
    
    // If allStreams is provided but empty, log the issue
    if (lingkupOrParams.allStreams !== undefined && lingkupOrParams.allStreams.length === 0) {
    }
  }
  
  // Normalize lingkup for matching
  const normalizedLingkup = lingkup.toLowerCase().replace(/^stream\s+/, '');
  
  const colorMap = NODE_COLORS;
  
  // Try exact match first
  if (colorMap[lingkup]) {
    return colorMap[lingkup];
  }
  
  // Try normalized match
  if (colorMap[normalizedLingkup]) {
    return colorMap[normalizedLingkup];
  }
  
  // Try some common mappings
  const mappings: { [key: string]: string } = {
    'stream sp': 'sp',
    'stream mi': 'mi', 
    'stream ssk': 'ssk',
    'stream moneter': 'moneter',
    'stream market': 'market',
    'stream internal': 'internal bi',
    'stream eksternal': 'external bi',
    'middleware': 'middleware'
  };
  
  const mappedKey = mappings[lingkup.toLowerCase()];
  if (mappedKey && colorMap[mappedKey]) {
    return colorMap[mappedKey];
  }
  
  // Default fallback
  return { background: '#ffffff', border: '#6b7280' };
}

/**
 * Get edge color based on connection type or use provided color
 */
export function getEdgeColor(type: string, color?: string, isAdminMode: boolean = false): string {
  // If color is provided directly, use it
  if (color && color !== '#000000' && color !== '') {
    return color;
  }
  
  // Fallback to hardcoded colors for backward compatibility
  const fallbackColors: { [key: string]: string } = {
    'direct': '#000000',
    'soa': '#02a330', 
    'sftp': '#002ac0',
    'soa-sftp': '#6b7280',
  };
  
  return fallbackColors[type] || '#6b7280';
}

/**
 * Remove duplicate edges between the same pair of nodes
 */
export function removeDuplicateEdges(inputEdges: Edge[]): Edge[] {
  // Map to keep only one edge per source-target pair, but if an edge is updated (appears later), it will be placed at the top
  const connectionMap = new Map<string, Edge>();
  const order: string[] = [];

  inputEdges.forEach(edge => {
    const connectionKey = `${edge.source}-${edge.target}`;
    // If already exists, remove from order (so the latest will be at the top)
    if (connectionMap.has(connectionKey)) {
      const idx = order.indexOf(connectionKey);
      if (idx !== -1) order.splice(idx, 1);
    }
    connectionMap.set(connectionKey, edge);
    order.unshift(connectionKey); // Always put the latest at the front
  });

  // Return edges in the order of most recently updated first
  return order.map(key => connectionMap.get(key)!);
}

/**
 * Move a specific edge to the top of the rendering order (end of array)
 */
export function moveEdgeToTop(edges: Edge[], edgeId: string): Edge[] {
  const edgeIndex = edges.findIndex(e => e.id === edgeId);
  if (edgeIndex === -1) {
    return edges; // Edge not found, return original array
  }
  
  const targetEdge = edges[edgeIndex];
  const newEdges = [...edges];
  
  // Remove the edge from its current position
  newEdges.splice(edgeIndex, 1);
  
  // Add it to the end (top rendering order in Vue Flow)
  newEdges.push(targetEdge);
  
  return newEdges;
}

/**
 * Validate node data and ensure required properties
 */
export function validateAndCleanNodes(nodes: Node[]): Node[] {
  return nodes.reduce((acc, node) => {
    if (!acc.find(n => n.id === node.id)) {
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
      };
      acc.push(validNode);
    } else {
      console.warn('Duplicate node found and removed:', node.id);
    }
    return acc;
  }, [] as Node[]);
}

/**
 * Apply automatic layout with constraints for nodes
 */
export function applyAutomaticLayoutWithConstraints(nodes: Node[]): void {
  const {
    STREAM_PADDING,
    STREAM_MIN_WIDTH,
    STREAM_MIN_HEIGHT,
    NODE_WIDTH,
    NODE_HEIGHT,
    NODES_PER_ROW,
    NODE_SPACING_X,
    NODE_SPACING_Y,
  } = LAYOUT_CONSTANTS;

  // Separate nodes by type
  const streamNode = nodes.find(n => n.data.is_parent_node);
  const homeStreamNodes = nodes.filter(n => n.data.is_home_stream && !n.data.is_parent_node);
  const externalNodes = nodes.filter(n => !n.data.is_home_stream && !n.data.is_parent_node);

  // Calculate stream dimensions based on content
  const nodeCount = homeStreamNodes.length;
  const rows = Math.ceil(nodeCount / NODES_PER_ROW);
  const cols = Math.min(nodeCount, NODES_PER_ROW);
  
  const contentWidth = cols * NODE_WIDTH + (cols - 1) * (NODE_SPACING_X - NODE_WIDTH);
  const contentHeight = rows * NODE_HEIGHT + (rows - 1) * (NODE_SPACING_Y - NODE_HEIGHT);
  
  const streamWidth = Math.max(STREAM_MIN_WIDTH, contentWidth + 2 * STREAM_PADDING);
  const streamHeight = Math.max(STREAM_MIN_HEIGHT, contentHeight + 2 * STREAM_PADDING + 40);

  // Position stream parent node
  if (streamNode) {
    streamNode.position = { x: 50, y: 50 };
    if (streamNode.style) {
      streamNode.style.width = `${streamWidth}px`;
      streamNode.style.height = `${streamHeight}px`;
    }
    
    if (streamNode.data) {
      streamNode.data.dimensions = {
        width: streamWidth,
        height: streamHeight
      };
    }
  }

  // Position home stream nodes
  homeStreamNodes.forEach((node, nodeIndex) => {
    const row = Math.floor(nodeIndex / NODES_PER_ROW);
    const col = nodeIndex % NODES_PER_ROW;
    
    const x = 50 + STREAM_PADDING + col * NODE_SPACING_X;
    const y = 50 + STREAM_PADDING + 40 + row * NODE_SPACING_Y;
    
    node.position = { x, y };
  });

  // Position external nodes around the stream
  const streamCenterX = 50 + streamWidth / 2;
  const streamCenterY = 50 + streamHeight / 2;
  const radius = Math.max(streamWidth, streamHeight) / 2 + 100;

  externalNodes.forEach((node, index) => {
    const angle = (index * 2 * Math.PI) / Math.max(externalNodes.length, 1);
    const x = streamCenterX + radius * Math.cos(angle) - NODE_WIDTH / 2;
    const y = streamCenterY + radius * Math.sin(angle) - NODE_HEIGHT / 2;
    
    node.position = { x, y };
  });
}

// Common style constants
const STREAM_NODE_STYLE = {
  backgroundColor: 'rgba(59, 130, 246, 0.3)',
  border: '2px solid #3b82f6',
  borderRadius: '8px',
};

const APP_NODE_STYLE = {
  width: '120px',
  height: '80px',
  borderRadius: '8px',
};

/**
 * Create node with appropriate styling
 */
export function createStyledNode(node: any, savedLayout?: any, isAdmin: boolean = false): Node {
  const nodeColors = getNodeColor(node.data?.lingkup || '', isAdmin);
  
  const newNode: Node = {
    id: node.id,
    type: node.data.is_parent_node ? 'stream' : 'app',
    position: savedLayout?.position || { x: 0, y: 0 },
    data: node.data,
    draggable: isAdmin ? true : !node.data.is_parent_node,
    selectable: false,
    connectable: false,
    focusable: true,
    deletable: false,
    zIndex: node.data.is_parent_node ? (isAdmin ? -10 : -1) : (isAdmin ? 10 : 1)
  };
  
  if (node.data.is_parent_node) {
    // Stream node
    newNode.style = {
      cursor: isAdmin ? 'grab' : 'default',
      ...STREAM_NODE_STYLE,
      width: savedLayout?.dimensions?.width ? `${savedLayout.dimensions.width}px` : '300px',
      height: savedLayout?.dimensions?.height ? `${savedLayout.dimensions.height}px` : '200px'
    };
    
    if (savedLayout?.dimensions) {
      newNode.data = {
        ...newNode.data,
        dimensions: savedLayout.dimensions
      };
    }
  } else {
    // App node
    newNode.style = {
      ...APP_NODE_STYLE,
      backgroundColor: nodeColors.background,
      border: `2px solid ${nodeColors.border}`,
    };
  }
  
  return newNode;
}

/**
 * Create edge with appropriate styling
 */
export function createStyledEdge(edge: any, selectedEdgeId?: string): Edge {
  const edgeColor = getEdgeColor(edge.data?.connection_type || 'direct', edge.data?.color);
  const isSelected = selectedEdgeId === edge.id;
  const isBothWays = edge.data?.direction === 'both_ways';
  
  const styledEdge: Edge = {
    ...edge,
    type: 'smoothstep',
    updatable: false,
    animated: isSelected,
    style: {
      ...(edge.style || {}),
      strokeWidth: isSelected ? 4 : 2, 
      stroke: edgeColor,
    },
    markerEnd: {
      type: 'arrowclosed',
      color: edgeColor,
    } as any,
    sourceHandle: edge.sourceHandle || undefined,
    targetHandle: edge.targetHandle || undefined,
    data: {
      ...edge.data
    }
  };

  // Add arrow at the start for bidirectional connections
  if (isBothWays) {
    styledEdge.markerStart = {
      type: 'arrowclosed',
      color: edgeColor,
    } as any;
  }

  return styledEdge;
}

/**
 * Update edge styles based on selection
 */
export function updateEdgeStyles(edges: Edge[], selectedEdgeId?: string): Edge[] {
  return edges.map(edge => {
    // Preserve original color from backend or fall back to calculated color
    const originalColor = edge.style?.stroke
    const edgeColor = originalColor || getEdgeColor(edge.data?.connection_type || 'direct', edge.data?.color)
    const isSelected = selectedEdgeId === edge.id;
    const isBothWays = edge.data?.direction === 'both_ways';
    
    const updatedEdge = {
      ...edge,
      animated: isSelected,
      style: {
        ...edge.style,
        stroke: edgeColor, // Use preserved color
        strokeWidth: isSelected ? 4 : 2,
      },
      markerEnd: {
        type: 'arrowclosed',
        color: edgeColor,
      } as any,
    };

    // Add arrow at the start for bidirectional connections
    if (isBothWays) {
      updatedEdge.markerStart = {
        type: 'arrowclosed',
        color: edgeColor,
      } as any;
    }

    return updatedEdge;
  });
}

/**
 * Common edge click handler logic
 */
export function useEdgeSelection() {
  const selectedEdgeId = ref<string | null>(null);
  
  function handleEdgeClick(clickedEdgeId: string) {
    if (selectedEdgeId.value === clickedEdgeId) {
      selectedEdgeId.value = null;
    } else {
      selectedEdgeId.value = clickedEdgeId;
    }
  }
  
  function handlePaneClick() {
    if (selectedEdgeId.value) {
      selectedEdgeId.value = null;
    }
  }
  
  function updateEdgeStylesWithSelection(edges: Edge[]): Edge[] {
    return updateEdgeStyles(edges, selectedEdgeId.value ?? undefined);
  }
  
  return {
    selectedEdgeId,
    handleEdgeClick,
    handlePaneClick,
    updateEdgeStylesWithSelection,
  };
}

/**
 * Handle node click events
 */
export function handleNodeClick(node: any, isAdminMode: boolean = false, allowedStreams: string[] = []) {
  
  if (!isAdminMode && node.type === 'app' && node.id) {
    // Check if the node's stream is in allowed streams
    const nodeStream = node.data?.lingkup || node.data?.stream_name;
    if (nodeStream && allowedStreams.length > 0) {
      // Convert both to lowercase for case-insensitive comparison
      const isAllowed = allowedStreams.some(allowedStream => 
        allowedStream.toLowerCase() === nodeStream.toLowerCase()
      );
      
      if (!isAllowed) {
        return; // Don't navigate for restricted streams
      }
    }
    
    // In user mode, redirect to app integration page
    window.location.href = `/integration/app/${node.id}`;
  }
  
  // In admin mode, node clicks are handled by the admin-specific logic
}

/**
 * Handle node drag stop events
 */
export function handleNodeDragStop(event: any, nodes: Ref<Node[]>) {
  const { node } = event;
  const nodeIndex = nodes.value.findIndex(n => n.id === node.id);
  if (nodeIndex !== -1) {
    nodes.value[nodeIndex].position = node.position;
  }
}

/**
 * Fit view utility
 */
export function fitView(vueFlowRef: any, padding: number = 50) {
  if (vueFlowRef?.value) {
    vueFlowRef.value.fitView({ padding });
  }
}

/**
 * Initialize nodes with layout data
 */
export function initializeNodesWithLayout(
  inputNodes: Node[],
  savedLayout: any,
  isAdminMode: boolean = false,
  allowedStreams: string[] = []
): Node[] {
  const uniqueNodes = validateAndCleanNodes(inputNodes);
  const hasSavedLayout = savedLayout?.nodes_layout && Object.keys(savedLayout.nodes_layout).length > 0;

  return uniqueNodes.map(node => {
    const savedNode = savedLayout?.nodes_layout?.[node.id];
    const nodeColors = getNodeColor(node.data?.lingkup || '', isAdminMode);
    
    // Check if node is clickable based on allowed streams
    const nodeStream = node.data?.lingkup || node.data?.stream_name;
    const isClickable = isAdminMode || !nodeStream || allowedStreams.length === 0 || 
      allowedStreams.some(allowedStream => allowedStream.toLowerCase() === nodeStream.toLowerCase());
    
    // Use saved position if available, otherwise use default or input position
    let nodePosition = { x: 0, y: 0 };
    if (hasSavedLayout && savedNode?.position) {
      nodePosition = savedNode.position;
    } else if (node.position) {
      nodePosition = node.position;
    }
    
    const newNode: Node = {
      id: node.id,
      type: node.data.is_parent_node ? 'stream' : 'app',
      position: nodePosition,
      data: node.data,
      draggable: isAdminMode || !node.data.is_parent_node, // Stream nodes not draggable in user mode
      selectable: isAdminMode,
      connectable: isAdminMode,
      focusable: true,
      deletable: isAdminMode,
      zIndex: node.data.is_parent_node ? (isAdminMode ? -10 : -1) : (isAdminMode ? 10 : 1)
    };
    
    if (node.data.is_parent_node) {
      // Stream node - use saved style if available, otherwise use defaults
      const baseStreamStyle = {
        cursor: isAdminMode ? 'grab' : 'default',
        backgroundColor: 'rgba(59, 130, 246, 0.3)',
        border: '2px solid #3b82f6',
        borderRadius: '8px',
        width: '300px',
        height: '200px'
      };
            
      // Apply saved style if available
      if (savedNode?.style) {
        newNode.style = {
          ...baseStreamStyle,
          ...savedNode.style,
          cursor: isAdminMode ? 'grab' : 'default', // Always override cursor based on mode
        };
      } else {
        newNode.style = baseStreamStyle;
      }
      
      // Handle dimensions
      if (savedNode?.dimensions) {
        newNode.data = {
          ...newNode.data,
          dimensions: savedNode.dimensions
        };
        // Update style dimensions to match
        if (newNode.style) {
          newNode.style.width = `${savedNode.dimensions.width}px`;
          newNode.style.height = `${savedNode.dimensions.height}px`;
        }
      }
    } else {
      // App node - prioritize saved style colors over computed colors
      const baseAppStyle = {
        cursor: isAdminMode ? 'grab' : (isClickable ? 'pointer' : 'not-allowed'),
        width: '120px',
        height: '80px',
        borderRadius: '8px',
      };
      
      
      // Use saved style if it exists and has color information
      if (savedNode?.style) {
        // Build the style object by merging base style with saved properties
        newNode.style = {
          ...baseAppStyle,
          ...savedNode.style, // This will include all saved style properties
        };
        
        // Override cursor based on mode (always use computed cursor)
        if (newNode.style) {
          newNode.style.cursor = isAdminMode ? 'grab' : (isClickable ? 'pointer' : 'not-allowed');
        }
        
      } else {
        // No saved style, use computed colors
        newNode.style = {
          ...baseAppStyle,
          backgroundColor: nodeColors.background,
          border: `2px solid ${nodeColors.border}`,
        };
      }
    }
    
    return newNode;
  });
}

/**
 * Initialize edges with layout data
 */
export function initializeEdgesWithLayout(
  inputEdges: Edge[],
  savedLayout: any,
  selectedEdgeId: string | null = null,
  isAdminMode: boolean = false
): Edge[] {
  // Build quick lookup maps
  const freshEdgeMap = new Map<string, Edge>();
  inputEdges.forEach(e => freshEdgeMap.set(e.id, e));
  const layoutEdges: any[] = savedLayout?.edges_layout || [];
  const layoutEdgeMap = new Map<string, any>();
  layoutEdges.forEach(se => layoutEdgeMap.set(se.id, se));

  // 1) Start from fresh edges (backend is source of truth for metadata)
  const mergedEdges: Edge[] = inputEdges.map(edge => {
    const savedEdge = layoutEdgeMap.get(edge.id);
    if (!savedEdge) return edge;

    return {
      ...edge,
      sourceHandle: savedEdge.sourceHandle ?? edge.sourceHandle,
      targetHandle: savedEdge.targetHandle ?? edge.targetHandle,
      // Overlay saved style but let backend style/values override when present
      style: {
        ...(savedEdge.style || {}),
        ...(edge.style || {}),
      },
    } as Edge;
  });

  // 2) Add edges that exist only in saved layout (not returned by backend)
  layoutEdges.forEach((savedEdge: any) => {
    if (freshEdgeMap.has(savedEdge.id)) return;
    const connectionType = savedEdge.data?.connection_type || 'direct';
    const color = getEdgeColor(connectionType, savedEdge.data?.color, isAdminMode);

    const fallbackEdge: Edge = {
      id: savedEdge.id ?? `${savedEdge.source}-${savedEdge.target}`,
      source: String(savedEdge.source),
      target: String(savedEdge.target),
      type: 'smoothstep',
      sourceHandle: savedEdge.sourceHandle || undefined,
      targetHandle: savedEdge.targetHandle || undefined,
      style: {
        stroke: color,
        strokeWidth: 2,
        ...(savedEdge.style || {}),
      },
      data: {
        connection_type: connectionType,
        direction: savedEdge.data?.direction || 'one_way',
        color,
        // keep any layout-provided metadata like names/labels
        ...savedEdge.data,
      } as any,
    } as Edge;

    mergedEdges.push(fallbackEdge);
  });

  return removeDuplicateEdges(mergedEdges).map(edge => {
    const edgeColor = getEdgeColor(edge.data?.connection_type || 'direct', edge.data?.color, isAdminMode);
    const isSelected = selectedEdgeId === edge.id;
    const isBothWays = edge.data?.direction === 'both_ways';
    
    const baseEdge = {
      ...edge,
      type: 'smoothstep',
      updatable: isAdminMode,
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
      data: {
        connection_type: edge.data?.connection_type || 'direct',
        direction: edge.data?.direction || 'one_way',
        ...edge.data
      }
    };

    // Add arrow at the start for bidirectional connections
    if (isBothWays) {
      baseEdge.markerStart = {
        type: 'arrowclosed',
        color: edgeColor,
      } as any;
    }

    // Ensure source/target handles are properly set
    baseEdge.sourceHandle = edge.sourceHandle || undefined;
    baseEdge.targetHandle = edge.targetHandle || undefined;

    return baseEdge;
  });
}

/**
 * Custom wheel handler for Vue Flow with pan/zoom controls
 * - Normal scroll: pan up/down
 * - Shift + scroll: pan left/right  
 * - Ctrl/Cmd + scroll: zoom in/out
 */
export function createCustomWheelHandler(zoomIn: Function, zoomOut: Function, setViewport: Function, getViewport: Function) {
  return function onWheel(event: WheelEvent) {
    event.preventDefault();
    
    const viewport = getViewport();
    const delta = event.deltaY;
    const ctrlKey = event.ctrlKey || event.metaKey;
    const shiftKey = event.shiftKey;

    if (ctrlKey) {
      // Ctrl + scroll = zoom
      const zoomFactor = 0.1;
      if (delta < 0) {
        zoomIn(zoomFactor);
      } else {
        zoomOut(zoomFactor);
      }
    } else if (shiftKey) {
      // Shift + scroll = pan left/right
      const panAmount = 50;
      const newX = viewport.x + (delta > 0 ? -panAmount : panAmount);
      setViewport({ x: newX, y: viewport.y, zoom: viewport.zoom });
    } else {
      // Normal scroll = pan up/down
      const panAmount = 50;
      const newY = viewport.y + (delta > 0 ? -panAmount : panAmount);
      setViewport({ x: viewport.x, y: newY, zoom: viewport.zoom });
    }
  };
}

/**
 * Custom context menu handler for Vue Flow
 * Prevents browser context menu on empty Vue Flow space
 * while still allowing it on specific elements (nodes, edges, streams)
 */
export function createCustomContextMenuHandler() {
  return function onContextMenu(event: MouseEvent) {
    // Check if the right-click target is the Vue Flow pane (empty space)
    const target = event.target as HTMLElement;
    
    // If clicking on Vue Flow pane/viewport (empty space), prevent context menu
    if (target.classList.contains('vue-flow__pane') || 
        target.classList.contains('vue-flow__viewport') ||
        target.closest('.vue-flow__pane') ||
        target.closest('.vue-flow__viewport')) {
      event.preventDefault();
      return false;
    }
    
    // Allow context menu for nodes, edges, and other specific elements
    // (they will have their own classes and won't match the above conditions)
    return true;
  };
}

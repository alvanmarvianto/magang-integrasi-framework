import { ref, Ref } from 'vue';
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

const EDGE_COLORS: { [key: string]: string } = {
  'direct': '#000000',
  'soa': '#02a330', 
  'sftp': '#002ac0',
};

/**
 * Get node color based on lingkup/stream
 */
export function getNodeColor(lingkup: string, isAdminMode: boolean = false): { background: string; border: string } {
  const colorMap = NODE_COLORS;
  return colorMap[lingkup] || { background: '#ffffff', border: '#6b7280' };
}

/**
 * Get edge color based on connection type
 */
export function getEdgeColor(type: string, isAdminMode: boolean = false): string {
  const colorMap = EDGE_COLORS;
  return colorMap[type] || '#6b7280';
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
      cursor: isAdmin ? 'grab' : 'pointer',
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
  const edgeColor = getEdgeColor(edge.data?.connection_type || 'direct');
  const isSelected = selectedEdgeId === edge.id;
  
  return {
    ...edge,
    type: 'smoothstep',
    updatable: false,
    animated: isSelected,
    style: {
      ...(edge.style || {}),
      strokeWidth: isSelected ? 4 : 2, 
      stroke: edgeColor, // Keep original edge color
    },
    markerEnd: {
      color: edgeColor,
    } as any,
    sourceHandle: edge.sourceHandle || undefined,
    targetHandle: edge.targetHandle || undefined,
    data: {
      ...edge.data
    }
  };
}

/**
 * Update edge styles based on selection
 */
export function updateEdgeStyles(edges: Edge[], selectedEdgeId?: string): Edge[] {
  return edges.map(edge => createStyledEdge(edge, selectedEdgeId));
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
  
  return {
    selectedEdgeId,
    handleEdgeClick,
    handlePaneClick,
  };
}

/**
 * Handle node click events
 */
export function handleNodeClick(node: any, isAdminMode: boolean = false) {
  
  if (!isAdminMode && node.type === 'app' && node.id) {
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
  isAdminMode: boolean = false
): Node[] {
  const uniqueNodes = validateAndCleanNodes(inputNodes);
  const hasSavedLayout = savedLayout?.nodes_layout && Object.keys(savedLayout.nodes_layout).length > 0;

  return uniqueNodes.map(node => {
    const savedNode = savedLayout?.nodes_layout?.[node.id];
    const nodeColors = getNodeColor(node.data?.lingkup || '', isAdminMode);
    
    const newNode: Node = {
      id: node.id,
      type: node.data.is_parent_node ? 'stream' : 'app',
      position: hasSavedLayout ? (savedNode?.position || { x: 0, y: 0 }) : { x: 0, y: 0 },
      data: node.data,
      draggable: isAdminMode || !node.data.is_parent_node, // Stream nodes not draggable in user mode
      selectable: isAdminMode,
      connectable: isAdminMode,
      focusable: true,
      deletable: isAdminMode,
      zIndex: node.data.is_parent_node ? (isAdminMode ? -10 : -1) : (isAdminMode ? 10 : 1)
    };
    
    if (node.data.is_parent_node) {
      // Stream node
      newNode.style = {
        cursor: isAdminMode ? 'grab' : 'default',
        backgroundColor: 'rgba(59, 130, 246, 0.3)',
        border: '2px solid #3b82f6',
        borderRadius: '8px',
        width: savedNode?.dimensions?.width ? `${savedNode.dimensions.width}px` : '300px',
        height: savedNode?.dimensions?.height ? `${savedNode.dimensions.height}px` : '200px'
      };
      
      if (savedNode?.dimensions) {
        newNode.data = {
          ...newNode.data,
          dimensions: savedNode.dimensions
        };
      }
    } else {
      // App node
      newNode.style = {
        cursor: isAdminMode ? 'grab' : 'pointer',
        width: '120px',
        height: '80px',
        backgroundColor: nodeColors.background,
        border: `2px solid ${nodeColors.border}`,
        borderRadius: '8px'
      };
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
  let edgesData = inputEdges;
  
  // If we have saved layout edges, merge them with fresh input data
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
        };
      }
      // If no fresh data found, use saved edge as fallback
      return savedEdge;
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
    const edgeColor = getEdgeColor(edge.data?.connection_type || 'direct', isAdminMode);
    const isSelected = selectedEdgeId === edge.id;
    
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
        ...edge.data
      }
    };

    // Ensure source/target handles are properly set
    baseEdge.sourceHandle = edge.sourceHandle || undefined;
    baseEdge.targetHandle = edge.targetHandle || undefined;

    return baseEdge;
  });
}

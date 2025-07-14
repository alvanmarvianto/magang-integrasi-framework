import { ref, computed, nextTick } from 'vue';
import { useVueFlow } from '@vue-flow/core';
import type { Node, Edge, Position, NodeMouseEvent } from '@vue-flow/core';

export interface AppNodeData {
  label: string;
  app_id: number;
  stream_name: string;
  lingkup: string;
  is_home_stream: boolean;
  is_parent_node?: boolean;
}

export interface AppNode extends Node {
  data: AppNodeData;
}

export interface AppEdgeData {
  label: string;
  connection_type: string;
}

export type AppEdge = Edge<AppEdgeData>;

export interface StreamGroup {
  name: string;
  nodes: AppNode[];
  position: Position;
  dimensions: { width: number; height: number };
}

export function useVueFlowStreamIntegration() {
  const { fitView, setViewport, getViewport } = useVueFlow();
  
  const nodes = ref<AppNode[]>([]);
  const edges = ref<AppEdge[]>([]);
  const isLayouted = ref(false);

  // Layout constants
  const STREAM_PADDING = 20;
  const STREAM_MIN_WIDTH = 300;
  const STREAM_MIN_HEIGHT = 200;
  const NODE_WIDTH = 120;
  const NODE_HEIGHT = 40;
  const NODES_PER_ROW = 2;
  const NODE_SPACING_X = 150;
  const NODE_SPACING_Y = 80;
  const STREAM_SPACING_X = 400;
  const STREAM_SPACING_Y = 300;

  const getNodeColor = (lingkup: string): { background: string; border: string } => {
    const colorMap: { [key: string]: { background: string; border: string } } = {
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
    
    return colorMap[lingkup] || { background: '#ffffff', border: '#6b7280' };
  };

  const getEdgeColor = (type: string): string => {
    const colorMap: { [key: string]: string } = {
      'direct': '#000000',
      'soa': '#02a330',
      'sftp': '#002ac0',
    };
    
    return colorMap[type] || '#6b7280';
  };

  // Remove duplicate edges between the same pair of nodes
  const removeDuplicateEdges = (inputEdges: AppEdge[]): AppEdge[] => {
    const connectionMap = new Map<string, AppEdge>();
    
    inputEdges.forEach(edge => {
      // Create a unique key for each connection pair (bidirectional)
      const key1 = `${edge.source}-${edge.target}`;
      const key2 = `${edge.target}-${edge.source}`;
      
      // Check if we already have a connection between these nodes
      if (!connectionMap.has(key1) && !connectionMap.has(key2)) {
        connectionMap.set(key1, edge);
      }
    });
    
    return Array.from(connectionMap.values());
  };

  const calculateStreamLayout = (streamNodes: AppNode[]): { width: number; height: number } => {
    const nodeCount = streamNodes.length;
    if (nodeCount === 0) return { width: STREAM_MIN_WIDTH, height: STREAM_MIN_HEIGHT };

    const rows = Math.ceil(nodeCount / NODES_PER_ROW);
    const cols = Math.min(nodeCount, NODES_PER_ROW);
    
    const contentWidth = cols * NODE_WIDTH + (cols - 1) * (NODE_SPACING_X - NODE_WIDTH);
    const contentHeight = rows * NODE_HEIGHT + (rows - 1) * (NODE_SPACING_Y - NODE_HEIGHT);
    
    return {
      width: Math.max(STREAM_MIN_WIDTH, contentWidth + 2 * STREAM_PADDING),
      height: Math.max(STREAM_MIN_HEIGHT, contentHeight + 2 * STREAM_PADDING)
    };
  };

  const layoutNodes = (inputNodes: AppNode[], inputEdges: AppEdge[], currentStreamName: string) => {
    // Separate nodes by type
    const homeStreamNodes: AppNode[] = [];
    const externalNodes: AppNode[] = [];
    let homeStreamParentNode: AppNode | undefined = undefined;

    inputNodes.forEach(node => {
      if (node.data.app_id === -1 && node.data.is_home_stream) {
        // This is the home stream parent node (not group type)
        homeStreamParentNode = node;
      } else if (node.data.is_home_stream) {
        // Apps belonging to the home stream
        homeStreamNodes.push(node);
      } else {
        // All other apps (external to home stream)
        externalNodes.push(node);
      }
    });

    const layoutedNodes: AppNode[] = [];
    
    // Layout the home stream parent node first
    if (homeStreamParentNode) {
      const parentNode = homeStreamParentNode as AppNode;
      const { width: streamWidth, height: streamHeight } = calculateStreamLayout(homeStreamNodes);
      
      // Position the home stream parent node
      const groupX = 150;
      const groupY = 150;
      
      const updatedParentNode: AppNode = {
        id: parentNode.id,
        type: 'streamParent', // Use custom stream parent type
        data: parentNode.data,
        position: { x: groupX, y: groupY },
        style: {
          backgroundColor: 'rgba(59, 130, 246, 0.3)',
          width: `${streamWidth}px`,
          height: `${streamHeight}px`,
          border: '2px solid #3b82f6',
          borderRadius: '8px',
        },
        selectable: true,
        draggable: true, // Make parent draggable
      };

      layoutedNodes.push(updatedParentNode);

      // Position child nodes within the home stream parent
      homeStreamNodes.forEach((node, nodeIndex) => {
        const row = Math.floor(nodeIndex / NODES_PER_ROW);
        const col = nodeIndex % NODES_PER_ROW;
        
        // Position relative to parent node (not absolute coordinates)
        const nodeX = STREAM_PADDING + col * NODE_SPACING_X;
        const nodeY = STREAM_PADDING + 40 + row * NODE_SPACING_Y; // +40 for parent title
        
        const nodeColors = getNodeColor(node.data.lingkup);
        
        const layoutedNode: AppNode = {
          id: node.id,
          type: node.type,
          data: {
            ...node.data,
            label: node.data.label.replace(/\s+\w+$/, ''), // Remove any trailing stream name
          },
          position: { x: nodeX, y: nodeY },
          // Set parent-child relationship HERE in the layout
          parentNode: currentStreamName,
          extent: 'parent',
          style: {
            width: NODE_WIDTH,
            height: NODE_HEIGHT,
            backgroundColor: nodeColors.background,
            border: `2px solid ${nodeColors.border}`,
            borderRadius: '6px',
          },
          draggable: true,
          selectable: true,
        };

        layoutedNodes.push(layoutedNode);
      });
    }

    // Layout external nodes around the home stream
    if (externalNodes.length > 0) {
      // Position external nodes around the parent
      externalNodes.forEach((node, index) => {
        let nodeX, nodeY;
        
        if (index < 3) {
          // Top row
          nodeX = 150 + (index * (NODE_SPACING_X + 20));
          nodeY = 50;
        } else if (index < 6) {
          // Right side  
          nodeX = 650;
          nodeY = 150 + ((index - 3) * NODE_SPACING_Y);
        } else if (index < 9) {
          // Bottom row
          nodeX = 150 + ((index - 6) * (NODE_SPACING_X + 20));
          nodeY = 550;
        } else {
          // Left side
          nodeX = 50;
          nodeY = 150 + ((index - 9) * NODE_SPACING_Y);
        }
        
        const nodeColors = getNodeColor(node.data.lingkup);
        
        const layoutedNode: AppNode = {
          id: node.id,
          type: node.type,
          data: {
            ...node.data,
            label: node.data.label.replace(/\s+\w+$/, ''), // Remove any trailing stream name
          },
          position: { x: nodeX, y: nodeY },
          // Explicitly set NO parent for external nodes
          parentNode: undefined,
          extent: undefined,
          style: {
            width: NODE_WIDTH,
            height: NODE_HEIGHT,
            backgroundColor: nodeColors.background,
            border: `2px solid ${nodeColors.border}`,
            borderRadius: '6px',
          },
          draggable: true,
          selectable: true,
        };

        layoutedNodes.push(layoutedNode);
      });
    }

    return {
      nodes: layoutedNodes,
      edges: removeDuplicateEdges(inputEdges).map(edge => {
        const edgeColor = getEdgeColor(edge.data?.connection_type || 'direct');
        return {
          id: edge.id,
          source: edge.source,
          target: edge.target,
          type: 'default', // Use default rigid edges
          data: edge.data,
          style: {
            stroke: edgeColor,
            strokeWidth: 2,
          },
          markerEnd: {
            type: 'arrowclosed',
            color: edgeColor,
          } as any,
        };
      })
    };
  };

  const initializeLayout = async (inputNodes: AppNode[], inputEdges: AppEdge[], streamName: string) => {
    const { nodes: layoutedNodes, edges: layoutedEdges } = layoutNodes(inputNodes, inputEdges, streamName);
    
    nodes.value = layoutedNodes;
    edges.value = layoutedEdges;
    
    await nextTick();
    
    // Fit view immediately with better settings
    setTimeout(() => {
      try {
        fitView({ 
          padding: 80,
          includeHiddenNodes: false,
          minZoom: 0.5,
          maxZoom: 1.5,
          duration: 800,
        });
        isLayouted.value = true;
      } catch (error) {
        console.warn('FitView failed, retrying in 200ms:', error);
        // Quick retry
        setTimeout(() => {
          try {
            fitView({ 
              padding: 80,
              includeHiddenNodes: false,
              minZoom: 0.5,
              maxZoom: 1.5,
              duration: 800,
            });
            isLayouted.value = true;
          } catch (retryError) {
            console.error('FitView failed after retry:', retryError);
            isLayouted.value = true; // Still mark as layouted to hide loading
          }
        }, 200);
      }
    }, 100);
  };

  const onNodeClick = (event: NodeMouseEvent) => {
    const node = event.node as AppNode;
    if (node.type !== 'group' && node.data.app_id > 0) {
      // Navigate to app integration page
      window.location.href = `/integration/app/${node.data.app_id}`;
    }
  };

  const resetLayout = async () => {
    if (nodes.value.length > 0 && edges.value.length > 0) {
      const currentStreamName = nodes.value.find(n => n.data.is_home_stream)?.data.stream_name || '';
      await initializeLayout([...nodes.value], [...edges.value], currentStreamName);
    }
  };

  const centerView = () => {
    fitView({ 
      padding: 80,
      includeHiddenNodes: false,
      minZoom: 0.5,
      maxZoom: 1.5,
      duration: 800,
    });
  };

  // Handle parent node drag to move children along
  const onNodeDragStop = (event: any) => {
    // Vue Flow handles parent-child dragging automatically
    // This event is here for potential future enhancements
    console.log('Node dragged:', event.node.id);
  };

  return {
    nodes,
    edges,
    isLayouted,
    initializeLayout,
    onNodeClick,
    onNodeDragStop,
    resetLayout,
    centerView,
  };
}

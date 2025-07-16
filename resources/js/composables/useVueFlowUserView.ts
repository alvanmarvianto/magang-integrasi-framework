import { ref } from 'vue'
import type { Node, Edge } from '@vue-flow/core'

export function useVueFlowUserView() {
  const selectedEdgeId = ref<string | null>(null)

  function getNodeColor(lingkup: string): { background: string; border: string } {
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
    }
    
    return colorMap[lingkup] || { background: '#ffffff', border: '#6b7280' }
  }

  function getEdgeColor(type: string): string {
    const colorMap: { [key: string]: string } = {
      'direct': '#000000',
      'soa': '#02a330', 
      'sftp': '#002ac0',
    }
    
    return colorMap[type] || '#6b7280'
  }

  function removeDuplicateEdges(inputEdges: Edge[]): Edge[] {
    const connectionMap = new Map<string, Edge>()
    
    inputEdges.forEach(edge => {
      const key1 = `${edge.source}-${edge.target}`
      const key2 = `${edge.target}-${edge.source}`
      
      if (!connectionMap.has(key1) && !connectionMap.has(key2)) {
        connectionMap.set(key1, edge)
      }
    })
    
    return Array.from(connectionMap.values())
  }

  function applyAutomaticLayoutWithConstraints(nodes: any[]) {
    const STREAM_PADDING = 20
    const STREAM_MIN_WIDTH = 300
    const STREAM_MIN_HEIGHT = 200
    const NODE_WIDTH = 120  // Back to original size
    const NODE_HEIGHT = 80  // Back to original size
    const NODES_PER_ROW = 2
    const NODE_SPACING_X = 150  // Back to original spacing
    const NODE_SPACING_Y = 110  // Back to original spacing

    const streamNode = nodes.find(n => n.data.is_parent_node)
    const homeStreamNodes = nodes.filter(n => n.data.is_home_stream && !n.data.is_parent_node)
    const externalNodes = nodes.filter(n => !n.data.is_home_stream && !n.data.is_parent_node)

    const nodeCount = homeStreamNodes.length
    const rows = Math.ceil(nodeCount / NODES_PER_ROW)
    const cols = Math.min(nodeCount, NODES_PER_ROW)
    
    const contentWidth = cols * NODE_WIDTH + (cols - 1) * (NODE_SPACING_X - NODE_WIDTH)
    const contentHeight = rows * NODE_HEIGHT + (rows - 1) * (NODE_SPACING_Y - NODE_HEIGHT)
    
    const streamWidth = Math.max(STREAM_MIN_WIDTH, contentWidth + 2 * STREAM_PADDING)
    const streamHeight = Math.max(STREAM_MIN_HEIGHT, contentHeight + 2 * STREAM_PADDING + 40)

    if (streamNode) {
      const groupX = 150
      const groupY = 150
      
      const updatedStreamNode = nodes.find(n => n.id === streamNode.id)
      if (updatedStreamNode) {
        updatedStreamNode.position = { x: groupX, y: groupY }
        updatedStreamNode.style = {
          backgroundColor: 'rgba(59, 130, 246, 0.3)',
          width: `${streamWidth}px`,
          height: `${streamHeight}px`,
          border: '2px solid #3b82f6',
          borderRadius: '8px'
        }
        updatedStreamNode.dimensions = {
          width: streamWidth,
          height: streamHeight
        }
      }
    }

    homeStreamNodes.forEach((node, nodeIndex) => {
      const updatedNode = nodes.find(n => n.id === node.id)
      if (updatedNode) {
        const row = Math.floor(nodeIndex / NODES_PER_ROW)
        const col = nodeIndex % NODES_PER_ROW
        
        const nodeX = 150 + STREAM_PADDING + col * NODE_SPACING_X
        const nodeY = 150 + STREAM_PADDING + 40 + row * NODE_SPACING_Y
        
        updatedNode.position = { x: nodeX, y: nodeY }
        updatedNode.parentNode = undefined
        updatedNode.extent = undefined
      }
    })

    externalNodes.forEach((node, index) => {
      const updatedNode = nodes.find(n => n.id === node.id)
      if (updatedNode) {
        let nodeX, nodeY
        
        if (index < 3) {
          nodeX = 150 - 200 + (index * 200)
          nodeY = 150 - 80
        } else if (index < 6) {
          nodeX = 150 + streamWidth + 50
          nodeY = 150 + (index - 3) * 100
        } else if (index < 9) {
          nodeX = 150 - 200 + ((index - 6) * 200)
          nodeY = 150 + streamHeight + 50
        } else {
          nodeX = 150 - 150
          nodeY = 150 + (index - 9) * 100
        }
        
        updatedNode.position = { x: nodeX, y: nodeY }
        updatedNode.parentNode = undefined
        updatedNode.extent = undefined
      }
    })
  }

  function updateEdgeStyles(edges: any[]) {
    return edges.map(edge => {
      const edgeColor = getEdgeColor(edge.data?.connection_type || 'direct')
      const isSelected = selectedEdgeId.value === edge.id
      
      return {
        ...edge,
        animated: isSelected,
        style: {
          ...edge.style,
          stroke: edgeColor,
          strokeWidth: isSelected ? 4 : 2,
        },
      }
    })
  }

  function handleEdgeClick(edgeId: string) {
    if (selectedEdgeId.value === edgeId) {
      selectedEdgeId.value = null
    } else {
      selectedEdgeId.value = edgeId
    }
  }

  function handlePaneClick() {
    selectedEdgeId.value = null
  }

  return {
    selectedEdgeId,
    getNodeColor,
    getEdgeColor,
    removeDuplicateEdges,
    applyAutomaticLayoutWithConstraints,
    updateEdgeStyles,
    handleEdgeClick,
    handlePaneClick,
  }
}

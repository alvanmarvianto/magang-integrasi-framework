import { 
  getNodeColor, 
  getEdgeColor, 
  removeDuplicateEdges, 
  applyAutomaticLayoutWithConstraints,
  updateEdgeStyles,
  useEdgeSelection
} from './useVueFlowCommon'
import { ref, type Ref } from 'vue'
import type { Node, Edge } from '@vue-flow/core'

export function useVueFlowUserView() {
  const { selectedEdgeId, handleEdgeClick, handlePaneClick, updateEdgeStylesWithSelection } = useEdgeSelection()

  // Node highlight state (user mode)
  const selectedAppNodeId = ref<string | null>(null)
  const connectedNodeIds = ref<Set<string>>(new Set())
  const connectedEdgeIds = ref<Set<string>>(new Set())

  function computeConnections(nodeId: string, edges: Edge[]) {
    const nodeIds = new Set<string>()
    const edgeIds = new Set<string>()
    edges.forEach((e: any) => {
      if (e.source === nodeId || e.target === nodeId) {
        edgeIds.add(e.id)
        if (e.source && e.source !== nodeId) nodeIds.add(e.source)
        if (e.target && e.target !== nodeId) nodeIds.add(e.target)
      }
    })
    connectedNodeIds.value = nodeIds
    connectedEdgeIds.value = edgeIds
  }

  function applyNodeHighlight(nodesRef: Ref<Node[]>, edgesRef: Ref<Edge[]>) {
    if (!selectedAppNodeId.value) return
    const selectedId = selectedAppNodeId.value
    const nodeSet = connectedNodeIds.value
    const edgeSet = connectedEdgeIds.value

    nodesRef.value = nodesRef.value.map((n: any) => {
      const isRelevant = n.id === selectedId || nodeSet.has(n.id)
      const isStreamNest = n.type === 'stream' || n.data?.is_parent_node
      return {
        ...n,
        style: { ...(n.style || {}), transition: 'opacity 200ms ease', opacity: isStreamNest ? 1 : (isRelevant ? 1 : 0.25) },
      } as any
    })

  edgesRef.value = edgesRef.value.map((e: any) => {
      const isConnected = edgeSet.has(e.id)
      return {
        ...e,
    animated: false,
    style: { ...(e.style || {}), transition: 'opacity 200ms ease', opacity: isConnected ? 1 : 0.15 },
      } as any
    })
  }

  function clearNodeHighlight(nodesRef: Ref<Node[]>, edgesRef: Ref<Edge[]>) {
    selectedAppNodeId.value = null
    connectedNodeIds.value = new Set()
    connectedEdgeIds.value = new Set()
    nodesRef.value = nodesRef.value.map((n: any) => ({
      ...n,
      style: { ...(n.style || {}), transition: 'opacity 200ms ease', opacity: 1 },
    }))
    edgesRef.value = edgesRef.value.map((e: any) => ({
      ...e,
      animated: false,
      style: { ...(e.style || {}), transition: 'opacity 200ms ease', opacity: 1 },
    }))
  }

  function handleAppNodeClick(node: any, nodesRef: Ref<Node[]>, edgesRef: Ref<Edge[]>) {
    if (!node || node.type !== 'app') return
    if (selectedAppNodeId.value === node.id) {
      clearNodeHighlight(nodesRef, edgesRef)
      return
    }
    selectedAppNodeId.value = String(node.id)
    computeConnections(selectedAppNodeId.value, edgesRef.value)
    applyNodeHighlight(nodesRef, edgesRef)
  }

  function applyEdgeHighlightById(edgeId: string, nodesRef: Ref<Node[]>, edgesRef: Ref<Edge[]>) {
    const edge = edgesRef.value.find(e => e.id === edgeId)
    if (!edge) return
    // Clear node selection state when highlighting by edge
    selectedAppNodeId.value = null
    connectedNodeIds.value = new Set()
    connectedEdgeIds.value = new Set([edgeId])

    const sourceId = String(edge.source)
    const targetId = String(edge.target)

    nodesRef.value = nodesRef.value.map((n: any) => {
      const isStreamNest = n.type === 'stream' || n.data?.is_parent_node
      const isRelevant = n.id === sourceId || n.id === targetId
      return {
        ...n,
        style: { ...(n.style || {}), transition: 'opacity 200ms ease', opacity: isStreamNest ? 1 : (isRelevant ? 1 : 0.25) },
      } as any
    })

    edgesRef.value = edgesRef.value.map((e: any) => {
      const isRelevant = e.id === edgeId
      return {
        ...e,
        animated: isRelevant ? e.animated : false,
        style: { ...(e.style || {}), transition: 'opacity 200ms ease', opacity: isRelevant ? 1 : 0.15 },
      } as any
    })
  }

  return {
    selectedEdgeId,
    selectedAppNodeId,
    connectedNodeIds,
    connectedEdgeIds,
    getNodeColor,
    getEdgeColor,
    removeDuplicateEdges,
    applyAutomaticLayoutWithConstraints,
    updateEdgeStyles,
    updateEdgeStylesWithSelection,
    handleEdgeClick,
    handlePaneClick,
    // node highlight helpers
    handleAppNodeClick,
    applyNodeHighlight,
    clearNodeHighlight,
  applyEdgeHighlightById,
  }
}

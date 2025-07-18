import { 
  getNodeColor, 
  getEdgeColor, 
  removeDuplicateEdges, 
  applyAutomaticLayoutWithConstraints,
  updateEdgeStyles,
  useEdgeSelection
} from './useVueFlowCommon'

export function useVueFlowUserView() {
  const { selectedEdgeId, handleEdgeClick, handlePaneClick } = useEdgeSelection()

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

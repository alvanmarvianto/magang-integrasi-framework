<template>
  <div class="admin-vue-flow-container">
    <!-- Error State -->
    <ErrorState
      v-if="error"
      :message="error"
      back-route="admin.index"
    />

    <!-- No Data State -->
    <ErrorState
      v-else-if="nodes.length === 0"
      message="No diagram data available"
      back-route="admin.index"
    />

    <!-- Normal Content -->
    <template v-else>
      <!-- Layout Navbar -->
      <LayoutNavbar
        :title="`App Diagram - ${appName}`"
        :layout-changed="layoutChanged"
        :saving="saving"
        :refreshing="refreshing"
        :show-layout-selector="true"
        :show-refresh-button="true"
        :allowed-streams="allowedStreams"
        :function-apps="functionApps"
        :current-app-id="appId"
        @save="saveLayout"
        @refresh="refreshLayout"
        @reset="resetLayout"
        @stream-change="onStreamChange"
        @app-change="onAppChange"
        :showBackButton="true" backUrl="/admin"
      />

      <!-- Vue Flow -->
      <div class="vue-flow-wrapper">
        <VueFlow 
          ref="vueFlowRef"
          :key="vueFlowKey"
          :nodes="nodes"
          :edges="edges"
          :default-edge-options="defaultEdgeOptions"
          :pan-on-scroll="true"
          :pan-on-scroll-mode="PanOnScrollMode.Free"
          :pan-on-scroll-speed="0.5"
          :zoom-on-scroll="false"
          :zoom-on-pinch="true"
          :zoom-on-double-click="false"
          class="vue-flow admin-mode"
          @node-drag-stop="onNodeDragStop"
          @edge-update="onEdgeUpdate"
          @edge-click="onEdgeClick"
          @node-click="onNodeClick"
          @pane-click="onPaneClick"
          @nodes-change="onNodesChange"
          @edges-change="onEdgesChange"
          @wheel="onWheel"
          @contextmenu="onContextMenu"
          :validate-connection="validateConnection"
        >
          <!-- Custom Node Types -->
          <template #node-stream="nodeProps">
            <StreamNest v-bind="nodeProps" :admin-mode="true" @resize="onStreamResize" />
          </template>

          <!-- Custom App Node with Handles -->
          <template #node-app="nodeProps">
            <AppNode v-bind="nodeProps" :admin-mode="true" />
          </template>

          <Background :variant="BackgroundVariant.Dots" />
          <Controls />
        </VueFlow>
      </div>

      <DetailsSidebar
        :visible="showDetails"
        :detailType="detailType"
        :edgeData="selectedEdgeData"
        :nodeData="selectedNodeData"
        :isAdmin="true"
        :offsetTop="'5rem'"
        @close="closeDetails"
      />

    <!-- Status -->
    <div v-if="statusMessage" class="status-message" :class="statusType">
      {{ statusMessage }}
    </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, markRaw } from 'vue'
import { VueFlow } from '@vue-flow/core'
import { Background, BackgroundVariant } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import { PanOnScrollMode } from '@vue-flow/core'
import { router } from '@inertiajs/vue3'
import StreamNest from '@/components/VueFlow/StreamNest.vue'
import AppNode from '@/components/VueFlow/AppNode.vue'
import LayoutNavbar from '@/components/Admin/LayoutNavbar.vue'
import DetailsSidebar from '@/components/Sidebar/DetailsSidebar.vue'
import ErrorState from '@/components/ErrorState.vue'
import { useAdminLayout } from '@/composables/useAdminLayout'
import type { Node, Edge } from '@vue-flow/core'

// Add necessary CSS imports
import '@vue-flow/core/dist/style.css'
import '@vue-flow/core/dist/theme-default.css'
import '@vue-flow/controls/dist/style.css'

// Props
interface Props {
  appId: number
  appName: string
  streamName: string
  nodes: Node[]
  edges: Edge[]
  savedLayout: {
    nodes_layout?: Record<string, any>
    edges_layout?: any[]
    app_config?: Record<string, any>
  } | null
  allowedStreams?: string[]
  functionApps?: { app_id: number; app_name: string }[]
  error?: string | null
}

const props = defineProps<Props>()

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
  onEdgeUpdate,
  onEdgeClick,
  onNodeClick,
  onPaneClick,
  onNodesChange,
  onEdgesChange,
  initializeLayout,
  fitView,
  validateConnection,
  resetLayout,
  closeDetails,
} = useAdminLayout({
  savedLayout: props.savedLayout,
  nodes: props.nodes,
  edges: props.edges,
  disableArrowMarkers: true, // Disable arrow markers for app layout
})

// Additional refs for app layout
const refreshing = ref(false)

// Initialize layout on mount
onMounted(() => {
  initializeLayout()
})

async function saveLayout() {
  if (!layoutChanged.value) {
    return
  }

  saving.value = true
  
  try {
    // Build layout data from current nodes and edges
    const nodesLayout: Record<string, any> = {}
    
    nodes.value.forEach(node => {
      nodesLayout[node.id] = {
        position: node.position,
        style: node.style || {}
      }
    })

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

    const appConfig = {
      lastUpdated: new Date().toISOString(),
      totalNodes: nodes.value.length,
      totalEdges: edges.value.length,
      app_id: props.appId,
      app_name: props.appName,
    }

    // Use Inertia router instead of fetch for proper CSRF handling
    router.post(`/admin/app/${props.appId}/layout`, {
      nodes_layout: nodesLayout,
      edges_layout: edgesLayout,
      app_config: appConfig,
    }, {
      onSuccess: () => {
        layoutChanged.value = false
        showStatus('Layout berhasil disimpan!', 'success')
      },
      onError: (errors) => {
        console.error('Save errors:', errors)
        showStatus('Gagal menyimpan layout', 'error')
      },
      onFinish: () => {
        saving.value = false
      }
    })
  } catch (error) {
    console.error('Save error:', error)
    showStatus('Gagal menyimpan layout', 'error')
    saving.value = false
  }
}

async function refreshLayout() {
  if (refreshing.value) {
    return
  }

  refreshing.value = true
  
  try {
    // Use Inertia router to refresh the app layout
    router.visit(`/admin/app/${props.appId}/layout/refresh`, {
      method: 'get',
      onSuccess: () => {
        // Reload the current page to get fresh data
        router.reload()
      },
      onError: (errors) => {
        console.error('Refresh errors:', errors)
        showStatus('Gagal refresh layout', 'error')
      },
      onFinish: () => {
        refreshing.value = false
      }
    })
  } catch (error) {
    console.error('Refresh error:', error)
    showStatus('Gagal refresh layout', 'error')
    refreshing.value = false
  }
}

// Event handlers for LayoutNavbar
function onStreamChange(stream: string) {
  if (layoutChanged.value) {
    showStatus('Simpan perubahan sebelum mengganti diagram', 'error')
    return
  }
  
  router.visit(`/admin/stream/${stream}`)
}

function onAppChange(appId: number | string) {
  if (appId === props.appId) {
    return // Already on this app
  }
  
  if (layoutChanged.value) {
    showStatus('Simpan perubahan sebelum mengganti diagram', 'error')
    return
  }
  
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
